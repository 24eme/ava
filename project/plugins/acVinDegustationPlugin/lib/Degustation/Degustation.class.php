<?php
/**
 * Model for Degustation
 *
 */

class Degustation extends BaseDegustation implements InterfacePieceDocument, InterfaceMouvementLotsDocument {

	protected $piece_document = null;
	protected $tri = null;
	protected $cm = null;
    protected $docToSave = array();

    public function __construct() {
        parent::__construct();
        $this->initDocuments();
				$this->cm = new CampagneManager('08-01', CampagneManager::FORMAT_PREMIERE_ANNEE);
    }

		public function getDateStdr() {
			return ($this->date && preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $this->date, $m))? $m[1] : date ('Y-m-d');
		}

		public function getMaster() {
			return $this;
		}

    public function isLotsEditable(){
      return false;
    }

		public function getCampagneByDate() {
			return $this->cm->getCampagneByDate($this->getDateStdr());
		}

    public function __clone() {
        parent::__clone();
        $this->initDocuments();
    }

    protected function initDocuments() {
        $this->piece_document = new PieceDocument($this);
    }

    public function getConfiguration() {
        return ConfigurationClient::getInstance()->getConfiguration($this->getDateStdr());
    }

    public function constructId() {
				$dateId = str_replace("-", "", preg_replace("/(.+) (.+):(.+)$/","$1$2$3",$this->date));
        $id = sprintf("%s-%s-%s", DegustationClient::TYPE_COUCHDB, $dateId, $this->getLieuNom(true));

        $this->set('_id', $id);
    }


		public function getConfigProduits() {

				return $this->getConfiguration()->declaration->getProduits();
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

    public function save() {
        $this->generateMouvementsLots();

        parent::save();

		$this->fillDocToSaveFromLots();
        $this->saveDocumentsDependants();
    }

	public function storeEtape($etape) {
	    if ($etape == $this->etape) {

	        return false;
	    }

	    $this->add('etape', $etape);

	    return true;
	}

	public function getVersion() {
			return null;
	}

	public function findLot($origineMouvement) {
		foreach($this->lots as $lot) {
			if($lot->origine_mouvement != $origineMouvement) {
				continue;
			}

			return $lot;
		}

		return null;
	}

    public function updateLotLogement($lot, $logement)
    {
        $lots = $this->getLots();
        $lots[$lot->getKey()]->numero_logement_operateur = $logement;
        // TODO: voir pour les mouvements
    }

    public function updateLot($key, $lot)
    {
        $this->lots[$key] = $lot;
    }

	public function getInfosDegustation(){
		$infos = array();
		$infos["nbLots"] = count($this->getLots());
		$infos["nbLotsLeurre"] = count($this->getLots()) - count($this->getLotsWithoutLeurre());;
		$infos["nbLotsSansLeurre"] = count($this->getLotsWithoutLeurre());
		$infos['nbLotsPrelevable'] = count(DegustationClient::getInstance()->getLotsPrelevables());
		$infos['nbLotsRestantAPrelever'] = $this->getNbLotsRestantAPreleve();
		$infos['nbLotsPreleves'] = $this->getNbLotsPreleves();
		$infos['nbLotsPrelevesSansLeurre'] = $this->getNbLotsPreleves() - $infos["nbLotsLeurre"];
		$infos["nbAdherents"] = count($this->getAdherentsPreleves());
  	$infos["nbAdherentsLotsRestantAPrelever"] = count($this->getAdherentsByLotsWithStatut(Lot::STATUT_ATTENTE_PRELEVEMENT));
		$infos["nbAdherentsPreleves"] = count($this->getAdherentsPreleves());
		$infos["degustateursConfirmes"] = $this->getDegustateursConfirmes();
		$infos["nbDegustateursConfirmes"] = count($infos["degustateursConfirmes"]);
		$infos["nbDegustateursATable"] = count($this->getDegustateursATable());
		$infos["nbDegustateursSansTable"] = $infos["nbDegustateursConfirmes"] -	$infos["nbDegustateursATable"];
		$infos["degustateurs"] = array();
		foreach (DegustationConfiguration::getInstance()->getColleges() as $college_key => $libelle) {
			$collegeVar = ucfirst(str_replace('_','',$college_key));
			$infos["degustateurs"][$libelle] = array();
			$infos["degustateurs"][$libelle]['confirmes'] = $this->getNbDegustateursStatutWithCollege(true,$college_key);
			$infos["degustateurs"][$libelle]['total'] = count($this->degustateurs->getOrAdd($college_key));
			$infos["degustateurs"][$libelle]['key'] = "nb".$collegeVar;
		}
		$tables = $this->getTablesWithFreeLots();
		$infos["nbTables"] = count($tables);
		$infos["nbFreeLots"] = count($this->getFreeLots());
		$infos["nbLotsAnonymises"] = count($this->getLotsAnonymized());
		$infos["nbLotsConformes"] = $this->getNbLotsConformes();
		$infos["nbLotsNonConformes"] = $this->getNbLotsNonConformes();
		return $infos;
	}

    /**** MOUVEMENTS LOTS ****/

    public function clearMouvementsLots(){
        $this->remove('mouvements_lots');
        $this->add('mouvements_lots');
    }

    public function addMouvementLot($mouvement) {

        return $this->mouvements_lots->add($mouvement->declarant_identifiant)->add($mouvement->getUnicityKey(), $mouvement);
    }

	public function fillDocToSaveFromLots() {
		foreach ($this->lots as $lot) {
            if ($lot->isLeurre()) {
                continue;
            }
            $lotPere = $lot->getLotPere();
            if(!$lotPere) {
                continue;
            }

            $this->docToSave[$lotPere->getDocument()->_id] = $lotPere->getDocument()->_id;
        }
	}

    public function saveDocumentsDependants() {
        foreach($this->docToSave as $docId) {
            (acCouchdbManager::getClient()->find($docId))->save();
        }
    }

	public function getLot($uniqueId) {

        foreach($this->lots as $lot) {
            if($lot->getUniqueId() != $uniqueId) {

                continue;
            }

            return $lot;
        }

        return null;
    }

    public function generateMouvementsLots()
    {
        $this->clearMouvementsLots();

        foreach ($this->lots as $lot) {
            if ($lot->isLeurre()) {
                continue;
            }
            switch($lot->statut) {
                case Lot::STATUT_CONFORME_APPEL:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_CONFORME_APPEL));

                case Lot::STATUT_RECOURS_OC:
                    $this->addMouvementLot($lot->buildMouvement( Lot::STATUT_RECOURS_OC));

                case Lot::STATUT_CONFORME:
                case Lot::STATUT_NONCONFORME:
                    $this->addMouvementLot($lot->buildMouvement($lot->statut));

                case Lot::STATUT_DEGUSTE:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_DEGUSTE));

                case Lot::STATUT_ANONYMISE:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_ANONYMISE));

                case Lot::STATUT_ATTABLE:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_ATTABLE));

                case Lot::STATUT_PRELEVE:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_PRELEVE));

                case Lot::STATUT_ATTENTE_PRELEVEMENT:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_ATTENTE_PRELEVEMENT));

                case Lot::STATUT_AFFECTE_DEST:
                    $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_AFFECTE_DEST));

                default:
                    break;
            }

            if ($lot->statut === Lot::STATUT_NONCONFORME && $lot->isAffectable()) {
                $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_AFFECTABLE));
                $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_AFFECTE_SRC));
            } elseif(in_array($lot->statut, array(Lot::STATUT_NONCONFORME, Lot::STATUT_RECOURS_OC))) {
                $this->addMouvementLot($lot->buildMouvement(Lot::STATUT_MANQUEMENT_EN_ATTENTE));
            }
        }
    }

    /**** FIN DES MOUVEMENTS LOTS ****/

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

    public static function getUrlvisualisationPiece($id, $admin = false) {
    	return null;
    }

    public static function getUrlGenerationCsvPiece($id, $admin = false) {
    	return null;
    }

    public static function isvisualisationMasterUrl($admin = false) {
    	return false;
    }

    public static function isPieceEditable($admin = false) {
    	return false;
    }

    public function setLots($lots)
    {
         $this->fillDocToSaveFromLots();

		 $this->remove('lots');
		 $this->add('lots');

        foreach($lots as $key => $lot) {
            $lot->statut = Lot::STATUT_AFFECTE_DEST;
            $lot = $this->lots->add(null, $lot);
        }
	 }

	 public function getAdherentsByLotsWithStatut($statut = null){
		 $lots = $this->getLotsWithStatut($statut);
		 $lotsByAdherents = array();
		 foreach ($lots as $lot) {
			 if(!array_key_exists($lot->getDeclarantIdentifiant(),$lotsByAdherents)){
				 	$lotsByAdherents[$lot->getDeclarantIdentifiant()] = array();
				}
				$lotsByAdherents[$lot->getDeclarantIdentifiant()][] = $lot;
		 }

 	 	return $lotsByAdherents;
	}

	public function getAdherentsPreleves(){
		$adherents = array();
		foreach ($this->getLots() as $lot) {
				if($lot->isPreleve()){
					$adherents[$lot->getDeclarantIdentifiant()] = $lot->getDeclarantIdentifiant();
				}
		}
	 return $adherents;
 }

	 public function getNbLotsWithStatut($statut = null, $including_leurre = true){
			return count($this->getLotsWithStatut($statut,$including_leurre));
	 }

	 public function getLotsWithStatut($statut = null, $including_leurre = true){
		 if(!$statut){
			 return array();
		 }
		 $lots = array();
		 foreach ($this->getLots() as $lot) {
				if(!$including_leurre && $lot->isLeurre()){
					continue;
				}
				if($lot->statut == $statut){
					$lots[] = $lot;
				}
			}
			return $lots;
	 }

    public function getLotsByOperateurs($identifiant = null)
    {
        $lots = [];
        foreach ($this->getLots() as $lot) {
            if ($lot->isLeurre()) {
                continue;
            }

            if ($identifiant && $lot->declarant_identifiant !== $identifiant) {
                continue;
            }

            if (array_key_exists($lot->declarant_identifiant, $lots) === false) {
                $lots[$lot->declarant_identifiant] = [];
            }

            $lots[$lot->declarant_identifiant][] = $lot;
        }

        return $lots;
    }

    public function getLotsConformes($identifiant = null)
    {
        $all_lots = $this->getLotsByOperateurs($identifiant);
        $conformes = [];

        foreach ($all_lots as $operateur => $lots) {
            $conformes[$operateur] = [];
            foreach ($lots as $lot) {
                if ($lot->statut === Lot::STATUT_CONFORME) {
                    $conformes[$operateur][] = $lot;
                }
            }
        }

        return $conformes;
    }

    public function getLotsNonConformes($identifiant = null)
    {
        $all_lots = $this->getLotsByOperateurs($identifiant);
        $nonconformes = [];

        foreach ($all_lots as $operateur => $lots) {
            $nonconformes[$operateur] = [];
            foreach ($lots as $lot) {
                if ($lot->statut === Lot::STATUT_NONCONFORME) {
                    $nonconformes[$operateur][] = $lot;
                }
            }
        }

        return $nonconformes;
    }

	 public function getLotsByOperateursAndConformites(){
		 $lotsByAdherents = array();
		 $conformiteArray = array(Lot::STATUT_CONFORME,Lot::STATUT_NONCONFORME);
		 foreach ($conformiteArray as $bool => $conformite) {
			 foreach ($this->getLotsConformesOrNot(!$bool) as $lot) {
				 if($lot->isLeurre()){
					 continue;
				 }
				 if(!array_key_exists($lot->getDeclarantIdentifiant(),$lotsByAdherents)){
					 $lotsByAdherents[$lot->getDeclarantIdentifiant()] = new stdClass();
					 $lotsByAdherents[$lot->getDeclarantIdentifiant()]->declarant_nom = $lot->declarant_nom;
                     $lotsByAdherents[$lot->getDeclarantIdentifiant()]->email_envoye = $lot->email_envoye;
                     if(!$lot->email_envoye){
                         $lotsByAdherents[$lot->getDeclarantIdentifiant()]->email_envoye = false;
                     }
					 $lotsByAdherents[$lot->getDeclarantIdentifiant()]->lots = array();
					}
					if(!array_key_exists($conformite,$lotsByAdherents[$lot->getDeclarantIdentifiant()]->lots)){
						$lotsByAdherents[$lot->getDeclarantIdentifiant()]->lots[$conformite] = array();
 					}
				 $lotsByAdherents[$lot->getDeclarantIdentifiant()]->lots[$conformite][$lot->getUnicityKey()] = $lot;
				 if($lotsByAdherents[$lot->getDeclarantIdentifiant()]->email_envoye === false){
                     $lotsByAdherents[$lot->getDeclarantIdentifiant()]->email_envoye = false;
                 }
			 }
		 }
		return $lotsByAdherents;
	 }

	 public function getLotsConformitesOperateur($identifiant){
		$lotsByAdherents = $this->getLotsByOperateursAndConformites();
	 return $lotsByAdherents[$identifiant];
	}

	 public function getLotByNumArchive($numero_archive){
		 foreach ($this->lots as $lot) {
			 if($lot->numero_archive == $numero_archive){
				 return $lot;
			 }
		 }
		 return null;
	 }

	 public function getNbLotsRestantAPreleve(){
		 return $this->getNbLotsWithStatut(Lot::STATUT_ATTENTE_PRELEVEMENT,false);
	 }

	 public function getLotsDegustes(){
		 return array_merge($this->getLotsWithStatut(Lot::STATUT_CONFORME,false),$this->getLotsWithStatut(Lot::STATUT_NONCONFORME,false));
	 }


	 public function getNbLotsPreleves(){
		 return count($this->getLotsPreleves());
	 }

	 public function getNbLotsConformes(){

			return count($this->getLotsConformesOrNot(true));
	 }

	 public function getNbLotsNonConformes(){

		 return count($this->getLotsConformesOrNot(false));
	 }

	 public function getLotsConformesOrNot($conforme = true){
		 $lots = array();
		 foreach ($this->getLotsDegustes() as $lot) {
			 if($conforme && $lot->exist('conformite') && $lot->conformite == Lot::CONFORMITE_CONFORME){
				 $lots[] = $lot;
			 }
			 if(!$conforme && $lot->isNonConforme()){
				 $lots[] = $lot;
			 }
		 }
		 return $lots;
	 }

    /**** FIN DES PIECES ****/


		/**** Gestion des tables de la degustation ****/

		public function getLotsPreleves() {
	   		$lots = array();
	   		foreach ($this->getLots() as $lot) {
	   			if(!$lot->leurre && in_array($lot->statut, array(Lot::STATUT_PRELEVABLE, Lot::STATUT_NONPRELEVABLE, Lot::STATUT_ATTENTE_PRELEVEMENT))) {
	   				continue;
	   			}
	   			$lots[] = $lot;
	   		}
			return $lots;
		}

		public function getLotsPrelevesCustomSort(array $tri = null) {
			$lots = $this->getLotsPreleves();
			if (!$tri) {
				$tri = array('couleur', 'appellation', 'cepage');
			}
			$this->tri = $tri;
	   		uasort($lots, array($this, "sortLotsByThisTri"));
	   		return $lots;
   	 	}

		public function getFreeLots(){
			$freeLots = array();
			foreach ($this->getLotsPreleves() as $lot) {
				if(! $lot->exist('numero_table') || !$lot->numero_table){
					$freeLots[] = $lot;
				}
			}
			return $freeLots;
		}

		public function getTablesWithFreeLots($add_default_table = false){
			$tables = array();
			$freeLots = $this->getFreeLots();
			foreach ($this->lots as $lot) {
				if($lot->exist('numero_table') && $lot->numero_table){
					if(!isset($tables[$lot->numero_table])){
						$tables[$lot->numero_table] = new stdClass();
						$tables[$lot->numero_table]->lots = array();
						$tables[$lot->numero_table]->freeLots = $freeLots;
					}
					$tables[$lot->numero_table]->lots[] = $lot;
				}
			}

			if($add_default_table && !count($tables)){
				$table = new stdClass();
				$table->lots = array();
				$table->freeLots = $freeLots;
				$tables[] = $table;
			}
			return $tables;
		}

		public function getLotsWithoutLeurre(){
			$lots = array();
			foreach ($this->lots as $lot) {
					if ($lot->leurre === true) {
							continue;
					}
					$lots[] = $lot;
			}
			return $lots;
		}

		public function getLotsByTable($numero_table){
			$lots = array();
			foreach ($this->getLots() as $lot) {
				if(intval($lot->numero_table) == $numero_table){
					$lots[] = $lot;
				}
			}
			$this->tri = ['numero_anonymat'];
			usort($lots, array($this, "sortLotsByThisTri"));
 		 	return $lots;
		}

        public function getLotsNonAnonymisable(){
            $lotsNonAnonymisable = array();
            foreach ($this->getLots() as $lot) {
            if(!$lot->isAnonymisable())
                $lotsNonAnonymisable[$lot->getHash()] = $lot;
            }

            return $lotsNonAnonymisable;
        }

        public function cleanLotsNonAnonymisable(){
			$this->fillDocToSaveFromLots();
            foreach ($this->getLotsNonAnonymisable() as $hashLot => $lot) {
                $this->remove($hashLot);
            }
			$this->generateMouvementsLots();
        }

		public function anonymize(){
            $this->cleanLotsNonAnonymisable();

			for($table = 1; true ; $table++) {
				$lots = $this->getLotsByTable($table);
				if (!count($lots)) {
					break;
				}
				$this->tri = ['couleur','appellation','cépage'];
				usort($lots, array($this, 'sortLotsByThisTri'));
				foreach ($lots as $k => $lot){
					if ($lot->numero_anonymat) {
						throw new sfException("L'anonymat a déjà été réalisé");
					}

                    $lot->anonymize($k);
				}
			}

            $this->generateMouvementsLots();
		}

		public function desanonymize(){
			for($table = 1; true ; $table++) {
				$lots = $this->getLotsByTable($table);
				if (!count($lots)) {
					break;
				}
				foreach ($lots as $k => $lot){
					if ($lot->numero_anonymat){
						$lot->numero_anonymat = null;
					}
				}
			}

            $this->generateMouvementsLots();
		}

		public function isAnonymized(){
			for($table = 1; true ; $table++) {
				$lots = $this->getLotsByTable($table);
				if (!count($lots)) {
					return false;
				}
				foreach ($lots as $k => $lot){
					if ($lot->numero_anonymat) {
					return true;
					}
				}
			}
			return false;
		}

        public function getLotsAnonymized(){
            $lotsAnon = array();
            foreach ($this->getLots() as $k => $lot){
                if (!$lot->leurre && $lot->numero_anonymat) {
                    $lotsAnon[$lot->numero_anonymat] = $lot;
                }
            }
            return $lotsAnon;
        }

		public function getLotsTableOrFreeLots($numero_table, $free = true){
			$lots = array();
			foreach ($this->getLotsPreleves() as $lot) {
				if(($lot->numero_table == $numero_table)){
					$lots[] = $lot;
					continue;
				}

				if($free && is_null($lot->numero_table))  {
					$lots[] = $lot;
					continue;
				}
			}
			return $lots;
		}

		public function getLotsTableOrFreeLotsCustomSort($numero_table, array $tri,  $free = true){
			$lots = $this->getLotsTableOrFreeLots($numero_table, $free);
			$this->tri = $tri;
			uasort($lots, array($this, 'sortLotsByThisTri'));
			return $lots;
		}

		public function hasFreeLots(){
			foreach ($this->getLotsPreleves() as $lot) {
				if(!$lot->exist("numero_table") || is_null($lot->numero_table)){
					return true;
				}
			}
			return false;
		}

		public function getSyntheseLotsTable($numero_table = null){
			$lots = $this->getLotsPreleves();
			$syntheseLots =  $this->createSynthesFromLots($lots, $numero_table);
			ksort($syntheseLots);
			return $syntheseLots;
		}
		public function getSyntheseLotsTableCustomTri($numero_table = null, array $tri){
			$lots = $this->getLotsPrelevesCustomSort($tri);
			return $this->createSynthesFromLots($lots, $numero_table, $tri);
		}
		private function createSynthesFromLots($lots, $numero_table, array $tri = null) {
			$syntheseLots = array();
			foreach ($lots as $lot) {
				if($lot->numero_table == $numero_table || is_null($numero_table) || is_null($lot->numero_table)){
					if(!array_key_exists($lot->getTriHash($tri),$syntheseLots)){
						$synthese = new stdClass();
						$synthese->lotsTable = array();
						$synthese->lotsFree = array();
						$synthese->libelle = $lot->getTriLibelle($tri);
						$synthese->details = '';
						if (!$tri || in_array('Cépage', $tri)) {
							$synthese->details = $lot->getDetails();
						}
						$synthese->millesime = $lot->getMillesime();

						$syntheseLots[$lot->getTriHash($tri)] = $synthese;
					}
					if($lot->numero_table == $numero_table || (is_null($numero_table) && $lot->numero_table)){
						$syntheseLots[$lot->getTriHash($tri)]->lotsTable[] = $lot;
					}else{
						$syntheseLots[$lot->getTriHash($tri)]->lotsFree[] = $lot;
					}
				}
			}
			return $syntheseLots;
		}

		public function getFirstNumeroTable(){
			$tables = array_keys($this->getTablesWithFreeLots());
			if(!count($tables)) { return 0; }
			return min($tables);
		}

		public function getLastNumeroTable(){
			$tables = array_keys($this->getTablesWithFreeLots());
			if(!count($tables)) { return 0; }
			return max($tables);
		}

    public function sortLotsByThisTri($a, $b){
			$a_data = '';
			$b_data = '';
			foreach($this->tri as $t) {
				$a_data .= $a->getValueForTri($t);
				$b_data .= $b->getValueForTri($t);
				if ( $this->tri == ['numero_anonymat']){
					$cmp = $a_data-$b_data;
					if ($cmp !=0) {
						return $cmp;
					}
				}
				else{
					$cmp = strcmp($a_data, $b_data);
					if ($cmp) {
					return $cmp;
					}
				}
			}
      return 0;
      }
    public function addLeurre($hash, $cepages, $numero_table)
        {
            if (! $this->exist('lots')) {
                $this->add('lots');
            }
            $leurre = $this->lots->add();
            $leurre->leurre = true;
            $leurre->numero_table = $numero_table;
            $leurre->setProduitHash($hash);
						$leurre->details = $cepages;

						$leurre->statut = Lot::STATUT_NONPRELEVABLE;

            return $leurre;
        }

		/**** Fin Gestion des tables de la degustation ****/


		/**** Gestion dégustateurs ****/

		public function getNbDegustateursStatutWithCollege($confirme = true ,$college = null){
			return count($this->getDegustateursStatutWithCollege($confirme,$college));
		}

		public function getDegustateursStatutWithCollege($confirme = true ,$college = null){
			$degustateurs = array();
			foreach ($this->getDegustateursStatutsParCollege() as $collegeDegs => $degs) {
				if($collegeDegs != $college){
					continue;
				}
				foreach ($degs as $compte_id => $confirmeDeg) {
						if($confirmeDeg == $confirme){
							$degustateurs[] = $compte_id;
						}
					}
				}
			return  $degustateurs;
		}

		public function getDegustateursStatutsParCollege(){
			$degustateursByCollege = array();
			foreach ($this->degustateurs as $college => $degs) {
				if(!array_key_exists($college,$degustateursByCollege)){
					$degustateursByCollege[$college] = array();
				}
				foreach ($degs as $compte_id => $degustateur) {
						$degustateursByCollege[$college][$compte_id] = ($degustateur->exist('confirmation') && !is_null($degustateur->confirmation) && $degustateur->confirmation);
					}
			}
			return $degustateursByCollege;
		}


		public function getDegustateursConfirmes(){
			$degustateurs = array();
			foreach ($this->degustateurs as $college => $degs) {
				foreach ($degs as $compte_id => $degustateur) {
					if($degustateur->exist('confirmation') && !is_null($degustateur->confirmation)){
						$degustateurs[$compte_id] = $degustateur;
					}
				}
			}
			return $degustateurs;
		}

		public function getDegustateursConfirmesTableOrFreeTable($numero_table = null){
			$degustateurs = array();
			foreach ($this->getDegustateursConfirmes() as $id => $degustateur) {
				if(($degustateur->exist('numero_table') && $degustateur->numero_table == $numero_table)
					|| (!$degustateur->exist('numero_table') || is_null($degustateur->numero_table))){
					$degustateurs[$id] = $degustateur;
				}
			}
			return $degustateurs;
		}

		public function getDegustateursATable(){
			$degustateurs = array();
			foreach ($this->degustateurs as $college => $degs) {
				foreach ($degs as $compte_id => $degustateur) {
					if($degustateur->exist('numero_table') && !is_null($degustateur->numero_table)){
						$degustateurs[$compte_id] = $degustateur;
					}
				}
			}
			return $degustateurs;
		}

		public function hasAllDegustateursConfirmation(){
			$confirmation = true;
			foreach ($this->getDegustateurs() as $collegeKey => $degustateursCollege) {
				foreach ($degustateursCollege as $compte_id => $degustateur) {
					if(!$degustateur->exist('confirmation')){
						$confirmation = false;
						break;
					}
				}
			}
			return $confirmation;
		}

		/**** Fin Gestion dégustateurs ****/

		/**** Gestion PDF ****/

		public function getEtiquettesFromLots($maxLotsParPlanche){
			$nbLots = 0;
			$planche = 0;
			$etiquettesPlanches = array();
			$etablissements = array();
			$produits = array();
			$lots = array();

			foreach ($this->getLots() as $key => $lot) {
				if($lot->leurre)
					continue;
				$lots[] = $lot;
			}
			$lotsSorted = $lots;

			usort($lotsSorted, function ($lot1, $lot2) {
					if (strcmp($lot1->declarant_nom, $lot2->declarant_nom) === 0) {
							return 0;
					}
					return strcmp($lot1->declarant_nom, $lot2->declarant_nom) < 0  ? -1 : 1;
			});

			foreach ($lotsSorted as $lot) {
				if($nbLots > $maxLotsParPlanche){
					$planche++;
					$nbLots = 0;
				}
				if(!array_key_exists($planche,$etiquettesPlanches)){
					$etiquettesPlanches[$planche] = array();
				}

				if(!array_key_exists($lot->declarant_identifiant,$etablissements)){
					$etablissements[$lot->declarant_identifiant] = EtablissementClient::getInstance()->findByIdentifiant($lot->declarant_identifiant);
				}

				if(!array_key_exists($lot->produit_hash,$produits)){
					$produits[$lot->produit_hash] = $lot->getConfig()->getCouleur()->getLibelle();
				}

				$infosLot = new stdClass();
				$infosLot->lot = $lot;
				$infosLot->etablissement = $etablissements[$lot->declarant_identifiant];
				$infosLot->couleur = $produits[$lot->produit_hash];
				$etiquettesPlanches[$planche][] = $infosLot;
				$nbLots++;
			}
			return $etiquettesPlanches;
		}

		public function getAllLotsTables(){
			$tables = $this->getTablesWithFreeLots();
      $allTablesLots = array();
      foreach ($tables as $key => $value) {
        foreach ($value as $lot) {
          if(!$lot)
            continue;
          $allTablesLots = array_merge($allTablesLots, $lot);
        }

      }
			return $allTablesLots;
		}

		public function getLotTableBySlice($slice){
			$allTablesLots = $this->getAllLotsTables();
			$lotsBySlice = array();
			$cpt = 0;
			$n = intval(count($allTablesLots)/$slice);
			foreach ($allTablesLots as $key => $lot) {
				if($cpt < $slice){
					$cpt++;
				}else {
					$n--;
					$cpt = 1;
				}
				$lotsBySlice[$n][] = $lot;
			}
			return $lotsBySlice;
		}

		public function getLotsByNumDossier(){
			$lots = array();
			foreach ($this->getLotsTablesByNumAnonyme() as $numTab => $lotTable) {
				foreach ($lotTable as $numAnonyme => $lot) {
					$lots[$lot->numero_dossier][$numAnonyme] = $lot;
				}
			}

			return $lots;
		}

		public function getLotsByNumDossierNumLogementOperateur(){
			$lots = array();
			foreach ($this->getLots() as  $lot) {
			  $lots[$lot->numero_dossier][$lot->numero_logement_operateur] = $lot;
			}

			return $lots;
		}

        public function getLotByNumDossierNumLogementOperateur($numero_dossier, $numero_logement_operateur){
            $allLots = $this->getLotsByNumDossierNumLogementOperateur();
            if(!isset($allLots[$numero_dossier])){
                return null;
            }
            if(!isset($allLots[$numero_dossier][$numero_logement_operateur])){
                return null;
            }
			return $allLots[$numero_dossier][$numero_logement_operateur];
		}

        public function getLotsByNumDossierNumArchive(){
            $lots = array();
            foreach ($this->getLots() as $lot) {
              $lots[$lot->numero_dossier][$lot->numero_archive] = $lot;
            }

            return $lots;
        }

        public function getLotByNumDossierNumArchive($numero_dossier, $numero_archive){
            $allLots = $this->getLotsByNumDossierNumArchive();
            if(!isset($allLots[$numero_dossier])){
                return null;
            }
            if(!isset($allLots[$numero_dossier][$numero_archive])){
                return null;
            }
            return $allLots[$numero_dossier][$numero_archive];
        }

		public function getOdg(){
			return sfConfig::get('sf_app');
		}

		public function getLotsTablesByNumAnonyme(){
			$lots = array();
			for($numTab=1; $numTab <= $this->getLastNumeroTable(); $numTab++) {
				$table = chr($numTab+64);
				foreach ($this->getLotsByTable($numTab) as $key => $lot) {
					$lots[$numTab][$lot->getNumeroAnonymat()] = $lot;
				}
			}
			return $lots;
		}

		public function getComptesDegustateurs(){
			$arrayAssocDegustCompte = array();
			foreach ($this->getDegustateursStatutsParCollege() as $college => $degs) {
				if(count($degs)){
					foreach ($degs as $id_compte => $value) {
						$compte = CompteClient::getInstance()->findByIdentifiant($id_compte);
						$arrayAssocDegustCompte[$college][$id_compte] = $compte;
					}
				}
			}
			return $arrayAssocDegustCompte;
		}

		public function getVolumeLotsConformesOrNot($conforme = true){
			$volume = 0;
			foreach ($this->getLotsDegustes() as $lot) {
				if($conforme && $lot->exist('conformite') && $lot->conformite == Lot::CONFORMITE_CONFORME){
					$volume += $lot->volume;
				}
				if(!$conforme && $lot->isNonConforme()){
					$volume += $lot->volume;
				}
			}
			return $volume;
		}

		public function getEtablissementLotsConformesOrNot($conforme = true){
			$etablissements = array();

			foreach ($this->getLotsDegustes() as $lot) {
				$etablissement = EtablissementClient::getInstance()->findByIdentifiant($lot->declarant_identifiant);
				if($conforme && $lot->exist('conformite') && $lot->conformite == Lot::CONFORMITE_CONFORME){
					$etablissements[$lot->declarant_identifiant] = $etablissement;
				}
				if(!$conforme && $lot->isNonConforme()){
					$etablissements[$lot->declarant_identifiant] = $etablissement;
				}
			}
			return $etablissements;
		}

		public function isMailEnvoyeEtablissement($identifiant){
				return boolval($this->getLotsConformitesOperateur($identifiant)->email_envoye);
		}

		public function setMailEnvoyeEtablissement($identifiant, $date){
				foreach ($this->getLotsConformitesOperateur($identifiant)->lots as $conformite => $lots) {
					foreach ($lots as $lot) {
						$lot->email_envoye = $date;
					}
				}
		}

		public function getLotsDegustesByAppelation(){
			$degust = array();
			foreach ($this->getLotsDegustes() as $key => $lot) {
				$degust[$lot->getConfig()->getAppellation()->getLibelle()][] = $lot;
			}

			return $degust;
		}

		public function addLot($mouvement, $statut) {

			$lot = $this->lots->add(null, $mouvement);
            $lot->statut = $statut;
            return $lot;
		}

		public function getNbLotByTypeForNumDossier($numDossier){
			$lots = array();
			foreach ($this->getLotsByNumDossierNumLogementOperateur()[$numDossier] as $numCuve => $lot) {
				$lots[$lot->getTypeLot()] +=1;
			}
			return $lots;
		}

		/** Mis à jour par la degustation du volume d'un lot de DRev **/
		public function modifyVolumeLot($hash_lot,$volume){

			$lot = $this->get($hash_lot);

			// Drev => modificatrice + changement dans Drev
			$lotDrevOriginal = $lot->getLotPere();
            $lotDrevOriginalToSave = clone $lotDrevOriginal;

			// $modificatrice
			$modificatrice = $lotDrevOriginal->getDocument()->generateModificative();
			$modificatrice->save();

			$modificatrice = DRevClient::getInstance()->find($modificatrice->_id);


		    $lotModificatrice = $modificatrice->get($lotDrevOriginal->getHash());
            $lotModificatrice->volume = $volume;
            $lotModificatrice->statut = Lot::STATUT_PRELEVABLE;

            $modificatrice->validate();
			$modificatrice->validateOdg();
			$modificatrice->save();

			$lot->volume = $volume;
		}
}
