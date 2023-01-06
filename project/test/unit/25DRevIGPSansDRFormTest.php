<?php

require_once(dirname(__FILE__).'/../bootstrap/common.php');

sfContext::createInstance($configuration);

if ($application != 'igp13') {
    $t = new lime_test(1);
    $t->ok(true, "pass AOC");
    return;
}

$t = new lime_test(114);

$viti =  CompteTagsView::getInstance()->findOneCompteByTag('test', 'test_viti')->getEtablissement();

//Suppression des DRev précédentes
foreach(DRevClient::getInstance()->getHistory($viti->identifiant, acCouchdbClient::HYDRATE_ON_DEMAND) as $k => $v) {
    DRevClient::getInstance()->deleteDoc(DRevClient::getInstance()->find($k, acCouchdbClient::HYDRATE_JSON));
    $dr = DRClient::getInstance()->find(str_replace("DREV-", "DR-", $k), acCouchdbClient::HYDRATE_JSON);
    if($dr) { DRClient::getInstance()->deleteDoc($dr); }
    $sv12 = SV12Client::getInstance()->find(str_replace("DREV-", "SV12-", $k), acCouchdbClient::HYDRATE_JSON);
    if($sv12) { SV12Client::getInstance()->deleteDoc($sv12); }
    $sv11 = SV11Client::getInstance()->find(str_replace("DREV-", "SV11-", $k), acCouchdbClient::HYDRATE_JSON);
    if($sv11) { SV11Client::getInstance()->deleteDoc($sv11); }
}

foreach(HabilitationClient::getInstance()->getHistory($viti->identifiant) as $k => $v) {
    HabilitationClient::getInstance()->deleteDoc(HabilitationClient::getInstance()->find($k, acCouchdbClient::HYDRATE_JSON));
}

$config = ConfigurationClient::getCurrent();
$produitconfig1 = null;
$produitconfig2 = null;
$produitconfig_horsDR = null;
foreach($config->getProduits() as $produitconfig) {
    if(!$produitconfig->getRendement()) {
        continue;
    }
    if(!$produitconfig1) {
        $produitconfig1 = $produitconfig->getCepage();
        continue;
    } elseif(!$produitconfig2) {
        $produitconfig2 = $produitconfig->getCepage();
        continue;
    } elseif(!$produitconfig_horsDR) {
        $produitconfig_horsDR = $produitconfig->getCepage();
        continue;
    }

    break;
}

$campagne = (date('Y')-1)."";

$drev = DRevClient::getInstance()->createDoc($viti->identifiant, $campagne);
$drev->save();


$t->comment("Étape lots");

if($drev->storeEtape(DrevEtapes::ETAPE_LOTS)) {
    $drev->save();
}

$form = new DRevLotsForm($drev);
$defaults = $form->getDefaults();

$t->is(count($form['lots']), 0, "pour l'instant pas de lots car pas de DR");

exit;

$t->is($form['lots']['0']['produit_hash']->getValue(), $produit1->getParent()->getHash(), 'lot 1 : un produit est déjà sélectionné');
$t->is($form['lots']['0']['millesime']->getValue(), $campagne, 'lot 1 : le millesime est prérempli');

$valuesRev = array(
    'lots' => $form['lots']->getValue(),
    '_revision' => $drev->_rev,
);
$valuesRev['lots']['0']['numero'] = "Cuve A";
$valuesRev['lots']['0']['volume'] = 1008.2;
$valuesRev['lots']['0']['destination_type'] = DRevClient::LOT_DESTINATION_VRAC_FRANCE;
$valuesRev['lots']['0']['destination_date'] = '30/11/'.$campagne;

$form->bind($valuesRev);
$t->ok($form->isValid(), "Le formulaire est valide");
$form->save();

