<?php

class DouaneCsvFile
{
  const CSV_TYPE = 0;
  const CSV_CAMPAGNE = 1;
  const CSV_RECOLTANT_ID = 2;
  const CSV_RECOLTANT_CVI = 3;
  const CSV_RECOLTANT_LIBELLE = 4;
  const CSV_RECOLTANT_CODE_COMMUNE = 5;
  const CSV_RECOLTANT_COMMUNE = 6;
  const CSV_BAILLEUR_NOM = 7;
  const CSV_BAILLEUR_PPM = 8;
  const CSV_PRODUIT_CERTIFICATION = 9;
  const CSV_PRODUIT_GENRE = 10;
  const CSV_PRODUIT_APPELLATION = 11;
  const CSV_PRODUIT_MENTION = 12;
  const CSV_PRODUIT_LIEU = 13;
  const CSV_PRODUIT_COULEUR = 14;
  const CSV_PRODUIT_CEPAGE = 15;
  const CSV_PRODUIT_INAO = 16;
  const CSV_PRODUIT_LIBELLE = 17;
  const CSV_PRODUIT_COMPLEMENT = 18;
  const CSV_LIGNE_CODE = 19;
  const CSV_LIGNE_LIBELLE = 20;
  const CSV_VALEUR = 21;
  const CSV_TIERS_CVI = 22;
  const CSV_TIERS_LIBELLE = 23;
  const CSV_TIERS_CODE_COMMUNE = 24;
  const CSV_TIERS_COMMUNE = 25;
  const CSV_COLONNE_ID = 26;

  const CSV_ENTETES = '#Type;Campagne;Identifiant;CVI;Raison Sociale;Code Commune;Commune;Bailleur Nom;Bailleur PPM;Certification;Genre;Appellation;Mention;Lieu;Couleur;Cepage;INAO;Produit;Complement;Code;Categorie;Valeur;CVI Tiers;Valeur Motif / Raison Sociale Tiers;Code Commune Tiers;Commune Tiers;Id Colonne'."\n";

  protected $file = null;
  protected $separator = null;
  protected $csvdata = null;
  protected $ignore = null;

  public static function getCategories() {
      return array(
          '04' => "4. Superficie de récolte",
          '04b' => "4b. Superficie de récolte",
          '05' => "5. Récolte totale",
          '06' => "6. Récolte vendue sous forme de raisins. Volume de vins obtenu",
          '07' => "7. Récolte vendue sous forme de moûts. Volume de moût obtenu",
          '08' => "8. Récolte apportée en cave coopérative par l'adhérent. Volume obtenu",
          '09' => "9. Récolte en cave particulière. Volume obtenu",
          '10' => "10. Volume en vinification",
          '11' => "11. Volume en concentration",
          '12' => "12. Volume autre destination",
          '13' => "13. Volume de MC ou de MCR",
          '14' => "14. Volume de vin sans AO/IGP avec ou sans cépage",
          '15' => "15. Vol. de vin avec AO/IGP avec/sans cépage dans la limite du rdt autorisé",
          '16' => "16. Vol. vin dépassement du rdt autorisé en AOP à livrer aux usages industriels",
          '17' => "17. Vol. d'eau éliminée en cas d'enrichissement par concentration partielle",
          '18' => "18. Volume Substituable Individuel (VSI)",
          '19' => "19. Volume complémentaire individuel (VCI)",
          '22' => "22. Motif de non récolte",
      );
  }

  public function getFileName() {
    return $this->file;
  }

  public function __construct($file, $ignore_first_if_comment = 1) {
    $this->ignore = $ignore_first_if_comment;
    if (!file_exists($file) && !preg_match('/^http/', $file))
      throw new Exception("Cannont access $file");

    $this->file = $file;
    $handle = fopen($this->file, 'r');
    if (!$handle)
      throw new Exception('invalid_file');
    $buffer = fread($handle, 500);
    fclose($handle);
    $buffer = preg_replace('/$[^\n]*\n/', '', $buffer);
    if (!$buffer) {
      throw new Exception('invalid_file');
    }
    if (!preg_match('/("?)[0-9a-zA-Z]{6}("?)([,;\t])/', $buffer, $match)) {
      throw new Exception('invalid_csv_file');
    }
    $this->separator = $match[3];
  }

  protected static function clean($array) {
    for($i = 0 ; $i < count($array) ; $i++) {
      $array[$i] = preg_replace('/^ +/', '', preg_replace('/ +$/', '', $array[$i]));
    }
    return $array;
  }

  public function getCsv() {
    if ($this->csvdata)
      return $this->csvdata;

    $handler = fopen($this->file, 'r');
    if (!$handler)
      throw new Exception('Cannot open csv file anymore');
    $this->csvdata = array();
    while (($data = fgetcsv($handler, 0, $this->separator)) !== FALSE) {
      $this->csvdata[] = self::clean($data);
    }
    fclose($handler);
    if ($this->ignore && !preg_match('/^(DR|SV11|SV12)$/', $this->csvdata[0][0]))
      array_shift($this->csvdata);
    return $this->csvdata;
  }

  public static function getNewInstanceFromType($type, $file)  {
      switch ($type) {
          case 'DR':

              return new DRCsvFile($file);
          case 'SV11':

              return new SV11CsvFile($file);
          case 'SV12':

              return new SV12CsvFile($file);
      }

      return null;
  }
}
