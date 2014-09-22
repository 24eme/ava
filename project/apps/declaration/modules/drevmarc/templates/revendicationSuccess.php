<?php include_partial('drevmarc/step', array('step' => 'revendication', 'drevmarc' => $drevmarc)) ?>
<div class="page-header">
    <h2>Revendication</h2>
</div>

<form role="form" action="<?php echo url_for("drevmarc_revendication", $drevmarc) ?>" method="post" class="ajaxForm" id="form_drevmarc_<?php echo $drevmarc->_id; ?>" >
    <div class="frame">	
        <?php echo $form->renderHiddenFields() ?>
        <?php echo $form->renderGlobalErrors() ?>

    <p></p>	
    <div class="row">
        <div class="col-xs-11">
            <table class="table table-striped ">
                <tbody>
                    <tr>
                        <td  class="col-xs-5">
                            <label class="control-label" for="">Période de distillation :</label>
                        </td>
                        <td class="col-xs-7 form-inline">
                            <div class="form-group">
                                <div class="col-xs-1">
                                    <?php echo $form['debut_distillation']->render(array('class' => 'datepicker-control date-picker text-right', 'placeholder' => 'Du')); ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-1">
                                    <?php echo $form['fin_distillation']->render(array('class' => 'datepicker-control date-picker text-right', 'placeholder' => 'Au')); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php
                    $periode_distillation_error = false;
                    foreach ($form->getFormFieldSchema() as $key => $item):
                        if ($item instanceof sfFormField && $item->hasError()):
                            $periode_distillation_error = ($item->renderId() == "drevmarc_revendication_debut_distillation") || ($item->renderId() == "drevmarc_revendication_fin_distillation");
                            if($periode_distillation_error):
                                break;
                            endif;
                        endif;
                    endforeach;
                    if ($periode_distillation_error):
                        ?>
                        <tr>
                            <td class="col-xs-5"></td>
                            <td class="col-xs-7 form-inline">
                                <div class="form-group">
                                    <div class="col-xs-11">                                        
                                        <span class="text-danger"><?php echo $form['debut_distillation']->renderError(); ?></span>
                                        <span class="text-danger"><?php echo $form['fin_distillation']->renderError(); ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                        <?php endif; ?> 
                        <td  class="col-xs-5">
                            <?php echo $form['qte_marc']->renderLabel(null, array('class' => 'control-label')); ?>
                        </td>
                        <td class="col-xs-7 form-inline">
                            <div class="form-group">
                                <div class="col-xs-11">                            
                                    <?php echo $form['qte_marc']->render(array('class' => 'form-control input-rounded text-right', 'placeholder' => 'En kg (minimum 50kg)')); ?>
                                    <span class="text-danger"><?php echo $form['qte_marc']->renderError(); ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td  class="col-xs-5">
                            <?php echo $form['volume_obtenu']->renderLabel(null, array('class' => 'control-label')); ?>
                        </td>
                        <td class="col-xs-7 form-inline">
                            <div class="form-group ">
                                <div class="col-xs-11">
                                    <?php echo $form['volume_obtenu']->render(array('class' => 'form-control input-rounded text-right', 'placeholder' => 'En hl d\'alcool pur')); ?>
                                    <span class="text-danger"><?php echo $form['volume_obtenu']->renderError(); ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td  class="col-xs-5">
                            <?php echo $form['titre_alcool_vol']->renderLabel(null, array('class' => 'control-label')); ?>
                        </td>
                        <td class="col-xs-7 form-inline">
                            <div class="form-group">
                                <div class="col-xs-11">
                                    <?php echo $form['titre_alcool_vol']->render(array('class' => 'form-control input-rounded text-right', 'placeholder' => 'En ° (minimum 40°)')); ?>
                                    <span class="text-danger"><?php echo $form['titre_alcool_vol']->renderError(); ?></span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-xs-2"></div>
    </div>
    <div class="row row-margin">
        <div class="col-xs-4"><a href="<?php echo url_for("drevmarc_exploitation", $drevmarc) ?>" class="btn btn-primary btn-lg btn-block"><span class="eleganticon arrow_carrot-left pull-left"></span>Étape précédente</a></div>
        <div class="col-xs-4 col-xs-offset-4"><button type="submit" class="btn btn-primary btn-lg btn-block"><span class="eleganticon arrow_carrot-right pull-right"></span>Étape suivante</button></div>
    </div>
</form>