<style>
  #declassement_filigrane{
    position:absolute;
    font-size: 4em;
    top: 22%;
    left: 37%;
    rotate: -33deg;
    opacity: 0.3;
    text-transform: uppercase;
  }
  .block-chgtDenom{
    background-color: #f8f8f8;
    border: 1px solid #e7e7e7;
  }
</style>
<?php include_partial('infoLotOrigine', array('lot' => $chgtDenom->getLotOrigine(), 'opacity' => true)); ?>

<div class="col-sm-12 mb-5">
  <div class="text-center">
    <strong>Devient</strong><br />
    <span class="glyphicon glyphicon-chevron-down"></span>
  </div>
</div>

<?php
  foreach($chgtDenom->lots as $k => $lot):
?>
  <div class="alert block-chgtDenom col-sm-<?php if (count($chgtDenom->lots) == 1): ?>12<?php else: ?>6<?php endif; ?>">
  <?php if($chgtDenom->changement_type == ChgtDenomClient::CHANGEMENT_TYPE_DECLASSEMENT && $lot->statut == Lot::STATUT_DECLASSE): ?>
    <div id="declassement_filigrane" class="text-danger">Déclassé</div>
  <?php endif; ?>
    <h4>Dossier n°<strong><?php echo $lot->numero_dossier; ?></strong> – Lot n°<strong><?php echo $lot->numero_archive; ?></strong><?php if($chgtDenom->isValidee()): ?><a href="<?php echo url_for('degustation_etablissement_list',array('identifiant' => $lot->declarant_identifiant))."#".$lot->numero_dossier.$lot->numero_archive; ?>" class="btn btn-default btn-xs pull-right">visu du lot&nbsp;<span class="glyphicon glyphicon-chevron-right"></span></a><?php endif; ?></h4>
    <table class="table table-condensed" style="margin: 0;">
      <tbody>
        <tr>
          <td>
            <div>
              <div style="border: none;" class="m-3">
                Logement :
                <?php if(!$chgtDenom->isValide()): ?>
                  <a href="#" data-toggle="modal" data-target="#modal_lot_<?php echo $k ?>">
                    <strong><?php echo $lot->numero_logement_operateur; ?></strong>&nbsp;<span class="glyphicon glyphicon-edit">&nbsp;</span>
                  </a>
                <?php else: ?>
                  <strong><?php echo $lot->numero_logement_operateur; ?></strong>
                <?php endif; ?>
              </div>

              <?php if($chgtDenom->changement_type == ChgtDenomClient::CHANGEMENT_TYPE_CHANGEMENT): ?>
              <div style="border: none;" class="m-3">
                Produit : <strong><?php echo showProduitLot($lot) ?></strong>
              </div>
              <?php endif; ?>

              <div style="border: none;" class="m-3">Volume : <strong><?php echoFloat($lot->volume); ?></strong>&nbsp;<small class="text-muted">hl</small></div>
            </div>
          </td>
          <td>
            <?php if ($sf_user->isAdmin() && $chgtDenom->changement_type == ChgtDenomClient::CHANGEMENT_TYPE_CHANGEMENT): ?>
              <div class="text-center">
                <?php if(isset($form['lots'])): ?>
                <div style="margin-bottom: 0;" class="<?php if($form['lots'][$lot->getKey()]->hasError()): ?>has-error<?php endif; ?>">
                  <?php echo $form['lots'][$lot->getKey()]['affectable']->renderError() ?>
                    <div class="col-xs-12">
                      <?php if ($sf_user->isAdmin() && !$chgtDenom->validation_odg): ?>
                        <span>Dégustable&nbsp;&nbsp; :</span>
                        <?php echo $form['lots'][$lot->getKey()]['affectable']->render(array('class' => "chgtDenom bsswitch", "data-preleve-adherent" => "$lot->numero_dossier", "data-preleve-lot" => "$lot->numero_logement_operateur",'data-size' => 'small', 'data-on-text' => "<span class='glyphicon glyphicon-ok-sign'></span>", 'data-off-text' => "<span class='glyphicon'></span>", 'data-on-color' => "success")); ?>
                      <?php else: ?>
                          <span>Dégustable&nbsp;:&nbsp;&nbsp;</span>
                          <span class="<?php if($lot->affectable):?> glyphicon glyphicon-ok-sign <?php else:?>glyphicon glyphicon-ban-circle <?php endif; ?>"></span>
                      <?php endif; ?>
                    </div>
                </div>
              <?php else: ?>
                <div style="margin-bottom: 0;" class="">
                  <div class="col-xs-12">
                      <span>Dégustable&nbsp;:&nbsp;&nbsp;</span>
                      <span class="<?php if($lot->affectable):?> glyphicon glyphicon-ok-sign <?php else:?>glyphicon glyphicon-ban-circle <?php endif; ?>"></span>
                  </div>
                </div>
              <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>

      </tbody>
    </table>
  </div>
<?php endforeach; ?>
