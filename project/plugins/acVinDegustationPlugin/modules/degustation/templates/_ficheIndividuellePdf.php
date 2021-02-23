<?php use_helper('TemplatingPDF'); ?>
<style>
<?php echo style(); ?>

</style>
      <table>
        <tr>
          <td><?php echo tdStart() ?><br>
              <strong>Date : <?php echo substr($degustation->date,0,10); ?></strong><br>
              <strong>Heure : <?php echo substr($degustation->date,11,16); ?></strong><br>
              <strong>Commission: <?php echo $lots[0]->getNumeroTableStr(); ?></strong><br>
          </td>
          <td><?php echo tdStart() ?><br>
              <strong>Campagne : <?php echo $degustation->campagne ?></strong><br>
              <strong>Millesime :</strong><br>
          </td>
          <td><?php echo tdStart() ?><br>
            <strong>Lieu: <?php echo $degustation->lieu; ?></strong>
          </td>
        </tr>
      </table>
      <p> <strong> Nom : &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>
          <strong>Prénom : &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>
          <strong>Signature : &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>
          <strong>Table : &nbsp;<?php echo $lots[0]->getNumeroTableStr(); ?></strong>
      </p>
    </table>

<table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
  <tr style="line-height:20px;">
     <th class="topempty bg-white"style="width:7%; "><?php echo tdStart() ?><strong>Anon</strong></th>
     <th class="topempty bg-white" style="width:10%; "><?php echo tdStart() ?><strong>Couleur</strong></th>
     <th class="topempty bg-white"style="width:14%;"><?php echo tdStart() ?><strong>IGP</strong></th>
     <th class="topempty bg-white"style="width:14%;"><?php echo tdStart() ?><strong>Cépage</strong></th>
    <th colspan="4"style="width:16%;"><?php echo tdStart() ?><strong>NOTATION</strong></th>
     <th class="bg-white" colspan="2"style="width:10%;"><?php echo tdStart() ?><strong>Avis</strong></th>
     <th class="bg-white"  colspan="2"style="width:10%;"><?php echo tdStart() ?><strong>Typicité cépage</strong></th>
     <th class="topempty bg-white" style="width:22%;"><strong>Motifs (si non conforme)</strong></th>
  </tr>
  <tr style="line-height:13px;">
    <th class="empty bg-white"></th>
    <th class="empty bg-white"></th>
    <th class="empty bg-white"></th>
    <th class="empty bg-white"></th>
    <th style="width:4%;"><?php echo tdStart() ?><strong><small>Visuel <br>/12</small></strong></th>
    <th style="width:4%;"><?php echo tdStart() ?><strong><small>Oifactif <br>/12</small></strong></th>
    <th style="width:4%;"><?php echo tdStart() ?><strong><small>Gustatif  <br> /24</small></strong></th>
    <th style="width:4%;"><?php echo tdStart() ?><strong><small>NOTE TOTALE /48</small></strong></th>
    <th class="bg-white" style="width:5%;" ><?php echo tdStart() ?><strong><small>C</small></strong></th>
    <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>NC</small></strong></th>
    <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>C</small></strong></th>
    <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>NC</small></strong></th>
    <th class="empty bg-white"></th>
  </tr>

<?php $i=0;
 foreach($lots as $lotInfo): ?>
   <?php if($i == 16 || ($i - 16) % 19 > 17): ?>
</table>
     <br pagebreak="true" />
     <p>Suite des lots table <?php echo $lotInfo->getNumeroTableStr(); ?><p/>
     <br/>
     <table border="1px" class="table" cellspacing=0 cellpadding=0 style="text-align: center;border-collapse:collapse;" scope="colgroup" >
       <tr style="line-height:20px;">
          <th class="topempty bg-white"style="width:7%; "><?php echo tdStart() ?><strong>Anon</strong></th>
          <th class="topempty bg-white" style="width:10%; "><?php echo tdStart() ?><strong>Couleur</strong></th>
          <th class="topempty bg-white"style="width:14%;"><?php echo tdStart() ?><strong>IGP</strong></th>
          <th class="topempty bg-white"style="width:14%;"><?php echo tdStart() ?><strong>Cépage</strong></th>
         <th colspan="4"style="width:16%;"><?php echo tdStart() ?><strong>NOTATION</strong></th>
          <th class="bg-white" colspan="2"style="width:10%;"><?php echo tdStart() ?><strong>Avis</strong></th>
          <th class="bg-white"  colspan="2"style="width:10%;"><?php echo tdStart() ?><strong>Typicité cépage</strong></th>
          <th class="topempty bg-white" style="width:22%;"><strong>Motifs (si non conforme)</strong></th>
       </tr>
       <tr style="line-height:13px;">
         <th class="empty bg-white"></th>
         <th class="empty bg-white"></th>
         <th class="empty bg-white"></th>
         <th class="empty bg-white"></th>
         <th style="width:4%;"><?php echo tdStart() ?><strong><small>Visuel <br>/12</small></strong></th>
         <th style="width:4%;"><?php echo tdStart() ?><strong><small>Oifactif <br>/12</small></strong></th>
         <th style="width:4%;"><?php echo tdStart() ?><strong><small>Gustatif  <br> /24</small></strong></th>
         <th style="width:4%;"><?php echo tdStart() ?><strong><small>NOTE TOTALE /48</small></strong></th>
         <th class="bg-white" style="width:5%;" ><?php echo tdStart() ?><strong><small>C</small></strong></th>
         <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>NC</small></strong></th>
         <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>C</small></strong></th>
         <th class="bg-white" style="width:5%;"><?php echo tdStart() ?><strong><small>NC</small></strong></th>
         <th class="empty bg-white"></th>
       </tr>
   <?php endif;?>

    <tr style="line-height:11px;">
      <td><?php echo tdStart() ?>&nbsp;<strong><?php echo $lotInfo->getNumeroAnonymat() ?></strong></td>
      <td><?php echo tdStart() ?>&nbsp;<strong><?php echo $lotInfo->getConfig()->getCouleur()->getLibelle();  ?></strong></td>
      <td><?php echo tdStart() ?>
        &nbsp;<?php echo $lotInfo->getConfig()->getAppellation()->getLibelle(); ?>
        <?php if(DegustationConfiguration::getInstance()->hasSpecificiteLotPdf() && DrevConfiguration::getInstance()->hasSpecificiteLot()): ?>
        <br/><small style="color: #777777;font-size :14px"><?php echo " ($lotInfo->specificite)";?></small>
      <?php endif ?>
      </td>
      <td><?php echo tdStart() ?>&nbsp;<small><?php echo $lotInfo->details;?></small></td>
      <td><?php echo tdStart() ?></td>
      <td><?php echo tdStart() ?></td>
      <td><?php echo tdStart() ?></td>
      <td><?php echo tdStart() ?></td>
      <td><?php echo tdStart() ?><span class="zap">o</span></td>
      <td><?php echo tdStart() ?><span class="zap">o</span></td>
      <td><?php echo tdStart() ?><span class="zap">o</span></td>
      <td><?php echo tdStart() ?><span class="zap">o</span></td>
      <td><?php echo tdStart() ?>&nbsp;</td>
    </tr>
    <?php $i++; ?>
  <?php endforeach; ?>
</table>
