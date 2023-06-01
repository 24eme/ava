<?php use_helper("Date"); ?>
<?php use_helper('Float') ?>
<?php use_helper('Lot') ?>

<?php include_partial('degustation/breadcrumb', array('degustation' => $degustation, "options" => array("nom" => "Tables des échantillons"))); ?>
<?php include_partial('degustation/step', array('degustation' => $degustation, 'active' => DegustationEtapes::ETAPE_TABLES)); ?>

<div class="row row-condensed">
  <div class="col-xs-3">
      <?php include_partial('degustation/organisationTableManuelleSidebar', compact('degustation', 'numero_table')); ?>
  </div>
  <div class="col-xs-9">
      <h2>Fiches</h2>
      <div id="commission-pdf-row">
          <div class="btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <span class="glyphicon glyphicon-user"></span> Dégustateurs <i class="caret"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a id="btn_pdf_presence_degustateurs" href="<?php echo url_for('degustation_fiche_presence_degustateurs_pdf', $degustation) ?>"><span class="glyphicon glyphicon-file"></span> Feuille de présence des dégustateurs</a></li>
              <li><a id="btn_pdf_fiche_individuelle_degustateurs" href="<?php echo url_for('degustation_fiche_individuelle_pdf', $degustation) ?>"><span class="glyphicon glyphicon-file"></span> Fiche individuelle des dégustateurs</a></li>
              <li><a id="btn_pdf_fiche_resultats_table" href="<?php echo url_for('degustation_fiche_recap_tables_pdf', $degustation) ?>"><span class="glyphicon glyphicon-file"></span>&nbsp;Fiche des résultats par table</a></li>
            </ul>
          </div>

          <div class="btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
               <span class="glyphicon glyphicon-transfer"></span> Tiers <i class="caret"></i>
            </button>
            <ul class="dropdown-menu">
              <li><a id="btn_csv_etiquette" href="<?php echo url_for('degustation_etiquette_csv', $degustation) ?>"><span class="glyphicon glyphicon-list"></span>&nbsp;Tableur des lots pour les laboratoires</a></li>
            </ul>
          </div>
      </div>

      <h2>Synthèse de toutes les tables</h2>
      <?php foreach ($degustation->getTables() as $table => $lots): ?>
      <h3>Table <?php echo DegustationClient::getNumeroTableStr($table); ?></h3>
      <table style="margin-bottom: 0px;" class="table table-condensed">
        <thead>
            <tr>
                <th class="col-xs-2">Table</th>
                <th class="col-xs-8">Produit</th>
                <th class="col-xs-2">Lots</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0; ?>
            <?php foreach ($degustation->getSyntheseLotsTable($table) as $hash => $lotsProduit): ?>
                <tr>
                    <td><?php echo DegustationClient::getNumeroTableStr($table) ?></td>
                    <td><?php echo $lotsProduit->libelle ?></td>
                    <td><?php echo count($lotsProduit->lotsTable); $total += count($lotsProduit->lotsTable) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" class="text-right text-bold"><strong>Total :</strong></td>
                <td><?php echo $total ?> lots</td>
            </tr>
            <tr>
                <td colspan="2" class="text-right text-bold">Dont :</td>
                <td><?php echo count(array_filter($degustation->getLeurres()->getRawValue(), function ($lot) use ($table) {
                    return $lot->numero_table == $table;
                })) ?> leurre(s)</td>
            </tr>
        </tbody>
      </table>
      <?php endforeach ?>

      <div class="row row-margin row-button">
        <div class="col-xs-4">
          <a href="<?php echo url_for("degustation_organisation_table", ['id' => $degustation->_id, 'numero_table' => count($degustation->getTables())]) ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Précédent</a>
        </div>
        <div class="col-xs-4 col-xs-offset-4 text-right">
            <a href="<?php echo url_for(DegustationEtapes::getInstance()->getNextLink(DegustationEtapes::ETAPE_TABLES), ['id' => $degustation->_id]) ?>" class="btn btn-success btn-upper">
                Terminer
            </a>
        </div>
      </div>
  </div>
</div>
