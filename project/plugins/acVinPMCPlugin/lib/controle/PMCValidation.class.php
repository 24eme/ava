<?php
class PMCValidation extends DocumentValidation
{

    protected $etablissement = null;
    protected $produit_revendication_rendement = array();

    public function __construct($document, $options = null)
    {
        $this->etablissement = $document->getEtablissementObject();
        parent::__construct($document, $options);
        $this->noticeVigilance = true;
    }

    public function configure()
    {
        $this->addControle(self::TYPE_FATAL, 'produits_multi_region', "Les produits d'une même mise en circulation ne doivent pas être géré par 2 syndicats différents");
        $this->addControle(self::TYPE_FATAL, 'lot_incomplet_fatal', "Cette information est incomplète");
        $this->addControle(self::TYPE_FATAL, 'revendication_manquante', "Vous n'avez pas fait votre déclaration de revendication");
        $this->addControle(self::TYPE_ERROR, 'lot_incomplet', "Cette information est incomplète");
        $this->addControle(self::TYPE_ERROR, 'facture_missing', 'Vous n\'avez pas réglé toutes vos factures, vous ne pouvez donc pas valider votre déclaration');
        $this->addControle(self::TYPE_ERROR, 'limite_volume_lot', 'La limite de volume pour un lot est dépassé');
        $this->addControle(self::TYPE_ERROR, 'volume_depasse', "Vous avez dépassé le volume total revendiqué");
        $this->addControle(self::TYPE_WARNING, 'depassement_8515', "Vous devez présenter un papier");
        $this->addControle(self::TYPE_ENGAGEMENT, '8515', "Vous devrez justifier votre assemblage 85/15");
        $this->addControle(self::TYPE_WARNING, 'lot_a_completer', "Cette information pourrait être renseignée");
        $this->addControle(self::TYPE_ERROR, 'date_degust_anterieure', "La date souhaité de dégustation ne peut pas être dans le passé");
        $this->addControle(self::TYPE_WARNING, 'date_degust_proche', "La date souhaité de dégustation est dans moins de 5 semaines et risque de ne pas être validée");
        $this->addControle(self::TYPE_ERROR, 'logement_chai_inexistant', "Vous devez créer le chai logeant le vin");
        $this->addControle(self::TYPE_ERROR, 'logement_chai_secteur_inexistant', "Vous devez affecter un secteur au chai logeant le vin");

        if ($this->document->getType() === PMCNCCLient::TYPE_MODEL) {
            $this->addControle(self::TYPE_ERROR, 'volume_coherent', "Le volume doit rester le même");
        }
    }

    public function controle()
    {
        $this->controleLotsGenerique('pmc_lots');

        if ($this->document->getType() === PMCNCCLient::TYPE_MODEL) {
            $this->controlePMCNC();
        }
    }


