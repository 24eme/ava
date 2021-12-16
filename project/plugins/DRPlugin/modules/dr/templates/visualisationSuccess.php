<?php use_helper('Date'); ?>

<?php include_partial('dr/breadcrumb', array('dr' => $dr )); ?>
<?php include_partial('global/flash'); ?>

<div class="page-header no-border clearfix">
    <h2>
        Déclaration de Récolte <?= $dr->campagne ?>
        <small class="pull-right">
            <i class="glyphicon glyphicon-file"></i>
            Déclaration importée le <?= format_date($dr->date_import, "dd/MM/yyyy", "fr_FR") ?>
            <?php if ($dr->exist('validation') && $dr->validation): ?>
                et approuvée le <?= format_date($dr->validation, "dd/MM/yyyy", "fr_FR") ?>
            <?php endif ?>
        </small>
    </h2>
</div>

<div class="well mb-5">
    <?php include_partial('etablissement/blocDeclaration', ['etablissement' => $dr->getEtablissementObject()]); ?>
</div>

<?php if (isset($validation) && $validation->hasPoints()): ?>
    <?php include_partial('dr/pointsAttentions', ['validation' => $validation, 'noLink' => true]); ?>
<?php endif ?>

<?php include_partial('dr/recap', compact('dr', 'lignesAAfficher', 'configuration')) ?>

<div class="row row-margin row-button">
    <div class="col-xs-4">
        <a href="<?= ($service) ?: url_for('declaration_etablissement', ['identifiant' => $dr->identifiant, 'campagne' => $dr->campagne]) ?>"
            class="btn btn-default"
        >
            <i class="glyphicon glyphicon-chevron-left"></i> Retour
        </a>
    </div>

    <div class="col-xs-4 text-center">
        <a class="btn btn-default" href="<?php echo url_for('get_fichier', array('id' => $dr->_id)) ?>">
            <i class="glyphicon glyphicon-file"></i> DR
        </a>
    </div>

    <div class="col-xs-4 text-right">
        <?php if($dr->exist('validation') && $dr->validation): ?>
            <a class="btn btn-default btn-sm" href="<?= url_for('dr_devalidation', $dr) ?>"
                onclick="return confirm('Êtes vous sûr de vouloir dévalider cette DR');"
            >
                <span class="glyphicon glyphicon-remove-sign"> Dévalider</span>
            </a>
        <?php elseif(isset($validation) && $validation->hasErreurs()) : ?>
            <a href="#" class="btn btn-default disabled">
                Approuver la DR
            </a>
        <?php else : ?>
            <a href="<?= url_for('dr_approbation', ['id' => $dr->_id]) ?>" class="btn btn-default">
                Approuver la DR
            </a>
        <?php endif ?>
    </div>
</div>
