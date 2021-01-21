<div class="modal fade" id="<?php if(!isset($html_id)): ?>popupLeurreForm<?php else: ?><?php echo $html_id ?><?php endif; ?>" role="dialog" aria-labelledby="Ajouter un leurre" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" action="<?php echo $url ?>" role="form" class="form-horizontal">
                <?php echo $form->renderHiddenFields(); ?>
            	<?php echo $form->renderGlobalErrors(); ?>
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title" id="myModalLabel">Ajouter un leurre à la table <?php echo DegustationClient::getNumeroTableStr($table) ?></h4>
				</div>
				<div class="modal-body">
					<span class="error"><?php echo $form['hashref']->renderError() ?></span>
					<div class="form-group row">
						<div class="col-xs-10 col-xs-offset-1">
						<?php echo $form['hashref']->render(array("data-placeholder" => "Sélectionnez un produit", "class" => "form-control select2 select2-offscreen select2autocomplete", "required" => true)) ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<a class="btn btn-default btn pull-left" data-dismiss="modal">Annuler</a>
					<button type="submit" class="btn btn-success btn pull-right">Ajouter le produit</button>
				</div>
			</form>
		</div>
	</div>
</div>
