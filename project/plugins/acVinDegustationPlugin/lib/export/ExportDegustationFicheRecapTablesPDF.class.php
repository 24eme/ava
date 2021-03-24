<?php

class ExportDegustationFicheRecapTablesPDF extends ExportPDF {

    protected $degustation = null;

    public function __construct($degustation, $type = 'pdf', $use_cache = false, $file_dir = null, $filename = null) {
        $this->degustation = $degustation;

        if (!$filename) {
            $filename = $this->getFileName(true);
        }
        parent::__construct($type, $use_cache, $file_dir, $filename);
    }

    public function create() {
        $lotsByTable = array();
      foreach ($this->degustation->getLotsSortByTables() as $lot) {
          $lotsByTable[$lot->numero_table][$lot->numero_anonymat] = $lot;
      }

        if (empty($lotsByTable)) {
            throw new sfException('Pas de lots attablés : '.$this->degustation->_id);
        }

      foreach($lotsByTable as $numeroTable => $lots) {
          @$this->printable_document->addPage(
            $this->getPartial('degustation/ficheRecapTablesPdf',
            array(
              'degustation' => $this->degustation,
              'lots' => $lots,
              'numTab' => $numeroTable
            )
          ));
        }
    }


    public function output() {
        if($this->printable_document instanceof PageableHTML) {
            return parent::output();
        }

        return file_get_contents($this->getFile());
    }

    public function getFile() {

        if($this->printable_document instanceof PageableHTML) {
            return parent::getFile();
        }

        return sfConfig::get('sf_cache_dir').'/pdf/'.$this->getFileName(true);
    }

    protected function getHeaderTitle() {
        $titre = $this->degustation->getNomOrganisme();

        return $titre;
    }

    protected function getHeaderSubtitle() {

        $header_subtitle = sprintf("%s\n\n", $this->degustation->lieu)."FICHE DE SYNTHÈSE";

        return $header_subtitle;
    }


    protected function getFooterText() {
        $footer= sprintf($this->degustation->getNomOrganisme()." — %s", $this->degustation->getLieuNom());
        return $footer;
    }

    protected function getConfig() {

        return new ExportDegustationFicheRecapTablesPDFConfig();
    }

    public function getFileName($with_rev = false) {

        return self::buildFileName($this->degustation, true);
    }

    public static function buildFileName($degustation, $with_rev = false) {
        $filename = sprintf("fiche_synthese_recap_tables_%s", $degustation->_id);


        if ($with_rev) {
            $filename .= '_' . $degustation->_rev;
        }


        return $filename . '.pdf';
    }

}
