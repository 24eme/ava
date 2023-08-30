<?php

class VIP2C
{

    const VIP2C_COLONNE_MILLESIME = 0;
    const VIP2C_COLONNE_CVI = 3;
    const VIP2C_COLONNE_PRODUIT = 6;
    const VIP2C_COLONNE_VOLUME = 7;

    static $csv_seuil;

    public static function getContratsAPIURL($cvi, $millesime)
    {
        $api_link = sfConfig::get('app_api_contrats_link');
        $secret = sfConfig::get('app_api_contrats_secret');
        if (!$api_link || !$secret) {
            return array();
        }

        if ($millesime < VIP2C::getConfigMillesimeVolumeSeuil()) {
            return array();
        }

        $epoch = (string) time();

        $md5 = md5($secret."/".$cvi."/".$millesime."/".$epoch);
        return $api_link."/".$cvi."/".$millesime."/".$epoch."/".$md5;
    }

    public static function getContratsFromAPI($cvi, $millesime)
    {
        $url = self::getContratsAPIURL($cvi, $millesime);
        if (!$url) {
            return array();
        }
        $content = file_get_contents($url);

        $result = json_decode($content,true);

        return($result);
    }

    public static function getVolumeSeuilFromCSV($cvi, $millesime){
        if(!VIP2C::hasVolumeSeuil()){
            return null;
        }
        $configFile = fopen(sfConfig::get('sf_root_dir')."/".sfConfig::get('app_api_contrats_fichier_csv'),"r");

        $volumes = array();
        while (($line = fgetcsv($configFile)) !== false) {
            if ($line[self::VIP2C_COLONNE_MILLESIME] != $millesime) {
                continue;
            }
            if (!isset($volumes[$line[self::VIP2C_COLONNE_CVI]])) {
                $volumes[$line[self::VIP2C_COLONNE_CVI]] = array();
            }
            $volumes[$line[self::VIP2C_COLONNE_CVI]][$line[self::VIP2C_COLONNE_PRODUIT]] = str_replace(",","",$line[self::VIP2C_COLONNE_VOLUME]);
        }
        fclose($configFile);

        if (!isset($volumes[$cvi])) {
            return null;
        }
        return $volumes[$cvi];
    }

    public static function getVolumeSeuilProduitFromCSV($cvi, $millesime, $hash_produit) {
        if (!self::$csv_seuil[$millesime]) {
            self::$csv_seuil[$millesime] = self::getVolumeSeuilFromCSV($cvi, $millesime);
        }
        if (!isset(self::$csv_seuil[$millesime][$hash_produit])) {
            return null;
        }
        return self::$csv_seuil[$millesime][$hash_produit];
    }

    public static function getConfigCampagneVolumeSeuil() {
        return DRevConfiguration::getInstance()->getCampagneVolumeSeuil();
    }

    public static function getConfigMillesimeVolumeSeuil() {
        return substr(self::getConfigCampagneVolumeSeuil(), 0, 4);
    }

    public static function getProduitsHashWithVolumeSeuil($cvi, $millesime) {
        $r = self::getVolumeSeuilFromCSV($cvi, $millesime);
        if (!$r) {
            return array();
        }
        return array_keys($r);
    }

    public static function hasVolumeSeuil() {
        return DRevConfiguration::getInstance()->hasVolumeSeuil();
    }

}
