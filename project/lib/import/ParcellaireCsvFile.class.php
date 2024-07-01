<?php

class ParcellaireCsvFile
{
    /** @var string CSV_TYPE_PARCELLAIRE Le nom du type de CSV */
    const CSV_TYPE_PARCELLAIRE = 'PARCELLAIRE';
    const CSV_FORMAT_ORIGINE = 0;
    const CSV_FORMAT_CVI = 1;
    const CSV_FORMAT_SIRET = 2;
    const CSV_FORMAT_NOM = 3;
    const CSV_FORMAT_ADRESSE = 4;
    const CSV_FORMAT_CP = 5;
    const CSV_FORMAT_COMMUNE_OP = 6;
    const CSV_FORMAT_EMAIL = 7;
    const CSV_FORMAT_IDU = 8;
    const CSV_FORMAT_COMMUNE = 9;
    const CSV_FORMAT_LIEU_DIT = 10;
    const CSV_FORMAT_SECTION = 11;
    const CSV_FORMAT_NUMERO_PARCELLE = 12;
    const CSV_FORMAT_PRODUIT = 13;
    const CSV_FORMAT_CEPAGE = 14;
    const CSV_FORMAT_SUPERFICIE = 15;
    const CSV_FORMAT_SUPERFICIE_CADASTRALE = 16;
    const CSV_FORMAT_CAMPAGNE = 17;
    const CSV_FORMAT_ECART_PIED = 18;
    const CSV_FORMAT_ECART_RANG = 19;
    const CSV_FORMAT_FAIRE_VALOIR = 20;
    const CSV_FORMAT_STATUT = 21;
    const CSV_FORMAT_DATE_MAJ = 22;

    /** @var Csv $file Le fichier CSV */
    private $file;

    /** @var ParcellaireCsvFormat $format Le format du CSV en entrée */
    private $format;

    /** @var Parcellaire $parcellaire Le parcellaire de sortie*/
    private $parcellaire;

    /** @var string $cvi Le numéro CVI */
    private $cvi = '';

    /** @var string $etablissement L'identifiant de l'établissement */
    private $etablissement = '';

    /** @var string $date_maj La date de mise à jour */
    private $date_maj = '';

    private $contextInstance = '';

    /**
     * Constructeur.
     *
     * @param Etablissement $etablissement L'établissement à mettre à jour
     * @param Csv $file Le csv
     * @param ParcellaireCsvFormat $format Le format du fichier CSV
     *
     * @throws Exception Si le CVI n'est rattaché à aucun établissement
     */
    public function __construct(Etablissement $etablissement, Csv $file, $contextInstance = null)
    {
        $this->etablissement = $etablissement->identifiant;
        $this->file = $file;
        $this->contextInstance = ($contextInstance)? $contextInstance : sfContext::getInstance();

        list(,$this->cvi) = explode('-', pathinfo($file->getFilename(), PATHINFO_FILENAME));


        if ($etablissement->cvi !== $this->cvi) {
            $m = sprintf("Les cvi de l'établissement et du nom du fichier ne correspondent pas : %s ≠ %s",
                $etablissement->cvi,
                $this->cvi
            );
            throw new Exception($m);
        }

        $this->parcellaire = ParcellaireClient::getInstance()->findOrCreate(
            $this->etablissement,
            date('Y-m-d'),
            'PRODOUANE'
        );
        
        if ($this->parcellaire->getParcelles()) {

            $this->parcellaire->remove('declaration');
            $this->parcellaire->add('declaration');
        }
    }

    /**
     * Retourne le fichier CSV
     *
     * @return Csv
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Retourne le CVI
     *
     * @return string
     */
    public function getCvi()
    {
        return $this->cvi;
    }

    /**
     * Retourne la date de mise à jour
     *
     * @return string
     */
    public function getDateMaj()
    {
        return $this->date_maj;
    }

    /**
     * Retourne l'identifiant de l'établissement
     *
     * @return string
     */
    public function getEtablissement()
    {
        return $this->etablissement;
    }

    /**
     * Retourne le parcellaire
     *
     * @return Parcellaire
     */
    public function getParcellaire()
    {
        return $this->parcellaire;
    }

