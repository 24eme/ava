<?php

class ParcellaireClient extends acCouchdbClient {

    const TYPE_MODEL = "Parcellaire";
    const TYPE_COUCHDB = "PARCELLAIRE";

    const MODE_SAVOIRFAIRE_FERMIER = 'FERMIER';
    const MODE_SAVOIRFAIRE_PROPRIETAIRE = 'PROPRIETAIRE';
    const MODE_SAVOIRFAIRE_METAYER = 'METAYER';

    const PARCELLAIRE_DEFAUT_PRODUIT_HASH = '/declaration/certifications/DEFAUT/genres/DEFAUT/appellations/DEFAUT/mentions/DEFAUT/lieux/DEFAUT/couleurs/DEFAUT/cepages/DEFAUT';
    const PARCELLAIRE_DEFAUT_PRODUIT_LIBELLE = 'Appellation non reconnue';

    public static $modes_savoirfaire = array(
        self::MODE_SAVOIRFAIRE_FERMIER => "Fermier",
        self::MODE_SAVOIRFAIRE_PROPRIETAIRE => "Propriétaire",
        self::MODE_SAVOIRFAIRE_METAYER => "Métayer",
    );

    public static function getInstance() {
        return acCouchdbManager::getClient("Parcellaire");
    }

    /**
     * Créé un nouveau document de type Parcellaire
     *
     * @param string $identifiant L'identifiant etablissement du parcellaire
     * @param string La date de campagne du parcellaire
     * @param string Le type de document
     *
     * @return Le document créé
     */
    public function createDoc($identifiant, $campagne, $type = self::TYPE_COUCHDB)
    {
        $parcellaire = new Parcellaire();
        $parcellaire->initDoc($identifiant, $campagne, $type);

        return $parcellaire;
    }

    /**
     * Recherche une entrée dans les documents existants
     *
     * @param string $identifiant L'identifiant etablissement du parcellaire
     * @param string $date La date de création du parcellaire
     *
     * @return Un document existant
     */
    public function findByArgs($identifiant, $date)
    {
        $id = self::TYPE_COUCHDB . '-' . $identifiant . '-' . $date;
        return $this->find($id);
    }

    /**
     * Scrape le site des douanes via le scrapy
     *
     * @param string $cvi Le numéro du CVI à scraper
     *
     * @throws Exception Si aucun CVI trouvé
     * @return string Le fichier le plus récent
     */
    public function scrapeParcellaireCSV($cvi, $scrappe = true, $contextInstance = null)
    {
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();
        $scrapydocs = ProdouaneScrappyClient::getDocumentPath($contextInstance);
        $status = 0;
        if ($scrappe) {
            $status = ProdouaneScrappyClient::exec("download_parcellaire.sh", "$cvi", $output);
        }

        $file = $scrapydocs.'/parcellaire-'.$cvi.'.csv';

        if (empty($file)) {
            $contextInstance->getLogger()->info("scrapeParcellaireCSV() : pas de fichiers trouvés");
        }
        if ($status != 0) {
            $contextInstance->getLogger()->info("scrapeParcellaireCSV() : retour du scrap problématique : $status");
            throw new sfException(end($output));
        }

        return $file;
    }
    /**
     * Scrape le site des douanes via le scrapy
     *
     * @param string $cvi Le numéro du CVI à scraper
     *
     * @throws Exception Si aucun CVI trouvé
     * @return string Le fichier le plus récent
     */
    public function scrapeParcellaireJSON($cvi, $contextInstance = null)
    {
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();
        $scrapydocs = ProdouaneScrappyClient::getDocumentPath();
        $status = ProdouaneScrappyClient::exec("download_parcellaire_geojson.sh", "$cvi", $output);
        $file = $scrapydocs.'/cadastre-'.$cvi.'-parcelles.json';
        $message = "";

        if (empty($file)) {
            $message = "Les parcelles n'existent pas dans les fichier du Cadastre. ";

            if($status != 0){
                $message .= "La récupération des geojson n'a pas fonctionné.";
            }
        }

        if(!empty($message)){
            $contextInstance->getLogger()->info("scrapeParcellaireJSON: error: ".$message);
            throw new Exception($message);
        }

        return $file;
    }

    /**
     * Prend un chemin de fichier en paramètre et le transforme en Parcellaire
     * Vérifie que le nouveau parcellaire est différent du courant avant de le
     * sauver
     *
     * @param Etablissement $etablissement L'établissement à mettre à jour
     * @param Array &$error Le potentiel message d'erreur de retour
     *
     * @return bool
     */
    public function saveParcellaire(Etablissement $etablissement, Array &$errors, $contextInstance = null, $scrapping = true)
    {
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();
        $fileCsv = ProdouaneScrappyClient::getDocumentPath($contextInstance).'/parcellaire-'.$etablissement->cvi.'.csv';

        $fileCsv = $this->scrapeParcellaireCSV($etablissement->cvi, $scrapping, $contextInstance);
        $filePdf = str_replace('.csv', '-parcellaire.pdf', $fileCsv);

        $lastParcellaire = $this->getLast($etablissement->identifiant);
        if($filePdf && is_file($filePdf) && $lastParcellaire && $lastParcellaire->hasParcellairePDF() && md5_file($filePdf) == $lastParcellaire->getParcellairePDFMd5()) {

            return null;
        }

        $return = $this->saveParcellairePDF($etablissement, $filePdf, $errors['pdf']);
        $returncsv = $this->saveParcellaireCSV($etablissement, $fileCsv, $errors['csv'], $contextInstance);

        if ($returncsv) {
            $fileJson = ProdouaneScrappyClient::getDocumentPath($contextInstance).'/cadastre-'.$etablissement->cvi.'-parcelles.json';
            if($scrapping) {
                $fileJson = $this->scrapeParcellaireJSON($etablissement->cvi, $contextInstance);
            }
            $this->saveParcellaireGeoJson($etablissement, $fileJson, $errors['json']);
        }
        return $return || $returncsv;
    }

