<?php include_partial('parcellaireAffectationCoop/breadcrumb', array('parcellaireAffectationCoop' => $parcellaireAffectationCoop)); ?>
<?php include_partial('parcellaireAffectationCoop/step', array('step' => 'saisies', 'parcellaireAffectationCoop' => $parcellaireAffectationCoop)) ?>

<div class="page-header no-border">
    <h2>Déclaration d'irrigation de <?php echo $parcellaireIrrigable->declarant->raison_sociale ?></h2>
</div>
<form id="validation-form" action="" method="post" >
    <?php include_partial("parcellaireIrrigable/formParcelles", ['parcellaireIrrigable' => $parcellaireIrrigable]); ?>

    <div class="row row-margin row-button">
        <div class="col-xs-4"><button type="submit" name="retour" value="1" class="btn btn-default"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
        <div class="col-xs-4 text-center">
        </div>
        <div class="col-xs-4 text-right"><button class="btn btn-primary">Continuer <span class="glyphicon glyphicon-chevron-right"></span></button></div>
    </div>
</form>
