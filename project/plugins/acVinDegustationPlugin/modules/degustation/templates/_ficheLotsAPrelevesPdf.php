<?php use_helper('TemplatingPDF'); ?>
<style>
<?php echo style(); ?>
.bg-white{
  background-color:white;
}
th {
  background-color:white;
}

</style>
    <div>
      <table>
        <?php echo tdStart() ?>
        <tr>
          <td style="width:20%;">
          </td>
          <td style="width:30%;">
            <p>Préleveur :</p>
          </td>
          <td style="width:30%">
            <p>Date d'édition : <?php echo $date_edition;?></p>
          </td>
          <td style="width:20%;">
          </td>
        </tr>
      </table>
    </div>
    <div>
      <table>
        <?php foreach($etablissements as $numDossier => $etablissement): ?>
          <?php $nbLotTotal += count($lots[$numDossier]); ?>
        <?php endforeach; ?>
        <tr style="line-height: 25em; height:25em;">
          <td style="width:20%;"></td>
          <td style="width:80%;"><?php echo "Nombre total d'opérateurs : ".count($etablissements)." - Nombre total de lots à Prélever : ".$nbLotTotal; ?></td>
        </tr>
      </table>
      <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
        <tr style="line-height:20px;">
          <th class="topempty bg-white"style="width:20%;"><?php echo tdStart() ?><strong>Raison sociale</strong></th>
          <th class="topempty bg-white"style="width:20%;"><?php echo tdStart() ?><strong>Adresse prélèvement</strong></th>
          <th class="topempty bg-white"style="width:15%;"><?php echo tdStart() ?><strong>Tel / Fix / Port </strong></th>
          <th class="topempty bg-white"style="width:12%;"><?php echo tdStart() ?><strong>Dosssier /<br/> Nb Lots</strong></th>
          <th class="topempty bg-white"style="width:16%;"><?php echo tdStart() ?><strong>Laboratoire</strong></th>
          <th class="topempty bg-white"style="width:15%;"><?php echo tdStart() ?><strong>Date /<br/> Heure</strong></th>
        </tr>
        <?php $i=0;
    foreach($etablissements as $numDossier => $etablissement): ?>
    <?php if($i == 14 || ($i - 14) % 17 > 16): //display 14 Lots on the first page and below 17 Lots all others pages?>
      </table>
      <br pagebreak="true" />
      <p>Suite des lots<p/>
      <br/>
      <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
        <tr style="line-height:20px;">
          <th class="topempty bg-white"style="width:20%;"><?php echo tdStart() ?><strong>Raison sociale</strong></th>
          <th class="topempty bg-white"style="width:20%;"><?php echo tdStart() ?><strong>Adresse prélèvement</strong></th>
          <th class="topempty bg-white"style="width:15%;"><?php echo tdStart() ?><strong>Tel / Fix / Port </strong></th>
          <th class="topempty bg-white"style="width:12%;"><?php echo tdStart() ?><strong>Dosssier /<br/> Nb Lots</strong></th>
          <th class="topempty bg-white"style="width:16%;"><?php echo tdStart() ?><strong>Laboratoire</strong></th>
          <th class="topempty bg-white"style="width:15%;"><?php echo tdStart() ?><strong>Date /<br/> Heure</strong></th>
        </tr>
      <?php endif;?>
         <tr style="line-height:17px;">
           <td><?php echo tdStart() ?><strong><small><?php echo $etablissement->raison_sociale ?></small></strong></td>
           <td><?php echo tdStart() ?>
              <small><?php echo $etablissement->adresse ?></small>
              <br/>
              <strong><small><?php echo $etablissement->code_postal. ' '.$etablissement->commune; ?></small></strong>
           </td>
           <td><?php echo tdStart() ?>
             <small>
             <?php echo ($etablissement->telephone_bureau) ? 'Fix: '.$etablissement->telephone_bureau : '' ?><br/>
             <?php echo ($etablissement->telephone_perso) ? 'Port: '.$etablissement->telephone_perso : '' ?><br/>
            </small>
          </td>
          <td><?php echo tdStart() ?>
            <small>n°&nbsp;<?php echo $numDossier; ?></small><br/>
            <small><?php echo count($lots[$numDossier]); ?>&nbsp;lot(s)</small>
          </td>
          <td><?php echo tdStart() ?>
            <small><?php //echo $degustation->laboratoire; ?></small>
          </td>
          <td><?php echo tdStart() ?>

          </td>
         </tr>
         <?php $i++; ?>
      <?php endforeach; ?>
      </table>
    </div>
