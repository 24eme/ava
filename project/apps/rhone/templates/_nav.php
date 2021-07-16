<?php $route = ($sf_request->getAttribute('sf_route')) ? $sf_request->getAttribute('sf_route')->getRawValue() : NULL; ?>
<?php $etablissement = null ?>
<?php $compte = null; ?>

<?php if($route instanceof EtablissementRoute): ?>
    <?php $etablissement = $route->getEtablissement(); ?>
    <?php $compte = $etablissement->getMasterCompte(); ?>
<?php endif; ?>
<?php if($route instanceof CompteRoute): ?>
    <?php $compte = $route->getCompte(); ?>
    <?php //$etablissement = $compte->getEtablissementObj(); ?>
<?php endif; ?>
<?php if($route instanceof SocieteRoute): ?>
    <?php $etablissement = $route->getEtablissement(); ?>
    <?php $compte = $route->getSociete()->getMasterCompte(); ?>
<?php endif; ?>

<?php if($sf_user->isAuthenticated() && !$sf_user->hasCredential(myUser::CREDENTIAL_ADMIN) &&  !$sf_user->hasCredential(myUser::CREDENTIAL_DREV_ADMIN) && !$sf_user->hasCredential(myUser::CREDENTIAL_HABILITATION) && !$sf_user->hasCredential(myUser::CREDENTIAL_STALKER) && (!$compte || !$etablissement)): ?>
    <?php $compte = $sf_user->getCompte(); ?>
    <?php $etablissement = $compte->getSociete()->getEtablissementPrincipal(); ?>
<?php endif; ?>


