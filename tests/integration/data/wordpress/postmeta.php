<?php
/**
 * Métadonnées des médias : fichier réel de chacun, tailles dérivées d'une image
 */
$fichiers = array(
	611 => '2008/06/canola2.jpg',
	617 => '2008/06/dsc20050813_115856_52.jpg',
	755 => '2008/06/100_5540.jpg',
	756 => '2008/06/cep00032.jpg',
	757 => '2008/06/dcp_2082.jpg',
	761 => '2008/06/dsc20050102_192118_51.jpg',
	770 => '2008/06/img_0767.jpg',
	771 => '2008/06/img_8399.jpg',
	967 => '2013/03/image-alignment-580x300-1.jpg',
	968 => '2013/03/image-alignment-150x150-1.jpg',
	1690 => '2013/12/2014-slider-mobile-behavior.mov',
);
$metas = array();
foreach ($fichiers as $id => $fichier) {
	$metas[] = array('post_id' => $id, 'meta_key' => '_wp_attached_file', 'meta_value' => $fichier);
}
$metas[] = array('post_id' => 967, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize(array(
	'width' => 580, 'height' => 300, 'file' => '2013/03/image-alignment-580x300-1.jpg',
	'sizes' => array('thumbnail' => array('file' => 'image-alignment-580x300-1-150x150.jpg', 'width' => 150, 'height' => 150)),
)));
return $metas;
