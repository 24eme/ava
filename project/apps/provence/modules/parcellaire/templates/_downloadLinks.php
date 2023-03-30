<div class="dropdown dropup center-block" style="width: 150px;">
    <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Télécharger...&nbsp;<span class="caret"></span></button>
    <ul class="dropdown-menu">
<?php if($sf_user->isAdmin()): ?>
        <li class="dropdown-header">Documents internes</li>
        <li><a href="<?php echo url_for('parcellaire_export_ods', array('id' => $parcellaire->_id)); ?>" class="dropdown-item">Télécharger le doc de contrôle</a></li>
        <li><a href="<?php echo url_for('parcellaire_export_geo', array('id' => $parcellaire->_id)); ?>" class="dropdown-item">Télécharger les coordonnées géographiques</a></li>
        <li class="divider"></li>
        <li class="dropdown-header">Documents partagés avec les opérateurs</li>
<?php endif; ?>
        <li><a href="<?php echo url_for('parcellaire_export_csv', array('id' => $parcellaire->_id)); ?>" class="dropdown-item">Télécharger le CSV du parcellaire</a></li>
        <li><a href="<?php echo url_for('parcellaire_pdf', array('id' => $parcellaire->_id)); ?>" class="dropdown-item">Télécharger le PDF Douanier</a></li>
    </ul>
</div>