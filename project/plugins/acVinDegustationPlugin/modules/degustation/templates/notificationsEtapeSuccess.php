<?php use_helper("Date"); ?>
<?php use_helper('Float') ?>
<?php use_helper('Lot') ?>

<?php include_partial('degustation/breadcrumb', array('degustation' => $degustation)); ?>

<?php include_partial('degustation/step', array('degustation' => $degustation, 'active' => DegustationEtapes::ETAPE_NOTIFICATIONS)); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success" role="alert"><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif; ?>

<div class="page-header no-border">
  <h2>Notifications pour les opérateurs</h2>
  <h3><?php echo ucfirst(format_date($degustation->date, "P", "fr_FR"))." à ".format_date($degustation->date, "H")."h".format_date($degustation->date, "mm") ?> <small><?php echo $degustation->getLieuNom(); ?></small></h3>
</div>
<div class="row row-condensed">
  <div class="col-xs-12">
    <div class="panel panel-default">
      <div class="panel-body">

        <div class="row row-condensed">
          <div class="col-xs-12">
            <h3>Échantillons par opérateurs</h3>
            <table class="table table-bordered table-condensed">
              <thead>
                <tr>
                  <th class="col-xs-3 text-left">Opérateur</th>
                  <th class="col-xs-5 text-left">Echantillons dégustés</th>
                  <th class="col-xs-2 text-left">Notifications</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($degustation->getLotsByOperateurs() as $identifiant => $lots): ?>
                    <tr>
                      <td><?= $lots[0]->declarant_nom ?></td>
                      <td>
                        <?php foreach ($lots as $lot): ?>
                        <span data-toggle="tooltip"
                              data-html="true"
                              title="<?= strip_tags(showProduitLot($lot) . "<br>" . $lot->getShortLibelleConformite(), '<br>')?>"
                              class="label label-<?= ($lot->isNonConforme()) ? 'danger' : 'success'?>">
                                <span class="glyphicon glyphicon-<?= ($lot->isNonConforme()) ? 'remove' : 'ok' ?>"></span>
                        </span>&nbsp;
                        <?php endforeach; ?>
                      </td>
                      <td>
                        <?php if (true): ?>
                        <div class="btn-group">
                          <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Notifier <span class="caret"></span>
                          </button>
                          <ul class="dropdown-menu text-left">
                            <li>
                                <a class="btn" href="#" class="link-mail-auto"
                                  data-retour="<?php echo url_for('degustation_envoi_mail_resultats', array('id' => $degustation->_id, 'identifiant' => $lots[0]->declarant_identifiant)); ?>">
                                  <i class="glyphicon glyphicon-envelope"></i>&nbsp;Envoyer par mail
                              </a>
                            </li>
                            <li>
                              <a href="<?php echo url_for('degustation_mail_resultats_previsualisation', array('id' => $degustation->_id, 'identifiant' => $lots[0]->declarant_identifiant)); ?>" class="btn">
                                  <i class="glyphicon glyphicon-eye-open"></i>&nbsp;Prévisualiser
                              </a>
                            </li>
                          </ul>
                        </div>
                        <?php else: ?>
                            /** Mail envoyé */
                            /** <a href="<?php echo url_for('degustation_mail_resultats_previsualisation',array('id' => $degustation->_id, 'identifiant' => $lot->declarant_identifiant)); ?>" class="btn btn-default btn-sm disabled">
                                <i class="glyphicon glyphicon-send"></i>&nbsp;&nbsp;<?php echo format_date($conformitesLots->email_envoye, "dd/MM/yyyy")." à ".format_date($conformitesLots->email_envoye, "H")."h".format_date($conformitesLots->email_envoye, "mm"); ?>
                            </a>
                          <br/><a href="<?php echo url_for('degustation_envoi_mail_resultats',array('id' => $degustation->_id, 'identifiant' => $lot->declarant_identifiant,'envoye' => 0)); ?>" ><small>Remettre en non envoyé</small></a>
                          **/
                        <?php endif ?>
                      </td>
                    </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

              <div class="row row-margin row-button">
                <div class="col-xs-4"><a href="<?php echo url_for("degustation_resultats_etape", $degustation) ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
                <div class="col-xs-4 text-center">
                </div>
                <div class="col-xs-4 text-right">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php
  if(isset($popup)):
    include_partial('degustation/previewMailPopup', array('degustation' => $degustation, 'etablissement' => $etablissement, 'lots' => $lots));
 endif;
  ?>