$t->is(count($drev->lots), 2, "Les deux lots sont conservés dans la DRev");
$t->is($drev->lots[0]->numero, $valuesRev['lots']['0']['numero'], "Le numéro de cuve du lot 1 est bien enregistré");
$t->is($drev->lots[0]->volume, $valuesRev['lots']['0']['volume'], "Le volume du lot 1 est bien enregistré");
$t->is($drev->lots[0]->destination_type, $valuesRev['lots']['0']['destination_type'], "Le type de destination lot 1 est bien enregistré");
$t->is($drev->lots[0]->destination_date, join('-', array_reverse(explode('/', $valuesRev['lots']['0']['destination_date']))), "La date de destination du lot 1 est bien enregistré");
$t->is($drev->lots[0]->produit_hash, $valuesRev['lots']['0']['produit_hash'], "La hash du produit du lot 1 est bien enregistré");
$t->is($drev->lots[0]->produit_libelle, $produit1->getLibelle(), "Le libellé du produit du lot 1 est bien enregistré");
$t->is($drev->lots[0]->millesime, $valuesRev['lots']['0']['millesime'], "Le millesime du lot 1 est bien enregistré");

if($drev->storeEtape(DrevEtapes::ETAPE_VALIDATION)) {
    $drev->save();
}

$t->comment("Étape validation");


