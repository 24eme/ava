<?php

/**
 * Model for Societe
 *
 */
class Societe extends BaseSociete implements InterfaceCompteGenerique {

    private $comptes = null;

    public function constructId() {
        $this->set('_id', 'SOCIETE-' . $this->identifiant);
    }

    public function removeContact($idContact) {
        if ($this->contacts->exist($idContact)) {
            $contact = $this->getCompte($idContact);
            $this->contacts->remove($idContact);
            $this->removeFromComptes($contact);
            $contact->delete();
            $contact = NULL;
        }
    }

    public function addNewEnseigne() {
        $this->enseignes->add(count($this->enseignes), "");
    }

    public function getInterlocuteursWithOrdre() {
        foreach ($this->contacts as $key => $interlocuteur) {
            if (is_null($interlocuteur->ordre))
                $interlocuteur->ordre = 2;
        }
        return $this->contacts;
    }

    public function getMaxOrdreContacts() {
        $max = 0;
        foreach ($this->contacts as $contact) {
            if ($max < $contact->ordre)
                $max = $contact->ordre;
        }
        return $max;
    }

    public function hasChais() {
        return count($this->etablissements);
    }

    public function setIdentifiant($identifiant) {
        $r = $this->_set('identifiant', $identifiant);

        $this->code_comptable_client = $this->getCodeComtableClient();

        return $r;
    }

    public function getCodeComtableClient() {
        if(!$this->_get('code_comptable_client') && class_exists("FactureConfiguration")) {
            return FactureConfiguration::getInstance()->getPrefixCodeComptable().((int)$this->identifiant)."";
        }

        return $this->_get('code_comptable_client');
    }

    public function canHaveChais() {
        return in_array($this->type_societe, SocieteClient::getSocieteTypesWithChais());
    }

    public function getFamille() {
        if (!$this->canHaveChais())
            throw new sfException('La societe ' . $this->identifiant . " ne peut pas avoir famille n'ayant pas d'établissement");
        return $this->getTypeSociete();
    }

    public function getRegionViticole($throwexception = true) {
        if (!$this->isTransaction()) {
            return '';
        }
        $regions = $this->getRegionsViticoles($throwexception);
        if (count($regions) > 1) {
            if ($throwexception) {
                throw new sfException("La societe " . $this->identifiant . " est reliée des établissements de plusieurs régions viticoles, ce qui n'est pas permis");
            }
            return array_shift($regions);
        }
        if (!count($regions)) {
            if ($throwexception) {
                throw new sfException("La societe " . $this->identifiant . " n'a pas de région viti :(");
            }
            return '';
        }
        return array_shift($regions);
    }

    private function getRegionsViticoles($excludeSuspendus = true) {
        $regions = array();
        foreach ($this->getEtablissementsObj() as $id => $e) {
            if ($e->etablissement->isActif()) {
                $regions[$e->etablissement->region] = $e->etablissement->region;
            }
        }
        //Si tous suspendus que !excludeSuspendus, on va tout de même chercher des régions
        if (!count($regions) && !$excludeSuspendus) {
            foreach ($this->getEtablissementsObj() as $id => $e) {
                $regions[$e->etablissement->region] = $e->etablissement->region;
            }
        }
        return $regions;
    }

    public function getEtablissementsObject($withSuspendu = true) {
        $etablissements = array();
        foreach ($this->etablissements as $id => $obj) {
            $etb = EtablissementClient::getInstance()->find($id);
            if (!$withSuspendu) {
                if (!$etb->isActif()) {
                    continue;
                }
            }
            $etablissements[$id] = $etb;
        }
        return $etablissements;
    }

    public function getEtablissementPrincipal() {
        $etablissements = $this->getEtablissementsObject();
        if (!count($etablissements)) {
            return null;
        }
        foreach ($etablissements as $id => $etbObj) {
            return $etbObj;
        }
        return null;
    }

    public function getContactsObj() {
      if (!$this->comptes && !count($this->comptes)) {
        foreach ($this->contacts as $id => $obj) {
          $this->addToComptes(CompteClient::getInstance()->find($id));
        }
      }
      return $this->comptes;
    }

