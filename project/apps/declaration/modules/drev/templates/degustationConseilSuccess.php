<?php include_partial('drev/step', array('step' => 'degustation_conseil', 'drev' => $drev)) ?>

<div class="page-header">
    <h2>Dégustation conseil <small>Réaliser par l'AVA</small></h2>
</div>

<?php include_partial('drev/stepDegustationConseil', array('step' => 'prelevement', 'drev' => $drev)) ?>

<form method="post" action="" role="form" class="form-horizontal ajaxForm">
    
    <div class="row">
        <div class="col-xs-7">
            <p>Vin prêt à être dégusté ou plus proche de la commercialisation...</p>
            <?php echo $form->renderHiddenFields(); ?>
            <?php echo $form->renderGlobalErrors(); ?>
            <?php if(isset($form[DRev::CUVE_ALSACE])): ?>
            <div class="row-margin">
                <h3>AOC Alsace</h3>
                <div class="col-xs-offset-1">
                    <p>Semaine à partir de laquelle le vin est prêt à être dégusté :</p>
                    <div class="form-group <?php if($form[DRev::CUVE_ALSACE]["date"]->hasError()): ?>has-error<?php endif; ?>">
                        <?php echo $form[DRev::CUVE_ALSACE]["date"]->renderError(); ?>
                        <?php echo $form[DRev::CUVE_ALSACE]["date"]->renderLabel(null, array("class" => "col-xs-5 control-label")); ?>
                        <div class="col-xs-7">
                            <div class="input-group date-picker">
                                <?php echo $form[DRev::CUVE_ALSACE]["date"]->render(array("class" => "form-control")); ?>
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-calendar"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if(isset($form[DRev::CUVE_VTSGN])): ?>
            <div class="row-margin">
                <h3>VT / SGN</h3>
                <div class="col-xs-offset-1">
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                              <input name="<?php echo $form["vtsgn_demande"]->renderName() ?>" value="<?php echo $form["vtsgn_demande"]->getValue() ?>" type="checkbox" <?php if($form[DRev::CUVE_VTSGN]["date"]->getValue()): ?>checked="checked"<?php endif; ?> class="checkbox-relation" data-relation="#degustation_conseil_cuve_vtsgn_date_form_group" /> Demande de prélévement volontaire des VT / SGN
                            </label>
                        </div>
                    </div>
                    <div id="degustation_conseil_cuve_vtsgn_date_form_group" class="form-group <?php if(!$form[DRev::CUVE_VTSGN]["date"]->getValue()): ?>hidden<?php endif; ?> <?php if($form[DRev::CUVE_VTSGN]["date"]->hasError()): ?>has-error<?php endif; ?>">
                        <?php echo $form[DRev::CUVE_VTSGN]["date"]->renderError(); ?>
                        <?php echo $form[DRev::CUVE_VTSGN]["date"]->renderLabel(null, array("class" => "col-xs-5 control-label")); ?>
                        <div class="col-xs-7">
                            <?php echo $form[DRev::CUVE_VTSGN]["date"]->render(array("class" => "form-control")); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-xs-4 col-xs-offset-1">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h2 class="panel-title">Lieu de prélèvement</h2>
                </div>
                <div class="panel-body form-chai">
                    <?php $chai = $drev->chais->get(DRev::CUVE) ?>
                    <?php if(!$formPrelevement): ?>
                    <p>
                        <?php echo $chai->adresse ?><br />
                        <?php echo $chai->code_postal ?> <?php echo $chai->commune ?>
                    </p>
                    <?php endif; ?>
                    <?php if(isset($form['chai'])): ?>
                        <div class="form-group <?php if(!$formPrelevement): ?>hidden<?php endif; ?>">
                            <?php echo $form["chai"]->renderError(); ?>
                            <?php echo $form["chai"]->render(array("class" => "form-control")); ?>
                        </div>
                        <?php if(!$formPrelevement): ?>
                        <div class="row-margin text-right">
                            <button type="button" class="btn btn-sm btn-warning">Modifier</button>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-margin row-button">
        <div class="col-xs-6">
            <a href="<?php echo url_for("drev_revendication", $drev) ?>" class="btn btn-primary btn-lg btn-upper"><span class="eleganticon arrow_carrot-left"></span>&nbsp;&nbsp;Retourner <small>à l'étape précédente</small></a>
        </div>
        <div class="col-xs-6 text-right">
            <button type="submit" class="btn btn-default btn-lg btn-upper">Valider <small>et répartir les lots</small>&nbsp;&nbsp;<span class="eleganticon arrow_carrot-right"></span></button>
        </div>
    </div>
</form>



