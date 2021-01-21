<?php use_helper('Float') ?>
<?php use_helper('Version') ?>

<?php if ($drev->exist('achat_tolerance') && $drev->get('achat_tolerance')): ?>
  <div class="alert alert-info" role="alert">
    <p>Les volumes récoltés ont fait l'objet d'achats réalisés dans le cadre de la tolérance administrative ou sinistre climatique.</p>
  </div>
<?php endif; ?>

<?php if(count($drev->getProduitsWithoutLots())): ?>
  <h3>Revendication AOP</h3>

  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <?php if (($drev->getDocumentDouanierType() == DRCsvFile::CSV_TYPE_DR) || ($drev->getDocumentDouanierType() == SV11CsvFile::CSV_TYPE_SV11)): ?>
          <th class="col-xs-4"><?php if (count($drev->declaration->getProduitsWithoutLots()) > 1): ?>Produits revendiqués<?php else: ?>Produit revendiqué<?php endif; ?></th>
            <th class="col-xs-2 text-center">Superficie revendiquée&nbsp;<small class="text-muted">(ha)</small></th>
            <th class="col-xs-2 text-center">Volume millesime <?php echo $drev->campagne-1 ?> issu du VCI&nbsp;<small class="text-muted">(hl)</small></th>
            <th class="col-xs-2 text-center">Volume issu de la récolte <?php echo $drev->campagne ?>&nbsp;<small class="text-muted">(hl)</small></th>
            <th class="col-xs-2 text-center">Volume revendiqué net total&nbsp;<?php if($drev->hasProduitWithMutageAlcoolique()): ?><small>(alcool compris)</small>&nbsp;<?php endif; ?><small class="text-muted">(hl)</small></th>
          <?php else: ?>
            <th class="col-xs-6"><?php if (count($drev->declaration->getProduitsWithoutLots()) > 1): ?>Produits revendiqués<?php else: ?>Produit revendiqué<?php endif; ?></th>
              <th class="col-xs-2 text-center">Superficie revendiquée&nbsp;<small class="text-muted">(ha)</small></th>
              <th class="col-xs-2 text-center">Volume issu de la récolte <?php echo $drev->campagne ?>&nbsp;<small class="text-muted">(hl)</small></th>
              <th class="col-xs-2 text-center">Volume revendiqué net total&nbsp;<?php if($drev->hasProduitWithMutageAlcoolique()): ?><small>(alcool compris)</small>&nbsp;<?php endif; ?><small class="text-muted">(hl)</small></th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($drev->declaration->getProduitsWithoutLots() as $produit) : ?>
            <tr>
              <td><?php echo $produit->getLibelleComplet() ?><?php if($produit->isValidateOdg()): ?>&nbsp;&nbsp;&nbsp;<span class="glyphicon glyphicon-ok" ></span><?php endif ?><small class="pull-right <?php if($produit->getRendementEffectif() > $produit->getConfig()->getRendement()): ?>text-danger<?php endif; ?>">&nbsp;<?php echoFloat(round($produit->getRendementEffectif(), 2)); ?> hl/ha</small></td>
              <td class="text-right <?php echo isVersionnerCssClass($produit, 'superficie_revendique') ?>"><?php if($produit->superficie_revendique): ?><?php echoFloat($produit->superficie_revendique) ?> <small class="text-muted">ha</small><?php endif; ?></td>
              <?php if (($drev->getDocumentDouanierType() == DRCsvFile::CSV_TYPE_DR) || ($drev->getDocumentDouanierType() == SV11CsvFile::CSV_TYPE_SV11)): ?>
                <td class="text-right <?php echo isVersionnerCssClass($produit, 'volume_revendique_issu_vci') ?>"><?php if($produit->volume_revendique_issu_vci): ?><?php echoFloat($produit->volume_revendique_issu_vci) ?> <small class="text-muted">hl</small><?php endif; ?></td>
              <?php endif; ?>
              <td class="text-right <?php echo isVersionnerCssClass($produit, 'volume_revendique_issu_recolte') ?>"><?php if($produit->volume_revendique_issu_recolte): ?><?php echoFloat($produit->volume_revendique_issu_recolte) ?> <small class="text-muted">hl</small><?php endif; ?></td>
              <td class="text-right <?php echo isVersionnerCssClass($produit, 'volume_revendique_total') ?>"><?php if($produit->volume_revendique_total): ?><?php echoFloat($produit->volume_revendique_total) ?> <small class="text-muted">hl</small><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php $bailleurs = $drev->getBailleurs()->getRawValue(); ?>
      <?php if(count($bailleurs)): ?>
        <p style="margin-top: -10px; margin-bottom: 20px;">
          Une partie des volumes ont été récoltés pour le compte <?php if(count($bailleurs) > 1): ?>des<?php else: ?>du<?php endif; ?> bailleur<?php if(count($bailleurs) > 1): ?>s :<?php endif; ?>
            <?php $extra = '' ; foreach($bailleurs as $b): ?>
              <?php  if ($b['etablissement_id'] && $sf_user->hasDrevAdmin()) echo "<a href='".url_for('declaration_etablissement', array('identifiant' => $b['etablissement_id'], 'campagne' => $drev->campagne))."'>" ; ?>
                <?php echo $extra.$b['raison_sociale']; $extra = ', '; ?>
                <?php  if ($b['etablissement_id'] && $sf_user->hasDrevAdmin()) echo " (son espace) </a>"; ?>
              <?php endforeach; ?>.
              Ces volumes seront directement revendiqués par ce<?php if(count($bailleurs) > 1): ?>s<?php endif; ?> bailleur<?php if(count($bailleurs) > 1): ?>s<?php endif; ?>.
            </p>
          <?php endif; ?>
        <?php endif; ?>
        <?php if($drev->exist('lots')): ?>


            <?php
                $lots = $drev->getLotsByCouleur();
                $lotsHorsDR = $drev->getLotsHorsDR();
                $synthese_revendication = $drev->summerizeProduitsLotsByCouleur();
                ?>
              <?php if($dr): ?>
              <h3>Synthèse IGP</h3>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th class="text-center col-xs-5" style="border-top: hidden; border-left: hidden;"></th>
                    <th class="text-center col-xs-2" colspan="2">DR</th>
                    <th class="text-center col-xs-5" colspan="3">DRev</th>
                  </tr>
                </thead>
                <thead>
                  <tr>
                    <th class="text-center col-xs-5">Produit (millesime)</th>
                    <th class="text-center col-xs-1">Superficie</th>
                    <th class="text-center col-xs-1">Volume</th>
                    <th class="text-center col-xs-1">Nb lots</th>
                    <th class="text-center col-xs-1">Vol. revendiqué</th>
                    <th class="text-center col-xs-2">Restant à revendiquer</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lots as $couleur => $lotsByCouleur) : ?>
                    <tr>
                      <td><strong><a href="#filtre=<?php echo $couleur; ?>" class="hamzastyle_link" ><?php echo $couleur ?></strong></a><small class="pull-right">&nbsp;<?php if(isset($synthese_revendication[$couleur]) && $synthese_revendication[$couleur]['superficie_totale']): ?><?php echoFloat(round($synthese_revendication[$couleur]['volume_total'] / $synthese_revendication[$couleur]['superficie_totale'], 2)); ?>&nbsp;hl/ha</small><?php endif; ?></td>
                      <td class="text-right"><?php if(isset($synthese_revendication[$couleur]) && $synthese_revendication[$couleur]['superficie_totale']): ?><?php echoFloat($synthese_revendication[$couleur]['superficie_totale']); ?><small class="text-muted">&nbsp;ha</small><?php endif; ?></td>
                      <td class="text-right">
                        <?php if(isset($synthese_revendication[$couleur]) && $synthese_revendication[$couleur]['volume_total']): ?>

                          <?php echoFloat($synthese_revendication[$couleur]['volume_total']); ?><small class="text-muted">&nbsp;hl</small>

                        <?php endif; ?>
                      </td>
                      <td class="text-right"><?php  echo (count($lotsByCouleur))? count($lotsByCouleur) : 'aucun lots'; ?></td>
                      <td class="text-right">
                        <?php if(isset($synthese_revendication[$couleur]) && $synthese_revendication[$couleur]['volume_lots']): ?>

                          <?php echoFloat($synthese_revendication[$couleur]['volume_lots']); ?><small class="text-muted">&nbsp;hl</small>
                        <?php elseif (isset($lotsHorsDR[$couleur])): ?>
                          <?php echoFloat($lotsHorsDR[$couleur]->volume); ?><small class="text-muted">&nbsp;hl</small>
                        <?php endif; ?>


                      </td>
                      <td class="text-right">
                        <?php if(isset($synthese_revendication[$couleur]) && round($synthese_revendication[$couleur]['volume_restant'],2) >= 0): ?><?php echoFloat($synthese_revendication[$couleur]['volume_restant']); ?><small>&nbsp;hl</small><?php endif; ?>
                        <?php if(isset($synthese_revendication[$couleur]) && round($synthese_revendication[$couleur]['volume_restant'],2) < 0): ?><span class="text-danger">excédent : +<?php echoFloat($synthese_revendication[$couleur]['volume_restant']*-1); ?><small>&nbsp;hl</small></span><?php endif; ?>
                      </td>
                    </tr>

                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

          <h3 id="table_igp_title">Déclaration des lots IGP</h3>
          <div class="row">
              <input type="hidden" data-placeholder="Sélectionner un produit" data-hamzastyle-container=".table_igp" data-hamzastyle-mininput="3" class="select2autocomplete hamzastyle col-xs-12">
          </div>
          <br/>
          <table class="table table-bordered table-striped table_igp">
            <thead>
              <tr>
                <th class="col-xs-1">Date Rev.</th>
                <th class="col-xs-1">Lot</th>
                <th class="text-center col-xs-6">Produit (millesime)</th>
                <th class="text-center col-xs-1">Volume</th>
                <th class="text-center col-xs-3">Destination (date)</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $firstRow = true;
              $totalVolume = 0;
              foreach ($lots as $couleur => $lotsByCouleur) :
                $volume = 0;
                if(count($lotsByCouleur)):
                  foreach ($lotsByCouleur as $lot) :
                    $totalVolume+=$lot->volume;
                    ?>
                    <tr class="<?php echo isVersionnerCssClass($lot, 'produit_libelle') ?> hamzastyle-item" data-callbackfct="$.calculTotal()" data-words='<?php echo json_encode(array($lot->produit_libelle), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>'  >
                      <td>
                        <?php $drevDocOrigine = $lot->getDrevDocOrigine(); ?>
                        <?php if($drevDocOrigine): ?><a class="link pull-right" href="<?php echo url_for('drev_visualisation', $drevDocOrigine); ?>"><?php endif; ?>
                          <?php echo $lot->getDateVersionfr(); ?>
                          <?php if($drevDocOrigine): ?></a><?php endif; ?>
                        </td>
                        <td><?php echo $lot->numero_cuve; ?></td>
                        <td>
                          <?php echo $lot->produit_libelle; ?>
                          <small >
                          <?php if(DrevConfiguration::getInstance()->hasSpecificiteLot()): ?>
                            <?php echo ($lot->specificite && $lot->specificite != "aucune")? $lot->specificite : ""; ?>
                          <?php endif ?>
                          <?php echo ($lot->millesime)? " ".$lot->millesime."" : ""; ?></small>
                          <?php if(count($lot->cepages)): ?>
                            <br/>
                            <small class="text-muted">
                              <?php echo $lot->getCepagesToStr(); ?>
                            </small>
                          <?php endif; ?>
                          <?php if($lot->isProduitValidateOdg()): ?>&nbsp;&nbsp;&nbsp;<span class="glyphicon glyphicon-ok" ></span><?php endif ?>
                        </td>
                        <td class="text-right"><span class="lot_volume"><?php echoFloat($lot->volume); ?></span><small class="text-muted">&nbsp;hl</small></td>
                        <td class="text-center"><?php echo ($lot->destination_type)? DRevClient::$lotDestinationsType[$lot->destination_type] : ''; echo ($lot->destination_date) ? '<br/><small class="text-muted">'.$lot->getDestinationDateFr()."</small>" : ''; ?></td>
                      </tr>
                      <?php
                      $firstRow=false;
                    endforeach;
                  endif; ?>
                <?php endforeach; ?>
                <tr>
                  <td></td>
                  <td></td>
                  <td class="text-right">Total : </td>
                  <td class="text-right"><span class="total_lots"><?php echoFloat($totalVolume); ?></span><small class="text-muted">&nbsp;hl</small></td>
                  <td></td>
                </tr>
              </tbody>
            </table>
            <br/>

            <?php
                if(($sf_user->hasDrevAdmin() || $drev->validation) && (count($drev->getProduitsLots()) || count($drev->getLots())) && $drev->isValidee() && $drev->isModifiable()): ?>
                <div class="col-xs-12" style="margin-bottom: 20px;">
                  <a onclick="return confirm('Êtes vous sûr de vouloir revendiquer de nouveaux lots IGP ?')" class="btn btn-default pull-right" href="<?php echo url_for('drev_modificative', $drev) ?>">Revendiquer des nouveaux lots IGP</a>
                </div>
              <?php endif; ?>

          <?php endif; ?>
          <?php if(count($drev->declaration->getProduitsVci())): ?>
            <h3>Gestion du VCI</h3>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th class="col-xs-5"><?php if (count($drev->declaration->getProduitsVci()) > 1): ?>Produits revendiqués<?php else: ?>Produit revendiqué<?php endif; ?></th>
                    <th class="text-center col-xs-1">Stock <?php echo $drev->campagne - 1 ?><br /><small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">Rafraichi<br /><small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">Compl.<br /><small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">A détruire<br /><small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">Substitué<br /><small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">Constitué<br /><?php echo $drev->campagne ?>&nbsp;<small class="text-muted">(hl)</small></th>
                    <th class="text-center col-xs-1">Stock <?php echo $drev->campagne ?><br /><small class="text-muted">(hl)</small></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($drev->declaration->getProduitsVci() as $produit) : ?>
                    <tr>
                      <td>
                        <?php echo $produit->getLibelleComplet() ?>
                        <small class="pull-right">
                          <span class="<?php if($produit->getRendementVci() > $produit->getConfig()->getRendementVci()): ?>text-danger<?php endif; ?>">&nbsp;<?php echoFloat(round($produit->getRendementVci(), 2)); ?></span>
                          <span data-toggle="tooltip" title="Rendement&nbsp;VCI&nbsp;de&nbsp;l'année&nbsp;| Σ&nbsp;rendement&nbsp;cumulé"
                          class="<?php if($produit->getRendementVciTotal() > $produit->getConfig()->getRendementVciTotal()): ?>text-danger<?php endif; ?>">|&nbsp;Σ&nbsp;<?php echoFloat(round($produit->getRendementVciTotal(), 2)); ?></span>
                          hl/ha </small>
                        </td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'stock_precedent') ?>"><?php if($produit->vci->stock_precedent): ?><?php echoFloat($produit->vci->stock_precedent) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'rafraichi') ?>"><?php if($produit->vci->rafraichi): ?><?php echoFloat($produit->vci->rafraichi) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'complement') ?>"><?php if($produit->vci->complement): ?><?php echoFloat($produit->vci->complement) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'destruction') ?>"><?php if($produit->vci->destruction): ?><?php echoFloat($produit->vci->destruction) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'substitution') ?>"><?php if($produit->vci->substitution): ?><?php echoFloat($produit->vci->substitution) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'constitue') ?><?php if($produit->getRendementVci() > $produit->getConfig()->getRendementVci()): ?>text-danger<?php endif; ?>"><?php if($produit->vci->constitue): ?><?php echoFloat($produit->vci->constitue) ?> <small class="text-muted">hl</small><?php endif; ?></td>
                        <td class="text-right <?php echo isVersionnerCssClass($produit->vci, 'stock_final') ?><?php if($produit->getRendementVciTotal() > $produit->getConfig()->getRendementVciTotal()): ?> text-danger<?php endif; ?>"><?php if($produit->vci->stock_final): ?>
                          <?php if($produit->vci->exist('ajustement')){ echo "(+"; echoFloat($produit->vci->ajustement); echo ") "; } ?>
                          <?php echoFloat($produit->vci->stock_final) ?> <small class="text-muted">hl</small><?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
              <?php if($drev->hasProduitsReserveInterpro()): ?>
                <h3>Réserve interprofessionnelle</h3>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th class="col-xs-6">Produit</td>
                        <th class="col-xs-3 text-center">Volume mis en réserve</td>
                          <th class="col-xs-3 text-center">Volume revendiqué commercialisable</td>
                          </thead>
                          <tbody>
                            <?php foreach($drev->getProduitsWithReserveInterpro() as $p): ?>
                              <tr>
                                <td><?php echo $p->getLibelle(); ?></td>
                                <td class="text-right"><?php echoFloat($p->getVolumeReserveInterpro()); ?> <small class="text-muted">hl</small></td>
                                <td class="text-right"><?php echoFloat($p->getVolumeRevendiqueCommecialisable()); ?> <small class="text-muted">hl</small></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      <?php endif; ?>

                      <?php use_javascript('hamza_style.js'); ?>
