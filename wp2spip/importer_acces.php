<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Publie les contenus privés et protégés par mot de passe dans des zones d'Accès restreint
 *
 * Dans Wordpress, un contenu privé n'est visible que des éditeurs, un contenu protégé
 * que de ceux qui ont le mot de passe. importer_articles les laisse non publiés ;
 * si le plugin Accès restreint est actif, on les publie ici en les liant à une zone
 * réservée aux visiteurs identifiés (les mots de passe Wordpress ne sont pas repris).
 */
function wp2spip_importer_acces_dist($command) {
	include_spip('inc/plugin');
	if (!test_plugin_actif('accesrestreint')) {
		$command->output->writeln('Accès restreint n’est pas actif : les contenus privés ou protégés par mot de passe restent non publiés.');
		return;
	}

	// Les contenus Wordpress concernés
	$wp_posts = sql_allfetsel(
		'ID, post_status, post_password, post_date, post_modified',
		'wp_posts',
		array(
			sql_in('post_type', array('post', 'page')),
			'(post_status = "private" or (post_password != "" and ' . sql_in('post_status', array('publish', 'future')) . '))',
		),
		'',
		'ID',
		'',
		'',
		$command->base
	);
	$wp_posts = array_column($wp_posts, null, 'ID');

	include_spip('action/editer_objet');
	include_spip('action/editer_liens');
	include_spip('inc/autoriser');
	include_spip('inc/wp2spip');

	$zones = array();
	$nb_publies = 0;
	$nb_deja = 0;

	foreach ($wp_posts as $id_wordpress => $wp_post) {
		if (!$article = sql_fetsel('id_article, id_rubrique', 'spip_articles', 'id_wordpress = ' . intval($id_wordpress))) {
			continue;
		}
		$id_article = intval($article['id_article']);
		$cle = ($wp_post['post_status'] == 'private') ? 'prives' : 'proteges';
		$zones[$cle] ??= wp2spip_zone_acces($cle);
		if (!$zones[$cle]) {
			$command->output->writeln("<error>Impossible de créer la zone d’accès « $cle ».</error>");
			return false;
		}

		// Déjà traité lors d'un import précédent : on ne touche plus à ce qu'en a fait le site
		if (sql_countsel('spip_zones_liens', array('id_zone = ' . $zones[$cle], 'objet = "article"', 'id_objet = ' . $id_article))) {
			$nb_deja++;
			continue;
		}

		// Lier à la zone d'abord : une fois publié, le contenu doit déjà être protégé
		objet_associer(array('zone' => $zones[$cle]), array('article' => $id_article));
		if (!sql_countsel('spip_zones_liens', array('id_zone = ' . $zones[$cle], 'objet = "article"', 'id_objet = ' . $id_article))) {
			$command->output->writeln("<error>Impossible de lier l’article $id_article à la zone {$zones[$cle]} : il n’est pas publié.</error>");
			return false;
		}

		// Publier en gardant la date Wordpress (sinon SPIP mettrait la date du jour)
		autoriser_exception('instituer', 'article', $id_article, true);
		autoriser_exception('publierdans', 'rubrique', intval($article['id_rubrique']), true);
		if ($erreur = objet_modifier('article', $id_article, array('statut' => 'publie', 'date' => $wp_post['post_date']))) {
			return wp2spip_erreur_modification($command, 'article', $id_article, $erreur);
		}
		if (sql_getfetsel('statut', 'spip_articles', 'id_article = ' . $id_article) != 'publie') {
			$command->output->writeln("<error>L’article $id_article n’a pas pu être publié dans sa zone.</error>");
			return false;
		}

		// objet_associer() et objet_modifier() ont remis date_modif à la date du jour
		sql_updateq('spip_articles', array('date_modif' => $wp_post['post_modified']), 'id_article = ' . $id_article);
		$nb_publies++;
	}

	$command->output->writeln(count($wp_posts) . ' contenus privés ou protégés dans Wordpress : ' . $nb_publies . ' publiés en zone restreinte, ' . $nb_deja . ' déjà traités.');
}

/**
 * Retrouve ou crée la zone d'accès d'un type de contenu Wordpress
 *
 * L'identifiant est gardé en configuration pour réutiliser la même zone à chaque import,
 * même si elle a été renommée depuis.
 *
 * @param string $cle prives|proteges
 * @return int
 */
function wp2spip_zone_acces($cle) {
	include_spip('inc/config');
	if (
		$id_zone = intval(lire_config("wp2spip/zones/$cle"))
		and sql_countsel('spip_zones', 'id_zone = ' . $id_zone)
	) {
		return $id_zone;
	}

	$titres = array(
		'prives' => 'Wordpress : contenus privés',
		'proteges' => 'Wordpress : contenus protégés par mot de passe',
	);
	$descriptifs = array(
		'prives' => 'Contenus privés dans Wordpress, réservés ici aux visiteurs identifiés.',
		'proteges' => 'Contenus protégés par mot de passe dans Wordpress, réservés ici aux visiteurs identifiés (les mots de passe n’ont pas été repris).',
	);

	include_spip('action/editer_zone');
	autoriser_exception('creer', 'zone', '*', true);
	$id_zone = intval(zone_inserer());
	autoriser_exception('creer', 'zone', '*', false);

	zone_modifier($id_zone, array(
		'titre' => $titres[$cle],
		'descriptif' => $descriptifs[$cle],
		'publique' => 'oui',
		'privee' => 'non',
		'autoriser_si_connexion' => 'oui',
	));
	ecrire_config("wp2spip/zones/$cle", $id_zone);

	return $id_zone;
}
