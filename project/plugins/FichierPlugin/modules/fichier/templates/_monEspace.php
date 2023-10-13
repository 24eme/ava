<?php use_helper('Date'); ?>

<?php if (!$sf_user->hasDrevAdmin() && DRConfiguration::getInstance()->hasVisuTeledeclaration() === false): ?>
    <?php return; ?>
<?php endif; ?>
<?php if (class_exists("DRClient") && ($etablissement->famille == EtablissementFamilles::FAMILLE_PRODUCTEUR  || $etablissement->famille == EtablissementFamilles::FAMILLE_PRODUCTEUR_VINIFICATEUR) && in_array('dr', sfConfig::get('sf_enabled_modules'))): ?>
<div class="col-sm-6 col-md-4 col-xs-12">
    <div class="block_declaration panel <?php if($dr):?>panel-success<?php else: ?>panel-default<?php endif; ?>">
        <div class="panel-heading">
            <h3 class="panel-title">
                DR  <?php echo $periode; ?>
<?php if ( $sf_user->isAdmin() && $dr && $dr->isValideeOdg() ): ?>
                <span class="pull-right"><span class="glyphicon glyphicon-ok-circle"></span></span>
<?php endif; ?>
            </h3>
        </div>
            <div class="panel-body">
                <p class="explications">Espace de saisie de la Déclaration de Récolte pour le déclarant.</p>
                <div class="actions">
                    <?php if(!$dr): ?>
                        <a class="btn btn-default btn-block" href="<?php echo url_for('scrape_fichier', array('sf_subject' => $etablissement, 'periode' => $periode, 'type' => DRClient::TYPE_MODEL)) ?>"><span class="glyphicon glyphicon-cloud-download"></span>&nbsp;&nbsp;Importer depuis Prodouane</a>
                    <?php else: ?>
                        <a class="btn btn-block btn-default" href="<?php echo url_for('dr_visualisation', $dr) ?>">
                            <span class="glyphicon glyphicon-file"></span> Visualiser la synthèse de la DR
                        </a>
                	<?php endif; ?>
                    <a class="btn btn-xs btn-block btn-default" href="<?php echo ($dr)? url_for('edit_fichier', $dr) : url_for('new_fichier', array('sf_subject' => $etablissement, 'periode' => $periode, 'type' => DRClient::TYPE_MODEL)); ?>"><span class="glyphicon glyphicon-pencil"></span> <?php echo ($dr)? ($dr->exist('donnees'))? 'Poursuivre les modifications' : 'Modifier la déclaration' : 'Saisir la déclaration'; ?></a>
                </div>
            </div>
            <div class="panel-footer" style="padding-top: 0; padding-bottom: 0;">
                <a href="<?php echo url_for('pieces_historique', array('sf_subject' => $etablissement, 'categorie' => 'dr')) ?>" class="btn btn-xs btn-link btn-block text-muted">Voir tous les documents</a>
            </div>
    </div>
</div>
<?php endif; ?>
<?php if (class_exists("SV11Client") && class_exists("SV12Client") && ($etablissement->famille == EtablissementFamilles::FAMILLE_NEGOCIANT  || $etablissement->famille == EtablissementFamilles::FAMILLE_NEGOCIANT_VINIFICATEUR  || $etablissement->famille == EtablissementFamilles::FAMILLE_COOPERATIVE) && in_array('drev', sfConfig::get('sf_enabled_modules'))): ?>
<div class="col-sm-6 col-md-4 col-xs-12">
    <div class="block_declaration panel <?php if($sv):?>panel-success<?php else: ?>panel-default<?php endif; ?>">
        <div class="panel-heading">
            <h3 class="panel-title">
                Déclaration de production  <?php echo $periode; ?>
                <?php if ( $sf_user->isAdmin() && $sv && $sv->isValideeOdg() ): ?>
                    <span class="pull-right"><span class="glyphicon glyphicon-ok-circle"></span></span>
                <?php endif; ?>
            </h3>
        </div>
            <div class="panel-body">
                <p class="explications">Espace de récupération de la Déclaration de production pour le déclarant.</p>
                <div class="actions">
                  <?php if($sv): ?>
                      <a class="btn btn-block btn-default" href="<?php echo url_for('dr_visualisation', $sv) ?>">
                          <span class="glyphicon glyphicon-file"></span> Visualiser la synthèse du document
                      </a>
                	<?php else: ?>
                	<a class="btn btn-default btn-block" href="<?php echo url_for('scrape_fichier', array('sf_subject' => $etablissement, 'periode' => $periode, 'type' => ($etablissement->famille == EtablissementFamilles::FAMILLE_COOPERATIVE) ? 'SV11' : 'SV12')) ?>"><span class="glyphicon glyphicon-cloud-download"></span>&nbsp;&nbsp;Importer depuis Prodouane</a>
                	<?php endif; ?>
                </div>
            </div>
            <div class="panel-footer" style="padding-top: 0; padding-bottom: 0;">
                <a href="<?php echo url_for('pieces_historique', array('sf_subject' => $etablissement, 'categorie' => ($etablissement->famille == EtablissementFamilles::FAMILLE_COOPERATIVE) ? 'sv11' : 'sv12')) ?>" class="btn btn-xs btn-link btn-block text-muted">Voir tous les documents</a>
            </div>
    </div>
</div>
<?php endif; ?>
