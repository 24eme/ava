<?php
$csv = "# id société ; nom complet ; type ; id compte ; numéro interne ; civilité ; nom ; prénom ; adresse ; adresse complémentaire 1 ; adresse complémentaire 2 ; code postal ; commune ; pays ; téléphone bureau ; téléphone mobile ; téléphone perso ; fax ; email ; commentaire ; nom groupe ; fonction ; type société ; société raison sociale ; société adresse ; société adresse complémentaire 1; société adresse complémentaire 2; société code postal ; société commune ; société téléphone ; société fax ; société email; code de création \n";

$groupe = null;
if(isset($selected_typetags) && (count($selected_typetags->getRawValue()) == 1)){
  $tags = $selected_typetags->getRawValue();
  if(array_key_exists('groupes',$tags)){
    $groupe = $tags['groupes'][0];

  }
}

foreach ($results as $res) {
  $data = $res->getData();

  $societe_informations = $data['doc']['societe_informations'];
  $groupesAndFonction = CompteClient::getGroupesAndFonction($data['doc']['groupes'],$groupe);
  $id_societe = preg_replace('/SOCIETE-/', '', $data['doc']['id_societe']);
  $mot_de_passe = $data['doc']['mot_de_passe'];
  if(!$mot_de_passe){
    $mot_de_passe = compteClient::getInstance()->find("COMPTE-".$id_societe."01",acCouchdbClient::HYDRATE_JSON)->mot_de_passe;
  }

  $adresses_complementaires = explode('−',$data['doc']['adresse_complementaire']);
  $adresse_complementaire1 = $adresses_complementaires[0];
  $adresse_complementaire2 = "";
  if(count($adresses_complementaires) > 1){
    $adresse_complementaire2 = $adresses_complementaires[1];
  }

  $societe_adresses_complementaires = explode('−',$societe_informations['adresse_complementaire']);
  $societe_adresse_complementaire1 = $societe_adresses_complementaires[0];
  $societe_adresse_complementaire2 = "";
  if(count($societe_adresses_complementaires) > 1){
    $societe_adresse_complementaire2 = $societe_adresses_complementaires[1];
  }

  $csv .= '"'.$id_societe. '";';
  $csv .= '"'.sfOutputEscaper::unescape($data['doc']['nom_a_afficher']). '";';
  $csv .= '"'.CompteClient::getInstance()->createTypeFromOrigines($data['doc']['origines']).'";';
  $csv .= '" '.$data['doc']['identifiant']. '";';
  $csv .= '"'.$data['doc']['num_interne']. '";';
  $csv .= '"'.$data['doc']['civilite']. '";';
  $csv .= '"'.sfOutputEscaper::unescape($data['doc']['prenom']). '";';
  $csv .= '"'.sfOutputEscaper::unescape($data['doc']['nom']). '";';
  $csv .= '"'.sfOutputEscaper::unescape($data['doc']['adresse']). '";';
  $csv .= '"'.sfOutputEscaper::unescape($adresse_complementaire1). '";';
  $csv .= '"'.sfOutputEscaper::unescape($adresse_complementaire2). '";';
  $csv .= '"'.$data['doc']['code_postal']. '";';
  $csv .= '"'.sfOutputEscaper::unescape($data['doc']['commune']). '";';
  $csv .= '"'.$data['doc']['pays']. '";';
  $csv .= '"'.$data['doc']['telephone_bureau']. '";';
  $csv .= '"'.$data['doc']['telephone_mobile']. '";';
  $csv .= '"'.$data['doc']['telephone_perso']. '";';
  $csv .= '"'.$data['doc']['fax']. '";';
  $csv .= '"'.$data['doc']['email']. '";';
  $csv .= '"'.$data['doc']['commentaire']. '";';
  if($groupe){
    $csv .= '"'.$groupesAndFonction['nom']. '";';
    $csv .= '"'.$groupesAndFonction['fonction']. '";';
  }else{
      $csv .= '"";';
      $csv .= '"'.$data['doc']['fonction']. '";';;
  }

  $csv .= '"'.$societe_informations['type']. '";';
  $csv .= '"'.sfOutputEscaper::unescape($societe_informations['raison_sociale']). '";';
  $csv .= '"'.sfOutputEscaper::unescape($societe_informations['adresse']). '";';
  $csv .= '"'.sfOutputEscaper::unescape($societe_adresse_complementaire1). '";';
  $csv .= '"'.sfOutputEscaper::unescape($societe_adresse_complementaire2). '";';
  $csv .= '"'.$societe_informations['code_postal']. '";';
  $csv .= '"'.sfOutputEscaper::unescape($societe_informations['commune']). '";';
  $csv .= '"'.$societe_informations['telephone']. '";';
  $csv .= '"'.$societe_informations['fax']. '";';
  $csv .= '"'.$societe_informations['email']. '";';
  $csv .= '"'.(preg_match("/\{TEXT\}/", $data['doc']['mot_de_passe'])) ? str_replace("{TEXT}", "", $mot_de_passe) : null . '"';
  $csv .= "\n";
}
echo utf8_decode($csv);
