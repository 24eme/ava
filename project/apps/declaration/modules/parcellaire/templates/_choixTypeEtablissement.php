<div class="row">
    <div class="row col-xs-offset-1 col-xs-10 ">  

        <div class="form-group">
            <?php echo $parcellaireTypeProprietaireForm["type_proprietaire"]->renderError(); ?>
            <?php echo $parcellaireTypeProprietaireForm["type_proprietaire"]->renderLabel("type_proprietaire", array("class" => "col-xs-3 control-label")); ?>
            <div class="col-xs-9">
                <?php echo $parcellaireTypeProprietaireForm["type_proprietaire"]->render(array("class" => "form-control")); ?>
            </div>
        </div>

        <div class="form-group">
            <?php echo $parcellaireTypeProprietaireForm["vendeurs_select"]->renderError(); ?>
            <?php echo $parcellaireTypeProprietaireForm["vendeurs_select"]->renderLabel("vendeurs", array("class" => "col-xs-3 control-label")); ?>
            <div class="col-xs-9">
                <?php echo $parcellaireTypeProprietaireForm["vendeurs_select"]->render(array("class" => "form-control select2 select2-offscreen select2autocomplete", "placeholder" => "Ajouter des attributs")); ?>
            </div>
        </div>

    </div>
</div>
