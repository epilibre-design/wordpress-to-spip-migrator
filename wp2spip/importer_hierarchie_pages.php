<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Noms des tables WordPress (wp2spip_table())
include_spip('inc/wp2spip');

/**
 * Relie chaque page enfant à sa page parente par un lien a2a de type sous_page
 *
 * Dans Wordpress, une page a une page parente (post_parent) et un ordre parmi ses sœurs (menu_order, puis titre).
 * importer_articles en fait des pages uniques, sans hiérarchie : on garde ici la parenté et l'ordre par un lien
 * de la page parente vers la page enfant, dont le rang suit l'ordre Wordpress. La parente d'une page se retrouve
 * en lisant le lien à l'envers. Aucun squelette n'est fourni : le site exploite ces liens à sa façon.
 */
function wp2spip_importer_hierarchie_pages_dist($command) {
	include_spip('inc/wp2spip_plugins');

	// Pages enfants, dans l'ordre Wordpress des pages d'un même parent
	$wp_pages = sql_allfetsel(
		'ID, post_parent',
		wp2spip_table('posts'),
		wp2spip_where_pages_enfants(),
		'',
		'post_parent, menu_order, post_title, ID',
		'',
		'',
		$command->base
	);
	if (!$wp_pages) {
		return;
	}

	// La commande active a2a quand il est requis : inactif malgré tout, l'import ne continue pas
	// en perdant la hiérarchie des pages sans le dire
	include_spip('inc/plugin');
	if (!test_plugin_actif('a2a')) {
		$command->output->writeln('<error>' . count($wp_pages) . ' pages enfants à relier à leur page parente, mais le plugin a2a n’est pas actif.</error>');
		return false;
	}

	// Le type de liaison est ajouté à la configuration d'a2a : il reste défini après la désinstallation de wp2spip
	include_spip('inc/config');
	$types = lire_config('a2a/types_liaisons');
	if (!is_array($types)) {
		$types = array();
	}
	if (!isset($types['sous_page'])) {
		$types['sous_page'] = 'Sous-page (WordPress)';
		ecrire_config('a2a/types_liaisons', $types);
	}

	include_spip('action/a2a');
	$nb_crees = 0;
	$nb_deja = 0;
	$parents = array();
	$absentes = array();
	$conflits = array();
	foreach ($wp_pages as $wp_page) {
		$id_wordpress = intval($wp_page['ID']);
		$id_wordpress_parent = intval($wp_page['post_parent']);
		$id_page = intval(sql_getfetsel('id_article', 'spip_articles', 'id_wordpress = ' . $id_wordpress));
		$id_parent = intval(sql_getfetsel('id_article', 'spip_articles', 'id_wordpress = ' . $id_wordpress_parent));
		if (!$id_page or !$id_parent) {
			$absentes[] = "$id_wordpress (parent $id_wordpress_parent)";
			continue;
		}
		$lien = array('id_article = ' . $id_parent, 'id_article_lie = ' . $id_page, 'type_liaison = ' . sql_quote('sous_page'));
		if (sql_countsel('spip_articles_lies', $lien)) {
			$nb_deja++;
			$parents[$id_parent] = true;
			continue;
		}
		// a2a ne dit pas s'il a créé le lien : sans liaisons multiples, il n'en crée pas
		// si les deux pages sont déjà liées par un autre type
		action_a2a_lier_article_dist($id_page, $id_parent, 'sous_page');
		if (!sql_countsel('spip_articles_lies', $lien)) {
			$conflits[] = "$id_wordpress (parent $id_wordpress_parent)";
			continue;
		}
		$nb_crees++;
		$parents[$id_parent] = true;
	}

	$command->output->writeln("$nb_crees liens de sous-pages créés (a2a, type sous_page), pour " . count($parents) . ' pages parentes.');
	if ($nb_deja) {
		$command->output->writeln("$nb_deja liens de sous-pages déjà présents.");
	}
	if ($absentes) {
		$command->output->writeln('<error>Pages enfants ou parentes absentes de SPIP, pas de lien : ' . join(', ', $absentes) . '.</error>');
	}
	if ($conflits) {
		$command->output->writeln('<error>Liens sous_page non créés par a2a, les pages étant déjà liées par un autre type (liaisons multiples désactivées dans la configuration d’a2a) : ' . join(', ', $conflits) . '.</error>');
	}
	if ($absentes or $conflits) {
		return false;
	}
}