    private function addToComptes($compte) {
      if (!$this->comptes) {
        $this->comptes = array();
      }
      if ($compte === null) {
          throw new sfException("Could not add NULL compte");
      }
      $this->comptes[$compte->_id] = $compte;
    }
    private function removeFromComptes($compte) {
      $this->getContactsObj();
      unset($this->comptes[$compte->_id]);
    }


    public function getCompte($id) {
        $this->getContactsObj();
        if (!isset($this->comptes[$id]) || !$this->comptes[$id]) {
            var_dump(array_key_exists($id, $this->comptes));
          throw new sfException("Pas de compte ".$id);
        }
        return $this->comptes[$id];
    }

    public function addEtablissement($e) {
        if (!$this->etablissements->exist($e->_id)) {
            $this->etablissements->add($e->_id, array('nom' => $e->nom, 'cvi' => $e->cvi, 'ppm' => $e->ppm));
        } else {
            $this->etablissements->add($e->_id)->nom = $e->nom;
            $this->etablissements->add($e->_id)->cvi = $e->cvi;
            $this->etablissements->add($e->_id)->ppm = $e->ppm;
        }
    }

    public function removeEtablissement($e) {
      $this->etablissements->remove($e->_id);
    }

    public function addCompte($c, $ordre = null) {
        if (!$this->compte_societe) {
            $this->compte_societe = $c->_id;
        }
        if (!$c->_id) {
            return;
        }

        if (!$ordre) {
            $ordre = 0;
        }

        $cid = 'COMPTE-' . $c->identifiant;
        if (!$this->contacts->exist($c->_id)) {
            $this->addToComptes($c);
            $this->contacts->add($cid, array('nom' => $c->nom_a_afficher, 'ordre' => $ordre));
        } else {
            $this->contacts->add($cid)->nom = $c->nom_a_afficher;
            $this->contacts->add($cid)->ordre = $ordre;
        }
    }

    public static function cmpOrdreContacts($a, $b) {
        if ($a->ordre == $b->ordre) {
            return 0;
        }
        return (intval($a->ordre) < intval($b->ordre)) ? -1 : 1;
    }

    public function getMasterCompte() {
        if (!$this->compte_societe) {

            return null;
        }

        return $this->getCompte($this->compte_societe);
    }

    public function getContact() {

        return $this->getMasterCompte();
    }

    public function isManyEtbPrincipalActif() {
        $cptActif = 0;
        foreach ($this->getEtablissementsObj() as $etb) {
            if ($etb->etablissement->isSameCompteThanSociete() && $etb->etablissement->isActif()) {
                $cptActif++;
            }
            if ($cptActif > 1)
                return true;
        }
        return false;
    }

    public function isOperateur() {
        return SocieteClient::TYPE_OPERATEUR == $this->type_societe;
    }

    public function isTransaction() {
        return $this->isNegoOrViti() || $this->isCourtier();
    }

    public function isNegoOrViti() {
        return ($this->type_societe == SocieteClient::TYPE_OPERATEUR);
    }

    public function isCourtier() {
        return $this->type_societe == SocieteClient::TYPE_COURTIER;
    }

    public function isViticulteur() {
        if ($this->type_societe != SocieteClient::TYPE_OPERATEUR) {
            return false;
        }

        foreach ($this->getEtablissementsObj() as $id => $e) {
            if ($e->etablissement->famille == EtablissementFamilles::FAMILLE_PRODUCTEUR) {
                return true;
            }
        }
        return false;
    }

    public function isNegociant() {
        if ($this->type_societe != SocieteClient::TYPE_OPERATEUR) {
            return false;
        }

        foreach ($this->getEtablissementsObj() as $id => $e) {
            if ($e->etablissement->famille == EtablissementFamilles::FAMILLE_NEGOCIANT) {
                return true;
            }
        }
        return false;
    }

    public function isActif() {
        return $this->exist('statut') && $this->statut === EtablissementClient::STATUT_ACTIF;
    }

    public function isSuspendu() {
        return $this->exist('statut') && $this->statut === EtablissementClient::STATUT_SUSPENDU;
    }

    public function hasNumeroCompte() {
        return ($this->code_comptable_client || $this->code_comptable_fournisseur);
    }

