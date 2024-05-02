<?php

class importOperateursHabilitationsVentouxCsvTask extends sfBaseTask
{
    const CSV_NUMERO_ENREGISTREMENT = 0;
    const CSV_CODE_LEGENDE = 1;
    const CSV_INTITULE = 2;
    const CSV_NOM = 3;
    const CSV_PRENOM = 3;
    const CSV_ADRESSE_1 = 5;
    const CSV_ADRESSE_2 = 6;
    const CSV_CODE_POSTAL = 7;
    const CSV_VILLE = 8;
    const CSV_TELEPHONE = 9;
    const CSV_PORTABLE = 10;
    const CSV_EMAIL = 11;
    const CSV_DATE_SAISIE_IDENTIFICATION = 12;
    const CSV_CVI = 13;
    const CSV_SIRET = 14;
    const CSV_PRODUCTION_RAISINS = 15;
    const CSV_VINIFICATION = 16;
    const CSV_ACHAT_VENTE_VRAC = 17;
    const CSV_CONDITIONNEMENT = 18;
    const CSV_TIREUSE = 19;
    const CSV_ETAT_HABILITATION = 20;
    const CSV_DATE_HABILITATION = 21;
    const CSV_OBSERVATION = 22;

    const hash_produit = 'certifications/AOC/genres/TRANQ/appellations/VTX';

    const activites = [
        self::CSV_PRODUCTION_RAISINS => HabilitationClient::ACTIVITE_PRODUCTEUR,
        self::CSV_VINIFICATION => HabilitationClient::ACTIVITE_VINIFICATEUR,
        self::CSV_ACHAT_VENTE_VRAC => HabilitationClient::ACTIVITE_VRAC,
        self::CSV_CONDITIONNEMENT => HabilitationClient::ACTIVITE_CONDITIONNEUR,
        self::CSV_TIREUSE => HabilitationClient::ACTIVITE_VENTE_A_LA_TIREUSE,
    ];

    const status = [
        'habilité' => HabilitationClient::STATUT_HABILITE,
        'en cours' => HabilitationClient::STATUT_DEMANDE_HABILITATION
    ];

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
        $this->name = 'operateur-habilitation-ventoux';
        $this->briefDescription = 'Import des opérateurs et habilitations de ventoux (via un csv)';
        $this->detailedDescription = <<<EOF
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        // initialize the database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

        $csvfile = fopen($arguments['csv'], 'r');

        if (! $csvfile) {
            throw new sfException("Impossible d'ouvrir le fichier " . $arguments['csv']);
        }

        while(($data = fgetcsv($csvfile, 1000, ";")) !== false) {
            if (is_numeric($data[self::CSV_NUMERO_ENREGISTREMENT]) === false) {
                continue;
            }

            $etablissement = $this->importSocieteEtablissement($data);
            if ($etablissement === false) {
                continue;
            }

            $this->importHabilitation($etablissement, $data);
        }
    }

    private function importSocieteEtablissement($data)
    {
        $raison_sociale = implode(' ', array_map('trim', [$data[self::CSV_INTITULE], $data[self::CSV_NOM], $data[self::CSV_PRENOM]]));
        $newSociete = SocieteClient::getInstance()->createSociete($raison_sociale, SocieteClient::TYPE_OPERATEUR, $data[self::CSV_NUMERO_ENREGISTREMENT]);

        $societe = SocieteClient::getInstance()->find($newSociete->_id);

        if($societe) {
            return false;
        }

        $data = array_map('trim', $data);

        $societe = $newSociete;
        $societe->statut = SocieteClient::STATUT_ACTIF;
        $societe->siege->adresse = $data[self::CSV_ADRESSE_1] ?? null;
        $societe->siege->adresse_complementaire = $data[self::CSV_ADRESSE_2] ?? null;
        $societe->siege->code_postal = $data[self::CSV_CODE_POSTAL] ?? null;
        $societe->siege->commune = $data[self::CSV_VILLE] ?? null;
        $societe->telephone_bureau = Phone::format($data[self::CSV_TELEPHONE] ?? null);
        $societe->telephone_mobile = Phone::format($data[self::CSV_PORTABLE] ?? null);
        $societe->email = KeyInflector::unaccent($data[self::CSV_EMAIL] ?? null);
        $societe->siret = str_replace(" ", "", $data[self::CSV_SIRET] ?? null);

        try {
            $societe->save();
        } catch (Exception $e) {
            echo "$societe->_id save error :".$e->getMessage()."\n";
            return false;
        }

        if (strtoupper($data[self::CSV_PRODUCTION_RAISINS]) === "X") {
            $famille = EtablissementFamilles::FAMILLE_PRODUCTEUR;

            if (strtoupper($data[self::CSV_VINIFICATION]) === "X") {
                $famille = EtablissementFamilles::FAMILLE_PRODUCTEUR_VINIFICATEUR;
            }
        }

        if (strtoupper($data[self::CSV_ACHAT_VENTE_VRAC]) === "X" || strtoupper($data[self::CSV_TIREUSE]) === "X") {
            $famille = EtablissementFamilles::FAMILLE_NEGOCIANT;

            if (strtoupper($data[self::CSV_VINIFICATION]) === "X") {
                $famille = EtablissementFamilles::FAMILLE_NEGOCIANT_VINIFICATEUR;
            }
        }

        $etablissement = EtablissementClient::getInstance()->createEtablissementFromSociete($societe, $famille);
        $etablissement->nom = $raison_sociale;

        $cvi = null;
        if (isset($data[self::CSV_CVI])){
            $cvi = preg_replace('/[^A-Z0-9]+/', "", $data[self::CSV_CVI]);
            for($i = strlen($cvi) ; $i < 10 ;  $i++) {
                $cvi = $cvi."0";
            }
            if(!intval($cvi)) {
                $cvi = '';
            }
        }

        $etablissement->cvi = $cvi;
        $societe->pushAdresseTo($etablissement);
        $societe->pushContactTo($etablissement);
        $etablissement->save();

        return $etablissement;
    }

    private function importHabilitation($etablissement, $data)
    {
        $identifiant = $etablissement->_id;
        $date = $data[self::CSV_DATE_SAISIE_IDENTIFICATION];

        $habilitation = HabilitationClient::getInstance()->createOrGetDocFromIdentifiantAndDate($identifiant, $date);
        $produit = $habilitation->addProduit(self::hash_produit);
        $activites = $produit->add('activites');

        foreach ([$data[self::CSV_PRODUCTION_RAISINS], $data[self::CSV_VINIFICATION], $data[self::CSV_ACHAT_VENTE_VRAC], $data[self::CSV_CONDITIONNEMENT], $data[self::CSV_TIREUSE]] as $key => $activite) {
            if (strtoupper($activite) === "X") {

                $statut = self::status[trim(strtolower($data[self::CSV_ETAT_HABILITATION]))];
                $activites->add(self::activites[$key])
                          ->updateHabilitation($statut, null, $data[self::CSV_DATE_HABILITATION]);
            }
        }

        $habilitation->save(true);
    }
}
