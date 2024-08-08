<?php
/**
 * Model for ParcellaireAffectationCoop
 *
 */

class ParcellaireAffectationCoop extends BaseParcellaireAffectationCoop {


    public function constructId() {
        $this->set('_id', ParcellaireAffectationCoopClient::TYPE_COUCHDB.'-'.$this->identifiant.'-'.$this->periode);
    }

    public function initDoc($identifiant, $periode, $date) {
        $this->identifiant = $identifiant;
        $this->campagne = $periode.'-'.($periode + 1);
        $this->constructId();
    }

    public function getPeriode() {
        return preg_replace('/-.*/', '', $this->campagne);
    }

    public function getEtablissementObject() {

          return EtablissementClient::getInstance()->findByIdentifiant($this->identifiant);
    }

    public function getApporteursChoisis() {
        $apporteurs = array();
        foreach($this->apporteurs as $apporteur) {
            if(!$apporteur->apporteur) {
                continue;
            }
            $apporteurs[$apporteur->getKey()] = $apporteur;
        }

        return $apporteurs;
    }

    public function addApporteur($id_etablissement) {
        if($this->apporteurs->exist($id_etablissement)) {

            return $this->apporteurs->get($id_etablissement);
        }
        $etablissement = EtablissementClient::getInstance()->find($id_etablissement, acCouchdbClient::HYDRATE_JSON);
        if(!$etablissement) {
            return;
        }
        if(!$etablissement->cvi) {
            return;
        }
        $apporteur = $this->apporteurs->add($etablissement->_id);
        $apporteur->nom = $etablissement->nom;
        $apporteur->cvi = $etablissement->cvi;
        $apporteur->intention = true;
        $apporteur->apporteur = true;

        return $apporteur;
    }

    public function buildApporteurs(){
        $sv11 = SV11Client::getInstance()->find("SV11-".$this->identifiant."-".($this->getPeriode() - 1));

        $apporteurs = $this->apporteurs;
        $sv11Apporteurs = $sv11 ? $sv11->getApporteurs() : [];
        $apporteursArray = array();

        // Depuis les liaisons
        foreach($this->getEtablissementObject()->getLiaisonOfType(EtablissementClient::TYPE_LIAISON_COOPERATEUR) as $liaison) {
            $apporteursArray[$liaison->id_etablissement] = $liaison->libelle_etablissement;
        }

        // Depuis la SV11
        foreach($sv11Apporteurs as $idApporteur => $nom) {
            $apporteursArray[$idApporteur] = $nom;
        }

        asort($apporteursArray);

        foreach ($apporteursArray as $id => $nom ) {
            $apporteur = $this->addApporteur($id);
            if(!$apporteur) {
                continue;
            }
            $apporteur->provenance = (array_key_exists($id, $sv11Apporteurs))? SV11Client::TYPE_MODEL : "";
        }
    }

    public function updateApporteurs() {
        foreach($this->getApporteursChoisis() as $apporteur) {
            if($apporteur->getDocument(ParcellaireAffectationClient::TYPE_MODEL)) {
                continue;
            }
            try {
                $apporteur->updateParcelles();
                $apporteur->intention = ($apporteur->nb_parcelles_identifiees);
            }catch(sfException $e) {

            }
        }
    }

    public function getApporteursChanges(){
        $liaisons = array();

        foreach($this->getEtablissementObject()->getLiaisonOfType(EtablissementClient::TYPE_LIAISON_COOPERATEUR) as $liaison) {
            $liaisons[$liaison->id_etablissement] = true;
        }

        $retires = array();
        foreach ($this->getApporteurs() as $apporteur) {
            if($apporteur->apporteur) {
                continue;
            }
            if(!isset($liaisons[$apporteur->getEtablissementId()])) {
                continue;
            }
            if($apporteur->getEtablissementObject()->isSuspendu()) {
                continue;
            }

            $retires[] = $apporteur;
        }
        $ajoutes = array();
        foreach ($this->getApporteurs() as $id => $apporteur) {
            if(!$apporteur->apporteur) {
                continue;
            }
            if(isset($liaisons[$apporteur->getEtablissementId()])) {
                continue;
            }

            $ajoutes[] = $apporteur;
        }

      return array_merge($retires, $ajoutes);
    }


    public function storeEtape($etape) {
        if ($etape == $this->etape) {

            return false;
        }

        $this->add('etape', $etape);

        return true;
    }

}
