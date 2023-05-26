<?php
/**
 * Model for ParcellaireAffectationProduitDetail
 *
 */

class ParcellaireAffectationProduitDetail extends BaseParcellaireAffectationProduitDetail {

    public function getComparaisonKey() {

        return $this->superficie.$this->commune.$this->section.$this->numero_parcelle.$this->lieu.$this->cepage.$this->campagne_plantation;
    }

    public function getComparaisonHash() {

        return preg_replace("/-([A-Z]{1,2})(-[0-9]+-[0-9]{2}(-|$))/", '-000\1\2', $this->getHash());
    }

    public function getProduit() {

        return $this->getParent()->getParent();
    }

    public function getProduitLibelle() {

        return $this->getProduit()->getLibelle();
    }
    
    public function getIdentificationParcelleLibelle() {
    	return $this->section.'-'.$this->numero_parcelle.'<br />'.$this->commune.' '.$this->getLieuLibelle().' '.sprintf("%0.2f&nbsp;<small class='text-muted'>ha</small>", $this->superficie);
    }
    
    public function getIdentificationCepageLibelle() {
    	return $this->getProduitLibelle().'<br />'.$this->getCepageLibelle().' '.$this->campagne_plantation;
    }
    
    public function getDgc() {
        $communesDenominations = sfConfig::get('app_communes_denominations');
        $dgcFinal = null;
        foreach ($communesDenominations as $dgc => $communes) {
            if (!in_array($this->code_commune, $communes)) {
                continue;
            }
            if (strpos($dgc, $this->getLieuNode()->getKey()) !== false) {
                
                return $dgc;
            }
            
            $dgcFinal = $dgc;
        }
        return $dgcFinal;
    }
    
    public function getDgcLibelle() {
        $dgc = $this->getDgc();
        
        if(!$dgc) {
            
            return null;
        }
        
        return $this->getDocument()->getDgcLibelle($dgc);
    }

    public function getLieuLibelle() {
        if ($this->lieu) {

            return $this->lieu;
        }

        return $this->getLieuNode()->getLibelle();
    }
    
    public function getCepageLibelle() {

        return $this->getCepage();
    }

    public function getLieuNode() {

        return $this->getProduit()->getConfig()->getLieu();
    }

    public function getDateAffectationFr() {
        if (!$this->date_affectation) {
            return null;
        }
        $date = new DateTime($this->date_affectation);
    
        return $date->format('d/m/Y');
    }
}
