<?php use_helper('Float'); ?>
<?php use_helper('PointsAides');?>
<?php use_javascript("conditionnement.js", "last") ?>

<?php include_partial('conditionnement/breadcrumb', array('conditionnement' => $conditionnement )); ?>
<?php include_partial('conditionnement/step', array('step' => ConditionnementEtapes::ETAPE_LOTS, 'conditionnement' => $conditionnement, 'ajax' => true)) ?>

    <div class="page-header"><h2>Conditionnement des Lots IGP</h2></div>



    <?php echo include_partial('global/flash'); ?>
    <form role="form" action="<?php echo url_for("conditionnement_lots", $conditionnement) ?>" method="post" id="form_conditionnement_lots" class="form-horizontal">

    <?php echo $form->renderHiddenFields(); ?>
    <?php echo $form->renderGlobalErrors(); ?>

    <?php foreach($conditionnement->lots as $lot): ?>
      <?php if(!$lot->hasBeenEdited()){ continue; } ?>
      <div class="panel panel-default" style="border-color:rgba(130, 147, 69, 0.4);">
          <div class="panel-body panel-body-success">
            <div class="row">
              <div class="col-md-2"><?php echo Date::francizeDate($lot->date); ?></div>
              <div class="col-md-6"><strong><?php echo $lot->produit_libelle; ?></strong>
                <?php if(count($lot->cepages)): ?>
                  &nbsp;<small>
                    <?php echo $lot->getCepagesToStr(); ?>
                  </small>
                <?php endif; ?>
              </div>
              <div class="col-md-3"><?php echo $lot->millesime; ?></div>
              <div class="col-md-1 text-right">
                <?php if($isAdmin): ?>
                  <a href="<?php echo url_for("conditionnement_lots_delete", array('id' => $conditionnement->_id, 'numArchive' => $lot->numero_archive)) ?>" onclick='return confirm("Étes vous sûr de vouloir supprimer ce lot ?");' class="close" title="Supprimer ce lot" aria-hidden="true">×</a>
                <?php endif; ?>
              </div>
            </div>
            <div class="row">
              <div class="col-md-2"></div>
              <div class="col-md-3">Numéro lot : <?php echo $lot->numero_archive; ?></div>
              <div class="col-md-3"><strong>Volume : <?php echo $lot->volume; ?><small class="text-muted">&nbsp;hl</small></strong></div>
              <div class="col-md-3"><?php echo ($lot->destination_type)? ConditionnementClient::$lotDestinationsType[$lot->destination_type] : ''; echo ($lot->destination_date)? " (".Date::francizeDate($lot->destination_date).")" : ""; ?></div>
              <div class="col-md-1" >
              </div>
            </div>
          </div>
          <div class="row"></div>
      </div>
    <?php endforeach; ?>
    <?php foreach($form['lots'] as $key => $lot): ?>
        <?php $lotItem = $conditionnement->lots->get($key); ?>
        <?php if($key == count($form['lots']) - 1): ?>
          <a name="dernier"></a>
        <?php endif; ?>
        <div class="panel panel-default bloc-lot">
            <div class="panel-body" style="padding-bottom: 0;">
              <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">

                            <?php echo $lot['produit_hash']->renderLabel("Produit", array('class' => "col-sm-3 control-label")); ?>
                            <div class="col-sm-9">
                                  <?php echo $lot['produit_hash']->render(array("data-placeholder" => "Sélectionnez un produit", "class" => "form-control select2 select2-offscreen select2autocomplete")); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                      <button type="button" tabindex="-1" class="close lot-delete" title="Supprimer ce lot" aria-hidden="true">×</button>
                        <div class="form-group">
                          <div class="col-sm-7">
                            <div class="checkbox checkboxlots">
                              <label>
                                <input type="checkbox" <?php echo (count($lotItem->cepages->toArray(true, false)))? 'checked="checked"' : '' ?>
                                       id="lien_<?php echo $lot->renderId() ?>_cepages" data-toggle="modal"
                                       data-target="#<?php echo $lot->renderId() ?>_cepages" />
                                <span class="checkboxtext_<?php echo $lot->renderId() ?>_cepages"><?php echo (count($lotItem->cepages->toArray(true, false))) ? "Assemblages : " :  "Assemblage" ?></span></label>
                              </div>

                            </div>
                            <div class="col-sm-2" data-toggle = "tooltip" title = "Vous pouvez laisser à vide ce champs millésime.">
                                  <?php echo $lot['millesime']->render(array('data-default-value' => $conditionnement->getCampagne())); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <?php echo $lot['numero_logement_operateur']->renderLabel("Numéro lot", array('class' => "col-sm-3 control-label")); ?>
                      <div class="col-sm-3">
                            <?php echo $lot['numero_logement_operateur']->render(); ?>
                      </div>
                      <div class="col-sm-6 text-danger">
                            <?php echo $lot['numero_logement_operateur']->renderError(); ?>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <div class="col-sm-12">
                        <p>Vous pouvez laisser à vide le champs millésime.</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                      <div class="form-group">
                          <?php echo $lot['centilisation']->renderLabel("Conditionnement Centilisation", array('class' => "col-sm-3 control-label")); ?>
                          <div class="col-sm-9">
                                <?php echo $lot['centilisation']->render(array("data-placeholder" => "Sélectionnez une centilisation", "class" => "form-control select2 select2-offscreen select2autocomplete")); ?>
                          </div>
                          <div class="col-sm-6 text-danger">
                                <?php echo $lot['centilisation']->renderError(); ?>
                          </div>
                      </div>
                  </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <?php echo $lot['volume']->renderLabel("Volume</br>global", array('class' => "col-sm-4 control-label")); ?>
                            <div class="col-sm-5">
                                <div class="input-group">
                                    <?php echo $lot['volume']->render(); ?>
                                    <div class="input-group-addon">hl</div>
                                </div>
                            </div>
                            <div class="col-sm-6 text-danger">
                                  <?php echo $lot['volume']->renderError(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <?php if(DRevConfiguration::getInstance()->hasSpecificiteLot()): ?>
                      <div class="col-md-6">
                          <div class="form-group">
                              <?php echo $lot['specificite']->renderLabel("Spécificité", array('class' => "col-sm-3 control-label")); ?>
                              <div class="col-sm-9">
                                    <?php echo $lot['specificite']->render(); ?>
                              </div>
                              <div class="col-sm-6 text-danger">
                                    <?php echo $lot['specificite']->renderError(); ?>
                              </div>
                          </div>
                      </div>
                    <?php endif ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <?php echo $lot['destination_date']->renderLabel("Date de conditionnement", array('class' => "col-sm-4 control-label")); ?>
                            <div class="col-sm-5">
                                <div class="input-group date-picker">
                                    <?php echo $lot['destination_date']->render(array('placeholder' => "Date")); ?>
                                    <div class="input-group-addon"><span class="glyphicon-calendar glyphicon"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade modal_lot_cepages" data-lot=<?php echo $key ?> id="<?php echo $lot->renderId() ?>_cepages" role="dialog" aria-labelledby="Répartition des cépages" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="myModalLabel">Répartition des cépages</h4>
                        <h5>Déclarer seulement les cépages qui figureront sur l'étiquette </h5>
                    </div>
                    <div class="modal-body">
                        <?php for($i=0; $i < ConditionnementLotForm::NBCEPAGES; $i++): ?>
                            <div class="form-group ligne_lot_cepage">
                                <div class="col-sm-1"></div>
                                <div class="col-sm-7">
                                    <?php echo $lot['cepage_'.$i]->render(array("data-placeholder" => "Séléctionnez un cépage", "class" => "form-control select2 select2-offscreen select2autocomplete")); ?>
                                </div>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <?php echo $lot['repartition_'.$i]->render(); ?>
                                        <div class="input-group-addon">hl</div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-default btn pull-left" data-dismiss="modal">Fermer</a>
                        <a class="btn btn-success btn pull-right" data-dismiss="modal">Valider</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="text-right">
        <button type="submit" name="submit" value="add" class="btn btn-primary"><span class="glyphicon glyphicon-plus-sign"></span> Ajouter un lot</button>
    </div>
    <div style="margin-top: 20px;" class="row row-margin row-button">
        <div class="col-xs-4">
            <a tabindex="-1" href="<?php echo  url_for('conditionnement_exploitation', $conditionnement)  ?>?prec=1" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retourner à l'étape précédente</a>
        </div>
        <div class="col-xs-4 text-center">
        </div>
        <div class="col-xs-4 text-right">
            <button type="submit" class="btn btn-primary btn-upper">Valider et continuer <span class="glyphicon glyphicon-chevron-right"></span></button>
        </div>
    </div>
</form>
