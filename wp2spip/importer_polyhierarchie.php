<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Noms des tables WordPress (wp2spip_table())
include_spip('inc/wp2spip');

/**
 * Rattache les articles importés à toutes leurs catégories Wordpress
 *
 * La première catégorie reste la rubrique principale (gérée par importer_articles),
 * les autres deviennent des rubriques secondaires avec Polyhiérarchie.
 * Le traitement est relancé intégralement à chaque fois : il remet les parents
 * de chaque article en accord avec le Wordpress, y compris pour les articles
 * importés avant que ce traitement existe.
 */
function wp2spip_importer_polyhierarchie_dist($command) {
	// Tous les articles déjà importés (les pages uniques n'ont pas de catégories)
	$articles = sql_allfetsel('id_article, id_rubrique, id_wordpress', 'spip_articles', 'id_wordpress > 0 and id_rubrique > 0');
	if (!$articles) {
		$command->output->writeln('Aucun article importé à rattacher à ses catégories.');
		return;
	}

	// Correspondance catégorie Wordpress => rubrique SPIP
	$rubriques = array_column(
		sql_allfetsel('id_rubrique, id_wordpress', 'spip_rubriques', 'id_wordpress > 0'),
		'id_rubrique',
		'id_wordpress'
	);

	// Toutes les catégories de tous les posts, dans l'ordre Wordpress
	$categories = array();
	if ($relations = sql_allfetsel(
		'rel.object_id as id_post, tax.term_id as id_term',
		wp2spip_table('term_relationships') . ' as rel join ' . wp2spip_table('term_taxonomy') . ' as tax on tax.term_taxonomy_id=rel.term_taxonomy_id',
		array(
			'tax.taxonomy = "category"',
			sql_in('rel.object_id', array_column($articles, 'id_wordpress')),
		),
		'',
		'rel.object_id, rel.term_order, tax.term_id',
		'',
		'',
		$command->base
	)) {
		foreach ($relations as $relation) {
			if (isset($rubriques[$relation['id_term']])) {
				$categories[$relation['id_post']][] = intval($rubriques[$relation['id_term']]);
			}
		}
	}

	include_spip('inc/polyhier');
	include_spip('inc/rubriques');

	$nb_articles = count($articles);
	$nb_modifs = 0;
	$command->output->writeln("$nb_articles articles à rattacher à leurs catégories.");

	$progressBar = new ProgressBar($command->output, $nb_articles);
	$progressBar->setFormat('verbose');
	$progressBar->setRedrawFrequency(1);
	$progressBar->start();

	foreach ($articles as $article) {
		$ids_rubriques = $categories[$article['id_wordpress']] ?? array();

		// La rubrique principale est gérée par SPIP, on ne garde que les secondaires
		$ids_secondaires = array_values(array_diff($ids_rubriques, array(intval($article['id_rubrique']))));

		$changements = polyhier_set_parents(intval($article['id_article']), 'article', $ids_secondaires);
		if ($changements['add'] or $changements['remove']) {
			$nb_modifs++;
		}

		$progressBar->advance();
	}

	// Recalculer le statut des rubriques : une catégorie qui n'a que des articles
	// secondaires doit être publiée (Polyhiérarchie s'en charge dans le pipeline calculer_rubriques)
	calculer_rubriques();

	$command->output->writeln('');
	$command->output->writeln("$nb_modifs articles dont les rubriques secondaires ont changé.");
}
