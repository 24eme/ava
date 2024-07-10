<?php

class ImportParcellaireAffectationVentouxTask extends sfBaseTask
{
    const CSV_CVI = 2;
    const CSV_NOM_COMMUNE = 4;
    const CSV_LIEUDIT = 5;
    const CSV_SECTION = 7;
    const CSV_NUM_PARCELLE = 8;
    const CSV_SURFACE = 9;
    const CSV_ANNEE_PLANTATION = 10;
    const CSV_CEPAGE = 11;

    const DATE_VALIDATION = "04-15";

    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('csv', sfCommandArgument::REQUIRED, "Fichier csv"),
            new sfCommandArgument('periode', sfCommandArgument::REQUIRED, "Période")
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
        ));

        $this->namespace = 'import';
        $this->name = 'parcellaireaffectation-ventoux';
        $this->briefDescription = 'Import des affectations parcellaire';
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array()) {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $periode = $arguments['periode'];

        foreach(file($arguments['csv']) as $line) {
            $data = str_getcsv($line, ';');

            $etablissement = EtablissementClient::getInstance()->findByCvi($data[self::CSV_CVI]);
            if (!$etablissement) {
                echo "Error: Etablissement ".$data[self::CSV_CVI]." non trouvé\n";
                continue;
            }
            $parcellaireTotal = ParcellaireClient::getInstance()->getLast($etablissement->identifiant);
            if (!$parcellaireTotal) {
                $parcellaireTotal = new Parcellaire();
                echo "Parcellaire non trouvé;".$line;
            }
            $affectation = ParcellaireAffectationClient::getInstance()->findOrCreate($etablissement->identifiant, $periode);
            $affectation->parcellaire_origine = $parcellaireTotal->_id;
            $parcelle = $this->findParcelle($parcellaireTotal, $data);

            if (!$parcelle) {
                $produitHash = '/declaration/certifications/AOC/genres/TRANQ/appellations/VTX/mentions/DEFAUT/lieux/DEFAUT/couleurs';
                if(preg_match('/ B$/', $data[self::CSV_CEPAGE])) {
                    $produitHash .= '/blanc/cepages/DEFAUT';
                } else {
                    $produitHash .= '/rouge/cepages/DEFAUT';
                }
                try {
                    $parcelle = $parcellaireTotal->addParcelleWithProduit($produitHash, 'Ventoux', $data[self::CSV_CEPAGE], $data[self::CSV_ANNEE_PLANTATION], $data[self::CSV_NOM_COMMUNE], null, $data[self::CSV_SECTION], $data[self::CSV_NUM_PARCELLE], $data[self::CSV_LIEUDIT]);
                } catch (Exception $e) {
                    echo $e->getMessage().";".$line;
                    continue;
                }
                $parcelle->superficie = (float)($data[self::CSV_SURFACE]);
            }
            $affectationParcelle = $this->addParcelleFromParcellaireParcelle($affectation, $parcelle);

            $affectationParcelle->affectee = 1;
            $affectationParcelle->superficie = (float) $data[self::CSV_SURFACE];
            $affectationParcelle->date_affectation = $periode.'-'.self::DATE_VALIDATION;

            try {
                if(!$affectation->isValidee()) {
                    $affectation->validate($periode.'-'.self::DATE_VALIDATION);
                }
                $affectation->save();
            } catch(Exception $e) {
                sleep(60);
                if(!$affectation->isValidee()) {
                    $affectation->validate($periode.'-'.self::DATE_VALIDATION);
                }
                $affectation->save();
            }
        }
    }

    protected function addParcelleFromParcellaireParcelle($doc, $parcelle) {
        $produit = $parcelle->getProduit();
        $item = $doc->declaration->add('certifications/AOC/genres/TRANQ/appellations/VTX/mentions/DEFAUT/lieux/DEFAUT');
        $item->libelle = $produit->libelle;
        $subitem = $item->detail->add($parcelle->getKey());
        ParcellaireClient::CopyParcelle($subitem, $parcelle);

        return $subitem;
    }

    public function findParcelle($parcellaireTotal, $data) {
        $parcelleToFind = ParcellaireParcelle::freeInstance($parcellaireTotal);
        $parcelleToFind->lieu = strtoupper(trim($data[self::CSV_LIEUDIT]));
        $parcelleToFind->section = strtoupper(trim($data[self::CSV_SECTION]));
        $parcelleToFind->numero_parcelle = trim($data[self::CSV_NUM_PARCELLE]);
        $parcelleToFind->superficie = round(floatval(str_replace(',', '.', trim($data[self::CSV_SURFACE]))), 4);
        $parcelleToFind->cepage = trim($data[self::CSV_CEPAGE]);
        $parcelleToFind->campagne_plantation =  intval(explode("/", $data[self::CSV_ANNEE_PLANTATION])[0]);
        $parcelleFindedStrict = $parcellaireTotal->findParcelle($parcelleToFind, 1);
        $parcelleFindedLaxiste = $parcellaireTotal->findParcelle($parcelleToFind, 0.75);

        if($parcelleFindedStrict) {
            return $parcelleFindedStrict;
        }

        if ($parcelleFindedLaxiste) {
            //$erreurs[$numLigne] = ['message' => "Parcelle ambigües",  'numLigne' => $numLigne, 'ligne' => $data];
            return $parcelleFindedLaxiste;
        }

        return null;
    }
}
