<?php use_helper('TemplatingPDF'); ?>
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
          <span><strong>Nom :</strong> <?php echo $etablissement->nom ?></span>
        </p>
        <p>
          <span><strong>Adresse :</strong> <?php echo $etablissement->adresse ?></span>
        </p>
        <p>
          <span><strong>Code postal : </strong><?php echo $etablissement->code_postal ?></span>
          <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ville : <?php echo $etablissement->commune ?></span>
        </p>
        <p>
          <span><strong>Téléphone :</strong> <?php echo $etablissement->telephone_bureau ?></span>
        </p>
    </td>
  </tr>
  <tr>
    <td style="width:90%;">
      <br/><br/>
      <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Prélèvement&nbsp;date : _ _ _ _ </span>
      <span>&nbsp;&nbsp;Heure : _ _ _ _</span>
      <span>&nbsp;&nbsp;&nbsp;&nbsp;Lieu : <?php echo $degustation->lieu; ?> </span>
      <br/>
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
    <td><br/><br/><br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nombre des lots à prélever : <?php echo count($lots) ?></td>
    <td><br/><br/><br/>Volume total : <?php echo $volumeLotTotal ?> hl</td>

  </tr>
</table>
<div>
  <p><strong>(1)</strong> Type de lot : <strong>R</strong> = Revendication, <strong>VF</strong> = Transaction vrac France, <strong>VHF</strong> = Transaction Vrac Hors France, <strong>B</strong> = Conditionnement, <strong>E</strong> = Élevage</p>
  <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
    <tr style="line-height:20px;">
      <th style="width: 8%"><?php echo tdStart() ?><small>N° Dossier ODG</small></th>
      <th style="width: 7%"><?php echo tdStart() ?><small>N° Lot ODG</small></th>
      <th style="width: 6%"><?php echo tdStart() ?><small>Couleur</small></th>
      <th style="width: 10%"><?php echo tdStart() ?><small>Cépage</small></th>
      <th style="width: 7%"><?php echo tdStart() ?><small>Millésime</small></th>
      <th style="width: 5%"><?php echo tdStart() ?><small>Volume<br/>(hl)</small></th>
      <th class="bg-white" style="width:9%;"><?php echo tdStart() ?><small>N°Lot<br/>Opérateur</small></th>
      <th style="width: 7%"><?php echo tdStart() ?><small>Passage (spécificité)</small></th>
      <th style="width: 6%"><?php echo tdStart() ?><small>Type de lot (1)</small></th>
      <th style="width: 10%"><?php echo tdStart() ?><small>Contenant<br/>Logement</small></th>
      <th style="width: 25%"><?php echo tdStart() ?><small>Obs préleveur <br/>Obs opérateurs</small></th>
    </tr>
    <?php $i = 0;  foreach($lots as $numAnonyme => $lot): ?>
     <?php if($i == 2 || $i == 12): ?>
       </table>
       <br pagebreak="true" />
       <p>Suite des lots<p/>
       <br/>
       <p><strong>(1)</strong> Type de lot : <strong>R</strong> = Revendication, <strong>VF</strong> = Transaction vrac France, <strong>VHF</strong> = Transaction Vrac Hors France, <strong>B</strong> = Conditionnement, <strong>E</strong> = Élevage</p>
       <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
         <tr style="line-height:20px;">
           <th style="width: 8%"><?php echo tdStart() ?><small>N° Dossier ODG</small></th>
           <th style="width: 7%"><?php echo tdStart() ?><small>N° Lot ODG</small></th>
           <th style="width: 6%"><?php echo tdStart() ?><small>Couleur</small></th>
           <th style="width: 10%"><?php echo tdStart() ?><small>Cépage</small></th>
           <th style="width: 7%"><?php echo tdStart() ?><small>Millésime</small></th>
           <th style="width: 5%"><?php echo tdStart() ?><small>Volume<br/>(hl)</small></th>
           <th class="bg-white" style="width:9%;"><?php echo tdStart() ?><small>N°Lot<br/>Opérateur</small></th>
           <th style="width: 7%"><?php echo tdStart() ?><small>Passage (spécificité)</small></th>
           <th style="width: 6%"><?php echo tdStart() ?><small>Type de lot (1)</small></th>
           <th style="width: 10%"><?php echo tdStart() ?><small>Contenant<br/>Logement</small></th>
           <th style="width: 25%"><?php echo tdStart() ?><small>Obs préleveur <br/>Obs opérateurs</small></th>
         </tr>
     <?php endif;?>
     <tr style="line-height:17px;">
       <td><?php echo tdStart() ?><small><?php echo $lot->numero_dossier ?></small></td>
       <td><?php echo tdStart() ?><small><?php echo $lot->numero_archive  ?></small></td>
       <td><?php echo tdStart() ?>
         <small><?php echo $lot->getConfig()->getCouleur()->getLibelle(); ?></small>
       </td>
       <td><?php echo tdStart() ?>
         <small>
         <?php echo $lot->details;?><br/>
        </small>
      </td>
      <td><?php echo tdStart() ?>
        <small><?php echo $lot->millesime; ?></small>
      </td>
      <td><?php echo tdStart() ?>
        <small><?php echo $lot->volume; ?></small>
      </td>
      <td><?php echo tdStart() ?>
        <small><?php echo $lot->numero_logement_operateur ?></small>
      </td>
      <td><?php echo tdStart() ?>
        <small><?php echo $lot->isSecondPassage() ? $lot->getTextPassage(false)." passage" : $lot->getTextPassage(false)." passage $lot->specificite"; ?></small>
      </td>
      <td><?php echo tdStart() ?>
        <small><?php echo $lot->getTypeLot(); ?><?php echo tdStart() ?></small>
      </td>
      <td><small><?php echo $lot->numero_logement_operateur ?></small></td>
      <td><?php echo tdStart() ?>
      </td>
     </tr>
     <?php $i++; ?>
   <?php endforeach; ?>
  </table>
</div>
