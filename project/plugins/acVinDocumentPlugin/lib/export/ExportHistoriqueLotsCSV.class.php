<?php

class ExportHistoriqueLotsCSV {

    protected $header = false;
    protected $appName = null;

    public static function getHeaderCsv() {
        return "Application;Id Opérateur;Nom Opérateur;Campagne;Date lot;Num Dossier;Num Lot;Doc Ordre;Doc Type;Doc Id;Lot unique Id;Produit;Redegustation;Volume;Statut;Details;\n";
    }

    public function __construct($header = true, $appName = null) {
        $this->header = $header;
        $this->appName = $appName;
    }

    public function protectStr($str) {
    	return str_replace('"', '', $str);
    }

    protected function formatFloat($value) {

        return str_replace(".", ",", $value);
    }

    protected function getLots() {
        return MouvementLotHistoryView::getInstance()->getAllLotsWithHistorique()->rows;
    }

    public function exportAll() {
        $csv = "";
        $lots = $this->getLots();
        if ($this->header) {
            $csv .= $this->getHeaderCsv();
        }
        foreach($lots as $lot) {
          $values = (array)$lot->value;
          if (!$values['statut'] || !isset(Lot::$libellesStatuts[$values['statut']])) {
            continue;
          }
          $statut = Lot::$libellesStatuts[$values['statut']];
          $date = preg_split('/( |T)/', $values['date'], -1, PREG_SPLIT_NO_EMPTY);
          $redegustation = (preg_match("/ème dégustation/", $values['libelle']))? 'oui' : null;
          $csv .= sprintf("%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;\n",
              $this->appName,
              $values['declarant_identifiant'],
              $values['declarant_nom'],
              $values['campagne'],
              $date[0],
              $values['numero_dossier'],
              $values['numero_archive'],
              $values['document_ordre'],
              $values['document_type'],
              $values['document_id'],
              $values['lot_unique_id'],
              trim($this->protectStr($values['libelle'])),
              $redegustation,
              $this->formatFloat($values['volume']),
              $statut,
              $this->protectStr($values['detail'])
          );
        }
        return $csv;
    }
}
