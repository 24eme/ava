<?php use_helper('Float') ?>
<?php use_helper('Date') ?>
<?php use_helper('Lot') ?>

<?php include_partial('chgtdenom/breadcrumb', array('chgtDenom' => $chgtDenom )); ?>


    <div class="page-header no-border">
      <h2><?php if ($chgtDenom->isDeclassement()): ?>Déclassement<?php else: ?>Changement de dénomination<?php endif; ?> <?php if (!$chgtDenom->isChgtTotal()): ?>partiel<?php endif; ?>
      <?php if ($chgtDenom->isValide()): ?>
      <small class="pull-right">Télédéclaration signée le <?php echo format_date($chgtDenom->validation, "dd/MM/yyyy", "fr_FR"); ?><?php if($chgtDenom->isApprouve()): ?> et approuvée le <?php echo format_date($chgtDenom->validation_odg, "dd/MM", "fr_FR"); ?><?php endif; ?></small>
      <?php endif; ?>
      </h2>
    </div>
    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success" role="alert"><?php echo $sf_user->getFlash('notice') ?></div>
    <?php endif; ?>

    <?php if($sf_user->isAdmin()): ?>
      <?php include_partial('chgtdenom/recap', array('chgtDenom' => $chgtDenom, 'form' => $form)); ?>
    <?php else:?>
      <?php include_partial('chgtdenom/recap', array('chgtDenom' => $chgtDenom)); ?>
    <?php endif; ?>

    <?php if (isset($form)): ?>
    <form role="form" action="<?php echo url_for("chgtdenom_visualisation", $chgtDenom) ?>" method="post" class="form-horizontal" id="validation-form">
        <?php echo $form->renderHiddenFields(); ?>
        <?php echo $form->renderGlobalErrors(); ?>
          <?php if (isset($form['deguster'])): ?>
          <div class="row">
              <div class="col-md-12 text-right">
                <label>
                  <?php echo $form['deguster']->render() ?>
                  <?php echo $form['deguster']->renderLabel('À déguster') ?>
                </label>
              </div>
          </div>
        <?php endif; ?>
          <div style="margin-top: 20px;" class="row row-margin row-button">
              <div class="col-xs-6">
                  <a tabindex="-1" href="<?php echo url_for("declaration_etablissement", array('identifiant' => $chgtDenom->identifiant, 'campagne' => $chgtDenom->campagne)); ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a>
              </div>
              <div class="col-xs-6 text-right">
                  <button type="submit" id="btn-validation-document" data-toggle="modal" data-target="#confirmation-validation" class="btn btn-success btn-upper"><span class="glyphicon glyphicon-check"></span>&nbsp;&nbsp;Approuver le changement</button>
              </div>
          </div>
      </form>
      <?php include_partial('chgtdenom/popupConfirmationApprobation'); ?>
    <?php else: ?>
    <div style="margin-top: 20px;" class="row row-margin row-button">
        <div class="col-xs-6">
            <a tabindex="-1" href="<?php echo url_for("declaration_etablissement", array('identifiant' => $chgtDenom->identifiant, 'campagne' => $chgtDenom->campagne)); ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a>
        </div>
    </div>
    <?php endif; ?>