    /**
     * Analyse le CSV et le transforme en parcellaire
     *
     * @throws Exception Si une parcelle n'est pas conforme
     */
    public function convert()
    {
        $configuration = ConfigurationClient::getInstance()->getCurrent();

        foreach ($this->file->getLignes() as $parcelle) {

            if (!isset($is_old_format)) {
                if ($parcelle[self::CSV_FORMAT_ORIGINE] != 'Origine' && $parcelle[self::CSV_FORMAT_ORIGINE] != 'PRODOUANE' && $parcelle[self::CSV_FORMAT_ORIGINE] != 'INAO') {
                    $is_old_format = 1;
                }else{
                    $this->parcellaire->source = $parcelle[self::CSV_FORMAT_ORIGINE];
                    $is_old_format = 0;
                }
            }

            if (!is_numeric($parcelle[self::CSV_FORMAT_CVI - $is_old_format]) && !is_numeric($parcelle[self::CSV_FORMAT_SIRET - $is_old_format]) && !is_numeric($parcelle[self::CSV_FORMAT_CP - $is_old_format]) && !is_numeric($parcelle[self::CSV_FORMAT_IDU - $is_old_format]) && !is_numeric($parcelle[self::CSV_FORMAT_SUPERFICIE - $is_old_format])) {
                continue;
            }

            if ($parcelle[self::CSV_FORMAT_PRODUIT - $is_old_format] === null) {
                $this->contextInstance->getLogger()->info("Parcelle sans produit : ".implode(',', $parcelle));
                continue;
            }

            $libelles = explode(' - ', strtoupper($parcelle[self::CSV_FORMAT_PRODUIT - $is_old_format]));
            $libelle = trim(str_replace('*', '', $libelles[0]));
            $libelle_orig = $libelle;
            $libelle = str_replace('EDELZWICKER', 'ASSEMBLAGE EDELZWICKER', $libelle);
            $libelle = str_replace(array('AGC', 'AL G C', 'ALS G C', 'ALS GD CR', 'ALS GD C'), 'AOC ALSACE GRAND CRU', $libelle);
            $libelle = str_replace('ROSé (PINOT NOIR)', 'PINOT NOIR ROSE PINOT NOIR', $libelle);
            $libelle = str_replace(array('VDB CRéM ALSACE BLANC', 'CRéMANT ALSACE BLANC', 'VDB CRéM ALSACE BL', 'CRéMANT ALSACE BL', 'CRéMANT AOC ALSACE BLANC'), 'AOC CREMANT D\'ALSACE', $libelle);
            $libelle = str_replace('ALSACE CôTE ROUFFACH', 'ALSACE COMMUNALE COTE DE ROUFFACH', $libelle);
            $libelle = str_replace([' A-BERGBIETEN', ' ALTENBERG BERGBIETEN', ' ALT BERGBIETEN'], ' ALTENBERG DE BERGBIETEN', $libelle);
            $libelle = str_replace([' A-WOLXHEIM', ' ALTENBERG WOLXHEIM', ' ALT WOLXHEIM'], ' ALTENBERG DE WOLXHEIM', $libelle);
            $libelle = str_replace([' A-BERGHEIM', ' ALTENBERG BERGHEIM', ' ALT BERGHEIM'], ' ALTENBERG DE BERGHEIM', $libelle);
            $libelle = str_replace('ALSACE ALTENBERG', 'ALSACE GRAND CRU ALTENBERG', $libelle);
            $libelle = str_replace('KIRCH BARR', 'KIRCHBERG DE BARR', $libelle);
            $libelle = str_replace(['WINECK-SCHLOSS ', 'WINECK SCHLOSS '], 'WINECK-SCHLOSSBERG ', $libelle);
            $libelle = str_replace(['RG (PN)', 'ROUGE (PN)', 'ROUGE (PINOT NOIR)'], 'ROUGE PINOT NOIR', $libelle);
            $libelle = str_replace('ALSACE LDT', 'ALSACE LIEU DIT', $libelle);
            $libelle = str_replace('ALSACE OTTROTT', 'ALSACE COMMUNALE OTTROTT', $libelle);
            $libelle = str_replace('GRAND CRU FURSTENT ', 'GRAND CRU FURSTENTUM ', $libelle);
            $libelle = str_replace([' SGN ', ' VT ', ' GN '], ' ', $libelle);
            $libelle = str_replace(['VDB ', 'VCI '], '', $libelle);
            $libelle = str_replace(' PG', ' PINOT GRIS', $libelle);
            $libelle = str_replace('ALSACE ST-HIPPOLYTE', 'ALSACE COMMUNALE SAINT HIPPOLYTE', $libelle);
            $libelle = str_replace(['VAL LOIRE', 'VDP JARDIN DE FRANCE', 'VINS DE PAYS DU JARDIN DE LA FRANCE'], 'IGP Val de Loire', $libelle);
            $libelle = str_replace('CX ', 'COTEAUX ', $libelle);
            $libelle = str_replace('COTEAUX LAYON', 'COTEAUX DU LAYON', $libelle);
            $libelle = str_replace("FAYE-D'ANJOU", 'FAYE', $libelle);
            $libelle = str_replace('LOIRELOIRE', 'LOIRE LOIRE', $libelle);
            $libelle = str_replace(' RS', ' Rosé', $libelle);
            $libelle = str_replace([' grenat', ' rouge sec'], ' Rouge', $libelle);

            switch ($libelle) {
                case "CREMANT D'ALS ROSE":
                case "CRéM ALSACE ROSé":
                    $libelle = "AOC CREMANT D'ALSACE ROSE";
                    break;
                case 'ALSACE BERGHEIM GN GEW':
                    $libelle = 'AOC ALSACE GRAND CRU';
                    break;
                case 'ALSACE CHASSELAS OU GUTEDEL':
                    $libelle = 'ALSACE BLANC CHASSELAS';
                    break;
                case 'ALSACE GEWURZTRAMINER':
                    $libelle = 'ALSACE BLANC GEWURZT';
                    break;
                case 'ALSACE BLANC':
                    $libelle = 'AOC ALSACE BLANC';
                    break;
                case 'ALSACE KLEVENER HEILIGENSTEIN':
                    $libelle = 'ALSACE COMMUNALE KLEVENER DE HEILIGENSTEIN';
                    break;
                case 'ALSACE LIEU DIT GEWURZTRAMINER':
                    $libelle = 'ALSACE LIEU DIT BLANC GEWURZT';
                    break;
                case 'ALSACE ROSé PINOT NOIR':
                    $libelle = 'ALSACE PINOT NOIR ROSE';
                    break;
                case 'ALSACE ROUGE PINOT NOIR':
                    $libelle = 'AOC ALSACE PINOT NOIR ROUGE';
                    break;
                case 'ALSACE VAL ST GRéGOIRE PB':
                case 'ALSACE VAL GRéGOIRE PB':
                    $libelle = 'ALSACE COMMUNALE VAL SAINT GREGOIRE BLANC PINOT BLANC';
                    break;
                case 'AOC ALSACE GRAND CRU ALT BERGHEIM':
                case 'AOC ALSACE GRAND CRU ALTENBERG BERGHEIM':
                    $libelle = 'ALSACE GRAND CRU ALTENBERG DE BERGHEIM';
                    break;
                case 'AOC ALSACE GRAND CRU HATSCHBOU GEW':
                    $libelle = 'AOC ALSACE GRAND CRU HATSCHBOURG GEWURZT';
                    break;
                case 'ALSACE BLIENSCHWILLER BLANC (SYL)':
                case 'ALSACE BLIENSCHWILLER BL (SYL)':
                    $libelle = 'ALSACE COMMUNALE BLIENSCHWILLER BLANC SYLVANER';
                    break;
                case 'ALSACE PINOT OU KLEVNER':
                    $libelle = 'ALSACE COMMUNALE KLEVENER DE HEILIGENSTEIN';
                    break;
                case 'ALSACE BERGHEIM GEW':
                    $libelle = 'ALSACE-COMMUNALE-BERGHEIM-BLANC-GEWURZT';
                    break;
            }
            $libelle = preg_replace('/.*GRAND CRU/', 'AOC ALSACE GRAND CRU', $libelle);
            $libelle = preg_replace('/ GEWU?$/', ' GEWURZT', $libelle);
            $libelle = preg_replace('/ RIE$/', ' RIESLING', $libelle);
            $libelle = preg_replace('/ MUS$/', ' MUSCAT', $libelle);
            $libelle = preg_replace('/ MO$/', ' MUSCAT OTTONEL', $libelle);
            $libelle = preg_replace('/ SYL$/', ' SYLVANER', $libelle);

            $produit = $configuration->identifyProductByLibelle($libelle);
            $nb_reconnaissance = 1;
            if (!$produit) {
                $cepage = strtoupper($parcelle[self::CSV_FORMAT_CEPAGE - $is_old_format]);
                $cepage = str_replace(array('CEPAGE INCONNU', 'CEPAGE NON RENSEIGNE'), '', $cepage);
                $cepage = str_replace(array('MUSCAT A PETITS GRAINS', 'MUSCATS A PETITS GRAINS'), 'MUSCAT', $cepage);
                $libelle .= " ".$cepage;
                $libelle = str_replace('GEWURZTRAMINER', 'GEWURZT', $libelle);
                switch ($libelle) {
                    case 'ALSACE BLANC SAVAGNIN ROSE':
                        $libelle = 'ALSACE SAVAGNIN ROSE';
                        break;
                }
                $libelle = preg_replace('/ ?\.?(B|RS|R|N|G)$/', '', $libelle);
                $produit = $configuration->identifyProductByLibelle($libelle);
                $nb_reconnaissance++;
            }

            if (!$produit && ParcellaireConfiguration::getInstance()->getLimitProduitsConfiguration()) {
                $this->contextInstance->getLogger()->info("ParcellaireCsvFile : produit non reconnu : ".$parcelle[self::CSV_FORMAT_PRODUIT - $is_old_format] );
                continue;
            }
            $hash = ($produit) ? $produit->getHash() : null ;

            $prefix = substr($parcelle[self::CSV_FORMAT_IDU - $is_old_format], 5, 3);

            $new_parcelle = $this->parcellaire->addParcelle(
                $parcelle[self::CSV_FORMAT_IDU - $is_old_format],
                $parcelle[self::CSV_FORMAT_CEPAGE - $is_old_format],
                $parcelle[self::CSV_FORMAT_CAMPAGNE - $is_old_format],
                $parcelle[self::CSV_FORMAT_COMMUNE - $is_old_format],
                $parcelle[self::CSV_FORMAT_LIEU_DIT - $is_old_format]
            );

            $new_parcelle->ecart_rang = (float) $parcelle[self::CSV_FORMAT_ECART_RANG - $is_old_format];
            $new_parcelle->ecart_pieds = (float) $parcelle[self::CSV_FORMAT_ECART_PIED - $is_old_format];
            $new_parcelle->superficie = (float) str_replace(',', '.', $parcelle[self::CSV_FORMAT_SUPERFICIE - $is_old_format]);
            $new_parcelle->superficie_cadastrale = (float) str_replace(',', '.', $parcelle[self::CSV_FORMAT_SUPERFICIE_CADASTRALE - $is_old_format]);
            $new_parcelle->set('mode_savoirfaire',$parcelle[self::CSV_FORMAT_FAIRE_VALOIR - $is_old_format]);

            $new_parcelle = $this->parcellaire->affecteParcelleToHashProduit($hash, $new_parcelle);

            if (! $this->check($new_parcelle)) {
                $this->contextInstance->getLogger()->info("La parcelle ".$new_parcelle->getKey()." n'est pas conforme");
            }
            $this->contextInstance->getLogger()->info("Parcelle de ".$new_parcelle->getKey()." ajouté");
        }
    }

    /**
     * Vérifie une parcelle
     *
     * @param ParcellaireParcelle $parcelle La parcelle à vérifier
     * @return bool
     */
    private function check(ParcellaireParcelle $parcelle)
    {

        return $parcelle->getSuperficie();
    }

    /**
     * Sauve le parcellaire
     */
    public function save()
    {
        $this->parcellaire->save();
    }
}
