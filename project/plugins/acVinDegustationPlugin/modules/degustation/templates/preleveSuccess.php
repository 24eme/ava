<?php use_helper("Date"); ?>
<?php use_helper('Float') ?>
<?php use_helper('Lot') ?>

<?php include_partial('degustation/breadcrumb', array('degustation' => $degustation, 'options' => array('route' => 'degustation_preleve', 'nom' => 'Prélevements réalisés'))); ?>
<?php include_partial('degustation/step', array('degustation' => $degustation, 'active' => DegustationEtapes::ETAPE_PRELEVEMENTS)); ?>

<div class="page-header no-border">
  <h2>Échantillons prélevés</h2>
  <h3><?php echo ucfirst(format_date($degustation->date, "P", "fr_FR"))." à ".format_date($degustation->date, "H")."h".format_date($degustation->date, "mm") ?> <small><?php echo $degustation->getLieuNom(); ?></small></h3>
</div>

<?php include_partial('degustation/synthese', array('degustation' => $degustation, 'infosDegustation' => $infosDegustation)); ?>

<p>Sélectionner les lots qui ont été prélevés</p>
<div class="row">
  <div class="form-group col-xs-10">
    <input id="hamzastyle" type="hidden" data-placeholder="Sélectionner un nom :" data-hamzastyle-container="#table_prelevements" data-hamzastyle-mininput="3" class="select2autocomplete hamzastyle form-control">
  </div>

  <div class="col-xs-2">
    <button class="btn btn-block btn-default" id="btn-preleve-all">
      <i class="glyphicon glyphicon-ok-sign"></i>
      Tout prélever
    </button>
  </div>
</div>

<form action="<?php echo url_for("degustation_preleve", $degustation) ?>" method="post" class="form-horizontal degustation prelevements">
	<?php echo $form->renderHiddenFields(); ?>

    <div class="bg-danger">
    <?php echo $form->renderGlobalErrors(); ?>
    </div>

    <table class="table table-bordered table-condensed table-striped" id="table_prelevements">
        <thead>
            <tr>
                <th class="col-xs-3">Opérateur</th>
                <th class="col-xs-1">Provenance</th>
                <th class="col-xs-1">Logement</th>
                <th class="col-xs-3">Produit (millésime, spécificité)</th>
                <th class="col-xs-1">Volume</th>
                <th class="col-xs-1">Prélevé</th>
            </tr>
        </thead>
		<tbody>
		<?php foreach ($form['lots'] as $key => $formLot): ?>
    <?php $lot = $degustation->lots->get($key); ?>
      <tr class="vertical-center cursor-pointer hamzastyle-item" data-adherent="<?php echo $lot->numero_dossier; ?>" data-words='<?= json_encode(strtolower($lot->declarant_nom), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>'>
        <td><?php echo $lot->declarant_nom; ?></td>
        <td><?php echo $lot->getProvenance() ?></td>
        <td class="edit"><?= $lot->numero_logement_operateur ?>
          <?php if (! $lot->isLeurre()): ?>
            <span class="pull-right">
              <a title="Modifier le logement" href="<?php echo url_for('degustation_preleve_update_logement', ['id' => $degustation->_id, 'lot' => $key]) ?>"><i class="glyphicon glyphicon-pencil"></i></a>
            </span>
          <?php endif; ?>
        </td>
        <td>
            <?= showProduitLot($lot) ?>
        </td>
        <td class="text-right edit ">
              <?php echoFloat($lot->volume); ?><small class="text-muted">&nbsp;hl</small>
              &nbsp;
              <?php if($lot->isOrigineEditable()): ?>
              <a title="Modifier le lot dans la DRev" href="<?php echo url_for('degustation_update_lot', ['id' => $degustation->_id, 'lot' => $key]) ?>">
                <i class="glyphicon glyphicon-pencil"></i>
              </a>
              <?php else: ?>
              <i class="glyphicon glyphicon-pencil" style="opacity:0.0"></i>
          <?php endif; ?>
        </td>
      	<td class="text-center">
              <div style="margin-bottom: 0;" class="<?php if($formLot->hasError()): ?>has-error<?php endif; ?>">
              	<?php echo $formLot['preleve']->renderError() ?>
                  <div class="col-xs-12">
            	<?php echo $formLot['preleve']->render(array('class' => "degustation bsswitch", "data-preleve-adherent" => "$lot->numero_dossier", "data-preleve-lot" => "$lot->numero_logement_operateur",'data-size' => 'small', 'data-on-text' => "<span class='glyphicon glyphicon-ok-sign'></span>", 'data-off-text' => "<span class='glyphicon'></span>", 'data-on-color' => "success")); ?>
                  </div>
              </div>
              <span class="pull-right">
                <a onclick="return confirm('Êtes-vous sûr de vouloir supprimer le logement <?php echo $lot->numero_logement_operateur.' de '.$lot->volume."hl" ?> ?');" title="Supprimer le logement" href="<?php echo url_for('degustation_supprimer_lot_non_preleve', ['id' => $degustation->_id, 'lot' => $key]) ?>"><i class="glyphicon glyphicon-trash"></i></a>
              </span>
      	</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
	</table>

	<div class="row row-margin row-button">
        <div class="col-xs-4"><a href="<?php echo url_for("degustation_prelevements_etape", $degustation) ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
        <div class="col-xs-4 text-center">
        </div>
        <div class="col-xs-4 text-right"><button type="submit" class="btn btn-primary btn-upper">Valider</button></div>
    </div>
</form>
</div>

<?php use_javascript('hamza_style.js'); ?>
