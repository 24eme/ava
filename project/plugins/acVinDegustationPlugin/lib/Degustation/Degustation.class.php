<?php
/**
 * Model for Degustation
 *
 */

class Degustation extends BaseDegustation implements InterfacePieceDocument, InterfaceMouvementLotsDocument {

	protected $piece_document = null;

    public function __construct() {
        parent::__construct();
		//TODO : supprimer cette goretterie réalisée pour la démo
		$this->campagne = '2019';
        $this->initDocuments();
    }

    public function __clone() {
        parent::__clone();
        $this->initDocuments();
    }

    protected function initDocuments() {
        $this->piece_document = new PieceDocument($this);
    }

    public function getConfiguration() {

        return acCouchdbManager::getClient('Configuration')->retrieveConfiguration($this->getCampagne());
    }

    public function constructId() {
        $id = sprintf("%s-%s-%s", DegustationClient::TYPE_COUCHDB, str_replace("-", "", $this->date), $this->getLieuNom(true));

        $this->set('_id', $id);
    }

    public function getLieuNom($slugify = false) {
        return self::getNomByLieu($this->lieu, $slugify);
    }

    public static function getNomByLieu($lieu, $slugify = false) {
        if (strpos($lieu, "—") === false) {
            throw new sfException('Le lieu « '.$lieu.' » n\'est pas correctement formaté dans la configuration. Séparateur « — » non trouvé.');
        }
        $lieuExpld = explode('—', $lieu);
        return ($slugify)? KeyInflector::slugify($lieuExpld[0]) : $lieuExpld[0];
    }

    public function getEtablissementObject() {

        return EtablissementClient::getInstance()->find("ETABLISSEMENT-".$this->identifiant);
    }

	protected function doSave() {
		$this->piece_document->generatePieces();
	}

	public function storeEtape($etape) {
	    if ($etape == $this->etape) {

	        return false;
	    }

	    $this->add('etape', $etape);

	    return true;
	}

	public function validate($date = null) {
	    if(is_null($date)) {
	        $date = date('Y-m-d');
	    }
	    $this->validation = $date;
	    $this->updateMouvementsLots();
	    $this->generateMouvementsLots();
	}


	public function devalidate($reinit_version_lot = true) {
	    $this->validation = null;
	    if($this->exist('etape')) {
	        $this->etape = null;
	    }
	    $this->updateMouvementsLots(0);
	}

	public function updateMouvementsLots($preleve = 1) {
	    foreach ($this->lots as $lot) {
	        $doc = acCouchdbManager::getClient()->find($lot->id_document);
	        if ($doc instanceof InterfaceMouvementLotsDocument) {
	            if ($doc->exist($lot->origine_mouvement)) {
	               $doc->get($lot->origine_mouvement)->set('preleve', $preleve);
	               $doc->save();
	            }
	        }
	    }
	}

	public function generateMouvementsLots() {
	    // A implementer lorsque les lots devront etre redegustes
	}

	public function isValidee() {

	    return $this->validation;
	}

    /**** PIECES ****/

    public function getAllPieces() {
    	$pieces = array();
    	return $pieces;
    }

    public function generatePieces() {
    	return $this->piece_document->generatePieces();
    }

    public function generateUrlPiece($source = null) {
    	return null;
    }

    public static function getUrlVisualisationPiece($id, $admin = false) {
    	return null;
    }

    public static function getUrlGenerationCsvPiece($id, $admin = false) {
    	return null;
    }

    public static function isVisualisationMasterUrl($admin = false) {
    	return false;
    }

    public static function isPieceEditable($admin = false) {
    	return false;
    }

	public function getMvtLotsPrelevables() {
         $mvt = array();
         foreach (MouvementLotView::getInstance()->getByPrelevablePreleve($this->campagne, 1,0)->rows as $item) {
			 $mvt[Lot::generateMvtKey($item->value)] = $item->value;
		 }
		 ksort($mvt);
		 return $mvt;
	 }

	public function getLotsPrelevables() {
         $lots = array();
         foreach ($this->getMvtLotsPrelevables() as $key => $mvt) {
             $lot = MouvementLotView::generateLotByMvt($mvt);
             $lots[$key] = $lot;
         }
         return $lots;
     }

	 public function setLotsFromMvtKeys($keys, $statut){
		 $this->remove('mouvements_lots');
		 $this->remove('lots');
		 $this->add('mouvements_lots');
		 $this->add('lots');
		 $mvts = $this->getMvtLotsPrelevables();
		 foreach($keys as $key => $activated) {
			 $mvt = $mvts[$key];
			 if ($activated) {
				 $lot = MouvementLotView::generateLotByMvt($mvt);
				 $lot->statut = $statut;
				 $this->lots->add(null, $lot);
				 if (!$this->mouvements_lots->exist($mvt->declarant_identifiant)) {
					 $this->mouvements_lots->add($mvt->declarant_identifiant);
				 }
				 $mvt->prelevable = 0;
				 $mvt->id_document = $this->_id;
				 $this->mouvements_lots->{$mvt->declarant_identifiant}->add($key, $mvt);
			 }
		 }
	 }


    /**** FIN DES PIECES ****/


		/**** Gestion des tables de la degustation ****/

		public function getTablesWithFreeLots($add_default_table = false){
			$tables = array();
			$freeLots = array();
			foreach ($this->lots as $lot) {
				if($lot->exist('numero_table') && $lot->numero_table){
					if(!isset($tables[$lot->numero_table])){
						$tables[$lot->numero_table] = new stdClass();
						$tables[$lot->numero_table]->lots = array();
						$tables[$lot->numero_table]->freeLots = array();
					}
					$tables[$lot->numero_table]->lots[] = $lot;
				}else{
					$freeLots[] = $lot;
				}
			}

			foreach ($tables as $numero_table => $tableStruct) {
				$tableStruct->freeLots = $freeLots;
			}

			if($add_default_table && !count($tables)){
				$table = new stdClass();
				$table->lots = array();
				$table->freeLots = $freeLots;
				$tables[] = $table;
			}
			return $tables;
		}

		public function getLotsTableOrFreeLots($numero_table){
			$lots = array();
			foreach ($this->lots as $lot) {
				if(($lot->numero_table == $numero_table) || is_null($lot->numero_table)){
					$lots[] = $lot;
				}
			}
			return $lots;
		}

		public function getFirstNumeroTable(){
			$tables = array_keys($this->getTablesWithFreeLots());
			if(!count($tables)) { return 0; }
			return min($tables);
		}

		/**** Fin Gestion des tables de la degustation ****/

}
