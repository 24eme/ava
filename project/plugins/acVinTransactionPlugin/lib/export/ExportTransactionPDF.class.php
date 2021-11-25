<?php
class ExportTransactionPDF extends ExportDeclarationLotsPDF {

  protected function getHeaderTitle() {
      $date = new DateTime($this->declaration->date);
      $titre = sprintf("Déclaration de Vrac export du %s", $date->format('d/m/Y'));
      return $titre;
  }

  public function create() {
      @$this->printable_document->addPage($this->getPartial('transaction/pdf', array('document' => $this->declaration, 'etablissement' => $this->etablissement)));
  }
}
