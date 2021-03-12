<?php

class ConditionnementClient extends acCouchdbClient {

    const TYPE_MODEL = "Conditionnement";
    const TYPE_COUCHDB = "CONDITIONNEMENT";

    public static function getInstance()
    {
        return acCouchdbManager::getClient("Conditionnement");
    }

    public function find($id, $hydrate = self::HYDRATE_DOCUMENT, $force_return_ls = false) {
        $doc = parent::find($id, $hydrate, $force_return_ls);

        if($doc && $doc->type != self::TYPE_MODEL) {

            throw new sfException(sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
        }

        return $doc;
    }


    public function findMasterByIdentifiantAndCampagne($identifiant, $campagne, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $docs = DeclarationClient::getInstance()->viewByIdentifiantCampagneAndType($identifiant, $campagne, self::TYPE_MODEL);
        foreach ($docs as $id => $doc) {
            return $this->find($id, $hydrate);
        }
        return null;
    }

    public function findByIdentifiantAndDate($identifiant, $date, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $docid = self::TYPE_COUCHDB.'-'.$identifiant.'-'.str_replace('-', '', $date);
        $doc = $this->find($docid);
        return $doc;
    }


    public function findByIdentifiantAndCampagneAndDateOrCreateIt($identifiant, $campagne, $date, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $doc = $this->findByIdentifiantAndDate($identifiant, $date);
        if (!$doc) {
            $doc = $this->createDoc($identifiant, $campagne, $date);
        }
        return $doc;
    }

    public function createDoc($identifiant, $campagne, $date = null, $papier = false)
    {
        $doc = new Conditionnement();
        $doc->initDoc($identifiant, $campagne, $date);

        $doc->storeDeclarant();

        $etablissement = $doc->getEtablissementObject();

        if(!$etablissement->hasFamille(EtablissementFamilles::FAMILLE_PRODUCTEUR)) {
            $doc->add('non_recoltant', 1);
        }

        if(!$etablissement->hasFamille(EtablissementFamilles::FAMILLE_CONDITIONNEUR)) {
            $doc->add('non_conditionneur', 1);
        }

        if($papier) {
            $doc->add('papier', 1);
        }

        return $doc;
    }

    public function getIds($campagne) {
        $ids = $this->startkey_docid(sprintf("CONDITIONNEMENT-%s-%s", "0000000000", "0000"))
                    ->endkey_docid(sprintf("CONDITIONNEMENT-%s-%s", "9999999999", "9999"))
                    ->execute(acCouchdbClient::HYDRATE_ON_DEMAND)->getIds();

        $ids_campagne = array();

        foreach($ids as $id) {
            if(strpos($id, "-".$campagne) !== false) {
                $ids_campagne[] = $id;
            }
        }

        sort($ids_campagne);

        return $ids_campagne;
    }

    public function getDateOuvertureDebut() {
        $dates = sfConfig::get('app_dates_ouverture_conditionnement');

        return $dates['debut'];
    }

    public function getDateOuvertureFin() {
        $dates = sfConfig::get('app_dates_ouverture_conditionnement');

        return $dates['fin'];
    }

    public function isOpen($date = null) {
        if(is_null($date)) {

            $date = date('Y-m-d');
        }

        return $date >= $this->getDateOuvertureDebut() && $date <= $this->getDateOuvertureFin();
    }

    public function getHistory($identifiant, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $campagne_from = "0000";
        $campagne_to = "9999";

        return $this->startkey(sprintf("CONDITIONNEMENT-%s-%s", $identifiant, $campagne_from))
                    ->endkey(sprintf("CONDITIONNEMENT-%s-%s_ZZZZZZZZZZZZZZ", $identifiant, $campagne_to))
                    ->execute($hydrate);
    }

    public function findConditionnementsByCampagne($identifiant, $campagne, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT){
      $allConditionnement = ConditionnementClient::getInstance()->getHistory($identifiant);
      $conditionnements = array();
      foreach ($allConditionnement as $key => $conditionnement) {
        if($conditionnement->campagne == $campagne){
          $conditionnements[] = $conditionnement;
        }
      }
      return $conditionnements;
    }

}
