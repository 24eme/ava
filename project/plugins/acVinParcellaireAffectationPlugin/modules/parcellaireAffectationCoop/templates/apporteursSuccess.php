<div class="page-header no-border">
    <h2>Liste de vos apporteurs</h2>
</div>

    <p>Veuillez mettre à jour la liste de vos apporteurs</p>

    <form action="" method="post" class="form-horizontal">
        <table class="table table-condensed table-striped table-bordered">
            <tr>
                <th style="width: 0;"></th>
                <th class="text-right col-xs-1">Provenance</th>
                <th>Apporteur</th>
            </tr>
        <?php foreach ($form as $idApporteur => $apporteurForm): ?>
            <tr class="vertical-center cursor-pointer">
                <td>
                    <div style="margin-bottom: 0;" class="form-group">
                        <div class="col-xs-12">
                            <?php echo $apporteurForm->render(array('class' => "bsswitch", 'data-size' => 'small', 'data-on-text' => "<span class='glyphicon glyphicon-ok-sign'></span>", 'data-off-text' => "<span class='glyphicon'></span>", 'data-on-color' => "success")); ?>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <?php if(isset($apporteursSV11[$idApporteur])): ?>
                        SV11
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $apporteurs[$idApporteur]; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </table>
        <div class="row row-margin row-button">
            <div class="col-xs-4"><a href="<?php echo url_for("degustation") ?>" class="btn btn-default btn-upper"><span class="glyphicon glyphicon-chevron-left"></span> Retour</a></div>
            <div class="col-xs-4 text-center">
            </div>
            <div class="col-xs-4 text-right"><button type="submit" class="btn btn-primary btn-upper">Valider <span class="glyphicon glyphicon-chevron-right"></span></button></div>
        </div>
    </form>
</div>