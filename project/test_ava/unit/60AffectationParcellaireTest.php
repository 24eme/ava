<?php

require_once(dirname(__FILE__).'/../bootstrap/common.php');

$t = new lime_test();

$viti = EtablissementClient::getInstance()->find('ETABLISSEMENT-7523700100');
$campagne = (date('Y')-2)."";

foreach(ParcellaireClient::getInstance()->getHistory($viti->identifiant, acCouchdbClient::HYDRATE_ON_DEMAND) as $k => $v) {
    $parcellaireAffectation = ParcellaireClient::getInstance()->find($k);
    $parcellaireAffectation->delete(false);
}

$t->comment("Création d'une déclaration d'affectation parcellaire");

$parcellaireAffectation = ParcellaireClient::getInstance()->findOrCreate($viti->identifiant, $campagne);
$parcellaireAffectation->initProduitFromLastParcellaire();
$parcellaireAffectation->save();

$t->is($parcellaireAffectation->_id, "PARCELLAIRE-".$viti->identifiant."-".$campagne, "ID de l'affectation parcellaire : ".$parcellaireAffectation->_id);

$t->comment("Étape destination du raisin");

$form = new ParcellaireDestinationForm($parcellaireAffectation);

$t->pass("Fomulaire étape destinaion du raisin");

$t->comment("Étape Parcelles");

$appellation = ParcellaireClient::getInstance()->getFirstAppellation($parcellaireAffectation->getTypeParcellaire());
$appellationNode = $parcellaireAffectation->getAppellationNodeFromAppellationKey($appellation, true);
$parcelles = $appellationNode->getDetailsSortedByParcelle(false);

$form = new ParcellaireAjoutParcelleForm($parcellaireAffectation, $appellation);
$form = new ParcellaireAppellationEditForm($parcellaireAffectation, $appellation, $parcelles);

$t->pass("Fomulaires étape Parcelles");

$t->comment("Étape Acheteurs");

$form = new ParcellaireAcheteursForm($parcellaireAffectation);
$form = new ParcellaireAcheteursParcellesForm($parcellaireAffectation);

$t->pass("Fomulaires étape Acheteur");

$t->comment("Étape Validation");

$form = new ParcellaireValidationForm($parcellaireAffectation);

$t->pass("Fomulaire étape Validation");
