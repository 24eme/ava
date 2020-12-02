<?php

class ExportDegustationFicheLotsAPrelevesPDFConfig extends ExportPDFConfig
{
    public function __construct() {
        parent::__construct();
        $this->subject = 'Fiche Récapitulative des tables';
        $this->orientation = self::ORIENTATION_PORTRAIT;
        $this->keywords = 'Degustation';

        $this->font_name = 'helvetica';
        $this->margin_left = 1;
        $this->margin_right = 1;
        $this->font_size = 8;
        $this->font_size_main = 8;
    }
}
