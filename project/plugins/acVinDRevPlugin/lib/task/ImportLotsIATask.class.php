<?php

class ImportLotsIATask extends sfBaseTask
{
  const CSV_NUM_DOSSIER = 0;
  const CSV_NUM_LOT_ODG = 1;
  const CSV_CVI = 2;
  const CSV_RAISON_SOCIALE = 3;
  const CSV_NOM = 4;
  const CSV_ADRESSE_1 = 5;
  const CSV_ADRESSE_2 = 6;
  const CSV_CODE_POSTAL = 7;
  const CSV_VILLE = 8;
  const CSV_FAX = 9;
  const CSV_TELEPHONE = 10;
  const CSV_FAMILLE = 11;
  const CSV_NUM_LOT_OPERATEUR = 12;
  const CSV_TYPE = 13;
  const CSV_APPELLATION = 14;
  const CSV_COULEUR = 15;
  const CSV_CEPAGE_1 = 16;
  const CSV_POURCENT_CEPAGE_1 = 17;
  const CSV_CEPAGE_2 = 18;
  const CSV_POURCENT_CEPAGE_2 = 19;
  const CSV_CEPAGE_3 = 20;
  const CSV_POURCENT_CEPAGE_3 = 21;
  const CSV_MILLESIME = 22;
  const CSV_CAMPAGNE = 23;
  const CSV_VOLUME_RESIDUEL = 24;
  const CSV_VOLUME_INITIAL = 25;
  const CSV_DESTINATION = 26;
  const CSV_TRANSACTION_DATE = 27;
  const CSV_CONF = 28;
  const CSV_PRELEVE = 29;
  const CSV_STATUT = 30;
  const CSV_DATE_COMMISSION = 31;
  const CSV_DATE_VALIDATION = 32;
  const CSV_NOM_SITE = 33;
  const CSV_ADRESSE_1_SITE = 34;
  const CSV_ADRESSE_2_SITE = 35;
  const CSV_CODE_POSTAL_SITE = 36;
  const CSV_VILLE_SITE = 37;
  const CSV_EMAIL = 38;

  const TYPE_REVENDIQUE = 'R';

  const STATUT_PRELEVE = "PRELEVE";
  const STATUT_PRELEVABLE = "PRELEVE";
  const STATUT_DEGUSTE = "DEGUSTE";
  const STATUT_CONFORME = "CONFORME";
  const STATUT_NONCONFORME = "NON_CONFORME";
  const STATUT_CHANGE = "CHANGE";
  const STATUT_DECLASSE = "DECLASSE";

  protected $date;
  protected $convert_statut;
  protected $convert_activites;
  protected $etablissements;
  protected $produits;
  protected $cepages;

