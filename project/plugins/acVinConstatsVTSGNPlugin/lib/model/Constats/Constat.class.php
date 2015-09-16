<?php

/**
 * Model for Constat
 *
 */
class Constat extends BaseConstat {

    public function createOrUpdateFromRendezVous(Rendezvous $rdv) {
        if ($rdv->isRendezvousRaisin()) {
            $this->date_raisin = $rdv->getDateHeure();
            $this->statut_raisin = ConstatsClient::STATUT_NONCONSTATE;
            $this->statut_volume = ConstatsClient::STATUT_NONCONSTATE;
            $this->rendezvous_raisin = $rdv->_id;
        } elseif ($rdv->isRendezvousVolume()) {
            $this->rendezvous_volume = $rdv->_id;
            $this->date_volume = $rdv->getDateHeure();
        }
    }

    public function updateConstat($jsonContent) {

        $this->produit = $jsonContent->produit;
        $this->produit_libelle = $jsonContent->produit_libelle;
        $this->denomination_lieu_dit = $jsonContent->denomination_lieu_dit;
        $this->nb_contenant = $jsonContent->nb_contenant;
        $this->contenant = $jsonContent->contenant;
        $this->contenant_libelle = $jsonContent->contenant_libelle;
        $this->degre_potentiel_raisin = $jsonContent->degre_potentiel_raisin;
        $this->degre_potentiel_volume = (isset($jsonContent->degre_potentiel_volume)) ? $jsonContent->degre_potentiel_volume : null;
        $this->volume_obtenu = (isset($jsonContent->volume_obtenu)) ? $jsonContent->volume_obtenu : null;
        $this->type_vtsgn = (isset($jsonContent->type_vtsgn)) ? $jsonContent->type_vtsgn : null;
        $this->rendezvous_raisin = $jsonContent->rendezvous_raisin;

        $this->raison_refus = (isset($jsonContent->raison_refus)) ? $jsonContent->raison_refus : null;
        $this->raison_refus_libelle = (isset($jsonContent->raison_refus_libelle)) ? $jsonContent->raison_refus_libelle : null;
        $this->signature_base64 = isset($jsonContent->signature)?  $jsonContent->signature : null;
        $this->getDocument()->email = isset($jsonContent->email)?  $jsonContent->email : null;
        
        if ($jsonContent->type_constat == 'raisin') {
            $this->setStatutRaisinAndCreateVolumeRendezvous($jsonContent);
        }
        if ($jsonContent->type_constat == 'volume') {
            $this->setStatutVolumeAndRendezvous($jsonContent);
        }

        $this->statut_volume = $jsonContent->statut_volume;
        $this->statut_raisin = $jsonContent->statut_raisin;
    }

    public function setStatutRaisinAndCreateVolumeRendezvous($jsonContent) {

        if ($jsonContent->statut_raisin == ConstatsClient::STATUT_APPROUVE) {
            $newRdvVolume = RendezvousClient::getInstance()->findOrCreateRendezvousVolumeFromIdRendezvous($jsonContent->rendezvous_raisin, $jsonContent->nom_agent_origine);
            $newRdvVolume->save();
            $rendezvousRaisin = RendezvousClient::getInstance()->find($jsonContent->rendezvous_raisin);
            $rendezvousRaisin->set('statut', RendezvousClient::RENDEZVOUS_STATUT_REALISE);
            $rendezvousRaisin->save();

            $tourneeOrigine = TourneeClient::getInstance()->findTourneeByIdRendezvous($jsonContent->rendezvous_raisin);
            $newTournee = TourneeClient::getInstance()->findOrAddByDateAndAgent($newRdvVolume->getDate(), $tourneeOrigine->getAgentUniqueObj());
            $newTournee->addRendezVousAndReferenceConstatsId($newRdvVolume->_id, $this->getDocument());
            $newTournee->save();
            $this->date_volume = str_replace('-', '', $newTournee->getDate()) . substr($this->date_raisin, 8, 4);
            $this->rendezvous_volume = $newRdvVolume->_id;
        }

        if ($jsonContent->statut_raisin == ConstatsClient::STATUT_REFUSE) {
            $rendezvousRaisin = RendezvousClient::getInstance()->find($jsonContent->rendezvous_raisin);
            $rendezvousRaisin->set('statut', RendezvousClient::RENDEZVOUS_STATUT_REALISE);
            $rendezvousRaisin->save();
            if ($this->isAllConstatsForRendezVousRefuses($jsonContent)) {
                $rdvVolume = RendezvousClient::getInstance()->findOrCreateRendezvousVolumeFromIdRendezvous($jsonContent->rendezvous_raisin, $jsonContent->nom_agent_origine);
                if ($rdvVolume) {
                    $tourneeOrigine = TourneeClient::getInstance()->findTourneeByIdRendezvous($jsonContent->rendezvous_raisin);
                    $tournee = TourneeClient::getInstance()->find(sprintf("%s-%s-%s", TourneeClient::TYPE_COUCHDB, str_replace("-", "", $rdvVolume->getDate()), $tourneeOrigine->getAgentUnique()));
                    if ($tournee) {
                        $tournee->annuleRendezVous($rdvVolume->_id);
                        $tournee->save();
                    }
                    $rdvVolume->set('statut', RendezvousClient::RENDEZVOUS_STATUT_ANNULE);
                    $rdvVolume->save();
                }
            }
        }
    }

    public function setStatutVolumeAndRendezvous($jsonContent) {
        if (($this->statut_raisin == ConstatsClient::STATUT_APPROUVE) && ($this->statut_volume == ConstatsClient::STATUT_NONCONSTATE)) {
            $rendezvousVolume = RendezvousClient::getInstance()->find($jsonContent->rendezvous_volume);
            $rendezvousVolume->set('statut', RendezvousClient::RENDEZVOUS_STATUT_REALISE);
            $rendezvousVolume->save();
            $this->send_mail_required = true;
        }
        if (($this->statut_raisin == ConstatsClient::STATUT_APPROUVE) && ($jsonContent->statut_volume == ConstatsClient::STATUT_REFUSE)) {
            $rendezvousVolume = RendezvousClient::getInstance()->find($jsonContent->statut_volume);
            $rendezvousVolume->set('statut', RendezvousClient::RENDEZVOUS_STATUT_REALISE);
            $rendezvousVolume->save();
        }
    }

    public function determineTypeConstat() {
        if ($this->statut_raisin == ConstatsClient::STATUT_NONCONSTATE) {
            return ConstatsClient::CONSTAT_TYPE_RAISIN;
        }
        return ConstatsClient::CONSTAT_TYPE_VOLUME;
    }

    public function isConstatVolume() {
        return ($this->determineTypeConstat() == ConstatsClient::CONSTAT_TYPE_RAISIN) && $this->date_volume;
    }

    private function isAllConstatsForRendezVousRefuses($json) {

        foreach ($this->getDocument()->constats as $uniqKey => $constat) {
            $key_type_rdv = 'rendezvous_' . $json->type_constat;
            $key_type_statut = 'statut_' . $json->type_constat;
            if (($constat->$key_type_rdv == $json->$key_type_rdv) && ($constat->$key_type_statut != ConstatsClient::STATUT_REFUSE) && ($uniqKey != $this->getKey())) {
                return false;
            }
        }
        return true;
    }

}
