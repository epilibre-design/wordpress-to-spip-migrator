<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Noms des tables WordPress (wp2spip_table())
include_spip('inc/wp2spip');

/**
 * Étiquettes Wordpress (taxonomie post_tag) importées en mots-clés du groupe « Étiquettes », liés aux articles
 *
 * Chaque mot prend l'identifiant de son terme (id_mot = term_id), comme les rubriques pour les catégories.
 * Toutes les étiquettes sont importées, même sans contenu. Les liens viennent des relations post_tag des contenus
 * importés (articles et pages, quel que soit leur statut) : le traitement suit importer_articles.
 */
function wp2spip_importer_mots_dist($command) {
	$etiquettes = sql_allfetsel(
		'term.term_id, term.name, tax.description',
		wp2spip_table('term_taxonomy') . ' as tax join ' . wp2spip_table('terms') . ' as term on tax.term_id = term.term_id',
		'tax.taxonomy = ' . sql_quote('post_tag'),
		'',
		'term.term_id',
		'',
		'',
		$command->base
	);
	if (!$etiquettes) {
		return;
	}

	include_spip('action/editer_objet');
	include_spip('action/editer_liens');
	include_spip('inc/meta');
	include_spip('inc/wp2spip_html');

	if (!$id_groupe = wp2spip_groupe_etiquettes($command)) {
		return false;
	}

	// Déjà importés : seulement les mots du groupe ; un mot d'un autre groupe avec cet id_wordpress est une incohérence
	$ids_wordpress = array_map('intval', array_column($etiquettes, 'term_id'));
	$existants = sql_allfetsel('id_mot, id_groupe, id_wordpress', 'spip_mots', sql_in('id_wordpress', $ids_wordpress));
	if ($ailleurs = array_filter($existants, fn($mot) => intval($mot['id_groupe']) !== $id_groupe)) {
		$command->output->writeln('<error>Mots-clés venant d’étiquettes Wordpress, mais hors du groupe « Étiquettes » : ' . join(', ', array_column($ailleurs, 'id_mot')) . '.</error>');
		return false;
	}
	$deja = array_map('intval', array_column($existants, 'id_wordpress'));
	$a_creer = array_values(array_filter($etiquettes, fn($etiquette) => !in_array(intval($etiquette['term_id']), $deja)));

	// Chaque mot prend l'identifiant de son étiquette : ils doivent tous être libres
	if (!wp2spip_verifier_identifiants($command, 'mot', array_column($a_creer, 'term_id'))) {
		return false;
	}
	foreach ($a_creer as $etiquette) {
		$id_mot = intval($etiquette['term_id']);
		$set = array(
			'id_mot' => $id_mot,
			'id_wordpress' => $id_mot,
			'titre' => wp2spip_decoder_entites($etiquette['name']),
			'descriptif' => ($etiquette['description'] !== '') ? wp2spip_html_spip($etiquette['description']) : '',
		);
		if (objet_inserer('mot', $id_groupe, $set) != $id_mot) {
			return wp2spip_erreur_insertion($command, 'mot', $id_mot);
		}
	}

	// Liens : relations post_tag des contenus importés
	$relations = sql_allfetsel(
		'rel.object_id, tax.term_id',
		wp2spip_table('term_relationships') . ' as rel'
			. ' join ' . wp2spip_table('term_taxonomy') . ' as tax on tax.term_taxonomy_id = rel.term_taxonomy_id'
			. ' join ' . wp2spip_table('posts') . ' as post on post.ID = rel.object_id',
		array('tax.taxonomy = ' . sql_quote('post_tag'), sql_in('post.post_type', array('post', 'page'))),
		'',
		'tax.term_id, rel.object_id',
		'',
		'',
		$command->base
	);
	$nb_liens = 0;
	$absents = array();
	$non_crees = array();
	foreach ($relations as $relation) {
		$id_mot = intval($relation['term_id']);
		$id_article = intval($relation['object_id']);
		if (!sql_countsel('spip_articles', 'id_article = ' . $id_article)) {
			$absents[] = "$id_mot → $id_article";
			continue;
		}
		$lien = array('id_mot = ' . $id_mot, 'objet = ' . sql_quote('article'), 'id_objet = ' . $id_article);
		if (sql_countsel('spip_mots_liens', $lien)) {
			continue;
		}
		objet_associer(array('mot' => $id_mot), array('article' => $id_article));
		if (!sql_countsel('spip_mots_liens', $lien)) {
			$non_crees[] = "$id_mot → $id_article";
			continue;
		}
		$nb_liens++;
	}

	// Mots-clés visibles sur les articles
	ecrire_meta('articles_mots', 'oui');

	$command->output->writeln(count($a_creer) . ' étiquettes importées en mots-clés (groupe « Étiquettes »), ' . count($deja) . " déjà présentes ; $nb_liens liens avec les articles.");
	if ($absents) {
		$command->output->writeln('<error>Contenus étiquetés absents de SPIP, pas de lien (étiquette → contenu) : ' . join(', ', $absents) . '.</error>');
	}
	if ($non_crees) {
		$command->output->writeln('<error>Liens étiquette → article non créés : ' . join(', ', $non_crees) . '.</error>');
	}
	if ($absents or $non_crees) {
		return false;
	}
}

/**
 * Groupe « Étiquettes » : créé au premier passage, retrouvé ensuite par la méta wp2spip_groupe_etiquettes
 *
 * @param object $command
 * @return int id_groupe, ou 0 en cas d'échec
 */
function wp2spip_groupe_etiquettes($command) {
	if ($id_groupe = intval($GLOBALS['meta']['wp2spip_groupe_etiquettes'] ?? 0)) {
		if (sql_countsel('spip_groupes_mots', 'id_groupe = ' . $id_groupe)) {
			return $id_groupe;
		}
		$command->output->writeln("<error>Le groupe de mots-clés « Étiquettes » ($id_groupe, méta wp2spip_groupe_etiquettes) n’existe plus : remettre le SPIP à zéro, puis relancer un import complet.</error>");
		return 0;
	}
	$id_groupe = intval(objet_inserer('groupe_mots', null, array(
		'titre' => 'Étiquettes',
		'tables_liees' => 'articles',
		'unseul' => 'non',
		'obligatoire' => 'non',
		'minirezo' => 'oui',
		'comite' => 'oui',
		'forum' => 'non',
	)));
	if (!$id_groupe) {
		$command->output->writeln('<error>Impossible de créer le groupe de mots-clés « Étiquettes ».</error>');
		return 0;
	}
	ecrire_meta('wp2spip_groupe_etiquettes', $id_groupe);
	return $id_groupe;
}
