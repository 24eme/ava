<div class="page-header no-border">
    <h2>Récupération des apporteurs depuis votre SV11</h2>
</div>

<p style="margin-top: 40px; margin-bottom: 40px;" class="text-center">En continuant vos apporteurs vont être récupérés depuis votre SV11.</p>

<div class="row row-margin row-button">
    <div class="col-xs-4"><a href="<?php echo url_for("declaration_etablissement", array('identifiant' => $etablissement->identifiant)); ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
    <div class="col-xs-4 text-center">
    </div>
    <div class="col-xs-4 text-right"><a href="<?php echo url_for("parcellaireaffectationcoop_apporteurs", array("sf_subject" => $etablissement, "periode" => $periode)) ?>" class="btn btn-primary">Continuer<span class="glyphicon glyphicon-chevron-right"></span></a></div>
</div>