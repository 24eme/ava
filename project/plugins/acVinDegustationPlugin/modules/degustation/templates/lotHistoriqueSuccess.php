<?php use_helper('Date'); ?>
<?php use_helper('Lot'); ?>
<?php use_helper('Float') ?>
<?php $lot = null; ?>
<?php
foreach($mouvements as $lotKey => $m):
    $doc = acCouchdbManager::getClient()->find($m->value->document_id);
    $lot = $doc->get($m->value->lot_hash);
    break;
endforeach;
?>

<ol class="breadcrumb">
  <li><a href="<?php echo url_for('degustation'); ?>">Dégustation</a></li>
  <li><a href="<?php echo url_for('degustation_etablissement_list',array('identifiant' => $etablissement->identifiant)); ?>"><?php echo $etablissement->getNom() ?> (<?php echo $etablissement->identifiant ?> - <?php echo $etablissement->cvi ?>)</a></li>
  <li><a href="" class="active" >N° dossier : <?php echo $numero_dossier ?> - N° archive :<?php echo $numero_archive ?></a></li>
</ol>


<h2>Historique du lot de <?php echo $etablissement->getNom(); ?></h2>
<br/>

<?php include_partial('chgtdenom/infoLotOrigine', array('lot' => $lot)); ?>

<?php if (count($mouvements)): ?>
    <table class="table table-condensed table-striped">
      <thead>
        <th class="col-sm-1">Date</th>
        <th class="col-sm-2">Document</th>
        <th class="col-sm-3">Etape</th>
        <th class="col-sm-5">Détail</th>
        <th class="col-sm-1"></th>
      </thead>
      <tbody>
        <?php foreach($mouvements as $lotKey => $mouvement): ?>
              <tr>
                <td><?php echo format_date($mouvement->value->date, "dd/MM/yyyy", "fr_FR");  ?></td>
                <td>
                    <a href="<?php echo url_for(strtolower($mouvement->value->document_type).'_visualisation', array('id' => $mouvement->value->document_id));  ?>">
                    <?php echo $mouvement->value->document_type;  ?>
                    </a>
                </td>
                <td><?php echo Lot::$libellesStatuts[$mouvement->value->statut];  ?></td>
                <td><?php echo showDetailMvtLot($mouvement);  ?></td>
                <td>
                    <a href="#" class="btn btn-default btn-xs">voir</a>
                </td>
            </tr>
                <?php endforeach; ?>
            <tbody>
            </table>
          <?php endif; ?>
