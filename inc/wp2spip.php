<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/objets');

/**
 * Identifiants, parmi ceux à créer, déjà pris par un objet SPIP
 *
 * L'import crée articles, rubriques et documents avec l'identifiant de leur source Wordpress.
 * Les contenus déjà importés étant écartés avant, un identifiant pris l'est par un objet
 * qui ne vient pas de ce contenu Wordpress (SPIP non vierge, import interrompu d'une version
 * antérieure de wp2spip…).
 *
 * @param string $objet article, rubrique ou document
 * @param array $ids identifiants Wordpress des contenus pas encore importés
 * @return array identifiants occupés
 */
function wp2spip_identifiants_occupes($objet, $ids) {
	if (!$ids) {
		return array();
	}
	$cle = id_table_objet($objet);
	$occupes = sql_allfetsel($cle, table_objet_sql($objet), sql_in($cle, array_map('intval', $ids)));
	return array_map('intval', array_column($occupes, $cle));
}

/**
 * Vérifie, avant de créer le moindre objet de ce type, que les identifiants à créer sont libres
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param array $ids
 * @return bool false si un identifiant est occupé : le traitement doit s'arrêter
 */
function wp2spip_verifier_identifiants($command, $objet, $ids) {
	if (!$occupes = wp2spip_identifiants_occupes($objet, $ids)) {
		return true;
	}
	$command->output->writeln(
		'<error>' . count($occupes) . " identifiants de $objet déjà pris par des objets SPIP qui ne viennent pas de ce Wordpress : "
		. join(', ', array_slice($occupes, 0, 20)) . (count($occupes) > 20 ? '…' : '') . '</error>'
	);
	$command->output->writeln('<error>L’import se fait dans un SPIP vierge : remettre le SPIP à zéro, puis relancer un import complet.</error>');
	return false;
}

/**
 * Signale un objet créé que l'API n'a pas pu renseigner
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param int $id
 * @param string $erreur message retourné par objet_modifier()
 * @return bool false, à retourner par le traitement
 */
function wp2spip_erreur_modification($command, $objet, $id, $erreur) {
	$command->output->writeln("\n<error>Impossible de renseigner l’objet $objet $id : $erreur</error>");
	return false;
}

/**
 * Signale un objet qui n'a pas pu être créé avec l'identifiant de son contenu Wordpress
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param int $id
 * @return bool false, à retourner par le traitement
 */
function wp2spip_erreur_insertion($command, $objet, $id) {
	$command->output->writeln("\n<error>Impossible de créer l’objet $objet $id avec l’identifiant de son contenu Wordpress.</error>");
	return false;
}

/**
 * Décode les entités HTML d'un texte, sauf celles des caractères qui ont un sens en HTML
 *
 * Wordpress stocke souvent les noms et descriptions avec des entités (&#233;, &amp;…).
 * On garde &lt; &gt; &amp; (et leurs formes numériques) pour ne pas créer de balise.
 *
 * @param string $texte
 * @return string
 */
function wp2spip_decoder_entites($texte) {
	return preg_replace_callback('/&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', function ($entite) {
		$caractere = html_entity_decode($entite[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return in_array($caractere, array('<', '>', '&')) ? $entite[0] : $caractere;
	}, (string) $texte);
}

/**
 * Tables WordPress lues par wp2spip, sans leur préfixe
 *
 * @return array
 */
function wp2spip_tables_wordpress() {
	return array('posts', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'options', 'users', 'usermeta', 'comments');
}

/**
 * Préfixe des tables WordPress de l'import : celui noté par la commande, wp_ pour un SPIP qui n'a pas encore importé
 *
 * @return string
 */
function wp2spip_prefixe_tables() {
	return $GLOBALS['meta']['wp2spip_prefixe_tables'] ?? 'wp_';
}

/**
 * Nom complet d'une table WordPress
 *
 * @param string $nom nom sans préfixe : posts, postmeta…
 * @return string
 */
function wp2spip_table($nom) {
	return wp2spip_prefixe_tables() . $nom;
}

/**
 * Préfixe valide : la règle de WordPress (wp-admin/setup-config.php), qui permet de le placer dans les requêtes
 *
 * @param string $prefixe
 * @return bool
 */
function wp2spip_prefixe_valide($prefixe) {
	return (bool) preg_match('/^[A-Za-z0-9_]+$/', (string) $prefixe);
}

/**
 * Préfixe des tables déclaré dans le code d'un wp-config.php, lu sans l'exécuter
 *
 * L'affectation de $table_prefix doit y figurer exactement une fois, avec une valeur littérale : sinon
 * (absente, conditionnelle, calculée) le préfixe n'est pas supposé.
 *
 * @param string $source contenu de wp-config.php
 * @return string|null préfixe, ou null s'il est introuvable
 */
function wp2spip_prefixe_wp_config($source) {
	$jetons = array_values(array_filter(
		token_get_all((string) $source),
		fn($jeton) => !is_array($jeton) or !in_array($jeton[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))
	));
	$prefixes = array();
	$affectations = 0;
	foreach ($jetons as $i => $jeton) {
		if (!is_array($jeton) or $jeton[0] !== T_VARIABLE or $jeton[1] !== '$table_prefix' or ($jetons[$i + 1] ?? null) !== '=') {
			continue;
		}
		$affectations++;
		$valeur = $jetons[$i + 2] ?? null;
		if (is_array($valeur) and $valeur[0] === T_CONSTANT_ENCAPSED_STRING and ($jetons[$i + 3] ?? null) === ';') {
			$prefixes[] = ($valeur[1][0] === "'")
				? strtr(substr($valeur[1], 1, -1), array('\\\\' => '\\', "\\'" => "'"))
				: stripcslashes(substr($valeur[1], 1, -1));
		}
	}
	return ($affectations === 1 and count($prefixes) === 1) ? $prefixes[0] : null;
}

/**
 * Tables lues par wp2spip absentes de la base WordPress
 *
 * @param string $base identifiant de la base WordPress dans SPIP
 * @param string $prefixe
 * @return array noms complets des tables absentes
 */
function wp2spip_tables_wordpress_absentes($base, $prefixe) {
	$absentes = array();
	foreach (wp2spip_tables_wordpress() as $nom) {
		if (!sql_showtable($prefixe . $nom, true, $base)) {
			$absentes[] = $prefixe . $nom;
		}
	}
	return $absentes;
}
