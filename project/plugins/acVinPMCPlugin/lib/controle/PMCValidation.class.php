<?php
class PMCValidation extends DeclarationLotsValidation
{

    protected $etablissement = null;
    protected $produit_revendication_rendement = array();

    public function __construct($document, $options = null)
    {
        $this->etablissement = $document->getEtablissementObject();
        parent::__construct($document, $options);
        $this->noticeVigilance = true;
    }

    public function configure()
    {
        $this->configureLots();
    }

    public function controle()
    {
        $this->controleLotsGenerique('pmc_lots');
    }


    protected function controleLotsGenerique($routeName){
        if(!$this->document->exist('lots')){
            return;
        }

        foreach ($this->document->lots as $key => $lot) {

            if($lot->isEmpty()){
              continue;
            }
            if ($lot->hasBeenEdited()){
                continue;
            }

            if(!$lot->produit_hash){
              $this->addPoint(self::TYPE_FATAL, 'lot_incomplet_fatal', "Lot n° ".($key+1)." - Produit manquant", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }
            if(!$lot->volume && $lot->volume !== 0){
              $this->addPoint(self::TYPE_FATAL, 'lot_incomplet_fatal', "Lot n° ".($key+1)." - Volume manquant", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }

            $volume = sprintf("%01.02f",$lot->getVolume());

            if(!$lot->numero_logement_operateur){
              $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Numéro de logement", $this->generateUrl($routeName, array("id" => $this->document->_id)));
              continue;
            }

            if(in_array('destination_type', $lot->getFieldsToFill()) && !$lot->destination_type){
                $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Type de destination", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
                continue;
            }
            if($lot->specificite == Lot::SPECIFICITE_UNDEFINED){
                $this->addPoint(self::TYPE_ERROR, 'lot_incomplet', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Spécificité", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
                continue;
            }
            if(!$lot->millesime){
              $this->addPoint(self::TYPE_WARNING, 'lot_a_completer', $lot->getProduitLibelle(). " ( ".$volume." hl ) - Millésime", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
              continue;
            }

            if(count($lot->cepages)){
              $somme = 0.0;
              foreach ($lot->cepages as $cepage => $v) {
                $somme+=$v;
              }
              if(round($somme, 2) != round($lot->volume, 2)){
                $this->addPoint(self::TYPE_FATAL, 'lot_cepage_volume_different', $lot->getProduitLibelle(). " ( ".round($lot->volume, 2)." hl vs cépage ".round($somme, 2)." hl )", $this->generateUrl($routeName, array("id" => $this->document->_id, "appellation" => $key)));
              }
            }

            if ($lot->statut == Lot::STATUT_ELEVAGE) {
                $this->lotselevage[$lot->produit_hash][] = [$lot->getProduitLibelle(), $lot->volume];
            }
        }

        if (count($this->lotselevage) > 0) {
            $msg = [];
            foreach ($this->lotselevage as $hash => $lots) {
                $msg[] = count($lots) . " lot(s) de ".$lots[0][0]." (".array_sum(array_column($lots, 1))." hl)";
            }

            $this->addPoint(self::TYPE_ENGAGEMENT, DRevDocuments::DOC_ELEVAGE_CONTACT_SYNDICAT, implode(', ', $msg));
        }
    }

}
