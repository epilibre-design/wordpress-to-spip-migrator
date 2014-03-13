<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/sale');

function sale_wp2spip($contenu_sale, $correspondances = ''){
	if(!defined('_WP2SPIP_NO_SALE'))
		return sale($contenu_sale,$correspondances);
	else
		return $contenu_sale;
}
function wp_charset($texte){
	include_spip('inc/charsets');
	return importer_charset($texte, 'iso-8859-1');
}
?>