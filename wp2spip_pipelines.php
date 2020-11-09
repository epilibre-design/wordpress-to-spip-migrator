<?php
 
 if (!defined('_ECRIRE_INC_VERSION')) {
	 return;
 }
 
function  wp2spip_declarer_tables_objets_sql($tables) {
	// Pour chaque objet on rajoute un champ id_wordpress
	$tables[]['field']['id_wordpress'] = 'bigint(21) not null default 0';
	
	return $tables;
}
