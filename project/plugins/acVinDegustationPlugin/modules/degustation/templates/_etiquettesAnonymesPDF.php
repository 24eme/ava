<?php use_helper('TemplatingPDF'); ?>
<style>
</style>
    <table border="" class="" cellspacing=0 cellpadding=0 style="text-align: right;">
    <?php foreach($plancheLots as $lotInfo): ?>
        <tr>
          <?php for($i=0; $i <3 ; $i++): ?>
            <td style="text-align: left;">
                <table cellspacing=0 cellpadding=0 style="font-size:8px;padding:0px;">
                  <tr style="line-height:4px;">
                    <td style="overflow-wrap:break-word;">
                      <?php echo tdStart() ?>&nbsp;N°Dos:<strong><?php echo $lotInfo->lot->numero_cuve;  ?></strong>
                    </td>
                    <td style="overflow-wrap:break-word;">
                      <?php echo tdStart() ?>&nbsp;N°&nbsp;Lot&nbsp;ODG:<strong><?php echo $lotInfo->lot->numero_cuve;  ?></strong>
                    </td>
                  </tr>
                  <tr style="line-height:4px;">
                    <td colspan="2" style="overflow-wrap:break-word;text-align:center;line-height:8px;" >
                      <?php echo tdStart() ?>&nbsp;<strong><?php echo $lotInfo->lot->numero_cuve  ?></strong>
                    </td>
                  </tr>
                  <tr style="line-height:4px;">
                    <td colspan="2">
                      <?php echo tdStart() ?>&nbsp;Ville&nbsp;:&nbsp;<strong><?php echo ($lotInfo->etablissement)? $lotInfo->etablissement->commune : '';  ?></strong>
                    </td>
                  </tr>
                  <tr style="line-height:4px;">
                    <td colspan="2" style="overflow-wrap:break-word;">
                      <?php echo tdStart() ?><strong>&nbsp;IGP&nbsp;<?php echo $lotInfo->lot->produit_libelle .' '.  $lotInfo->lot->millesime;  ?></strong>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" style="overflow-wrap:break-word;">
                      <?php echo tdStart() ?>&nbsp;Lgt&nbsp;:&nbsp;<strong><?php echo $lotInfo->lot->numero_cuve;  ?></strong>
                    </td>
                  </tr>
                  <tr style="line-height:4px;">
                    <td colspan="2" style="overflow-wrap:break-word;text-align:center;line-height:6px;">
                      <?php echo tdStart() ?>&nbsp;Cépages&nbsp;:&nbsp;<strong><?php echo $lotInfo->lot->details;  ?></strong>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <?php echo tdStart() ?>&nbsp;Volume&nbsp;:&nbsp;<strong><?php echo sprintf("%.2f", $lotInfo->lot->volume);  ?> hl</strong>
                    </td>
                  </tr>
                </table>
            </td>
          <?php endfor; ?>
        </tr>
        <tr style="line-height:30px;"><td></td></tr>
    <?php endforeach; ?>
    </table>