    protected function controleLotsGenerique($routeName){
        if(!$this->document->exist('lots')){
            return;
        }

        $regions = array();
        foreach ($this->document->lots as $key => $lot) {
            foreach(RegionConfiguration::getInstance()->getOdgRegions() as $region) {
                if(RegionConfiguration::getInstance()->isHashProduitInRegion($region, $lot->produit_hash)) {
                    $regions[$region] = $region;
                }
            }
        }
        if(count($regions) > 1) {
            $this->addPoint(self::TYPE_FATAL, 'produits_multi_region', null, $this->generateUrl($routeName, array("id" => $this->document->_id)));
        }

        $drev = DRevClient::getInstance()->find(implode('-', ['DREV', $this->document->identifiant, substr($this->document->campagne, 0, 4)]));
        if ($drev === null || ! $drev->validation_odg) {
            $this->addPoint(self::TYPE_FATAL, 'revendication_manquante', "Déclaration de Revendication", true);
        }

        foreach ($this->document->lots as $key => $lot) {

            if($lot->isEmpty()){
              continue;
            }
            if ($lot->hasBeenEdited()){
                continue;
            }

            if(!$lot->produit_hash){
              $this->addPoint(self::TYPE_FATAL, 'lot_incomplet_fatal', "Lot n° ".($key+1)." - Produit manquant", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }
            if(!$lot->volume && $lot->volume !== 0){
              $this->addPoint(self::TYPE_FATAL, 'lot_incomplet_fatal', "Lot n° ".($key+1)." - Volume manquant", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }

            $volumeMax = strpos($lot->produit_hash, 'SCR') !== false ? 500 : 1000;
            if ($lot->volume > $volumeMax) {
                $this->addPoint(self::TYPE_ERROR, 'limite_volume_lot', 'Vous ne pouvez pas déclarer plus de '.$volumeMax.' hl de '.$lot->getProduitLibelle().' pour un même lot', $this->generateUrl($routeName, ["id" => $this->document->_id]));
                continue;
            }

            if ($lot->exist('engagement_8515') && $lot->engagement_8515) {
                $this->addPoint(self::TYPE_ENGAGEMENT, '8515', "Lot ".$lot->getProduitLibelle()." ( ".$lot->volume." hl )", $this->generateUrl($routeName, ["id" => $this->document->_id]));
            }

            $volume = sprintf("%01.02f",$lot->getVolume());

            if(!$this->document->isValideeOdg() && !$lot->numero_logement_operateur){
              $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Numéro de lot", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }
            if(in_array('destination_type', $lot->getFieldsToFill()) && !$lot->destination_type){
                $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Type de destination", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
                continue;
            }
            if($lot->specificite == Lot::SPECIFICITE_UNDEFINED){
                $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Spécificité", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
                continue;
            }
            if(!$lot->millesime){
              $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Millésime", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
              continue;
            }
            if(!$lot->date_degustation_voulue){
                $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Date de dégustation", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
                continue;
            }
            $date_degust = new DateTimeImmutable($lot->date_degustation_voulue);
            $nb_days_from_degust = (int) $date_degust->diff(new DateTimeImmutable($this->document->date))->format('%a');
            if(date('Y-m-d') < $lot->date_degustation_voulue){
              $this->addPoint(self::TYPE_ERROR, 'date_degust_anterieure', $lot->getProduitLibelle(). " ( ".$volume." hl )", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
              continue;
            }
            if(!$this->document->isValideeOdg() && date('Y-m-d') < $lot->date_degustation_voulue && $nb_days_from_degust <= 45){
              $this->addPoint(self::TYPE_WARNING, 'date_degust_proche', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Lot prélevable à partir du (" . $date_degust->format('d/m/Y') . ")", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
              continue;
            }
        }

        if (DRevConfiguration::getInstance()->hasLogementChais() && sfContext::getInstance()->getUser()->isAdmin()) {
            if (!$this->document->chais->nom && !$this->document->chais->adresse && !$this->document->chais->commune && ! $this->document->chais->code_postal) {
                $this->addPoint(self::TYPE_ERROR, 'logement_chai_inexistant', 'Logement', $this->generateUrl('pmc_exploitation', array("id" => $this->document->_id)));
            } elseif(!$this->document->chais->secteur) {
                $this->addPoint(self::TYPE_ERROR, 'logement_chai_secteur_inexistant', 'Logement', $this->generateUrl('pmc_exploitation', array("id" => $this->document->_id)));
            }
        }

        $this->factures = FactureClient::getInstance()->getFacturesByCompte($this->document->getEtablissementObject()->getSociete()->identifiant);
        foreach($this->factures as $f) {
            if ($f->hasNonPaiement()) {
                $this->addPoint(self::TYPE_ERROR, 'facture_missing', '');
                break;
            }
        }

    }

    protected function controlePMCNC()
    {
        if(!$this->document->exist('lots')){
            return;
        }

        $lotOrigine = $this->document->lots[0];
        $docProvenance = DeclarationClient::getInstance()->find($lotOrigine->id_document_provenance);
        $volumeOrigine = $docProvenance->getLot($lotOrigine->unique_id)->volume;

        $volumeTotal = array_reduce($this->document->lots->toArray(), function ($t, $lot) {
            $t += round($lot['volume'], 2);
            return $t;
        }, 0);

        if ($volumeTotal != $volumeOrigine) {
            $this->addPoint(self::TYPE_ERROR, 'volume_coherent', "Le volume revendiqué est de {$volumeTotal} hl alors que l'original est de {$volumeOrigine} hl", $this->generateUrl('pmc_lots', array("id" => $this->document->_id)));
        }
    }
}
