<?php

class ImportDegustationNonConformiteIATask extends ImportLotsIATask
{

    const CSV_DATE = 2;
    const CSV_DEFAUTS = 3;
    const CSV_GRAVITE = 4;
    const CSV_APPELLATION = 5;
    const CSV_COULEUR = 6;
    const CSV_MILLESIME = 7;
    const CSV_VOLUME = 8;
    const CSV_NUM_LOT_OPERATEUR = 10;
    const CSV_RAISON_SOCIALE = 11;
    const CSV_NOM = 11;
    const CSV_CVI = 12;

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
        $this->name = 'degustations-non-conformite-ia';
        $this->briefDescription = 'Import des non conformités dans les dégustations (via un csv)';
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array()) {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $this->initProduitsCepages();

        $this->etablissements = EtablissementAllView::getInstance()->getAll();

        $config = ConfigurationClient::getCurrent();
        $commissions = DegustationConfiguration::getInstance()->getCommissions();
        $degustation = null;
        $ligne=0;
        foreach(file($arguments['csv']) as $line) {
          $ligne++;
          $line = str_replace("\n", "", $line);
          $data = str_getcsv($line,';');
          if (!$data) {
            continue;
          }

          $etablissement = $this->identifyEtablissement($data);

          if (!$etablissement) {
             echo "WARNING;établissement non trouvé ".$data[self::CSV_RAISON_SOCIALE].";pas d'import;$line\n";
             continue;
          }

          $numero = trim($data[self::CSV_NUM_LOT_OPERATEUR]);
          $volume = str_replace(',','.',trim($data[self::CSV_VOLUME])) * 1;
          $date = new DateTime(preg_replace('|([0-9]+)/([0-9]+)/([0-9]+)|', '\3-\2-\1', $data[self::CSV_DATE]));
          $millesime = preg_match('/^[0-9]{4}$/', trim($data[self::CSV_MILLESIME]))? trim($data[self::CSV_MILLESIME])*1 : $campagne;
          $produitKey = $this->clearProduitKey(KeyInflector::slugify(trim($data[self::CSV_APPELLATION])." ".trim($data[self::CSV_COULEUR])));
          if (!isset($this->produits[$produitKey])) {
            echo "WARNING;produit non trouvé ".$data[self::CSV_APPELLATION].' '.$data[self::CSV_COULEUR].";pas d'import;$line\n";
            continue;
          }
          $produit = $this->produits[$produitKey];

          $mouvements = MouvementLotView::getInstance()->getByDeclarantIdentifiant($etablissement->identifiant);

          $mouvement = null;
          foreach ($mouvements->rows as $key => $mvt) {
              if(!preg_match("/^DEGUSTATION/", $mvt->id)) {
                  continue;
              }

              if($mvt->value->millesime != $millesime) {
                  unset($mouvements->rows[$key]);
                  continue;
              }
              if($mvt->value->produit_hash != $produit->getHash()) {
                  unset($mouvements->rows[$key]);
                  continue;
              }
              if($mvt->value->volume != $volume && $mvt->value->numero_cuve != $numero) {
                  continue;
              }

              $mouvement = $mvt;
              break;
          }

          if(!$mouvement) {
              echo "WARNING;Mouvement de lot non trouvé dans la dégustation;".$line."\n";
              continue;
          }

          $degustation = DegustationClient::getInstance()->find($mouvement->id);

          $lot = $degustation->findLot($mouvement->value->origine_mouvement);

          if(!$lot) {
              echo "WARNING;Lot non trouvé dans la dégustation;".$line."\n";
              continue;
          }

          if($data[self::CSV_GRAVITE] == "mineure") {
              $lot->statut = Lot::STATUT_NONCONFORME;
              $lot->conformite = Lot::CONFORMITE_NONCONFORME_MINEUR;
              $lot->motif = trim($data[self::CSV_DEFAUTS]);
          } else {
              echo "WARNING;Gravité non géré;".$line."\n";
              continue;
          }

          $degustation->generateMouvementsLots();
          $degustation->save();
        }
      }

    public function formatDate($date){
        if(!$date) {
            return null;
        }
      $jour=$date[0].$date[1];
      $mois=$date[3].$date[4];
      $annee=$date[6].$date[7].$date[8].$date[9];
      $d= $annee.'-'.$mois.'-'.$jour." 01:00";
      return $d;
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



}