    public function getSiegeAdresses() {
        $a = $this->siege->adresse;
        if ($this->siege->exist("adresse_complementaire")) {
            $a .= ' ; ' . $this->siege->adresse_complementaire;
        }
        return $a;
    }

// A VIRER
    protected function createCompteSociete() {
        if ($this->compte_societe) {
            return $this->getCompte($this->compte_societe);
        }

        $compte = CompteClient::getInstance()->findOrCreateCompteSociete($this);
        $this->compte_societe = $compte->_id;
        $compte->setSociete($this);
        if($this->statut) {
            $compte->statut = $this->statut;
        } else {
            $compte->statut = CompteClient::STATUT_ACTIF;
        }
        $compte->mot_de_passe = "{TEXT}" . sprintf("%04d", rand(1000, 9999));
        $compte->addOrigine($this->_id);
        $this->addCompte($compte, -1);
        $compte->nom = $this->raison_sociale;
        $this->addToComptes($compte);
        $this->pushContactAndAdresseTo($compte);
        return $compte;
    }

    public function getDateCreation() {
        $this->add('date_creation');
        return $this->_get('date_creation');
    }

    public function getDateModification() {
        $this->add('date_modification');
        return $this->_get('date_modification');
    }

    protected function doSave() {
        $this->add('date_modification', date('Y-m-d'));
    }

    public function save() {
        $this->interpro = "INTERPRO-declaration";

        if(count($this->etablissements)){
          $this->type_societe = SocieteClient::TYPE_OPERATEUR;
        }else{
          $this->type_societe = SocieteClient::TYPE_AUTRE;
        }
        parent::save();
    }


    public function isPresse() {
        return $this->exist('type_societe') && ($this->type_societe == SocieteClient::TYPE_PRESSE);
    }

    public function isInstitution() {
        return $this->exist('type_societe') && ($this->type_societe == SocieteClient::SUB_TYPE_INSTITUTION);
    }

    public function isSyndicat() {
        return $this->exist('type_societe') && ($this->type_societe == SocieteClient::SUB_TYPE_SYNDICAT);
    }

    public function getEmailTeledeclaration() {
        if ($this->exist('teledeclaration_email') && $this->teledeclaration_email) {
            return $this->teledeclaration_email;
        }
        if ($this->exist('email') && $this->email) {
            return $this->email;
        }
        $compteSociete = $this->getMasterCompte();
        if ($compteSociete->exist('societe_information') && $compteSociete->societe_information->exist('email') && $compteSociete->societe_information->email) {
            return $compteSociete->societe_information->email;
        }
        return $compteSociete->email;
    }

    public function setEmailTeledeclaration($email) {
        $this->add('teledeclaration_email', $email);
    }

    public function getCommentaire() {
        $c = $this->_get('commentaire');
        $c1 = $this->getMasterCompte()->get('commentaire');
        if ($c && $c1) {
            return $c . "\n" . $c1;
        }
        if ($c) {
            return $c;
        }
        if ($c1) {
            return $c1;
        }
    }

    public function addCommentaire($s) {
        $c = $this->get('commentaire');
        if ($c) {
            return $this->_set('commentaire', $c . "\n" . $s);
        }
        return $this->_set('commentaire', $s);
    }

    public function hasLegalSignature() {
        if ($this->exist('legal_signature'))
            return ($this->add('legal_signature')->add('v1'));
        return false;
    }

    public function delete() {
      foreach($this->getComptesAndEtablissements() as $id => $obj) {
        if ($obj) {
          $obj->delete();
        }
      }
      return parent::delete();
    }

    public function createEtablissement($famille) {
      $etablissement = new Etablissement();
      $etablissement->id_societe = $this->_id;
      $societeSingleton = SocieteClient::getInstance()->findSingleton($this->_id);
      if(!$societeSingleton) {
          throw new sfException("La société doit être créé avant de créer l'établissement");
      }
      $etablissement->setSociete($societeSingleton);
      $etablissement->identifiant = EtablissementClient::getInstance()->getNextIdentifiantForSociete($societeSingleton);
      if ($famille) {
          $etablissement->famille = $famille;
      }
      $etablissement->constructId();
      return $etablissement;
    }

