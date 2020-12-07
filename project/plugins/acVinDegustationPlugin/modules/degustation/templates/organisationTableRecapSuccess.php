<?php use_helper("Date"); ?>
<?php use_helper('Float') ?>

<?php include_partial('degustation/organisationTableHeader', array('degustation' => $degustation, 'tri' => $tri)); ?>

<div class="row row-condensed">
  <div class="col-xs-12">
    <div class="panel panel-default">
      <div class="panel-body">
        <div class="alert alert-info" role="alert">
          <h3>Synthèse toutes tables</h3>

          <table class="table table-condensed">
            <thead>
                <tr>
                    <th class="col-xs-2">Table</th>
                    <th class="col-xs-6">Couleur|Appellation|Cépage</th>
                    <th class="col-xs-2"></th>
                    <th class="col-xs-2">Nombre d'échantillons</th>
                </tr>
            </thead>
            <tbody>
            <?php $total = 0; ?>
            <?php foreach($degustation->getTablesWithFreeLots() as $numero_table => $table): ?>
                <tr data-toggle="collapse" data-target=".accordion_<?php echo $numero_table ?>" class="clickable" style="cursor:pointer;">
                    <td>Table <?php echo DegustationClient::getNumeroTableStr($numero_table) ?>&nbsp;<span class="caret"></span></td>
                    <td></td>
                    <td></td>
                    <td><strong><?php echo count($table->lots); $total += count($table->lots); ?></strong></td>
                </tr>
                    <?php foreach ($degustation->getSyntheseLotsTable($numero_table) as $hash => $lotsProduit): ?>
                      <tr class="vertical-center collapse accordion_<?php echo $numero_table ?>" data-hash="<?php echo $hash; ?>" >
                        <td></td>
                        <td><?php echo $lotsProduit->libelle ?>&nbsp;<small class="text-muted"><?php echo $lotsProduit->details; ?></small><?php echo ($lotsProduit->millesime)? ' ('.$lotsProduit->millesime.')' : ''; ?></td>
                        <td></td>
                        <td class="nblots"><?php echo count($lotsProduit->lotsTable) ?></td>
                      </tr>
                    <?php endforeach; ?>
            <?php endforeach ?>
              <tr>
                <td class=""></td>
                <td></td>
                <td class="text-right"><strong>Total : </strong></td>
                <td class="nblots"><strong><?php echo $total ?></strong></td>
              </tr>
            </tbody>
          </table>
        </div>
          <form action="<?php echo url_for("degustation_organisation_table_recap", array('id' => $degustation->_id)) ?>" method="post" class="form-horizontal degustation">
          	<?php echo $form->renderHiddenFields(); ?>
              <div class="bg-danger">
              <?php echo $form->renderGlobalErrors(); ?>
              </div>


              <table class="table table-bordered table-condensed table-striped">
              <thead>
                    <tr>
                      <th class="col-xs-10">Échantillons</th>
                      <th class="col-xs-2">Tables</th>
                    </tr>
              </thead>
              <tbody>
              <?php
                foreach ($degustation->getLotsPreleves($tri_array) as $lot):
                $name = $form->getWidgetNameFromLot($lot);
                if (isset($form[$name])):
              ?>
                <tr class="vertical-center cursor-pointer">
                  <td<?php if ($lot->leurre === true): ?> class="bg-warning"<?php endif ?>>
                      <div class="row">
                              <div class="col-xs-5 text-right">
                                  <?php if ($lot->leurre === true): ?><em>Leurre</em> <?php endif ?>
                                  <?php echo $lot->declarant_nom.' ('.$lot->numero_cuve.')'; ?>
                              </div>
                          <div class="col-xs-3 text-right"><?php echo $lot->produit_libelle;?></div>
                          <div class="col-xs-3 text-right"><small class="text-muted"><?php echo $lot->details; ?></small></div>
                        <div class="col-xs-1 text-right"><?php echo ($lot->millesime)? ' ('.$lot->millesime.')' : ''; ?></div>
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
                <div class="col-xs-4"><a href="<?php echo url_for("degustation_organisation_table", array('id' => $degustation->_id, 'numero_table' => count($degustation->getTablesWithFreeLots()), 'tri' => $tri)) ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Précédent</a></div>
                <div class="col-xs-4 text-center">
                </div>
                <div class="col-xs-4 text-right">
  					<button type="submit" class="btn btn-success btn-upper">Terminer</button>
      			</div>
            </div>
          </form>
      </div>
    </div>
  </div>
</div>
