<?php

class importHabilitationIACsvTask extends sfBaseTask
{

  const CSV_RS = 0;
  const CSV_CVI = 1;
  const CSV_PRODUIT = 2;
  const CSV_ACTIVITES = 3;
  const CSV_STATUT = 4;
  const CSV_ADRESSE = 5;
  const CSV_COMPLEMENT = 6;
  const CSV_CP = 7;
  const CSV_VILLE = 8;

  protected $date;
  protected $convert_statut;
  protected $convert_activites;
  protected $etablissements;

    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('fichier_habilitations', sfCommandArgument::REQUIRED, "Fichier csv pour l'import"),
        ));

        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'default'),
        ));

        $this->namespace = 'import';
        $this->name = 'habilitation-ia';
        $this->briefDescription = 'Import des habilitation (via un csv)';
        $this->detailedDescription = <<<EOF
EOF;

        $this->date = '2020-08-01';

        $this->convert_statut = array();
        $this->convert_statut['Habilité'] = HabilitationClient::STATUT_HABILITE;
        $this->convert_statut["Retiré"] = HabilitationClient::STATUT_RETRAIT;
        $this->convert_statut["Suspendu"] = HabilitationClient::STATUT_SUSPENDU;

        $this->convert_activites = array();
        $this->convert_activites['Producteur de raisin'] = HabilitationClient::ACTIVITE_PRODUCTEUR;
        $this->convert_activites['Vinificateur'] = HabilitationClient::ACTIVITE_VINIFICATEUR;
        $this->convert_activites['Conditionneur'] = HabilitationClient::ACTIVITE_CONDITIONNEUR;
        $this->convert_activites['Négociant'] = HabilitationClient::ACTIVITE_NEGOCIANT;
        $this->convert_activites['Vrac export'] = HabilitationClient::ACTIVITE_VRAC;


        $this->convert_products = array();
        $this->convert_products['Alpilles'] = 'certifications/IGP/genres/TRANQ/appellations/APL';
        $this->convert_products['Ardèche'] = 'certifications/IGP/genres/TRANQ/appellations/ARD';
        $this->convert_products['Collines Rhodaniennes'] = 'certifications/IGP/genres/TRANQ/appellations/CLR';
        $this->convert_products['Comtés Rhodaniens'] = 'certifications/IGP/genres/TRANQ/appellations/CDR';
        $this->convert_products["Ardèche - Coteaux de l'Ardèche"] = 'certifications/IGP/genres/TRANQ/appellations/ARD/mentions/DEFAUT/lieux/CDA';
        $this->convert_products['Mediterranee'] = 'certifications/IGP/genres/TRANQ/appellations/MED';
        $this->convert_products['Pays des Bouches du Rhône'] = 'certifications/IGP/genres/TRANQ/appellations/D13';
        $this->convert_products['Var'] = 'certifications/IGP/genres/TRANQ/appellations/VAR';
        $this->convert_products['Mont Caume'] = 'certifications/IGP/genres/TRANQ/appellations/MCA';
        $this->convert_products['Maures'] = 'certifications/IGP/genres/TRANQ/appellations/MAU';
        $this->convert_products['Alpes Maritimes'] = 'certifications/IGP/genres/TRANQ/appellations/AMA';
        $this->convert_products['Vaucluse'] = 'certifications/IGP/genres/TRANQ/appellations/VAU';
        $this->convert_products['Principaute Orange'] = 'certifications/IGP/genres/TRANQ/appellations/PDO';
        $this->convert_products['Aigues'] = 'certifications/IGP/genres/TRANQ/appellations/AIG';
        $this->convert_products['Val de Loire'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/DEFAUT';
        $this->convert_products['Loire Atlantique'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/LAT';
        $this->convert_products['Maine et Loire'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/MEL';
        $this->convert_products['Loir et Cher'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/LEC';
        $this->convert_products['Vendée'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/VEN';
        $this->convert_products['Cotes de la Charité'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/NIE';
        $this->convert_products['Indre et Loire'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/IDL';
        $this->convert_products["Coteaux du Cher et de l'Arnon"] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/CHE';
        $this->convert_products["Coteaux de Tannay"] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/NIE';
        $this->convert_products['Cher'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/CHE';
        $this->convert_products['Allier'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/ALL';
        $this->convert_products['Vienne'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/VIE';
        $this->convert_products['Nievre'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/NIE';
        $this->convert_products['Sarthe'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/SAR';
        $this->convert_products['Indre'] = 'certifications/IGP_VALDELOIRE/genres/TRANQ/appellations/VAL/mentions/DEFAUT/lieux/IND';
    }

    protected function execute($arguments = array(), $options = array())
    {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $this->etablissements = EtablissementAllView::getInstance()->getAll();

        $datas = array();
        foreach(file($arguments['fichier_habilitations']) as $line) {
            $line = str_replace("\n", "", $line);
            $data = str_getcsv($line, ';');
             if (!$data) {
               continue;
             }
             $eta = $this->identifyEtablissement($data);
             if (!$eta) {
                 echo "WARNING: établissement non trouvé ".$line." : pas d'import\n";
                 continue;
             }

             $produitKey = (isset($this->convert_products[trim($data[self::CSV_PRODUIT])]))? trim($this->convert_products[trim($data[self::CSV_PRODUIT])]) : null;

             if (!$produitKey) {
                 echo "WARNING: produit non trouvé ".$line." : pas d'import\n";
                 continue;
             }

            $habilitation = HabilitationClient::getInstance()->createOrGetDocFromIdentifiantAndDate($eta->identifiant, $this->date);
            $produit = $habilitation->addProduit($produitKey);
            if (!$produit) {
                echo "WARNING: produit $produitKey (".$data[self::CSV_PRODUIT].") non trouvé : ligne non importée\n";
                continue;
            }
            $hab_activites = $produit->add('activites');

            $statut = $this->convert_statut[trim($data[self::CSV_STATUT])];

            if (!$produitKey) {
                echo "WARNING: statut non trouvé ".$line." : pas d'import\n";
                continue;
            }

            $this->updateHabilitationStatut($hab_activites, $data, $statut, $this->date);

            $habilitation->save(true);
            //echo "SUCCESS: ".$habilitation->_id."\n";
        }
    }

    protected function identifyEtablissement($data) {
        foreach ($this->etablissements as $etab) {
            if (isset($data[self::CSV_CVI]) && trim($data[self::CSV_CVI]) && $etab->key[EtablissementAllView::KEY_CVI] == trim($data[self::CSV_CVI])) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_RS]) && trim($data[self::CSV_RS]) && KeyInflector::slugify($etab->key[EtablissementAllView::KEY_NOM]) == KeyInflector::slugify(trim($data[self::CSV_RS]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
            if (isset($data[self::CSV_RS]) && trim($data[self::CSV_RS]) && KeyInflector::slugify($etab->value[EtablissementAllView::VALUE_RAISON_SOCIALE]) == KeyInflector::slugify(trim($data[self::CSV_RS]))) {
                return EtablissementClient::getInstance()->find($etab->id);
                break;
            }
        }
        return null;
    }

    protected function updateHabilitationStatut($hab_activites,$data,$statut,$date){
        foreach (explode(",",$data[self::CSV_ACTIVITES]) as $act) {
            if ($activite = $this->convert_activites[trim($act)]) {
                $hab_activites->add($activite)->updateHabilitation($statut, null, $date);
            }
        }
    }
}
