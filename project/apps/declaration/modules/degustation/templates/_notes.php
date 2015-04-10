<?php use_javascript("degustation.js", "last") ?>
<?php use_helper("Date"); ?>
<?php use_helper('Degustation') ?>

<h2>Notes obtenues&nbsp;<div class="btn btn-default btn-sm"><?php echo count($tournee->getNotes()); ?>&nbsp;vins dégustés</div>

<?php if($tournee->statut == TourneeClient::STATUT_TERMINE): ?>
<a class="pull-right btn btn-link" href="<?php echo url_for("degustation_courriers_papier", $tournee) ?>"><span class="glyphicon glyphicon-file"></span>&nbsp;Courriers papiers à envoyer</a>
<?php endif; ?>
    </h2>

<?php $notes = $tournee->getNotes(); ?>
<?php $hasForm = isset($form) && $form; ?>
<div class="row">    
    <div class="col-xs-12">        
        <table class="table table-striped table-condensed">
            <tr>
                <th style="col-xs-1">N°</th>            
                <th class="col-xs-3">Opérateur</th> 
                <th class="col-xs-2">Produit</th> 
                <th class="col-xs-4">Notes et Appreciation</th> 
                <th class="col-xs-2">Courrier</th> 
            </tr>
            <?php foreach ($tournee->getNotes() as $note): ?>
                <tr>
                    <td><?php echo $note->prelevement->anonymat_degustation; ?></td>
                    <td><?php echo $note->operateur->raison_sociale; ?><br />
                    <small class="text-muted"><?php echo $note->operateur->cvi ?></small><br />
                    <small class="text-muted"><?php echo $note->operateur->commune ?></small>
                    </td> 
                    <td><?php echo $note->prelevement->libelle; ?></td> 
                    <td>
                        <ul style="margin: 0; padding: 0" >
                            <?php
                            foreach ($note->prelevement->notes as $noteType => $noteQualifie):
                                $defautsStr = "";
                                foreach ($noteQualifie->defauts as $key => $defaut):
                                    $defautsStr .= $defaut;
                                    if ($key != count($noteQualifie->defauts) - 1):
                                        $defautsStr .= ', ';
                                    endif;
                                endforeach;
                                ?>
                                <li>
                                    <span class="<?php echo ($noteQualifie->isMauvaiseNote()) ? "bg-danger text-danger" : ""; ?>">
                                    <?php
                                    echo DegustationClient::$note_type_libelles[$noteType] . ' : <strong class="pull-right">' . $noteQualifie->note . '</strong>';
                                    if (count($noteQualifie->defauts)):
                                        echo "<br/><small class='text-muted'>" . $defautsStr . "</small>";
                                    else:
                                       echo "<br/><small class='text-muted'>-</small>"; 
                                    endif;
                                    ?> 
                                    </span>
                                </li>                                
                            <?php endforeach; ?>
                        </ul>
                        <i class="text-muted"><?php echo $note->prelevement->appreciations; ?></i>
                    </td> 
                    <td class="text-center">
                        <?php if ($hasForm): ?>
                            <div class="type_courrier_for_visite" id="<?php echo $note->operateur->cvi.$note->prelevement->getHashForKey(); ?>">
                               
                                    <?php echo $form[$note->operateur->cvi.$note->prelevement->getHashForKey()]->renderError(); ?>
                                    <?php echo $form[$note->operateur->cvi.$note->prelevement->getHashForKey()]->render(array('class' => 'form-control select2')); ?>
                                <div id="<?php echo 'visite_date_degustation_courrier_' . $note->operateur->cvi.$note->prelevement->getHashForKey(); ?>" style="<?php echo ($note->prelevement->exist('type_courrier') && $note->prelevement->type_courrier == DegustationClient::COURRIER_TYPE_VISITE)? '' : 'display:none;' ?>" >
                                <div style="padding-top: 10px;" class="input-group date-picker" >
                                        <?php echo $form['visite_date_' . $note->operateur->cvi.$note->prelevement->getHashForKey()]->renderError(); ?>
                                        <?php echo $form['visite_date_' . $note->operateur->cvi.$note->prelevement->getHashForKey()]->render(array('class' => 'form-control')); ?>
                                        <div class="input-group-addon">
                                            <span class="glyphicon-calendar glyphicon"></span>
                                        </div>
                                    </div>
                                    <div style="padding-top: 10px;" class="input-group date-picker-time" >
                                        <?php echo $form['visite_heure_' . $note->operateur->cvi.$note->prelevement->getHashForKey()]->renderError(); ?>
                                        <?php echo $form['visite_heure_' . $note->operateur->cvi.$note->prelevement->getHashForKey()]->render(array('class' => 'form-control')); ?>
                                        <div class="input-group-addon">
                                            <span class="glyphicon glyphicon-time"></span>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        <?php else: ?>        
                            <?php if ($note->prelevement->exist('type_courrier') && $note->prelevement->type_courrier): ?>
                                <a href="<?php echo url_for('degustation_courrier_prelevement', $note->prelevement) ?>">
                                <?php endif; ?>
                                <?php echo getTypeCourrier($note->prelevement); ?>
                                <?php if ($note->prelevement->exist('type_courrier') && $note->prelevement->type_courrier): ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>