  public static $correspondancesCepages = array(
    "Cabernet sauvignon N" => "CAB-SAUV-N",
    "Chardonnay B" => "CHARDONN.B",
    "Cinsault N" => "CINSAUT N",
    "Clairette B" => "CLAIRET.B",
    "Mourvèdre N" => "MOURVED.N",
    "Muscat à petits grains B" => "MUS.PT.G.B",
    "Muscat à petits grains Rs" => "MUS.P.G.RS",
    "Muscat d'Hambourg N" => "MUS.HAMB.N",
    "Muscat PG B" => "MUS.PT.G.B",
    "Nielluccio N" => "NIELLUC.N",
    "Sauvignon B" => "SAUVIGN.B",
    "Savagnin Blanc B" => "SAVAGN.B",
    "Vermentino B" => "VERMENT.B"
  );
    public static $correspondancesStatuts = array(
      "Conforme" => Lot::STATUT_CONFORME,
      "Déclassé" => Lot::STATUT_DECLASSE,
      "Non Conforme" => Lot::STATUT_NONCONFORME,
      "Prélevé A" => Lot::STATUT_PRELEVE, //Prélevé Anonimisé
      "Prélevé NA" => Lot::STATUT_PRELEVE,//Prélevé Non Anonimisé
      "Prévu" => Lot::STATUT_ATTENTE_PRELEVEMENT,
      "Revendiqué C" => Lot::STATUT_PRELEVABLE,
      "Revendiqué NC" => Lot::STATUT_NONCONFORME
    );

    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('csv', sfCommandArgument::REQUIRED, "Fichier csv pour l'import"),
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
        ));

        $this->namespace = 'import';
        $this->name = 'lots-ia';
        $this->briefDescription = 'Import des lots (via un csv)';
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array()) {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $this->initProduitsCepages();

        $this->etablissements = EtablissementAllView::getInstance()->getAll();
        $drev = null;
        $ligne = 0;
        foreach(file($arguments['csv']) as $line) {
            $ligne++;
            $line = str_replace("\n", "", $line);
            $data = str_getcsv($line, ';');
            if (!$data) {
              continue;
            }

            $type = trim($data[self::CSV_TYPE]);
            if ($type != self::TYPE_REVENDIQUE) {
                echo "SQUEEZE;lot non issu de la revendication, type : ".$type.";pas d'import;$line\n";
                continue;
            }

            $etablissement = $this->identifyEtablissement($data);
            if (!$etablissement) {
               echo "WARNING;établissement non trouvé ".$data[self::CSV_RAISON_SOCIALE].";pas d'import;$line\n";
               continue;
            }
            $produitKey = $this->clearProduitKey(KeyInflector::slugify(trim($data[self::CSV_APPELLATION])." ".trim($data[self::CSV_COULEUR])));
            if (!isset($this->produits[$produitKey])) {
              echo "WARNING;produit non trouvé ".$data[self::CSV_APPELLATION].' '.$data[self::CSV_COULEUR].";pas d'import;$line\n";
              continue;
            }
            $produit = $this->produits[$produitKey];
            $cepages = array();
            $volume = str_replace(',','.',trim($data[self::CSV_VOLUME_INITIAL])) * 1;
            if (trim($data[self::CSV_CEPAGE_1])) {
              $cep1 = $this->identifyCepage($data[self::CSV_CEPAGE_1]);
              if (!$cep1) {
                echo "WARNING;cepage_1 non trouvé ".$data[self::CSV_CEPAGE_1].";$line\n";
              } else {
                  $pourcentage = trim($data[self::CSV_POURCENT_CEPAGE_1]) * 1;
                  $pourcentage = ($pourcentage > 1)? round($pourcentage/100, 2) : $pourcentage;
                  $cepages[$cep1] = ($pourcentage > 0)? round($volume * $pourcentage, 2) : $volume;
              }
            }
            if (trim($data[self::CSV_CEPAGE_2])) {
              $cep2 = $this->identifyCepage($data[self::CSV_CEPAGE_2]);
              if (!$cep2) {
                echo "WARNING;cepage_2 non trouvé ".$data[self::CSV_CEPAGE_2].";$line\n";
              } else {
                  $pourcentage = trim($data[self::CSV_POURCENT_CEPAGE_2]) * 1;
                  $pourcentage = ($pourcentage > 1)? round($pourcentage/100, 2) : $pourcentage;
                  $cepages[$cep2] = ($pourcentage > 0)? round($volume * $pourcentage, 2) : $volume;
              }
            }
            if (trim($data[self::CSV_CEPAGE_3])) {
              $cep3 = $this->identifyCepage($data[self::CSV_CEPAGE_3]);
              if (!$cep3) {
                echo "WARNING;cepage_3 non trouvé ".$data[self::CSV_CEPAGE_3].";$line\n";
              } else {
                  $pourcentage = trim($data[self::CSV_POURCENT_CEPAGE_3]) * 1;
                  $pourcentage = ($pourcentage > 1)? round($pourcentage/100, 2) : $pourcentage;
                  $cepages[$cep3] = ($pourcentage > 0)? round($volume * $pourcentage, 2) : $volume;
              }
            }
            $campagne = preg_replace('/\/.*/', '', trim($data[self::CSV_CAMPAGNE]));
            $millesime = preg_match('/^[0-9]{4}$/', trim($data[self::CSV_MILLESIME]))? trim($data[self::CSV_MILLESIME])*1 : $campagne;
            $numeroDossier = sprintf("%05d", trim($data[self::CSV_NUM_DOSSIER]));
            $numeroLot = sprintf("%05d", trim($data[self::CSV_NUM_LOT_ODG]));
            $numero = trim($data[self::CSV_NUM_LOT_OPERATEUR]);
            $destinationDate = (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', trim($data[self::CSV_TRANSACTION_DATE]), $m))? $m[3].'-'.$m[2].'-'.$m[1] : null;
            $date = (preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', trim($data[self::CSV_DATE_VALIDATION]), $m))? $m[3].'-'.$m[2].'-'.$m[1] : null;
            if ($date) {
                  $dt = new DateTime($date);
                  $date = $dt->modify('+1 minute')->format('c');
            }
            $prelevable = (strtolower(trim($data[self::CSV_PRELEVE])) == 'oui');
            $statut = null;
            if(isset($data[self::CSV_STATUT])){
              $statut = trim($data[self::CSV_STATUT]);
           }

           if (!isset(self::$correspondancesStatuts[$statut])) {
              echo "WARNING;statut inconnu ".$statut.";pas d'import;$line\n";
              continue;
           }

           $statut = self::$correspondancesStatuts[$statut];

            $newDrev = DRevClient::getInstance()->createDoc($etablissement->identifiant, $campagne, false, false);
            $newDrev->constructId();
            $newDrev->storeDeclarant();
            $newDrev->validation = $date;
            $newDrev->validation_odg = $date;
            $newDrev->numero_archive = $numeroDossier;

            if(!$drev || $newDrev->_id != $drev->_id) {
              $drev = DRevClient::getInstance()->findMasterByIdentifiantAndCampagne($etablissement->identifiant, $campagne);
              if($drev) { $drev->delete(); $drev = null; }
            }

            if(!$drev) {
                $drev = $newDrev;
            }

            $lot = $drev->addLot();

            $lot->produit_hash = $produit->getHash();
            $lot->produit_libelle = $produit->getLibelleFormat();
            $lot->cepages = $cepages;
            $lot->id_document = $drev->_id;
            $lot->millesime = $millesime;
            $lot->numero_dossier = $numeroDossier;
            $lot->numero_archive = $numeroLot;
            $lot->numero_cuve = $numero;
            $lot->volume = $volume;
            $lot->destination_type = null;
            $lot->elevage = false;
            if(preg_match('/VF/', $data[self::CSV_DESTINATION])) {
                $lot->destination_type .= DRevClient::LOT_DESTINATION_VRAC_FRANCE."_";
            }
            if(preg_match('/VHF/', $data[self::CSV_DESTINATION])) {
                $lot->destination_type .= DRevClient::LOT_DESTINATION_VRAC_EXPORT."_";
            }
            if(preg_match('/B/', $data[self::CSV_DESTINATION])) {
                $lot->destination_type .= DRevClient::LOT_DESTINATION_CONDITIONNEMENT."_";
            }
            if($lot->destination_type) {
                $lot->destination_type = preg_replace('/_$/', "", $lot->destination_type);
            }
            if(preg_match('/E/', $data[self::CSV_DESTINATION])) {
                $lot->elevage = true;
            }
            $lot->destination_date = $destinationDate;
            $lot->date = $date;
            $lot->statut = Lot::STATUT_NONPRELEVABLE;
            if ($statut == self::STATUT_NONCONFORME) {
              $lot->statut = self::STATUT_PRELEVABLE;
              $lot->specificite = "2ème passage $lot->specificite";
            }
            if($statut == Lot::STATUT_PRELEVABLE && $prelevable) {
                $lot->statut = Lot::STATUT_PRELEVABLE;
            }
            if($lot->elevage) {
                $lot->statut = Lot::STATUT_ELEVAGE;
            }

            $deleted = array();
            foreach($drev->lots as $k => $l) {
              if ($lot->getUnicityKey() == $l->getUnicityKey() && $lot->getKey() != $k) {
                $deleted[] = $l;
              }
            }
            foreach($deleted as $d) {
              $d->delete();
            }

            $lots = array_values($drev->lots->toArray(true, false));
            $drev->remove('lots');
            $drev->add('lots', $lots);

            $drev->generateAndAddMouvementLotsFromLot($lot, $lot->getUnicityKey());
            try {
            $drev->save();
        } catch(Exception $e) {
            echo "ERROR;".$e->getMessage().";".$line."\n";
        }
            echo "SUCCESS;Lot importé;".$drev->_id.";\n";
        }
    }

    protected function clearProduitKey($key) {
      $key = str_replace('PAYS-DES-', '', $key);
      $key = str_replace('VAR-VAR-', 'VAR-', $key);
      return $key;
    }

    protected function identifyCepage($key) {
      if (isset($this->cepages[KeyInflector::slugify(trim($key))])) {
        return $this->cepages[KeyInflector::slugify(trim($key))];
      } else {
        $correspondances = self::$correspondancesCepages;
        return (isset($correspondances[trim($key)]))? $correspondances[trim($key)] : null;
      }
    }

    protected function identifyEtablissement($data) {
        foreach ($this->etablissements as $etab) {
            if (isset($data[self::CSV_CVI]) && trim($data[self::CSV_CVI]) && $etab->key[EtablissementAllView::KEY_CVI] == trim($data[self::CSV_CVI])) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_RAISON_SOCIALE]) && trim($data[self::CSV_RAISON_SOCIALE]) && KeyInflector::slugify($etab->key[EtablissementAllView::KEY_NOM]) == KeyInflector::slugify(trim($data[self::CSV_RAISON_SOCIALE]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_RAISON_SOCIALE]) && trim($data[self::CSV_RAISON_SOCIALE]) && KeyInflector::slugify($etab->value[EtablissementAllView::VALUE_RAISON_SOCIALE]) == KeyInflector::slugify(trim($data[self::CSV_RAISON_SOCIALE]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_NOM]) && trim($data[self::CSV_NOM]) && KeyInflector::slugify($etab->key[EtablissementAllView::KEY_NOM]) == KeyInflector::slugify(trim($data[self::CSV_NOM]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_NOM]) && trim($data[self::CSV_NOM]) && KeyInflector::slugify($etab->value[EtablissementAllView::VALUE_RAISON_SOCIALE]) == KeyInflector::slugify(trim($data[self::CSV_NOM]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
        }
        return null;
    }

    public function initProduitsCepages() {
      $this->produits = array();
      $this->cepages = array();
      $produits = ConfigurationClient::getInstance()->getConfiguration()->declaration->getProduits();
      foreach ($produits as $key => $produit) {
        $this->produits[KeyInflector::slugify($produit->getLibelleFormat())] = $produit;
        foreach($produit->getCepagesAutorises() as $ca) {
          $this->cepages[KeyInflector::slugify($ca)] = $ca;
        }
      }
    }
}
