<?php printf("\xef\xbb\xbf"); //UTF8 BOM (pour windows) ?>
Identifiant;CVI Opérateur;Siret Opérateur;Nom Opérateur;Adresse Opérateur;Code postal Opérateur;Commune Opérateur;Email;Produit;Activité;Statut;Date;Commentaire;Id du doc
<?php foreach ($docs as $doc): ?>
<?php echo $doc->key[HabilitationActiviteView::KEY_IDENTIFIANT] ?>;<?php echo $doc->key[HabilitationActiviteView::KEY_CVI] ?>;;"<?php echo $doc->key[HabilitationActiviteView::KEY_RAISON_SOCIALE] ?>";;;;;<?php echo $doc->key[HabilitationActiviteView::KEY_PRODUIT_LIBELLE] ?>;<?php echo $doc->key[HabilitationActiviteView::KEY_ACTIVITE] ?>;<?php echo $doc->key[HabilitationActiviteView::KEY_STATUT] ?>;<?php echo $doc->key[HabilitationActiviteView::KEY_DATE] ?>;;<?php echo $doc->id ?><?php echo "\n"; ?>
<?php endforeach; ?>
