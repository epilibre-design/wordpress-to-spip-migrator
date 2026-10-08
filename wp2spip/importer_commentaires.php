<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Importe les commentaires Wordpress en messages de forum, en gardant les fils de discussion
 *
 * - commentaires approuvés => publiés, en attente => proposés ; spam, corbeille,
 *   trackbacks et pingbacks ne sont pas importés
 * - comment_type vide (Wordpress < 5.5) ou "comment" (Wordpress >= 5.5)
 * - chaque message garde son id_wordpress : un nouvel import ne crée pas de doublon
 */
function wp2spip_importer_commentaires_dist($command) {
	include_spip('inc/plugin');
	if (!test_plugin_actif('forum')) {
		$command->output->writeln('Le plugin Forum n’est pas actif : les commentaires ne sont pas importés.');
		return;
	}

	$correspondance_statuts = array(
		'1' => 'publie',
		'0' => 'prop',
	);

	$wp_comments = sql_allfetsel(
		'*',
		'wp_comments',
		array(
			sql_in('comment_approved', array_keys($correspondance_statuts)),
			sql_in('comment_type', array('', 'comment')),
		),
		'',
		'comment_ID',
		'',
		'',
		$command->base
	);
	if (!$wp_comments) {
		$command->output->writeln('Aucun commentaire à importer.');
		return;
	}
	$wp_comments = array_column($wp_comments, null, 'comment_ID');

	include_spip('sale_fonctions');

	// Correspondances Wordpress => SPIP déjà connues
	$articles = array_column(sql_allfetsel('id_article, id_wordpress', 'spip_articles', 'id_wordpress > 0'), 'id_article', 'id_wordpress');
	$auteurs = array_column(sql_allfetsel('id_auteur, id_wordpress', 'spip_auteurs', 'id_wordpress > 0'), 'id_auteur', 'id_wordpress');
	$forums = array_column(sql_allfetsel('id_forum, id_wordpress', 'spip_forum', 'id_wordpress > 0'), 'id_forum', 'id_wordpress');

	$nb_comments = count($wp_comments);
	$nb_import = 0;
	$nb_sans_article = 0;
	$command->output->writeln("$nb_comments commentaires à importer.");

	$progressBar = new ProgressBar($command->output, $nb_comments);
	$progressBar->setFormat('verbose');
	$progressBar->setRedrawFrequency(1);
	$progressBar->start();

	// 1. Créer les messages
	foreach ($wp_comments as $id_comment => $wp_comment) {
		$progressBar->advance();

		if (!$id_article = intval($articles[$wp_comment['comment_post_ID']] ?? 0)) {
			$nb_sans_article++;
			unset($wp_comments[$id_comment]);
			continue;
		}
		if (isset($forums[$id_comment])) {
			continue;
		}

		$forum = array(
			'objet' => 'article',
			'id_objet' => $id_article,
			'date_heure' => $wp_comment['comment_date'],
			'texte' => sale($wp_comment['comment_content']),
			'auteur' => $wp_comment['comment_author'],
			'email_auteur' => $wp_comment['comment_author_email'],
			'url_site' => $wp_comment['comment_author_url'],
			'ip' => $wp_comment['comment_author_IP'],
			'id_auteur' => intval($auteurs[$wp_comment['user_id']] ?? 0),
			'statut' => $correspondance_statuts[$wp_comment['comment_approved']],
			'id_wordpress' => intval($id_comment),
		);

		if ($id_forum = sql_insertq('spip_forum', $forum)) {
			$forums[$id_comment] = $id_forum;
			$nb_import++;
		}
	}

	// 2. Reconstituer les fils : id_parent = message auquel on répond, id_thread = premier message du fil
	// (en deux temps, car une réponse peut avoir été importée avant son parent)
	$threads = array();
	foreach ($wp_comments as $id_comment => $wp_comment) {
		if (!isset($forums[$id_comment])) {
			continue;
		}
		$id_parent = intval($forums[$wp_comment['comment_parent']] ?? 0);

		// Remonter jusqu'au premier message du fil ; un parent non importé (spam, corbeille…) fait commencer un nouveau fil
		$racine = $id_comment;
		$vus = array();
		while (
			($parent = $wp_comments[$racine]['comment_parent'] ?? 0)
			and isset($forums[$parent])
			and !isset($vus[$parent])
		) {
			$vus[$racine] = true;
			$racine = $parent;
		}
		$id_thread = intval($forums[$racine]);

		sql_updateq('spip_forum', array('id_parent' => $id_parent, 'id_thread' => $id_thread), 'id_forum = ' . intval($forums[$id_comment]));
		$threads[$id_thread] = true;
	}

	// 3. date_thread = date du dernier message publié du fil, comme le fait SPIP
	foreach (array_keys($threads) as $id_thread) {
		$date_thread = sql_getfetsel('max(date_heure)', 'spip_forum', array('id_thread = ' . $id_thread, 'statut = "publie"'))
			?: sql_getfetsel('max(date_heure)', 'spip_forum', 'id_thread = ' . $id_thread);
		sql_updateq('spip_forum', array('date_thread' => $date_thread), 'id_thread = ' . $id_thread);
	}

	$command->output->writeln('');
	$command->output->writeln("$nb_import messages importés, " . count($threads) . " fils de discussion."
		. ($nb_sans_article ? " $nb_sans_article commentaires ignorés (contenu non importé)." : ''));
}