<nav id="menu_navigation" class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header hidden-lg hidden-md">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="<?php echo url_for('accueil') ?>"></a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1" style="padding-left: 0;">
            <?php if($sf_user->hasCredential(myUser::CREDENTIAL_ADMIN) || $sf_user->hasCredential(myUser::CREDENTIAL_STALKER)): ?>
            <ul class="nav navbar-nav <?php if($compte): ?>mode-operateur<?php endif; ?>" style="border: 0;">
                <li id="nav_item_operateur" class="<?php if(!$compte): ?>disabled<?php endif; ?>"><a <?php if($compte): ?>onclick="document.location = $(this).parents('ul.mode-operateur').find('li.active a').attr('href');"<?php endif; ?> href="#"><span class="glyphicon glyphicon-user"></span></a></li>
                <li class="<?php if($route instanceof InterfaceDeclarationRoute): ?>active<?php endif; ?>  <?php if(!$sf_user->hasCredential(myUser::CREDENTIAL_ADMIN)): ?>disabled<?php endif; ?>"><a href="<?php if($etablissement && !$route instanceof InterfaceDeclarationRoute): ?><?php echo url_for('declaration_etablissement', $etablissement); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Déclarations</a></li>
                <li class="<?php if($route instanceof InterfaceDocumentsRoute): ?>active<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceDocumentsRoute): ?><?php echo url_for('pieces_historique', $etablissement); ?><?php else: ?><?php echo url_for('documents'); ?><?php endif; ?>">Documents</a></li>
                <li style="display:none;" class="<?php if($route instanceof InterfaceFacturationRoute): ?>active<?php endif; ?>"><a href="<?php if($compte  && !$route instanceof InterfaceFacturationRoute): ?><?php echo url_for('facturation_declarant', $compte); ?><?php else: ?><?php echo url_for('facturation'); ?><?php endif; ?>">Facturation</a></li>
                <li class="<?php if($route instanceof InterfaceHabilitationRoute): ?>active<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceHabilitationRoute): ?><?php echo url_for('habilitation_declarant', $etablissement); ?><?php else: ?><?php echo url_for('habilitation_demande'); ?><?php endif; ?>">Habilitations</a></li>
                <li class="<?php if($route instanceof InterfaceCompteRoute): ?>active<?php endif; ?>"><a href="<?php if($compte && !$route instanceof InterfaceCompteRoute): ?><?php echo url_for('compte_visualisation', $compte); ?><?php else: ?><?php echo url_for('compte_search'); ?><?php endif; ?>">Contacts</a></li>
            </ul>
            <?php elseif($sf_user->hasCredential(myUser::CREDENTIAL_DREV_ADMIN)): ?>
                <ul class="nav navbar-nav <?php if($compte): ?>mode-operateur<?php endif; ?>" style="border: 0;">
                    <li id="nav_item_operateur" class="<?php if(!$compte): ?>disabled<?php endif; ?>"><a <?php if($compte): ?>onclick="document.location = $(this).parents('ul.mode-operateur').find('li.active a').attr('href');"<?php endif; ?> href="#"><span class="glyphicon glyphicon-user"></span></a></li>
                    <li class="<?php if($route instanceof InterfaceDeclarationRoute): ?>active<?php endif; ?>"><a href="<?php if($etablissement && !$route instanceof InterfaceDeclarationRoute): ?><?php echo url_for('declaration_etablissement', $etablissement); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Déclarations</a></li>
                    <li class="<?php if($route instanceof InterfaceDocumentsRoute): ?>active<?php endif; ?> <?php if(!$compte): ?>disabled<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceDocumentsRoute): ?><?php echo url_for('pieces_historique', $etablissement); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Documents</a></li>
                    <li style="display:none;" class="<?php if($route instanceof InterfaceFacturationRoute): ?>active<?php endif; ?> <?php if(!$compte): ?>disabled<?php endif; ?>"><a href="<?php if($compte  && !$route instanceof InterfaceFacturationRoute): ?><?php echo url_for('facturation_declarant', $compte); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Facturation</a></li>
                    <li class="<?php if($route instanceof InterfaceHabilitationRoute): ?>active<?php endif; ?> <?php if(!$compte): ?>disabled<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceHabilitationRoute): ?><?php echo url_for('habilitation_declarant', $etablissement); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Habilitations</a></li>
                    <li class="<?php if($route instanceof InterfaceCompteRoute): ?>active<?php endif; ?> <?php if(!$compte): ?>disabled<?php endif; ?>"><a href="<?php if($compte && !$route instanceof InterfaceCompteRoute): ?><?php echo url_for('compte_visualisation', $compte); ?><?php else: ?><?php echo url_for('declaration'); ?><?php endif; ?>">Contacts</a></li>
                </ul>
            <?php elseif($sf_user->hasCredential(myUser::CREDENTIAL_HABILITATION)): ?>
            <ul class="nav navbar-nav <?php if($compte): ?>mode-operateur<?php endif; ?>" style="border: 0;">
                <li class="<?php if($route instanceof InterfaceDocumentsRoute): ?>active<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceDocumentsRoute): ?><?php echo url_for('pieces_historique', $etablissement); ?><?php else: ?><?php echo url_for('documents'); ?><?php endif; ?>">Documents</a></li>
                <li class="<?php if($route instanceof InterfaceHabilitationRoute): ?>active<?php endif; ?>"><a href="<?php if($etablissement  && !$route instanceof InterfaceHabilitationRoute): ?><?php echo url_for('habilitation_declarant', $etablissement); ?><?php else: ?><?php echo url_for('habilitation_demande'); ?><?php endif; ?>">Habilitations</a></li>
                <?php if(SocieteConfiguration::getInstance()->isVisualisationTeledeclaration()): ?>
                <li class="<?php if($route instanceof InterfaceCompteRoute): ?>active<?php endif; ?>"><a href="<?php if($compte && !$route instanceof InterfaceCompteRoute): ?><?php echo url_for('compte_visualisation', $compte); ?><?php else: ?><?php echo url_for('habilitation_demande'); ?><?php endif; ?>">Contacts</a></li>
                <?php endif; ?>
            </ul>
            <?php elseif($sf_user->isAuthenticated() && $etablissement): ?>
                <ul class="nav navbar-nav <?php if($compte): ?>mode-operateur<?php endif; ?>" style="border: 0;">
                    <li class="<?php if($route instanceof InterfaceDeclarationRoute): ?>active<?php endif; ?>"><a href="<?php echo url_for('declaration_etablissement', $etablissement); ?>">Déclarations</a></li>
                    <li class="<?php if($route instanceof InterfaceDocumentsRoute): ?>active<?php endif; ?>"><a href="<?php echo url_for('pieces_historique', $etablissement); ?>">Documents</a></li>
                    <?php if(SocieteConfiguration::getInstance()->isVisualisationTeledeclaration()): ?>
                    <li class="<?php if($route instanceof InterfaceHabilitationRoute): ?>active<?php endif; ?>"><a href="<?php echo url_for('habilitation_declarant', $etablissement); ?>">Habilitations</a></li>
                    <li class="<?php if($route instanceof InterfaceCompteRoute): ?>active<?php endif; ?>"><a href="<?php echo url_for('compte_visualisation', $compte); ?>">Contacts</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <ul class="nav navbar-nav navbar-right">
                <?php if($sf_user->hasCredential(myUser::CREDENTIAL_ADMIN)): ?>
                <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-cog"></span><span class="caret"></span></a>
                  <ul class="dropdown-menu">
                    <li><a href="<?php echo url_for("produits") ?>">Catalogue produit</a></li>
                    <li><a href="<?php echo url_for('export'); ?>">Génération</a></li>
                    <?php if(sfConfig::get('app_export_webpath', null)): ?>
                    <li><a href="<?php echo sfConfig::get('app_export_webpath', null) ?>">Export</a></li>
                    <li><a href="/metabase/">Metabase</a></li>
                    <?php endif; ?>
                  </ul>
                </li>
                <?php elseif($sf_user->isAuthenticated()): ?>
                 <li><a tabindex="-1" href="https://extranet.syndicat-cotesdurhone.com/" title="Extranet">Extranet</a></li>
                 <li><a tabindex="-1" href="<?php echo url_for("compte_teledeclarant_modification") ?>" title="Mon compte"><span class="glyphicon glyphicon-user"></span></a></li>
                <?php endif; ?>
                <?php if ($sf_user->hasCredential(myUser::CREDENTIAL_ADMIN) && $compte && !$sf_user->isUsurpationCompte()) : ?>
                     <li><a tabindex="-1" href="<?php echo url_for('auth_usurpation', array('identifiant' => $compte->identifiant)) ?>" title="Connexion mode déclarant"><span class="glyphicon glyphicon-cloud-upload"></span></a></li>
                <?php endif; ?>
                <?php if ($sf_user->isUsurpationCompte()): ?>
                    <li><a tabindex="-1" href="<?php echo url_for('auth_deconnexion_usurpation') ?>" title="Déconnexion du mode déclarant"><span class="glyphicon glyphicon-cloud-download"></span></a></li>
                <?php elseif ($sf_user->isAuthenticated()): ?>
                    <li><a tabindex="-1" href="<?php echo url_for('auth_logout') ?>" title="Déconnexion"><span class="glyphicon glyphicon-log-out"></span></a></li>
                <?php else: ?>
                    <li><a tabindex="-1" href="<?php echo url_for('common_accueil') ?>">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
