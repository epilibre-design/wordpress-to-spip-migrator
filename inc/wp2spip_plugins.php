<?php

/**
 * Plugins requis par le contenu Wordpress
 *
 * La commande d'import les télécharge et les active avant le premier traitement
 * (WordpressImporter::verifier_plugins()). Les critères sont ceux des traitements qui en ont besoin.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tables et champs déclarés par SPIP et ses plugins actifs, mais absents de la base
 *
 * Un plugin installé par SVP (plugins:svp:telecharger) dans un processus qui ne connaissait pas encore ses tables
 * a la version de son schéma notée sans que ses tables soient créées : ce contrôle le révèle.
 *
 * @return array tables (« spip_albums ») et champs (« spip_articles.page ») manquants
 */
function wp2spip_tables_manquantes() {
	include_spip('base/objets');
	include_spip('base/serial');
	include_spip('base/auxiliaires');
	$manquants = array();
	foreach (array_merge(lister_tables_principales(), lister_tables_auxiliaires()) as $table => $description) {
		if (empty($description['field'])) {
			continue;
		}
		if (!$existante = sql_showtable($table, true)) {
			$manquants[] = $table;
			continue;
		}
		foreach (array_keys($description['field']) as $champ) {
			if (!isset($existante['field'][$champ])) {
				$manquants[] = "$table.$champ";
			}
		}
	}
	return $manquants;
}
