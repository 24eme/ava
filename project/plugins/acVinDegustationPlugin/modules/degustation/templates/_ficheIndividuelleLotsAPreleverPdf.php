<?php use_helper('TemplatingPDF'); ?>
<?php use_helper('Lot') ?>
<?php use_helper('Float') ?>
<?php $adresseLgt = splitLogementAdresse($adresseLogement); ?>
<style>
<?php echo style(); ?>
.bg-white{
  background-color:white;
}
th {
  background-color:white;
  font-weight: bold;
}

p, div {
 line-height: 0.5;
}

.border{
  border-style: solid;
}

</style>
<table class="table" cellspacing=0 cellpadding=0 style="border-collapse:collapse;" scope="colgroup">
  <?php echo tdStart() ?>
  <tr>
      <th border="1px" class="border" style="width:50%; text-align: center;"><strong>Opérateur</strong></th>
      <th border="1px" class="border" style="width:50%; text-align: center;"><strong>Lieu entreposage et prélèvement</strong></th>
  </tr>
  <tr>
    <td border="1px" class="border">
        <p><span>&nbsp;&nbsp;<strong>Raison sociale :</strong> <?php echo $etablissement->raison_sociale ?></span></p>
        <p>
          <span><strong>Siret :</strong> <?php echo $etablissement->siret ?></span>
          <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>N° CVI :</strong> <?php echo $etablissement->cvi ?></span>
        </p>
        <p>
          <span><strong>Adresse :</strong> <?php echo $etablissement->adresse ?></span>
        </p>
        <p>
          <span><strong>Code postal :</strong> <?php echo $etablissement->code_postal ?></span>
          <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Ville :</strong> <?php echo $etablissement->commune ?></span>
        </p>
        <p>
          <span><strong>Téléphone :</strong> <?php echo $etablissement->telephone_bureau ?></span>
          <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Portable :</strong> <?php echo $etablissement->telephone_mobile ?></span>
        </p>
        <p></p>
    </td>
    <td border="1px" class="border">
        <p>
          <span><strong>Nom :</strong> <?php echo $adresseLgt['nom'] ?></span>
        </p>
        <p>
          <span><strong>Adresse :</strong> <?php echo $adresseLgt['adresse'] ?></span>
        </p>
        <p>
          <span><strong>Code postal : </strong><?php echo $adresseLgt['code_postal'] ?></span>
          <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ville : <?php echo $adresseLgt['commune'] ?></span>
        </p>
        <p>
          <span><strong>Téléphone :</strong> <?php echo $etablissement->telephone_bureau ?></span>
      </p>
    </td>
  </tr>
  <tr>
    <br/>
    <td style="width:30%;">
      <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Prélèvement&nbsp;date : _ _ _ _ </span>
      <br/>
    </td>
    <td style="width:20%;">
      <span>&nbsp;&nbsp;Heure : _ _ _ _</span>
    </td>
    <td style="width:50%; text-align: center;">
      <span>&nbsp;&nbsp;&nbsp;&nbsp;Labo : <?php echo $etablissement->getLaboLibelle(); ?> </span>
    </td>
  </tr>
  <tr>
    <th border="1px" class="border" style="width: 50%; text-align: center;"><strong>Nom et signature préleveur</strong></th>
    <th border="1px" class="border" style="width: 50%; text-align: center;"><strong>Nom et signature personne présente</strong></th>
  </tr>
  <tr>
    <td border="1px" class="border"><br/><br/></td>
    <td border="1px" class="border"></td>
  </tr>
  <tr>
    <td><br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nombre des lots à prélever : <?php echo count($lots) ?></td>
    <td><br/><br/>Volume total : <?php echo $volumeLotTotal ?> hl</td>
  </tr>
</table>
<div>
  <?php $i = 6; $table_header = true; foreach($lots as $key => $lot): ?>
   <?php if($i % 10 == 0 ): $table_header = true; ?>
    </table>
     <br pagebreak="true" />
     <p>Suite des lots<p/>
     <br/>
     <?php endif; ?>
    <?php if ($table_header): $table_header = false; ?>
     <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
       <tr style="line-height:20px;">
         <th style="width: 10%"><?php echo tdStart() ?><small>N° Dossier/Lot</small></th>
         <th style="width: 30%"><?php echo tdStart() ?><small>Produit/Cépage/Millésime</small></th>
         <th style="width: 5%"><?php echo tdStart() ?><small>Volume<br/>(hl)</small></th>
         <th style="width:8%"><?php echo tdStart() ?><small>N°Lot<br/>Opérateur</small></th>
         <th style="width: 10%"><?php echo tdStart() ?><small>Passage (spécificité)</small></th>
         <th style="width: 15%"><?php echo tdStart() ?><small>Destination</small></th>
         <th style="width: 22%"><?php echo tdStart() ?><small>Obs préleveur <br/>Obs opérateurs</small></th>
       </tr>
   <?php endif;?>
   <tr style="line-height:17px;">
     <td><?php echo tdStart() ?><small><?php echo $lot->numero_dossier.' / '.$lot->numero_archive ?></small></td>
     <td><?php echo tdStart() ?>
       <small><?php echo showProduitCepagesLot($lot); ?></small>
     </td>
    <td><?php echo tdStart() ?>
      <small><?php echoFloat($lot->volume); ?></small>
    </td>
    <td><?php echo tdStart() ?>
      <small><?php echo $lot->numero_logement_operateur ?></small>
    </td>
    <td><?php echo tdStart() ?>
      <small><?php echo $lot->isSecondPassage() ? $lot->getTextPassage(false) : $lot->getTextPassage(false)." $lot->specificite"; ?></small>
    </td>
    <td><?php echo tdStart() ?>
      <small>
        <?php echo $lot->getDestinationShort() ?>
      </small>
    </td>
    <td><?php echo tdStart() ?>
    </td>
   </tr>
   <?php $i++; ?>
  <?php endforeach; ?>
  </table>
</div>
