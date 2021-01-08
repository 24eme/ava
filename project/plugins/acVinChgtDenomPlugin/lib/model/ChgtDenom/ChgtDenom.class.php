<?php

class ChgtDenom extends BaseChgtDenom implements InterfaceDeclarantDocument, InterfacePieceDocument, InterfaceMouvementLotsDocument, InterfaceMouvementFacturesDocument {

    const DEFAULT_KEY = 'DEFAUT';

    protected $declarant_document = null;
    protected $mouvement_document = null;
    protected $piece_document = null;
  	protected $cm = null;

    public function __construct() {
        parent::__construct();
        $this->initDocuments();
				$this->cm = new CampagneManager('08-01', CampagneManager::FORMAT_PREMIERE_ANNEE);
    }

    public function __clone() {
        parent::__clone();
        $this->initDocuments();
    }

    protected function initDocuments() {
        $this->declarant_document = new DeclarantDocument($this);
        $this->mouvement_document = new MouvementFacturesDocument($this);
        $this->piece_document = new PieceDocument($this);
    }

		public function getDateStdr() {
			return ($this->date && preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $this->date, $m))? $m[1] : date ('Y-m-d');
		}

		public function getCampagneByDate() {
			return $this->cm->getCampagneByDate($this->getDateStdr());
		}

    public function constructId() {
        $id = 'CHGTDENOM-' . $this->identifiant . '-' . $this->date;
        $this->set('_id', $id);
        $this->set('campagne', $this->getCampagneByDate());
    }

    public function getConfiguration() {
        return ConfigurationClient::getInstance()->getConfiguration();
    }

    public function getConfigProduits() {
        return $this->getConfiguration()->declaration->getProduits();
    }

    public function initDoc($identifiant, $date) {
        $this->identifiant = $identifiant;
        $this->date = $date;
        $etablissement = $this->getEtablissementObject();
    }

    public function storeDeclarant() {
        $this->declarant_document->storeDeclarant();
        if($this->getEtablissementObject()->famille) {
            $this->declarant->famille = $this->getEtablissementObject()->famille;
        }
    }

    public function storeEtape($etape) {
        $etapeOriginal = ($this->exist('etape')) ? $this->etape : null;
        $this->add('etape', $etape);
        return $etapeOriginal != $this->etape;
    }

    public function validate($date = null) {
        if(is_null($date)) {
            $date = date('Y-m-d');
        }
        $this->validation = $date;
    }

    public function isPapier() {
        return $this->exist('papier') && $this->get('papier');
    }

    public function isValide() {
      return ($this->validation);
    }

    public function isValidee() {
      return $this->isValide();
    }

    public function isApprouve() {
      return ($this->validation_odg);
    }

    public function validateOdg($date = null) {
        if(is_null($date)) {
            $date = date('Y-m-d');
        }
        $this->validation_odg = $date;
    }

    public function getEtablissementObject() {
        return EtablissementClient::getInstance()->findByIdentifiant($this->identifiant);
    }

    public function getMvtLots() {
      $lots = array();
      $statuts = ($this->isValidee()||$this->isApprouve())? array(Lot::STATUT_CHANGE, Lot::STATUT_DECLASSE) : array(Lot::STATUT_CONFORME, Lot::STATUT_NONCONFORME);
      foreach (MouvementLotView::getInstance()->getAllByIdentifiantAndStatuts($this->identifiant, $statuts) as $item) {
          $key = Lot::generateMvtKey($item->value);
          $lots[$key] = $item->value;
      }
      return $lots;
    }

    public function getLotByNumArchive($numero_archive){
      foreach ($this->lots as $lot) {
        if($lot->numero_archive == $numero_archive){
          return $lot;
        }
      }
      return null;
    }

    public function hasLots() {
      return !empty($this->__get('changement_origine_mvtkey'));
    }

    public function getLotKey() {
      return ($this->hasLots())? $this->changement_origine_mvtkey : null;
    }

    public function getMvtLot() {
      $mvts = $this->getMvtLots();
      $key = $this->getLotKey();
      return ($mvts && $key && isset($mvts[$key]))? $mvts[$key] : null;
    }

	  protected function doSave() {
      $this->piece_document->generatePieces();
	  }

    public function clearMouvementsLots(){
      $this->remove('mouvements_lots');
      $this->add('mouvements_lots');
    }

    public function clearLots(){
      $this->remove('lots');
      $this->add('lots');
    }

    public function isDeclassement() {
      return (!$this->changement_produit);
    }
    public function isChgtTotal() {
      return ($this->changement_volume == $this->getMvtLot()->volume);
    }

    public function getPourcentagesCepages() {
      $total = 0;
      $cepages = array();
      foreach($this->changement_cepages as $pc) {
        $total += $pc;
      }
      foreach($this->changement_cepages as $cep => $pc) {
        $cepages[$cep] += round(($pc/$total) * 100);
      }
      return $cepages;
    }

    public function generateLots() {
      $mvtLot = $this->getMvtLot();
      $this->clearMouvementsLots();
      $this->clearLots();

      $lots = array();
      $lot = MouvementLotView::generateLotByMvt($mvtLot);
      $lot->numero_archive .= 'a';

      if (!$this->isChgtTotal()) {
        $lot->volume -= $this->changement_volume;
        $lot->statut = Lot::STATUT_CONFORME;
        $lotBis = MouvementLotView::generateLotByMvt($mvtLot);
        $lotBis->numero_archive .= 'b';
        $lotBis->volume = $this->changement_volume;
        $lotBis->produit_hash = ($this->isDeclassement())? null : $this->changement_produit;
        $lotBis->produit_libelle = ($this->isDeclassement())? 'Déclassement' : $this->changement_produit_libelle;
        $lotBis->statut = ($this->isDeclassement())? Lot::STATUT_DECLASSE : Lot::STATUT_CONFORME;
        $lotBis->details = '';
        foreach($this->getPourcentagesCepages() as $cep => $pc) {
            $lotBis->details .= $cep.' ('.$pc.'%) ';
        }
        $lots[] = $lot;
        $lots[] = $lotBis;
      } else {
        $lot->produit_hash = ($this->isDeclassement())? null : $this->changement_produit;
        $lot->produit_libelle = ($this->isDeclassement())? 'Déclassement' : $this->changement_produit_libelle;
        $lot->statut = ($this->isDeclassement())? Lot::STATUT_DECLASSE : Lot::STATUT_CONFORME;
        if (count($this->changement_cepages->toArray(true, false))) {
          $lot->details = '';
          foreach($this->getPourcentagesCepages() as $cep => $pc) {
              $lot->details .= $cep.' ('.$pc.'%) ';
          }
        }
        $lots[] = $lot;
      }
      foreach($lots as $l) {
        $this->lots->add(null, $l);
      }
    }

    private function generateMouvementLotsFromLot($lot, $key) {
        $mvt = new stdClass();
        $mvt->date = $lot->date;
  			$mvt->statut = $lot->statut;
  			$mvt->numero_dossier = $lot->numero_dossier;
  			$mvt->numero_archive = $lot->numero_archive;
  			$mvt->numero_cuve = $lot->numero_cuve;
        $mvt->millesime = $lot->millesime;
        $mvt->volume = $lot->volume;
        $mvt->elevage = $lot->elevage;
        $mvt->produit_hash = $lot->produit_hash;
        $mvt->produit_libelle = $lot->produit_libelle;
        $mvt->produit_couleur = ($lot->produit_hash)? $lot->getCouleurLibelle() : null;
        $mvt->region = '';
        $mvt->version = $this->getVersion();
        $mvt->origine_hash = $lot->getHash();
        $mvt->origine_type = 'chgtdenom';
        $mvt->origine_document_id = $this->_id;
        $mvt->id_document = $this->_id;
        $mvt->origine_mouvement = '/mouvements_lots/'.$lot->declarant_identifiant.'/'.$key;
  			$mvt->declarant_identifiant = $lot->declarant_identifiant;
  			$mvt->declarant_nom = $lot->declarant_nom;
        $mvt->destination_type = $lot->destination_type;
        $mvt->destination_date = $lot->destination_date;
        $mvt->details = $lot->details;
        $mvt->campagne = $this->campagne;
  			$mvt->specificite = $lot->specificite;
        return $mvt;
    }

    private function generateAndAddMouvementLotsFromLot($lot, $key) {
        $mvt = $this->generateMouvementLotsFromLot($lot, $key);
        if(!$this->add('mouvements_lots')->exist($this->identifiant)) {
            $this->add('mouvements_lots')->add($this->identifiant);
        }
        return $this->add('mouvements_lots')->get($this->identifiant)->add($key, $mvt);
    }

    public function generateMouvementsLots($prelevable = 1) {
        foreach($this->lots as $k => $lot) {
          if ($prelevable && $lot->produit_hash && $lot->produit_hash == $this->changement_produit) {
            $lot->statut = Lot::STATUT_PRELEVABLE;
          }
          $key = $lot->getUnicityKey();
          $mvt = $this->generateAndAddMouvementLotsFromLot($lot, $key);
        }
        $this->updateMouvementOrigineDocument();
    }

    private function updateMouvementOrigineDocument() {
      if ($doc = $this->getOrigineDocumentMvtLot()) {
          $doc->statut = ($this->isChgtTotal() && $this->isDeclassement())? Lot::STATUT_DECLASSE : Lot::STATUT_CHANGE;
          $doc->getDocument()->get($doc->origine_hash)->statut = $doc->statut;
          $doc->getDocument()->save();
      }
    }

    public function getOrigineDocumentMvtLot() {
      $mvtLot = $this->getMvtLot();
      if ($doc = acCouchdbManager::getClient()->find($mvtLot->origine_document_id)) {
        if ($doc->exist($mvtLot->origine_mouvement)) {
          return $doc->get($mvtLot->origine_mouvement);
        }
      }
      return null;
    }

    public function getCepagesToStr(){
      $cepages = $this->cepages;
      $str ='';
      $k=0;
      $total = 0.0;
      $tabCepages=array();
      foreach ($cepages as $c => $volume){
        $total+=$volume;
      }
      foreach ($cepages as $c => $volume){
        $p = ($total)? round(($volume/$total)*100) : 0.0;
        $tabCepages[$c]=$p;
      }
      arsort($tabCepages);
      foreach ($tabCepages as $c => $p) {
        $k++;
        $str.=" ".$c." (".$p.'%)';
        $str.= ($k < count($cepages))? ',' : '';
      }
      return $str;
    }

  	public function getVersion() {
  			return null;
  	}

    public function addCepage($cepage, $repartition) {
        $this->changement_cepages->add($cepage, $repartition);
    }

    public function getCepagesLibelle() {
        $libelle = null;
        foreach($this->changement_cepages as $cepage => $repartition) {
            if($libelle) {
                $libelle .= ", ";
            }
            $libelle .= $cepage . " (".$repartition."%)";
        }
        return $libelle;
    }

    /**** FIN DES MOUVEMENTS ****/

    /**** PIECES ****/
    public function getAllPieces() {
      $mvtLot = $this->getMvtLot();
      $libelle = ($this->isDeclassement())? 'Déclassement' : 'Changement de dénomination';
      $libelle .= ($this->isChgtTotal())? '' : ' partiel';
      $libelle .= ' du logement n°'.$mvtLot->numero;
      $libelle .= ($this->isPapier())? ' (Papier)' : ' (Télédéclaration)';
    	return (!$this->getValidation())? array() : array(array(
    		'identifiant' => $this->getIdentifiant(),
    		'date_depot' => $this->validation,
    		'libelle' => $libelle,
    		'mime' => Piece::MIME_PDF,
    		'visibilite' => 1,
    		'source' => null
    	));
    }

    public function generatePieces() {
    	return $this->piece_document->generatePieces();
    }

    public function generateUrlPiece($source = null) {
    	return null;
    }

    public static function getUrlVisualisationPiece($id, $admin = false) {
    	return sfContext::getInstance()->getRouting()->generate('chgtdenom_visualisation', array('id' => $id));
    }

    public static function getUrlGenerationCsvPiece($id, $admin = false) {
    	return null;
    }

    public static function isVisualisationMasterUrl($admin = false) {
    	return true;
    }

    public static function isPieceEditable($admin = false) {
    	return false;
    }
    /**** FIN DES PIECES ****/

    /**** MOUVEMENTS ****/

    public function getTemplateFacture() {
        if($templateName = FactureConfiguration::getInstance()->getUniqueTemplateFactureName($this->getCampagne())){
          return TemplateFactureClient::getInstance()->find($templateName);
        }
        return TemplateFactureClient::getInstance()->find("TEMPLATE-FACTURE-AOC-".$this->getCampagne());
    }

    public function getMouvementsFactures() {

        return $this->_get('mouvements');
    }

    public function getMouvementsFacturesCalcule() {
      $templateFacture = $this->getTemplateFacture();
      if(!$templateFacture) {
          return array();
      }

      $cotisations = $templateFacture->generateCotisations($this);

      $identifiantCompte = $this->getIdentifiant();

      $mouvements = array();

      $rienAFacturer = true;

      foreach($cotisations as $cotisation) {
          $mouvement = ChgtDenomMouvementFactures::freeInstance($this);
          $mouvement->fillFromCotisation($cotisation);
          $mouvement->facture = 0;
          $mouvement->facturable = 1;
          $mouvement->date = $this->getCampagne().'-12-10';
          $mouvement->date_version = $this->validation;
          $mouvement->version = $this->version;

          if(!$mouvement->quantite) {
              continue;
          }

          if($mouvement->quantite) {
              $rienAFacturer = false;
          }

          $mouvements[$mouvement->getMD5Key()] = $mouvement;
      }

      if($rienAFacturer) {
          return array($identifiantCompte => array());

      }

      return array($identifiantCompte => $mouvements);
    }

    public function getMouvementsFacturesCalculeByIdentifiant($identifiant) {

        return $this->mouvement_document->getMouvementsFacturesCalculeByIdentifiant($identifiant);
    }

    public function generateMouvementsFactures() {
        return $this->mouvement_document->generateMouvementsFactures();
    }

    public function findMouvementFactures($cle, $id = null){
      return $this->mouvement_document->findMouvementFactures($cle, $id);
    }

    public function facturerMouvements() {

        return $this->mouvement_document->facturerMouvements();
    }

    public function isFactures() {

        return $this->mouvement_document->isFactures();
    }

    public function isNonFactures() {

        return $this->mouvement_document->isNonFactures();
    }

    public function clearMouvementsFactures(){
        $this->remove('mouvements');
        $this->add('mouvements');
    }

    /**** FIN DES MOUVEMENTS ****/


    public function getVolumeFacturable($produitFilter = null)
    {
      $produitFilter = preg_replace("/^NOT /", "", $produitFilter, -1, $produitExclude);
			$produitExclude = (bool) $produitExclude;
			$regexpFilter = "#(".implode("|", explode(",", $produitFilter)).")#";
			if($produitFilter && !$produitExclude && !preg_match($regexpFilter, $this->changement_produit)) {
					return;
			}
			if($produitFilter && $produitExclude && preg_match($regexpFilter, $this->changement_produit)) {
					return;
			}

      return $this->changement_volume;
    }

}
