<?php

class TemplateFactureClient extends acCouchdbClient {

    const TYPE_MODEL = "TemplateFacture";
    const TYPE_COUCHDB = "TEMPLATE";

    public static function getInstance()
    {

        return acCouchdbManager::getClient("TemplateFacture");
    }

    public function find($id, $hydrate = self::HYDRATE_DOCUMENT, $force_return_ls = false) {
        $doc = parent::find($id, $hydrate, $force_return_ls);

        if($doc && $doc->type != self::TYPE_MODEL) {

            throw new sfException(sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
        }

        return $doc;
    }

    public function getTemplateIdFromCampagne($campagne_start = null, $region = null) {
        $template = FactureConfiguration::getinstance()->getUniqueTemplateFactureName();
        if (!$template){
            return null;
        }
        if (!$campagne_start) {
            $campagne_start = date('Y');
        }

        if (strpos($template, '%region%') !== false) {
            if ($region === null) {
                throw new sfException("Le template nécessite une région");
            }

            $template = str_replace('%region%', $region, $template);
        }

        for($d = $campagne_start ; $d > $campagne_start - 10 ; $d--) {
            $id = sprintf($template, $d);
            if ($this->find($id, self::HYDRATE_JSON)) {
                return $id;
            }
        }
        throw new sfException("Object TEMPLATE-FACTURE not found from template $id");
    }

    public function findByCampagne($campagne, $region = null, $hydrate = self::HYDRATE_DOCUMENT){
        $id = $this->getTemplateIdFromCampagne($campagne, $region);

        if(!$id) {

            return null;
        }

        return $this->find($id, $hydrate);
    }


    public function findAll() {
        return $this->startkey_docid(sprintf("TEMPLATE-FACTURE-%s", ""))
        ->endkey_docid(sprintf("TEMPLATE-FACTURE-%s", "Z"))
                    ->execute();
    }

}
