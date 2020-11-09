<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Fonction d'installation et de mise à jour du plugin
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function wp2spip_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(
		// Mettre à jour toutes les tables
		array('maj_tables', true),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin
 * Supprimer tous les id_wordpress
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function wp2spip_vider_tables($nom_meta_base_version) {
	include_spip('base/objets');
	$tables = lister_tables_objets_sql();
	
	foreach ($tables as $table => $infos) {
		if (isset($infos['field']['id_wordpress'])) {
			sql_alter("TABLE $table DROP id_wordpress");
		}
	}
	
	effacer_meta($nom_meta_base_version);
}