    public function createCompteFromEtablissement($etablissement) {
      $compte = CompteClient::getInstance()->createCompteFromEtablissement($etablissement);
      $this->addCompte($compte);
      return $compte;
    }

    public function switchStatusAndSave() {
      $newStatus = "";
      $this->save();

      if($this->isActif() || !$this->statut){
         $newStatus = SocieteClient::STATUT_SUSPENDU;
      }
      if($this->isSuspendu()){
         $newStatus = SocieteClient::STATUT_ACTIF;
      }
      foreach ($this->contacts as $keyCompte => $compte) {
          $contact = CompteClient::getInstance()->find($keyCompte);
          $contact->setStatut($newStatus);
          $contact->save();
      }
      foreach ($this->etablissements as $keyEtablissement => $etablissement) {
          $etablissement = EtablissementClient::getInstance()->find($keyEtablissement);
          $etablissement->setStatut($newStatus);
      }
      $this->setStatut($newStatus);
      $this->save();
    }


    /*** IMPLEMENTATION InterfaceCompteGenerique ***/

    public function setAdresse($adresse){
      $this->getOrAdd('siege')->adresse = $adresse;
    }

    public function setCommune($commune){
      $this->getOrAdd('siege')->commune = $commune;
    }
    public function setCodePostal($code_postal){
      $this->getOrAdd('siege')->code_postal = $code_postal;
    }
    public function setPays($pays){
      $this->getOrAdd('siege')->pays = $pays;
    }
    public function setAdresseComplementaire($adresse_complementaire){
      $this->getOrAdd('siege')->adresse_complementaire = $adresse_complementaire;
    }

    public function getAdresse(){
      if(!$this->siege){
        return null;
      }
      return $this->siege->adresse;
    }

    public function getCommune(){
      if(!$this->siege){
        return null;
      }
      return $this->siege->commune;
    }

    public function getCodePostal(){
        if(!$this->siege){
        return null;
      }
      return $this->siege->code_postal;
    }

    public function getPays(){
      if(!$this->siege){
        return null;
      }
      return $this->siege->pays;
    }

    public function getAdresseComplementaire(){
      if(!$this->siege){
        return null;
      }
      return $this->siege->adresse_complementaire;
    }

    public function setEmail($email){
      $this->email = $email;
    }

    public function setTelephonePerso($telephone_perso){
      $this->telephone_perso = $telephone_perso;
    }

    public function setTelephoneMobile($telephone_mobile){
      $this->telephone_mobile = $telephone_mobile;
    }

    public function setTelephoneBureau($telephone_bureau){
      $this->telephone_bureau = $telephone_bureau;
    }

    public function setSiteInternet($site_internet){
      $this->site_internet = $site_internet;
    }

    public function setFax($fax){
      $this->fax = $fax;
    }

    public function getEmail(){
      return ($this->exist("email"))? $this->_get("email") : ""; //TODO : a supprimer après le merge
      return $this->email;
    }
    public function getTelephoneBureau(){
      return $this->telephone_bureau;
    }
    public function getTelephonePerso(){
      return $this->telephone_perso;
    }
    public function getTelephoneMobile(){
      return $this->telephone_mobile;
    }
    public function getSiteInternet(){
      return $this->site_internet;
    }
    public function getFax(){
      return ($this->exist("fax"))? $this->_get("fax") : ""; //TODO : a supprimer après le merge
      return $this->fax;
    }

    /*** FIN IMPLEMENTATION InterfaceCompteGenerique ***/

    /*** TODO : Fonctions à retirer après le merge ****/
    public function getEtablissementsObj($withSuspendu = true) {
        $etablissements = array();
        foreach ($this->etablissements as $id => $obj) {
            $etb = EtablissementClient::getInstance()->find($id);
            if (!$withSuspendu) {
                if (!$etb->isActif()) {
                    continue;
                }
            }
            $etablissements[$id] = new stdClass();
            $etablissements[$id]->etablissement = $etb;
            $etablissements[$id]->ordre = $obj->ordre;
        }
        return $etablissements;
    }


}
