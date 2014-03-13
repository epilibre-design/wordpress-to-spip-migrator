<?php
/**
 * Plugin wp2spip
 * 
 * GNU/GPL v3
 * 
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_wp2spip_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!autoriser('configurer', 'plugins'))
		die('erreur');
	
	spip_log('wp2spip dump Start');
	$wp2spip=recuperer_fond('inc/wp2spip_export');
	$ou='wp2spip.xml';
	if(!is_dir(_DIR_DUMP)){
		include_spip('inc/flock');
		sous_repertoire(_DIR_DUMP);	
	}
	ecrire_fichier(_DIR_DUMP . "wp2spip.xml", $wp2spip);
	spip_log('wp2spip dump Done');
}
?>