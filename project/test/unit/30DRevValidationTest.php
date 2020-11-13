<?php

require_once(dirname(__FILE__).'/../bootstrap/common.php');

$nb_test = 21;
if ($application == 'loire') {
    $nb_test += 3;
}
if ($application == 'igp13') {
    $nb_test += 1;
}
$t = new lime_test($nb_test);

$viti =  CompteTagsView::getInstance()->findOneCompteByTag('test', 'test_viti')->getEtablissement();

$campagne = (date('Y')-1)."";

//Début des tests
$drev = DRevClient::getInstance()->find('DREV-' . $viti->identifiant . '-' .$campagne);
$drev->devalidate();

for ($i=1; $i < 99; $i++) {
  $drev_m = DRevClient::getInstance()->find($drev->_id.'-M'.sprintf("%02d",$i));
  if($drev_m){
    $drev_m->delete(false);
  }
}

$produits = $drev->getConfigProduits();
$produit1 = null;
foreach($produits as $produit) {
    if($application == 'loire' && !$produit->isRevendicationParLots()) {
        continue;
    }
    $produit1 = $produit;
    break;
}


$t->comment("Validation des Drev");
$date_validation_1 = "2019-06-30";
$date_validation_odg_1 = "2019-07-30";

$drev->validate($date_validation_1);
$drev->save();
$t->is($drev->isValidee(),true,"La Drev est validée");
$t->is($drev->getValidation(),$date_validation_1,"La date de validation est ".$date_validation_1);
$t->is($drev->isValideeOdg(),false,"La Drev n'est pas encore validée par l'odg");


$drev->validateOdg($date_validation_odg_1);
$drev->save();

$t->is($drev->isValidee(),true,"La Drev est validée");
$t->is($drev->isValideeOdg(),true,"La Drev est validée par l'odg");
$t->is($drev->getValidationOdg(),$date_validation_odg_1,"La date de validation de l'odg est ".$date_validation_odg_1);

if ($application == 'loire') {
    $t->is($drev->lots[0]->date_version,$date_validation_odg_1,"La date de version du lot est celle de la validation ODG");
}

$t->comment("Création d'une modificatrice  Drev");

$date_validation_2 = "2019-08-30";
$date_validation_odg_2 = "2019-08-31";

$drev_modificative = $drev->generateModificative();
$drev_modificative->save();



// Ajout d'un lot

$lot = null;
if ($application == 'loire') {
$lot = $drev_modificative->addLot();

$lot->millesime = $campagne;
$lot->numero = "14";
$lot->volume = 3.5;
$lot->destination_type = null;
$lot->destination_date = ($campagne+1).'-06-15';
$lot->produit_hash = $produit1->getHash();
$lot->destination_type = DRevClient::LOT_DESTINATION_VRAC_EXPORT;
$lot->addCepage("Chenin", 30);
$lot->addCepage("Sauvignon", 70);
}
$drev_modificative->validate($date_validation_2);
$drev_modificative->validateOdg($date_validation_odg_2);
$drev_modificative->save();

if ($lot) {
$lot = $drev_modificative->lots->getLast();
}

$t->is($drev_modificative->getVersion(),"M01","La Drev modificatrice est de rang 01");
$t->is($drev_modificative->isValidee(),true,"La Drev modificatrice est validée");
$t->is($drev_modificative->isValideeOdg(),true,"La Drev modificatrice est validée par l'odg");
$t->is($drev_modificative->getValidation(),$date_validation_2,"La date de validation est ".$date_validation_2);
$t->is($drev_modificative->getValidationOdg(),$date_validation_odg_2,"La date de validation de l'odg est ".$date_validation_odg_2);

if ($lot) {
    $t->is($drev_modificative->lots[0]->date_version,$date_validation_odg_1,"La date de version du lot de départ est celle de la validation ODG de la M00 ($date_validation_odg_1)");
    $t->is($lot->date_version,$date_validation_odg_2,"La date de version du dernier lot est celle de la validation ODG de la M01 ($date_validation_odg_2)");
}

if ($application == 'igp13') {
    $dateDegustVoulue = $campagne.'-09-25';
    $drev->setDateDegustationSouhaitee($dateDegustVoulue);
    $t->is($drev->date_degustation_voulue, $dateDegustVoulue, 'La date de dégustation voulue par l\'opérateur est '.$dateDegustVoulue);
}

$t->comment("Envoi de mail Drev");

$t->ok(Email::getInstance()->getMessageDRevValidation($drev), "Mail de validation à envoyer au déclarant");
$t->is(count(Email::getInstance()->getMessagesDRevValidationNotificationSyndicats($drev)), 0, "Mails de notification de validation à envoyer aux syndicats");
$t->ok(Email::getInstance()->getMessageDRevConfirmee($drev), "Mail de confirmation à envoyer au déclarant");
$t->ok(Email::getInstance()->getMessageDrevPapierConfirmee($drev), "Mail de confirmation papier à envoyer au déclarant");
$drev->validation = null;
$drev->validation_odg = null;
$t->is(count(Email::getInstance()->getMessagesDRev($drev, false)), 0, "Aucun mail envoyé");
$drev->validation = date('Y-m-d');
$t->is(count(Email::getInstance()->getMessagesDRev($drev, false)), 1, "Mail de validation envoyé si télédéclarant");
$t->is(count(Email::getInstance()->getMessagesDRev($drev, true)), 0, "Aucun mail de validation envoyé si admin");
$drev->validation_odg = date('Y-m-d');
$t->is(count(Email::getInstance()->getMessagesDRev($drev, true)), 1, "Mail de validation definitive envoyé");
$t->is(count(Email::getInstance()->getMessagesDRev($drev, false)), 1, "Mail de validation definitive envoyé");
$drev->add('papier', 1);
$t->is(count(Email::getInstance()->getMessagesDRev($drev, true)), 1, "Mail de validation papier envoyé");
