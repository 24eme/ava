<?php use_helper("Date"); ?>
<?php use_helper('Lot'); ?>
<?php use_helper('TemplatingPDF'); ?>

<?php $adresse = sfConfig::get('app_degustation_courrier_adresse'); ?>
<style>
    <?php echo style(); ?>
    table {
        font-size: 12px;
    }

    th {
        font-weight: bold;
    }
</style>

<table border="1" style="text-align:center;padding:10px;">
  <tr><td>FICHE DE NON CONFORMITÉ</td></tr>
  <tr>
    <td style="width: 50%">
      <strong>Opérateur :</strong> <?php echo $etablissement->raison_sociale ?><br>
      <strong>Adresse :</strong> <?php echo $etablissement->adresse .' '. $etablissement->adresse_complementaire .'<br/>'. $etablissement->code_postal .' '.$etablissement->commune ?>
    </td>
    <td style="width: 50%">
      <strong>Tél :</strong> <?php echo $etablissement->telephone_bureau ?> - <strong>Fax :</strong> <?php echo ($etablissement->fax) ?? '-' ?><br/>
      <strong>Courriel :</strong> <?php echo $etablissement->email ?><br/>
      <strong>CVI :</strong> <?php echo $etablissement->cvi ?> - <strong>N°SIRET :</strong> <?php echo $etablissement->siret ?>
    </td>
  </tr>
  <tr><td style="width: 100%">Lot non conforme à la dégustation, <strong><?php echo $lot->getTextPassage(); ?></strong></td></tr>
  <tr><td>Commission de Dégustation réunie le : <?php echo format_date($degustation->date, "P", "fr_FR"); ?></td></tr>
</table>

<p style="text-align: center"><strong>Lot concerné par la non-conformité : <?php echo showProduitLot($lot) ?></strong></p>

<table border="1" cellpadding=0 cellspacing=0 style="text-align: center;">
  <tr>
    <th style="width: 25%">N° Dos / N° Lot ODG</th>
    <th style="width: 15%">N°Lot OP</th>
    <th style="width: 50%">Produit</th>
    <th style="width: 10%">Volume<br/>(hl)</th>
  </tr>
  <tr>
    <td><?php echo $lot->numero_dossier ?> / <?php echo $lot->numero_archive ?></td>
    <td><?php echo $lot->numero_logement_operateur ?></td>
    <td><?php echo showProduitLot($lot) ?></td>
    <td><?php echo sprintf("%.2f", $lot->volume) ?></td>
  </tr>
</table>

<br/>
<br/>

<table border="1">
  <tr>
    <td>
      <strong>Description de l'anomalie (<?php echo $lot->getTextPassage(); ?>)</strong><br/><br/>
      <strong>Gravité :</strong> <?php echo Lot::$libellesConformites[$lot->conformite] ?><br/>
      <strong>Motif :</strong> <?php echo $lot->motif ?><br/>
      <strong>Observations constatées :</strong> <?php echo $lot->observation ?>
      <br/>
    </td>
  </tr>
  <tr>
    <td>
      <strong>Action corrective proposée</strong>
      <ul>
        <li>Mise en place d'une pratique oenologique permettant la disparition du défaut constaté</li>
        <li>Déclassement du lot concerné en vin sans indication géographique (Vin de France)</li>
      </ul>
    </td>
  </tr>
</table>

<table border="1">
<tr>
  <td style="font-weight:bold;">Date d'envoi fiche<br/></td>
  <td style="font-weight:bold;">Date de Notification :<br/></td>
  <td style="font-weight:bold;">Signature du responsable de l'ODG :<br/></td>
</tr>
</table>
<p><strong>Décision de l'opérateur : à <i>remplir</i> par l'opérateur et à retourner à l'ODG.</strong></p>

<table border="1">
  <tr style="text-align: center;">
    <td>
      <br/><?php echo tdStart() ?> <?php echo echoCheck('Déclassement', false) ?><br/>
    </td>
    <td>
      <br/><?php echo tdStart() ?> <?php echo echoCheck('Nouvelle dégustation', false) ?><br/>
    </td>
  </tr>
  <tr style="height: 250px">
    <td> Date :<br/><br/><br/><br/><br/><br/></td>
    <td> Signature opérateur :<br/><br/><br/><br/><br/><br/></td>
  </tr>
</table>