    public function saveParcellaireGeoJson($etablissement, $path, &$error, $contextInstance = null){
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();
        try {

            $parcellaire = new ParcellaireJsonFile($etablissement, $path, $contextInstance);

            $parcellaire->save();

        } catch (Exception $e) {
            $error = "Une erreur lors de la sauvgarde ".$e->getMessage();
            $contextInstance->getLogger()->info("saveParcellaireGeoJson() : exception ".$e->getMessage());
            return false;
        }

        return true;

    }

    public function saveParcellaireCSV(Etablissement $etablissement, $path, &$error, $contextInstance = null){
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();
        try {
            $csv = new Csv($path);
            $parcellaire = new ParcellaireCsvFile($etablissement, $csv, $contextInstance);
            $parcellaire->convert();

        } catch (Exception $e) {
            $contextInstance->getLogger()->info("saveParcellaireCSV() : exception ".$e->getMessage());
            $error = $e->getMessage();
            return false;
        }

        $parcellaire->save();

        return true;
    }

    public function saveParcellairePDF(Etablissement $etablissement, $file, &$error, $contextInstance = null) {
        $contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();

        if (!is_file($file) || empty($file)) {
            $message = "Le fichier PDF des parcelles ($file) n'existe pas ou est vide.";
            $contextInstance->getLogger()->info("saveParcellairePDF: error: ".$message);
            return false;
        }

        $this->findOrCreateDocPDF($etablissement->identifiant, date('Y-m-d'), 'PRODOUANE', $file, $etablissement->cvi);

        return $file;

    }

    public function find($id, $hydrate = self::HYDRATE_DOCUMENT, $force_return_ls = false) {
        $doc = parent::find($id, $hydrate, $force_return_ls);

        if ($doc && $doc->type != self::TYPE_MODEL) {
            sfContext::getInstance()->getLogger()->info("ParcellaireClient::find()".sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
            throw new sfException(sprintf("Document \"%s\" is not type of \"%s\"", $id, self::TYPE_MODEL));
        }

        return $doc;
    }

    public function findOrCreate($identifiant, $date = null, $source = null, $type = self::TYPE_COUCHDB) {
        if (! $date) {
            $date = date('Ymd');
        }
        $parcellaire = $this->getLast($identifiant);
        if ($parcellaire && $parcellaire->date == $date) {
            return $parcellaire;
        }
        $parcellaire = new Parcellaire();
        $parcellaire->initDoc($identifiant, $date);
        $parcellaire->source = $source;

        return $parcellaire;
    }

    public function findOrCreateDocPDF($identifiant, $date = null, $source = null, $path=null, $cvi = null, $type = self::TYPE_COUCHDB) {
        if (! $date) {
            $date = date('Ymd');
        }
        $parcellaire = $this->getLast($identifiant);

        if (!$parcellaire || $parcellaire->date != $date) {
            $parcellaire = $this->findOrCreate($identifiant, $date, $source, $type);
        }

        if($path){
            $parcellaire->storeAttachment($path, 'application/pdf', "import-cadastre-$cvi-parcelles.pdf");
            $parcellaire->save();
        }
        return $parcellaire;

    }

    public function findOrCreateDocJson($identifiant, $date = null, $source = null, $path=null, $cvi = null, $type = self::TYPE_COUCHDB) {
        if (! $date) {
            $date = date('Ymd');
        }
        $parcellaire = $this->getLast($identifiant);

        if (!$parcellaire || $parcellaire->date != $date) {
            $parcellaire = $this->findOrCreate($identifiant, $date, $source, $type);
        }

        if($path){
            $parcellaire->storeAttachment($path, 'text/json', "import-cadastre-$cvi-parcelles.json");
            $parcellaire->save();
        }
        return $parcellaire;
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

    public function getLast($identifiant, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT){
        $history = $this->getHistory($identifiant, $hydrate);

        return $this->findPreviousByIdentifiantAndDate($identifiant, '9999-99-99');
    }

    public function getHistory($identifiant, $date = '9999-99-99', $hydrate = acCouchdbClient::HYDRATE_DOCUMENT, $dateDebut = "0000-00-00") {

        return $this->startkey(sprintf(self::TYPE_COUCHDB."-%s-%s", $identifiant, str_replace('-', '', $dateDebut)))
                    ->endkey(sprintf(self::TYPE_COUCHDB."-%s-%s", $identifiant, str_replace('-', '', $date)))->execute($hydrate);
    }

    public function findAll($limit = null, $hydrate = acCouchdbClient::HYDRATE_DOCUMENT)
    {
    	$view = $this->startkey(sprintf(self::TYPE_COUCHDB."-%s-%s", "AAA0000000", "00000000"))
    	->endkey(sprintf(self::TYPE_COUCHDB."-%s-%s", "ZZZ9999999", "99999999"));
    	if ($limit) {
    		$view->limit($limit);
    	}
    	return $view->execute($hydrate)->getDatas();
    }

    public static function sortParcellesForCommune($a, $b) {
        $aK = $a->section.sprintf("%04d",$a->numero_parcelle);
        $bK = $b->section.sprintf("%04d",$b->numero_parcelle);
        return strcmp($aK,$bK);

    }

}
