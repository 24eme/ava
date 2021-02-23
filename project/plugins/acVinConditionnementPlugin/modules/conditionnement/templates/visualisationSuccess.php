<?php use_helper('Date') ?>

<?php include_partial('conditionnement/breadcrumb', array('conditionnement' => $conditionnement )); ?>
<?php if (isset($form)): ?>
    <form action="<?php echo url_for('conditionnement_visualisation', $conditionnement) ?>" method="post">
        <?php echo $form->renderHiddenFields(); ?>
        <?php echo $form->renderGlobalErrors(); ?>
<?php endif; ?>

<div class="page-header no-border">
    <h2>Déclaration de Conditionnement <?php echo $conditionnement->campagne ?>
    <?php if($conditionnement->isPapier()): ?>
    <small class="pull-right"><span class="glyphicon glyphicon-file"></span> Déclaration papier<?php if($conditionnement->validation && $conditionnement->validation !== true): ?> reçue le <?php echo format_date($conditionnement->validation, "dd/MM/yyyy", "fr_FR"); ?><?php endif; ?>
      <?php if($conditionnement->isSauvegarde()): ?> <span class="text-danger">Non facturé</span><?php endif; ?>
    <?php elseif($conditionnement->validation): ?>
    <small class="pull-right" style="font-size:50%">Télédéclaration<?php if($conditionnement->validation && $conditionnement->validation !== true): ?> signée le <?php echo format_date($conditionnement->validation, "dd/MM/yyyy", "fr_FR"); ?><?php endif; ?><?php if($conditionnement->validation_odg): ?> et approuvée le <?php echo format_date($conditionnement->validation_odg, "dd/MM", "fr_FR"); ?><?php endif; ?>
    <?php endif; ?>
  </small>
    </h2>
    <h4 class="mt-5 mb-0"><?php echo $conditionnement->declarant->nom; ?><span class="text-muted"> (<?php echo $conditionnement->declarant->famille; ?>)</span></h4>
</div>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success" role="alert"><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif; ?>

<?php if(!$conditionnement->validation): ?>
<div class="alert alert-warning">
    La saisie de cette déclaration n'est pas terminée elle est en cours d'édition
</div>
<?php endif; ?>

<?php if(!$conditionnement->isMaster()): ?>
    <div class="alert alert-info">
      Ce n'est pas la <a class="" href="<?php echo ($conditionnement->getMaster()->isValidee())? url_for('conditionnement_visualisation', $conditionnement->getMaster()) :  url_for('conditionnement_edit', $conditionnement->getMaster()) ?>"><strong>dernière version</strong></a> de la déclaration, le tableau récapitulatif n'est donc pas à jour.

    </div>
<?php endif; ?>

<?php if($conditionnement->validation && !$conditionnement->validation_odg && $sf_user->isAdmin()): ?>
    <div class="alert alert-warning">
        Cette déclaration est en <strong>attente de validation</strong> par l'ODG
    </div>
<?php endif; ?>

<?php if(isset($validation) && $validation->hasPoints()): ?>
    <?php include_partial('conditionnement/pointsAttentions', array('conditionnement' => $conditionnement, 'validation' => $validation, 'noLink' => true)); ?>
<?php endif; ?>

<?php include_partial('conditionnement/recap', array('conditionnement' => $conditionnement, 'form' => $form, 'dr' => $dr)); ?>

<?php if (ConditionnementConfiguration::getInstance()->hasDegustation()): ?>
    <h3>Dégustation</h3>
    <p style="margin-bottom: 30px;">Les vins seront prêt à être dégustés à partir du : <?php echo ($conditionnement->date_degustation_voulue)     ? date_format(date_create($conditionnement->validation), 'd/m/Y') : null;?></p>
<?php endif ?>
<div class="row row-margin row-button">
    <div class="col-xs-6">
        <a href="<?php if(isset($service)): ?><?php echo $service ?><?php else: ?><?php echo url_for("declaration_etablissement", array('identifiant' => $conditionnement->identifiant, 'campagne' => $conditionnement->campagne)); ?><?php endif; ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a>
    </div>

    <div class="col-xs-6 text-right">
        <div class="btn-group">
        <?php if ($conditionnement->validation && ConditionnementSecurity::getInstance($sf_user, $conditionnement->getRawValue())->isAuthorized(ConditionnementSecurity::DEVALIDATION)):
                if (!$conditionnement->validation_odg): ?>
                    <a class="btn btn-default" href="<?php echo url_for('conditionnement_devalidation', $conditionnement) ?>" onclick="return confirm('Êtes-vous sûr de vouloir réouvrir cette Conditionnement ?');"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;&nbsp;Réouvrir</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if(!$conditionnement->validation): ?>
                <a href="<?php echo url_for("conditionnement_edit", $conditionnement) ?>" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span>&nbsp;&nbsp;Continuer la saisie</a>
        <?php elseif(!$conditionnement->validation_odg && ( $sf_user->isAdmin() ||
                                                 ($sf_user->hasConditionnementAdmin() && ConditionnementConfiguration::getInstance()->hasValidationOdgRegion() && !$conditionnement->isValidateOdgByRegion($regionParam))
                                               )): ?>
        <?php $params = array("sf_subject" => $conditionnement, "service" => isset($service) ? $service : null); if($regionParam): $params=array_merge($params,array('region' => $regionParam)); endif; ?>
                <a onclick='return confirm("Êtes vous sûr de vouloir approuver cette déclaration ?");' href="<?php echo url_for("conditionnement_validation_admin", $params) ?>" class="btn btn-success btn-upper"><span class="glyphicon glyphicon-ok-sign"></span>&nbsp;&nbsp;Approuver</a>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php if (isset($form)): ?>
</form>
<?php endif; ?>
