<?php echo use_helper("Date"); ?>
<?php if(count($history) > 0): ?>
<h2>Derniers documents</h2>

<div class="list-group">
<?php $i=0; foreach ($history as $document): $i++; if ($i>$limit) { break; } ?>
<div class="list-group-item col-xs-12">
	<span class="col-sm-2 col-xs-12">
		<?php echo (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $document->key[PieceAllView::KEYS_DATE_DEPOT]))? format_date($document->key[PieceAllView::KEYS_DATE_DEPOT], "dd/MM/yyyy", "fr_FR") : null; ?>
	</span>
	<span class="col-sm-8 col-xs-12">
		<?php if(count($document->value[PieceAllView::VALUES_FICHIERS]) > 1): ?>
		  	<a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $document->key[PieceAllView::KEYS_LIBELLE] ?></a>
		  	<ul class="dropdown-menu">
		  		<?php 
		  			foreach ($document->value[PieceAllView::VALUES_FICHIERS] as $file): 
		    		$infos = explode('.', $file);
		    		$extention = (isset($infos[1]))? $infos[1] : "";
		  		?>
		  		<li><a href="<?php echo url_for('get_piece', array('doc_id' => $document->id, 'piece_id' => $document->value[PieceAllView::VALUES_KEY])) ?>?file=<?php echo $file ?>"><?php echo strtoupper($extention) ?></a></li>
		  		<?php endforeach; ?>
		  	</ul>
		<?php else: ?>
		<a href="<?php echo url_for('get_piece', array('doc_id' => $document->id, 'piece_id' => $document->value[PieceAllView::VALUES_KEY])) ?>"><?php echo $document->key[PieceAllView::KEYS_LIBELLE] ?></a>
		<?php endif; ?>
	</span>
	<span class="col-sm-2 col-xs-12">
		<?php if(count($document->value[PieceAllView::VALUES_FICHIERS]) > 1): ?>
		  	<a href="#" class="pull-right dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-duplicate"></span></a>
		  	<ul class="dropdown-menu">
		  		<?php 
		  			foreach ($document->value[PieceAllView::VALUES_FICHIERS] as $file): 
		    		$infos = explode('.', $file);
		    		$extention = (isset($infos[1]))? $infos[1] : "";
		  		?>
		  		<li><a href="<?php echo url_for('get_piece', array('doc_id' => $document->id, 'piece_id' => $document->value[PieceAllView::VALUES_KEY])) ?>?file=<?php echo $file ?>"><?php echo strtoupper($extention) ?></a></li>
		  		<?php endforeach; ?>
		  	</ul>
		<?php else: ?>
		<a class="pull-right" href="<?php echo url_for('get_piece', array('doc_id' => $document->id, 'piece_id' => $document->value[PieceAllView::VALUES_KEY])) ?>"><span class="glyphicon glyphicon-file"></span></a>
		<?php endif; ?>
		<?php if ($urlVisu = Piece::getUrlVisualisation($document->id, $sf_user->hasCredential(myUser::CREDENTIAL_ADMIN))): ?>
			<a class="pull-right" href="<?php echo $urlVisu ?>" style="margin: 0 10px;"><span class="glyphicon glyphicon-edit"></span></a>
		<?php else: ?>
			<?php echo $document->key[PieceAllView::KEYS_LIBELLE] ?>
		<?php endif; ?>
	</span>
</div>
<?php endforeach; ?>
</div>
<a href="<?php echo url_for('pieces_historique', $etablissement) ?>" style="margin-top: 20px; margin-bottom: 20px;" class="pull-right btn btn-warning btn-xs"><span class="glyphicon glyphicon-plus"></span>&nbsp;Plus de document</a>
<?php endif; ?>
