<?php use_helper("Date"); ?>
<?php use_helper('Float') ?>


<form action="<?php echo url_for("degustation_organisation_table", array('id' => $degustation->_id)) ?>" method="post" class="form-horizontal degustation">
	<?php echo $form->renderHiddenFields(); ?>
    <div class="bg-danger">
    <?php echo $form->renderGlobalErrors(); ?>
    </div>


    <table class="table table-bordered table-condensed table-striped">
    <thead>
          <tr>
            <th class="col-xs-9">Lots</th>
            <th class="col-xs-3">Tables</th>
          </tr>
    </thead>
    <tbody>
    <?php
      foreach ($degustation->getLotsSorted() as $lot):
      $name = $form->getWidgetNameFromLot($lot);
      if (isset($form[$name])):
    ?>
      <tr class="vertical-center cursor-pointer">
        <td<?php if ($lot->leurre === true): ?> class="bg-warning"<?php endif ?>>
            <div class="row">
                    <div class="col-xs-5 text-right">
                        <?php if ($lot->leurre === true): ?><em>Leurre</em> <?php endif ?>
                        <?php echo $lot->declarant_nom.' ('.$lot->numero.')'; ?>
                    </div>
                <div class="col-xs-5 text-right"><?php echo $lot->produit_libelle;?></div>
              <div class="col-xs-2 text-right"><?php echo ($lot->millesime)? ' ('.$lot->millesime.')' : ''; ?></div>
            </div>
        </td>
              <td class="text-center">
                  <div style="margin-bottom: 0;" class="form-group <?php if($form[$name]->hasError()): ?>has-error<?php endif; ?>">
                      <?php echo $form[$name]->renderError() ?>
                        <div class="col-xs-12">
                    <?php echo $form[$name]->render(array("class" => "form-control select2", "placeholder" => "Séléctionner une table")); ?>
                        </div>
                    </div>
              </td>
            </tr>
        <?php  endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>

<div class="row row-margin row-button">
      <div class="col-xs-4"><a href="<?php echo url_for("degustation_visualisation_prelevement", $degustation) ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
      <div class="col-xs-4 text-center">
				<button class="btn btn-sm btn-default ajax" data-toggle="modal" data-target="#popupLeurreForm" type="button"><span class="glyphicon glyphicon-plus-sign"></span>&nbsp;&nbsp;Ajouter un leurre</button>
      </div>
      <div class="col-xs-4 text-right">
					<button type="submit" class="btn btn-success btn-upper">Valider <span class="glyphicon glyphicon-chevron-right"></span></button>
			</div>
  </div>
</form>


<?php include_partial('degustation/popupAjoutLeurreForm', array('url' => url_for('degustation_ajout_leurre', $degustation), 'form' => $ajoutLeurreForm)); ?>
