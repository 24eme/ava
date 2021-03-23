<?php

class ExportDegustationAllNotificationsPDF extends ExportPDF {

    protected $degustation = null;
    protected $etablissement = null;
    protected $adresse;
    protected $responsable;

    public function __construct($degustation, $type = 'pdf', $use_cache = false, $file_dir = null, $filename = null) {
        $this->degustation = $degustation;
        $this->adresse = sfConfig::get('app_degustation_courrier_adresse');
        $this->responsable = sfConfig::get('app_degustation_courrier_responsable');

        if (!$filename) {
            $filename = $this->getFileName(true);
        }
        parent::__construct($type, $use_cache, $file_dir, $filename);
        if($this->printable_document->getPdf()){
          $this->printable_document->getPdf()->setPrintHeader(true);
          $this->printable_document->getPdf()->setPrintFooter(true);
        }
    }

    public function create() {
        $lots = array();
        $etablissements = [];

        foreach ($this->degustation->getLots() as $lot) {
            if (in_array($lot->declarant_identifiant, $etablissements) === false) {
                $etablissements[$lot->declarant_identifiant] = EtablissementClient::getInstance()->findByIdentifiant($lot->declarant_identifiant);
            }

            if (($lot->conformite == Lot::CONFORMITE_CONFORME || !$lot->conformite) && ($lot->statut == Lot::STATUT_PRELEVE || $lot->statut == Lot::STATUT_CONFORME) ) {
                $lots[$lot->declarant_identifiant]['conforme'][] = $lot;
            } elseif ($lot->statut == Lot::STATUT_NONCONFORME) {
                $lots[$lot->declarant_identifiant]['nonconforme'][] = $lot;
            }
        }

        foreach ($lots as $declarant => $lots_declarant) {
            if (isset($lots_declarant['conforme']) && count($lots_declarant['conforme'])) {
                $this->printable_document->addPage($this->getPartial('degustation/degustationConformitePDF', array('degustation' => $this->degustation, 'etablissement' => $etablissements[$declarant], 'lots' => $lots_declarant['conforme'], 'responsable' => $this->responsable)));
            }

            if (isset($lots_declarant['nonconforme']) && count($lots_declarant['nonconforme'])) {
                foreach ($lots_declarant['nonconforme'] as $lot_nonconforme) {
                    $this->printable_document->addPage($this->getPartial('degustation/degustationNonConformitePDF_page1', array('degustation' => $this->degustation, 'etablissement' => $etablissements[$declarant], "lot" => $lot_nonconforme, 'responsable' => $this->responsable)));
                    $this->printable_document->addPage($this->getPartial('degustation/degustationNonConformitePDF_page2', array('degustation' => $this->degustation, 'etablissement' => $etablissements[$declarant], "lot" => $lot_nonconforme)));
                }
            }
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
        return '';
    }

    protected function getFooterText() {
        return sprintf("%s     %s - %s  %s    %s\n\n", $this->adresse['raison_sociale'], $this->adresse['adresse'], $this->adresse['cp_ville'], $this->adresse['telephone'], $this->adresse['email']);
    }

    protected function getHeaderSubtitle() {

        return "";
    }


    protected function getConfig() {

        return new ExportDegustationConformitePDFConfig();
    }

    public function getFileName($with_rev = false) {

        return self::buildFileName($this->degustation, true);
    }

    public static function buildFileName($degustation, $with_rev = false) {
        $filename = sprintf("NOTIFICATIONS_%s", $degustation->_id);
        if ($with_rev) {
            $filename .= '_' . $degustation->_rev;
        }

        return $filename . '.pdf';
    }

}
