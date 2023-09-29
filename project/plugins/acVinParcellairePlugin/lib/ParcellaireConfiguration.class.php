<?php

class ParcellaireConfiguration {

    private static $_instance = null;
    protected $configuration;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new ParcellaireConfiguration();
        }
        return self::$_instance;
    }

    public function __construct() {
        if(!sfConfig::has('parcellaire_configuration_parcellaire')) {
			throw new sfException("La configuration pour le parcellaire n'a pas été défini pour cette application");
		}

        $this->configuration = sfConfig::get('parcellaire_configuration_parcellaire', array());
    }

    /*
     * On limite les produits du parcellaire aux seuls produits du catalogue produit.
     * les autres sont ignorés
     */
    public function getLimitProduitsConfiguration() {
        if(!isset($this->configuration['limit_produits_configuration'])) {
            return false;
        }

        return $this->configuration['limit_produits_configuration'];
    }

    /*
     * Seules les parcelles ayant au moins une troisième feuille sont prises
     * en compte dans les synthèse
     */
    public function isTroisiemeFeuille() {
        return !$this->getLimitProduitsConfiguration();
    }

    public function isAres()
    {
        return $this->configuration['unit'] == "ares";
    }

    public function getAiresInfos() {
        if(!isset($this->configuration['aires'])) {

            return array();
        }
        return $this->configuration['aires'];
    }

    public function getAireInfoFromDenominationId($id) {
        foreach($this->getAiresInfos() as $k => $aire) {
            if ($aire['denomination_id'] == $id) {
                return $aire;
            }
        }
        return null;
    }

    public function getAireInfo($key) {
        if(!isset($this->configuration['aires'][$key])) {

            return null;
        }

        return $this->configuration['aires'][$key];
    }

    public function affectationNeedsIntention() {
        if(!isset($this->configuration['affectation'])) {
            return true;
        }
        if(!isset($this->configuration['affectation']['needs_intention'])) {
            return true;
        }
        return $this->configuration['affectation']['needs_intention'];

    }
}
