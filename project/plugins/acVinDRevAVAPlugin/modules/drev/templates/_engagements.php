<h2 class="h3">J'ai pris connaissance des pièces à fournir</h2>

<div class="alert" role="alert" id="engagements">
    <div class="form-group">

        <div class="alert alert-danger <?php if(!$form->hasErrors()): ?>hidden<?php endif; ?>" role="alert">
    	    <ul class="error_list">
    			<li class="text-left">Vous devez cocher pour valider votre déclaration.</li>
    		</ul>
    	</div>

        <?php foreach ($validation->getEngagements() as $engagement): ?>
        <div class="checkbox-container <?php if ($form['engagement_' . $engagement->getCode()]->hasError()): ?>has-error<?php endif; ?>">
            <div class="checkbox<?php if(in_array($engagement->getCode(), [DRevDocuments::DOC_DR, DRevDocuments::DOC_SV]) && $drev->hasDROrSV()): ?> disabled<?php endif; ?>">
                <label>
                	<?php
                		if (in_array($engagement->getCode(), [DRevDocuments::DOC_DR, DRevDocuments::DOC_SV]) && $drev->hasDROrSV()) {
                			echo $form['engagement_' . $engagement->getCode()]->render(array('checked' => 'checked'));
                		} else {
                			echo $form['engagement_' . $engagement->getCode()]->render();
                		}
                	?>
                    <?php echo $engagement->getRawValue()->getMessage() ?>
                    <?php if ($engagement->getCode() == DRevDocuments::DOC_DR && $drev->hasDR()): ?>- <a href="<?php echo $drev->getAttachmentUri('DR.pdf'); ?>" target="_blank"><small>Télécharger ma DR</small></a><?php endif; ?>
                    <?php if ($engagement->getCode() == DRevDocuments::DOC_SV && $drev->hasSV()): ?>- <a href="<?php echo $drev->getAttachmentUri('SV.pdf'); ?>" target="_blank"><small>Télécharger mon document de production</small></a><?php endif; ?>
                </label>
            </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
