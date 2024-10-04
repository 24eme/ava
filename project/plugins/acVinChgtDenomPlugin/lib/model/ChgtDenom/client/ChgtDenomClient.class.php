<?php

class ChgtDenomClient extends acCouchdbClient implements FacturableClient {

    const TYPE_MODEL = "ChgtDenom";
    const TYPE_COUCHDB = "CHGTDENOM";
    const CHANGEMENT_TYPE_CHANGEMENT = "CHANGEMENT";
    const CHANGEMENT_TYPE_DECLASSEMENT = "DECLASSEMENT";

    public static function getInstance() {
        return acCouchdbManager::getClient("ChgtDenom");
    }

    public function find($id, $hydrate = self::HYDRATE_DOCUMENT, $force_return_ls = false) {
        $doc = parent::find($id, $hydrate, $force_return_ls);
        if($doc && $doc->type != self::TYPE_MODEL) {
            throw new sfException(sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
        }
        return $doc;
    }

    public function getHistory($identifiant, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $campagne_from = "00000000000000";
        $campagne_to = "99999999999999";
        return $this->startkey(sprintf("CHGTDENOM-%s-%s", $identifiant, $campagne_from))
                    ->endkey(sprintf("CHGTDENOM-%s-%s", $identifiant, $campagne_to))
                    ->execute($hydrate);
    }

    public function getHistoryCampagne($identifiant, $campagne, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $campagne_from = $campagne."0000000000";
        $campagne_to = ($campagne+1)."9999999999";
        return $this->startkey(sprintf("CHGTDENOM-%s-%s", $identifiant, $campagne_from))
                    ->endkey(sprintf("CHGTDENOM-%s-%s", $identifiant, $campagne_to))
                    ->execute($hydrate);
    }

    public function getLast($identifiant, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT){
        return $this->findPreviousByIdentifiantAndDate($identifiant, "99999999999999");
    }

    public function findPreviousByIdentifiantAndDate($identifiant, $date, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT) {
        $h = $this->getHistory($identifiant, $date, $hydrate);
        if (!count($h)) {
        return NULL;
        }
        $h = $h->getDocs();
        end($h);
        $doc = $h[key($h)];
        return $doc;
    }

    public function getLotsChangeable($identifiant, $campagne) {
        $lots = array();
        $lots_filtre = array();
        foreach (MouvementLotView::getInstance()->getByIdentifiant($identifiant, Lot::STATUT_CHANGEABLE)->rows as $row_lot) {
            $lots[$row_lot->value->unique_id] = $row_lot->value;
            $lots[$row_lot->value->unique_id]->type_document = substr($row_lot->value->id_document, 0, 4);
        }

        if($campagne){
          foreach ($lots as $unique_id => $lot) {
            if($campagne && $campagne == $lot->campagne){
              $lots_filtre[$unique_id] = $lot;
            }
          }
        }else{
          $lots_filtre = $lots;
        }

        return $lots_filtre;
    }

    public function createDoc($identifiant, $lot, $date = null, $papier = false) {
        $chgtdenom = new ChgtDenom();

        if(!$date) {
            $date = new DateTime();
        } else {
            $date = new DateTime($date);
        }

        $chgtdenom->identifiant = $identifiant;
        $chgtdenom->date = $date->format('Y-m-d H:i:s');

        if($papier) {
            $chgtdenom->add('papier', 1);
        }
        $chgtdenom->changement_type = self::CHANGEMENT_TYPE_DECLASSEMENT;
        $chgtdenom->storeDeclarant();
        $chgtdenom->setLotOrigine($lot);
        $chgtdenom->constructId();

        return $chgtdenom;
    }

    public function createDocFromProduction($doc, $hash)
    {
        $chgtdenom = new ChgtDenom();
        $chgtdenom->identifiant = $doc->identifiant;
        $chgtdenom->campagne = ConfigurationClient::getInstance()->getCampagneManager()->getCampagneByDate(''.$doc->campagne.'-08-01');
        $chgtdenom->changement_origine_id_document = $doc->_id;
        $chgtdenom->date = (new DateTime())->format('Y-m-d H:i:s');
        $chgtdenom->add('papier', 1);

        $chgtdenom->changement_type = self::CHANGEMENT_TYPE_DECLASSEMENT;
        $chgtdenom->changement_numero_logement_operateur = "Déclassé depuis le document douanier";
        $chgtdenom->origine_numero_logement_operateur = "Déclassé depuis le document douanier";
        $chgtdenom->origine_millesime = $doc->campagne;
        $chgtdenom->origine_produit_hash = "/declaration/".$hash;
        $chgtdenom->origine_produit_libelle = ConfigurationClient::getInstance()->getCurrent()->get(
            $chgtdenom->origine_produit_hash
        )->getLibelle();

        $lastDrev = DRevClient::getInstance()->findMasterByIdentifiantAndPeriode($doc->identifiant, $doc->campagne);
        $synthese = $lastDrev->summerizeProduitsLotsByCouleur();
        $chgtdenom->origine_volume = $synthese[$chgtdenom->origine_produit_libelle." ".$doc->campagne]["volume_restant_max"];

        $chgtdenom->storeDeclarant();
        $chgtdenom->constructId();

        return $chgtdenom;
    }

    public function findFacturable($identifiant, $campagne) {

      // TODO : A retirer : aujourd'hui on bypass les Chgts Denom facturables pour optimiser la page de facturation

      $chgtsdenomCampagne = $this->getHistoryCampagne($identifiant,$campagne);
      $chgtsdenomFacturants = array();
      foreach ($chgtsdenomCampagne as $chgtdenom) {
          $chgtsdenomFacturants[$chgtdenom->_id] = $chgtdenom;
      }
      return $chgtsdenomFacturants;
    }

    public function getPeriodeFromCampagne($campagne) {
        return preg_replace('/-.*/', '', $campagne);
    }

}
