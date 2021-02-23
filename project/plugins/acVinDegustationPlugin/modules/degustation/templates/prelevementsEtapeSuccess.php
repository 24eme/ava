<?php use_helper('Float') ?>

<?php include_partial('degustation/breadcrumb', array('degustation' => $degustation)); ?>
<?php include_partial('degustation/step', array('degustation' => $degustation, 'active' => DegustationEtapes::ETAPE_PRELEVEMENTS)); ?>


<div class="page-header no-border">
  <h2>Prélévements des lots/ Convocations des dégustateurs</h2>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="panel panel-default" style="min-height: 160px">
      <div class="panel-heading">
        <h2 class="panel-title">
          <div class="row">
            <div class="col-xs-12">Prélèvements</div>
          </div>
        </h2>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-xs-12">
            <strong>Organisation des prélèvements</strong>
            <br/>
            <br/>
        </div>
          <div class="col-xs-12">
              <a href="<?php echo url_for('degustation_fiche_lots_a_prelever_pdf', $degustation) ?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-file"></span>&nbsp;Fiche tournée prélevement</a>
              <a href="<?php echo url_for('degustation_fiche_individuelle_lots_a_prelever_pdf', $degustation) ?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-file"></span>&nbsp;Fiche individuelle de tournée (Lot à prélever)</a>
              <a href="<?php echo url_for('degustation_etiquette_pdf', $degustation) ?>" class="btn btn-default btn-xs"><span class="glyphicon glyphicon-file"></span>&nbsp;Tableau des étiquettes</a>

              <br/>
              <br/>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-8">
            <div class="row">
              <div class="col-xs-6">
                <strong class="lead"><?php echo $infosDegustation["nbLotsSansLeurre"]; ?></strong> <strong>lots au total</strong> prévus dans la dégustation<br/>
              </div>
              <div class="col-xs-6">
              </div>
            </div>
            <div class="row">
              <div class="col-xs-6">
                <strong class="lead"><?php echo $infosDegustation["nbLotsRestantAPrelever"]; ?></strong> <strong>lots</strong> restant à prélever chez
              </div>
              <div class="col-xs-6">
                <strong><span class="lead"><?php echo $infosDegustation["nbAdherentsLotsRestantAPrelever"]; ?></span> adhérents</strong>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-6">
                <strong class="lead"><?php echo $infosDegustation["nbLotsPrelevesSansLeurre"]; ?></strong> <strong>lots</strong> déjà prélevés chez
              </div>
              <div class="col-xs-6">
                <strong><span class="lead"><?php echo $infosDegustation["nbAdherentsPreleves"]; ?></span> adhérents</strong>
              </div>
            </div>
          </div>
          <div class="col-xs-12 text-right">
            <a class="btn btn-default btn-sm" href="<?php echo url_for('degustation_preleve', $degustation) ?>" >&nbsp;Saisir les prélévements effectués&nbsp;<span class="glyphicon glyphicon-pencil"></span></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-xs-12">
    <div class="panel panel-default" style="min-height: 160px">
      <div class="panel-heading">
        <h2 class="panel-title">Convocations des dégustateurs</h2>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-xs-7">
            <?php foreach ($infosDegustation["degustateurs"] as $college => $indicateurs): ?>
              <strong class="lead"><?php echo $indicateurs["confirmes"]; ?></strong> / <?php echo $indicateurs["total"]; ?> <strong><?php echo $college; ?></strong> confirmés<br/>
            <?php endforeach; ?>
          </div>
          <div class="col-xs-12 text-right">
            <a class="btn btn-default btn-sm" href="<?php echo url_for('degustation_degustateurs_confirmation', $degustation) ?>" >&nbsp;Confirmation dégustateurs&nbsp;<span class="glyphicon glyphicon-pencil"></span></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

	<div class="row row-button">
				<div class="col-xs-4"><a href="<?php echo url_for("degustation") ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
				<div class="col-xs-4 text-center">
				</div>
				<div class="col-xs-4 text-right"><a class="btn btn-primary btn-upper" href="<?php echo url_for('degustation_tables_etape', $degustation) ?>" >Valider&nbsp;<span class="glyphicon glyphicon-chevron-right"></span></a></div>
		</div>
