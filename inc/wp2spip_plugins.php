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

// Dépôt de plugins déclaré par l'import quand le SPIP n'en a aucun
if (!defined('_WP2SPIP_DEPOT_SVP')) {
	define('_WP2SPIP_DEPOT_SVP', 'https://plugins.spip.net/depots/principal.xml');
}

/**
 * Contenus privés, ou protégés par mot de passe et publiés ou programmés : ceux que publie importer_acces
 *
 * @return array conditions sur wp_posts
 */
function wp2spip_where_contenus_restreints() {
	return array(
		sql_in('post_type', array('post', 'page')),
		'(post_status = "private" or (post_password != "" and ' . sql_in('post_status', array('publish', 'future')) . '))',
	);
}

/**
 * Commentaires approuvés ou en attente, de type comment ou vide (Wordpress < 5.5) : ceux que importe importer_commentaires
 *
 * @return array conditions sur wp_comments
 */
function wp2spip_where_commentaires() {
	return array(
		sql_in('comment_approved', array('1', '0')),
		sql_in('comment_type', array('', 'comment')),
	);
}

/**
 * Contenus qui ont une galerie : bloc gallery ou raccourci [gallery]
 *
 * @return array conditions sur wp_posts
 */
function wp2spip_where_galeries() {
	return array(
		sql_in('post_type', array('post', 'page')),
		'(post_content like ' . sql_quote('%<!-- wp:gallery%') . ' or post_content like ' . sql_quote('%[gallery%') . ')',
	);
}

/**
 * Plugins requis par le contenu Wordpress
 *
 * Le pipeline wp2spip_plugins_requis reçoit la base Wordpress (args) et la liste (data) :
 * une extension de wp2spip peut y déclarer les siens.
 *
 * @param string $base
 * @return array préfixe => array('nom' => …, 'table' => table créée par le plugin, 'dist' => livré avec SPIP,
 *     'raison' => ce qui le rend nécessaire), pour les seuls plugins requis
 */
function wp2spip_plugins_requis($base) {
	$requis = array();
	if ($nb = sql_countsel('wp_posts', wp2spip_where_galeries(), '', '', $base)) {
		$requis['albums'] = array('nom' => 'Albums', 'table' => 'spip_albums', 'dist' => false, 'raison' => "$nb contenus avec une galerie");
	}
	if ($nb = sql_countsel('wp_posts', wp2spip_where_contenus_restreints(), '', '', $base)) {
		$requis['accesrestreint'] = array('nom' => 'Accès restreint', 'table' => 'spip_zones', 'dist' => false, 'raison' => "$nb contenus privés ou protégés");
	}
	if ($nb = sql_countsel('wp_comments', wp2spip_where_commentaires(), '', '', $base)) {
		$requis['forum'] = array('nom' => 'Forum', 'table' => 'spip_forum', 'dist' => true, 'raison' => "$nb commentaires");
	}
	return pipeline('wp2spip_plugins_requis', array('args' => array('base' => $base), 'data' => $requis));
}

/**
 * Plugin requis prêt : actif, et sa table créée
 *
 * @param string $prefixe
 * @param array $plugin description de wp2spip_plugins_requis()
 * @return bool
 */
function wp2spip_plugin_pret($prefixe, $plugin) {
	include_spip('inc/plugin');
	return test_plugin_actif($prefixe) and (empty($plugin['table']) or sql_showtable($plugin['table'], true));
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

/**
 * Plugin présent sur le disque (plugins/ ou plugins-dist/)
 *
 * @param string $prefixe
 * @return bool
 */
function wp2spip_plugin_present($prefixe) {
	foreach (array(_DIR_PLUGINS, _DIR_PLUGINS_DIST) as $dossier) {
		if (!is_dir($dossier)) {
			continue;
		}
		$fichiers = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
		);
		$fichiers->setMaxDepth(3);
		foreach ($fichiers as $fichier) {
			if (
				$fichier->getFilename() == 'paquet.xml'
				and preg_match('/\bprefix=["\']' . preg_quote($prefixe, '/') . '["\']/i', (string) file_get_contents($fichier->getPathname()))
			) {
				return true;
			}
		}
	}
	return false;
}
