<ol class="breadcrumb">
  <li><a href="<?php echo url_for('accueil'); ?>">Déclarations</a></li>
  <li><a href="<?php echo url_for('declaration_etablissement', $travauxmarc->getEtablissementObject()); ?>"><?php echo $travauxmarc->getEtablissementObject()->getNom() ?> (<?php echo $travauxmarc->getEtablissementObject()->identifiant ?>)</a></li>
  <li class="active"><a href="">DRev Marc de <?php echo $travauxmarc->getCampagne(); ?></a></li>
</ol>
