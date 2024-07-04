<?php include_partial('parcellaireIrrigable/breadcrumb', array('parcellaireIrrigable' => $parcellaireIrrigable)); ?>

<?php include_partial('parcellaireIrrigable/step', array('step' => 'parcelles', 'parcellaireIrrigable' => $parcellaireIrrigable)) ?>
<div class="page-header">
    <h2>Parcelles irrigables sur votre exploitation <br/><small>Merci d'indiquer vos parcelles irrigables en cliquant sur la ligne de la parcelle concernée.</small></h2>
</div>

<form action="<?php echo url_for("parcellaireirrigable_parcelles", $parcellaireIrrigable) ?>" method="post" class="form-horizontal">

    <?php include_partial('parcellaireIrrigable/formParcelles', ['parcellaireIrrigable' => $parcellaireIrrigable]); ?>

	<div class="row row-margin row-button">
        <div class="col-xs-4"><a href="<?php echo url_for("parcellaireirrigable_exploitation", $parcellaireIrrigable); ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retourner à l'étape précédente</a></div>
        <div class="col-xs-4 text-center">
            <button type="submit" name="saveandquit" value="1" class="btn btn-default">Enregistrer en brouillon</button>
        </div>
        <div class="col-xs-4 text-right"><button type="submit" class="btn btn-primary btn-upper">Valider et continuer <span class="glyphicon glyphicon-chevron-right"></span></button></div>
    </div>
</form>