/*

$t->comment("Étape lots");

if($drev->storeEtape(DrevEtapes::ETAPE_LOTS)) {
    $drev->save();
}

$form = new DRevLotsForm($drev);
$defaults = $form->getDefaults();

$t->is(count($form['lots']), 2, "autant de lots que de colonnes dans le DR");
foreach($form['lots'] as $k => $v) {
    print("k: $k\n");
}
$t->is($form['lots']['0']['produit_hash']->getValue(), $produit1->getParent()->getHash(), 'lot 1 : un produit est déjà sélectionné');
$t->is($form['lots']['0']['millesime']->getValue(), $campagne, 'lot 1 : le millesime est prérempli');

$valuesRev = array(
    'lots' => $form['lots']->getValue(),
    '_revision' => $drev->_rev,
);
$valuesRev['lots']['0']['numero'] = "Cuve A";
$valuesRev['lots']['0']['volume'] = 1008.2;
$valuesRev['lots']['0']['destination_type'] = DRevClient::LOT_DESTINATION_VRAC_FRANCE;
$valuesRev['lots']['0']['destination_date'] = '30/11/'.$campagne;

$form->bind($valuesRev);
$t->ok($form->isValid(), "Le formulaire est valide");
$form->save();

$t->is(count($drev->lots), 2, "Les deux lots sont conservés dans la DRev");
$t->is($drev->lots[0]->numero, $valuesRev['lots']['0']['numero'], "Le numéro de cuve du lot 1 est bien enregistré");
$t->is($drev->lots[0]->volume, $valuesRev['lots']['0']['volume'], "Le volume du lot 1 est bien enregistré");
$t->is($drev->lots[0]->destination_type, $valuesRev['lots']['0']['destination_type'], "Le type de destination lot 1 est bien enregistré");
$t->is($drev->lots[0]->destination_date, join('-', array_reverse(explode('/', $valuesRev['lots']['0']['destination_date']))), "La date de destination du lot 1 est bien enregistré");
$t->is($drev->lots[0]->produit_hash, $valuesRev['lots']['0']['produit_hash'], "La hash du produit du lot 1 est bien enregistré");
$t->is($drev->lots[0]->produit_libelle, $produit1->getLibelle(), "Le libellé du produit du lot 1 est bien enregistré");
$t->is($drev->lots[0]->millesime, $valuesRev['lots']['0']['millesime'], "Le millesime du lot 1 est bien enregistré");

if($drev->storeEtape(DrevEtapes::ETAPE_VALIDATION)) {
    $drev->save();
}

$t->comment("Étape validation");

$validation = new DRevValidation($drev);
$erreurs = $validation->getPointsByCodes('erreur');
$vigilances = $validation->getPointsByCodes('vigilance');

$t->is(array_key_first($erreurs), 'lot_volume_total_depasse', "Le volume total du lot est dépassé");
$t->is(count($erreurs), 1, 'Il y a un point bloquant');
$t->is($vigilances, null, "un point de vigilance à la validation");

$drev->lots[0]->volume = 8.2;
$drev->lotsImpactRevendication();
$drev->save();

$validation = new DRevValidation($drev);
$erreurs = $validation->getPointsByCodes('erreur');
$vigilances = $validation->getPointsByCodes('vigilance');

$t->is($erreurs, null, "pas d'erreur à la validation");
$t->is($vigilances, null, "un point de vigilance à la validation");

$t->comment("DRev validée");
$drev->validate();
$drev->save();

$t->is(count($drev->lots), 1, "La DRev validée ne contient plus que le lot saisi");
$t->is(count($drev->mouvements_lots->{$drev->identifiant}), 1, "La DRev validée contient le mouvement correspondant au lot saisi");
foreach ($drev->mouvements_lots->{$drev->identifiant} as $k => $mvt) {
    break;
}
$t->is($mvt->prelevable, 1, "Le mouvement est prelevable");
$t->is($mvt->preleve, 0, "Le mouvement n'a pas été prelevé");
$t->is($mvt->produit_hash, $drev->lots[0]->produit_hash, 'Le mouvement a la bonne hash');
$t->is($mvt->produit_libelle, $drev->lots[0]->produit_libelle, 'Le mouvement a le bon libellé');
$t->is($mvt->produit_couleur, $drev->lots[0]->getCouleurLibelle(), 'Le mouvement a le bon libellé de couleur');
$t->is($mvt->volume, $drev->lots[0]->volume, 'Le mouvement a le bon volume');
$t->is($mvt->date, $drev->lots[0]->date, 'Le mouvement a la bonne date');
$t->is($mvt->millesime, $drev->lots[0]->millesime, 'Le mouvement a le bon millesime');
$t->is($mvt->region, '', "Le mouvement a la bonne région");
$t->is($mvt->numero, $drev->lots[0]->numero, 'Le mouvement a le bon numero');
$t->is($mvt->version, 0, "Le mouvement a la version 0");
$t->is($mvt->origine_hash, $drev->lots[0]->getHash(), 'Le mouvement a bien comme origine le premier lot');
$t->is($mvt->origine_type, 'drev', 'le mouvement a bien comme origine une drev');
$t->is($mvt->origine_mouvement, $mvt->getHash(), 'le mouvement a bien comme origine de mouvement lui même');
$t->is($mvt->origine_document_id, $drev->_id, 'Le mouvement a la bonne origine de document');
$t->is($mvt->id_document, $drev->_id, 'Le mouvement a le bon document id');
$t->is($mvt->declarant_identifiant, $drev->identifiant, 'Le mouvement a le bon identifiant');
$t->is($mvt->declarant_nom, $drev->declarant->raison_sociale, 'Le mouvement a la bonne raison sociale');
$t->is($mvt->destination_type, $drev->lots[0]->destination_type, 'Le mouvement a le bon type de destination');
$t->is($mvt->destination_date, $drev->lots[0]->destination_date, 'Le mouvement a la bonne date de destination');
$t->is($mvt->details, '', "le mouvement n'a pas de détail car il n'a pas de répartition de cépage");
$t->is($mvt->campagne, $drev->campagne, "le mouvement a la bonne campagne");

$res = MouvementLotView::getInstance()->getByPrelevablePreleveRegionDateIdentifiantDocumentId($drev->campagne, 1, 0, '', $drev->lots[0]->date, $drev->identifiant, $drev->_id);
$t->is(count($res->rows), 1, 'on retrouve le mouvement dans la vue MouvementLot');
$t->is($res->rows[0]->id, $drev->_id, 'le mouvement correspond bien à notre drev');
$drevres = DRevClient::getInstance()->find($res->rows[0]->value->origine_document_id);
$t->ok($drevres, 'le mouvement pointe bien sur une Drev existante');
$t->ok( ($drevres instanceof InterfaceMouvementLotsDocument) , 'le mouvement pointe bien vers un document de type InterfaceMouvementLotsDocument');
$lotres = $drevres->get($res->rows[0]->value->origine_hash);
$t->ok($lotres, 'le mouvement correspond bien à un lot');
$mvtres = $drevres->get($res->rows[0]->value->origine_mouvement);
$t->ok($mvtres, 'le mouvement a bien un origine mouvement existant');
$t->ok( ($mvtres instanceof MouvementLots) , 'le mouvement correspond bien à un lot de type MouvementLots');
$t->ok( ($mvtres instanceof InterfaceMouvementLots) , 'le mouvement correspond bien à un lot de type InterfaceMouvementLots');
$t->is($mvtres->origine_mouvement, $res->rows[0]->value->origine_mouvement, "le mouvement l'origine mouvement correspond bien au mouvement");

$t->comment("Test de la synthèse des lots (visu/validation/_recap)");

$synthese = $drev->summerizeProduitsLotsByCouleur();
$t->is(count(array_keys($synthese)), 2, "On a bien toutes les couleurs de la DR en synthèse des lots");
$t->ok(isset($synthese[$drev->lots[0]->getCouleurLibelle()]), "On a bien la couleur du produit 1 en synthèse des lots");
$t->is($synthese[$drev->lots[0]->getCouleurLibelle()]['volume_lots'], 8.2, "On a le bon volume total en synthèse des lots");
$t->is($synthese[$drev->lots[0]->getCouleurLibelle()]['volume_max'], 208.2, "On a le bon volume issu de la dr en synthèse des lots");
$t->is($synthese[$drev->lots[0]->getCouleurLibelle()]['volume_restant'], 200, "On a le bon volume restant en synthèse des lots");

$t->comment("Gestion du prélèvement");
$mvtres->prelever();
$drevres->save();
$t->ok($mvtres->preleve, "Le mouvement prelevé est bien indiqué comme tel");
$res = MouvementLotView::getInstance()->getByPrelevablePreleveRegionDateIdentifiantDocumentId($drev->campagne, 1, 0, '', $drev->lots[0]->date, $drev->identifiant, $drev->_id);
$t->is(count($res->rows), 0, 'on retrouve plus le mouvement prelevé dans la vue MouvementLot');

$t->comment("Modificatrice ".$drev->_id."-M01");
$drev_modif = $drev->generateModificative();
$drev_modif->save();
$t->is($drev_modif->_id, $drev->_id.'-M01', "La modification a l'identifiant attendu");
$t->is(count($drev_modif->mouvements_lots), 0, "La Drev modificatrice juste crée a bien aucun mouvement de lots");
$t->comment("Ajout de lots");

if($drev_modif->storeEtape(DrevEtapes::ETAPE_LOTS)) {
    $drev_modif->save();
}

$drev_modif->addLot();
$drev_modif->addLot();
$form = new DRevLotsForm($drev_modif);
$defaults = $form->getDefaults();
$valuesRev = array(
    'lots' => $form['lots']->getValue(),
    '_revision' => $drev_modif->_rev,
);

$valuesRev['lots']['1']['numero'] = "Cuve B";
$valuesRev['lots']['1']['volume'] = 1;
$valuesRev['lots']['1']['destination_type'] = DRevClient::LOT_DESTINATION_VRAC_FRANCE;
$valuesRev['lots']['1']['destination_date'] = '30/11/'.$campagne;
$valuesRev['lots']['1']['produit_hash'] = $produitconfig2->getHash();
$valuesRev['lots']['1']['millesime'] = '2010';

$valuesRev['lots']['2'] = $valuesRev['lots']['1'];
$valuesRev['lots']['2']['numero'] = "Cuve C";
$valuesRev['lots']['2']['produit_hash'] = $produitconfig_horsDR->getHash();

$form->bind($valuesRev);

$t->ok($form->isValid(), "Le formulaire est valide");
$errors = null;
foreach ($form->getErrorSchema() as $key => $err) {
    if ($key) {
        $errors[$key] = $err->getMessage();
    }
}
$t->is($errors, null, "Pas d'erreur dans le formulaire validé");
$form->save();
$t->is($drev_modif->lots[1]->numero, $valuesRev['lots']['1']['numero'], "Le numéro de cuve du lot 2 est bien enregistré");
$t->is($drev_modif->lots[1]->volume, $valuesRev['lots']['1']['volume'], "Le volume du lot 2 est bien enregistré");
$t->is($drev_modif->lots[1]->produit_libelle, $produitconfig2->getLibelleComplet(), "Le libellé du produit du lot 2 est bien enregistré");
$t->is($drev_modif->lots[1]->millesime, $valuesRev['lots']['1']['millesime'], "Le millesime du lot 2 est bien enregistré");
$t->is($drev_modif->lots[2]->numero, $valuesRev['lots']['2']['numero'], "Le numéro de cuve du lot 3 est bien enregistré");
$t->is($drev_modif->lots[2]->volume, $valuesRev['lots']['2']['volume'], "Le volume du lot 3 est bien enregistré");
$t->is($drev_modif->lots[2]->produit_libelle, $produitconfig_horsDR->getLibelleComplet(), "Le libellé du produit du lot 3 est bien enregistré");
$t->is($drev_modif->lots[2]->millesime, $valuesRev['lots']['2']['millesime'], "Le millesime du lot 3 est bien enregistré");

//$drev_modif->remove('/lots/0');
$drev_modif->save();
$t->is(count($drev_modif->lots), 3, "l'ajout des deux produit a bien impacté le nombre de lots");

if($drev_modif->storeEtape(DrevEtapes::ETAPE_VALIDATION)) {
    $drev_modif->save();
}
$validation = new DRevValidation($drev_modif);
$erreurs = $validation->getPointsByCodes('erreur');
$vigilances = $validation->getPointsByCodes('vigilance');

$t->is($erreurs, null, "pas d'erreur");
$t->is(count($vigilances), 1, "un point de vigilances");
$t->ok(isset($vigilances['lot_igp_inexistant_dans_dr_warn']), "le point vigilance indique que le produit du 2d lot ne fait pas partie de la DR comme attendu");

$form = new DRevLotsForm($drev_modif);
$valuesRev = array(
    'lots' => $form['lots']->getValue(),
    '_revision' => $drev_modif->_rev,
);
$valuesRev['lots']['2']['produit_hash'] = $produitconfig1->getHash();
$form->bind($valuesRev);
$t->ok($form->isValid(), "Le formulaire de changement de nom du produit est valide");
$form->save();

$validation = new DRevValidation($drev_modif);
$erreurs = $validation->getPointsByCodes('erreur');
$vigilances = $validation->getPointsByCodes('vigilance');
$t->is($vigilances, null, "après le changement de produit hors DR, plus de point de vigilances");
$t->is($erreurs, null, "après le changement de produit hors DR, toujours pas d'erreur");

$drev_modif->validate();
$drev_modif->save();
$t->is(count($drev_modif->lots), 3, "Après la validation, le nombre de lots n'a pas changé");
$t->is($drev_modif->lots[0]->produit_libelle, $produitconfig1->getLibelleComplet(), "Après la validation, le lot 1 n'a pas changé");
$t->is($drev_modif->lots[1]->produit_libelle, $produitconfig2->getLibelleComplet(), "Après la validation, le lot 2 n'a pas changé");
$t->is($drev_modif->lots[2]->produit_libelle, $produitconfig1->getLibelleComplet(), "Après la validation, le lot 3 n'a pas changé");

$t->is(count($drev_modif->mouvements_lots->{$drev_modif->identifiant}), 2, "La Drev modificatrice validée a bien généré que 2 mouvements de lots (pour les seuls deux nouveaux lots)");
foreach($drev_modif->mouvements_lots->{$drev_modif->identifiant} as $k => $mvt) {
    break;
}
$t->is($mvt->version, 'M01', 'Le mouvement a le bon numéro de version');
$t->is($mvt->produit_hash, $produitconfig2->getHash(), 'Le mouvement a le bon hash');
$t->is($mvt->prelevable, 1, 'Le mouvement est prelevable');
$t->is($mvt->declarant_identifiant, $drev_modif->identifiant, 'Le mouvement a le bon identifiant de déclarant');
$t->is($mvt->declarant_nom, $drev->declarant->raison_sociale, 'Le mouvement a le bon nom de déclarant');
$t->is($mvt->prelevable, 1, 'Le mouvement est prelevable');

$res = MouvementLotView::getInstance()->getByPrelevablePreleveRegionDateIdentifiantDocumentId($drev_modif->campagne, 1, 0, '', $drev_modif->lots[0]->date, $drev->identifiant, $drev_modif->_id);
$t->is(count($res->rows), 2, 'on retrouve plus les 2 mouvements dans la vue MouvementLot');


$drev_modif2 = $drev_modif->generateModificative();

$drev_modif2->remove('/lots/0');
$t->is($drev_modif2->lots[1]->produit_libelle, $produitconfig2->getLibelleComplet(), "Après la suppression du lot 1, le 1er lot qui vient d'être ajouté est à la bonne place");
$t->is($drev_modif2->lots[2]->produit_libelle, $produitconfig1->getLibelleComplet(), "Après la suppression du lot 1, le 2d lot qui vient d'être ajouté est à la bonne place");
$drev_modif2->validate();
$drev_modif2->save();
$t->is($drev_modif2->lots[0]->produit_libelle, $produitconfig2->getLibelleComplet(), "Après la validation de la suppression du lot 1, le 1er lot est à la bonne place");
$t->is($drev_modif2->lots[1]->produit_libelle, $produitconfig1->getLibelleComplet(), "Après la validation de la suppression du lot 1, le 2d lot est à la bonne place");
$t->is(count($drev_modif2->mouvements_lots->{$drev_modif2->identifiant}), 1, "La 2d Drev modificatrice validée a bien généré que 1 mouvement de lots (celui de la suppression)");
foreach($drev_modif2->mouvements_lots->{$drev_modif->identifiant} as $k => $mvt) {
    break;
}
$t->is($mvt->version, 'M02', 'Le mouvement a le bon numéro de version');
$t->is($mvt->produit_hash, $produitconfig1->getHash(), 'Le mouvement a le bon hash');
$t->is($mvt->prelevable, 0, 'Le mouvement est prelevable');
$res = MouvementLotView::getInstance()->getByPrelevablePreleveRegionDateIdentifiantDocumentId($drev->campagne, 1, 0, '', $drev->lots[0]->date, $drev->identifiant, $drev_modif2->_id);
$t->is(count($res->rows), 0, 'on ne retrouve pas le mouvement comme prelevable dans la vue MouvementLot');
$res = MouvementLotView::getInstance()->getByPrelevablePreleveRegionDateIdentifiantDocumentId($drev->campagne, 0, 0, '', $drev->lots[0]->date, $drev->identifiant, $drev_modif2->_id);
$t->is(count($res->rows), 0, 'on retrouve le mouvement comme non prelevable dans la vue MouvementLot');

$res = MouvementLotView::getInstance()->getByDeclarantIdentifiant($drev->identifiant, $drev->campagne);
$ok = false;
foreach($res->rows as $r) {
    if ($r->value->origine_document_id == $drev->_id) {
        $ok = true;
    }
}
$t->ok($ok, "La vue de récupération par identifiant fonctionne");
*/