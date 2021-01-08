<?php

class ExportDegustationEtiquettesAnonymesPDFConfig extends ExportPDFConfig
{
    public function __construct() {
        parent::__construct();
        $this->subject = 'Etiquettes Anonymes de Degustation';
        $this->orientation = self::ORIENTATION_PORTRAIT;
        $this->keywords = 'Degustation';
        $this->creator = 'IGP';
        $this->author = 'IGP';

        $this->font_name = 'helvetica';
        $this->margin_left = 0;
        $this->margin_top = 4;
        $this->margin_right = 0;
        $this->margin_bottom = 0;
        $this->margin_header = 0;
        $this->margin_footer = 0;
        $this->font_size = 8;
        $this->font_size_main = 8;

    }
}
