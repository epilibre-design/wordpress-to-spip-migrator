<?php
/**
 * Vérifie, sur un SPIP importé, que les identifiants Wordpress sont conservés (spec § 6, sous-projet 1)
 *
 * - articles, rubriques et documents importés : identifiant SPIP = id_wordpress ;
 * - tous les contenus et catégories Wordpress sont importés ;
 * - liens internes : chaque [->articleN] désigne un article existant, et aucun lien
 *   ?p= ou ?page_id= vers le site d'origine ne reste dans les textes ;
 * - contenus privés ou protégés : jamais publiés hors d'une zone d'Accès restreint, et,
 *   si le plugin est actif, publiés dans leur zone.
 *
 * Usage, depuis le site SPIP (base Wordpress déclarée sous le nom "wordpress") :
 *   spip php:eval 'include "<wp2spip>/tests/integration/verifier_identifiants.php";'
 * Affiche OK, ou ECHEC et la liste des écarts avec le code de sortie 1.
 */
include_spip('base/objets');
include_spip('wp2spip/importer_articles');
$base = 'wordpress';
$echecs = array();

foreach (array('article', 'rubrique', 'document') as $objet) {
	$table = table_objet_sql($objet);
	$cle = id_table_objet($objet);
	$total = sql_countsel($table, 'id_wordpress > 0');
	$ecarts = sql_countsel($table, "id_wordpress > 0 and $cle != id_wordpress");
	echo "$table : $total objets importés, $ecarts identifiants différents de id_wordpress\n";
	if ($ecarts) {
		$echecs[] = "$table : $ecarts objets dont l'identifiant diffère de id_wordpress";
	}
}

$nb_wp = sql_countsel('wp_posts', sql_in('post_type', array('post', 'page')), '', '', $base);
$nb_spip = sql_countsel('spip_articles', 'id_wordpress > 0');
if ($nb_wp != $nb_spip) {
	$echecs[] = "articles : $nb_spip importés pour $nb_wp contenus Wordpress";
}
$nb_wp = sql_countsel('wp_term_taxonomy', sql_in('taxonomy', array('category', 'link_category')), '', '', $base);
$nb_spip = sql_countsel('spip_rubriques', 'id_wordpress > 0');
if ($nb_wp != $nb_spip) {
	$echecs[] = "rubriques : $nb_spip importées pour $nb_wp catégories Wordpress";
}
// Médias : ceux que importer_documents sélectionne (pièces jointes au statut inherit), comparés par identifiant
$ids_wp = array_map('intval', array_column(sql_allfetsel('ID', 'wp_posts', array('post_type = "attachment"', 'post_status = "inherit"'), '', '', '', '', $base), 'ID'));
$ids_spip = array_map('intval', array_column(sql_allfetsel('id_wordpress', 'spip_documents', 'id_wordpress > 0'), 'id_wordpress'));
if ($manquants = array_diff($ids_wp, $ids_spip)) {
	$echecs[] = 'documents : ' . count($manquants) . ' médias Wordpress non importés (' . join(', ', array_slice($manquants, 0, 10)) . (count($manquants) > 10 ? '…' : '') . ')';
}
if ($en_trop = array_diff($ids_spip, $ids_wp)) {
	$echecs[] = 'documents : ' . count($en_trop) . ' documents sans média Wordpress correspondant (' . join(', ', array_slice($en_trop, 0, 10)) . (count($en_trop) > 10 ? '…' : '') . ')';
}

// Blocs de l'éditeur : plus de commentaire <!-- wp: hors des blocs de code, plus de classe de présentation
// has-… ou is-… ; chaque <albumN> désigne un album existant, lié à l'article et qui contient un document
$avec_albums = (bool) sql_showtable('spip_albums', true);
foreach (sql_allfetsel('id_article, texte', 'spip_articles', 'id_wordpress > 0', '', 'id_article') as $article) {
	$id = intval($article['id_article']);
	$hors_code = preg_replace('#<(code|cadre)\b.*?</\1>#is', '', $article['texte']);
	if (strpos($hors_code, '<!-- wp:') !== false) {
		$echecs[] = "article $id : commentaire de bloc <!-- wp: restant";
	}
	if (preg_match('/\bclass=["\'][^"\']*\b(?:has|is)-[a-z0-9-]+/i', $hors_code, $trouve)) {
		$echecs[] = "article $id : classe de présentation restante ($trouve[0])";
	}
	preg_match_all('/<album(\d+)>/', $article['texte'], $trouves);
	foreach (array_unique($trouves[1]) as $id_album) {
		if (
			!$avec_albums
			or !sql_countsel('spip_albums_liens', array('id_album = ' . intval($id_album), 'objet = "article"', 'id_objet = ' . $id))
			or !sql_countsel('spip_documents_liens', array('objet = "album"', 'id_objet = ' . intval($id_album)))
		) {
			$echecs[] = "article $id : album $id_album absent, non lié à l'article, ou vide";
		}
		elseif ($non_publies = sql_allfetsel(
			'L.id_document',
			'spip_documents_liens AS L JOIN spip_documents AS D ON D.id_document = L.id_document',
			array('L.objet = "album"', 'L.id_objet = ' . intval($id_album), 'D.statut != "publie"')
		)) {
			$echecs[] = "article $id : album $id_album avec des documents non publiés (" . join(', ', array_column($non_publies, 'id_document')) . ')';
		}
	}
}

// Contenus privés ou protégés par mot de passe : jamais publiés hors d'une zone ;
// avec Accès restreint, les privés et les protégés publiés ou programmés sont publiés dans une zone
include_spip('inc/plugin');
$acces = test_plugin_actif('accesrestreint');
$restreints = sql_allfetsel(
	'ID, post_status',
	'wp_posts',
	array(sql_in('post_type', array('post', 'page')), '(post_status = "private" or post_password != "")'),
	'', '', '', '', $base
);
foreach ($restreints as $restreint) {
	$id = intval($restreint['ID']);
	$statut = sql_getfetsel('statut', 'spip_articles', 'id_article = ' . $id);
	$en_zone = $acces ? sql_countsel('spip_zones_liens', array('objet = "article"', 'id_objet = ' . $id)) : 0;
	if ($statut == 'publie' and !$en_zone) {
		$echecs[] = "article $id : contenu privé ou protégé publié hors d'une zone";
	}
	if (
		$acces
		and in_array($restreint['post_status'], array('private', 'publish', 'future'))
		and !($statut == 'publie' and $en_zone)
	) {
		$echecs[] = "article $id : contenu privé ou protégé non publié dans sa zone";
	}
}
echo count($restreints) . " contenus privés ou protégés vérifiés" . ($acces ? '' : ' (sans Accès restreint)') . "\n";

$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', $base);
foreach (sql_allfetsel('id_article, texte', 'spip_articles', 'id_wordpress > 0') as $article) {
	preg_match_all('#->article(\d+)\]#', $article['texte'], $trouves);
	foreach (array_unique($trouves[1]) as $id) {
		if (!sql_countsel('spip_articles', 'id_article = ' . intval($id))) {
			$echecs[] = "article {$article['id_article']} : lien vers article$id, inexistant";
		}
	}
	preg_match_all('#->([^\]]*[?&](?:p|page_id)=\d+[^\]]*)\]#', $article['texte'], $trouves);
	foreach ($trouves[1] as $url) {
		if (wp2spip_url_du_site($url, $url_wordpress)) {
			$echecs[] = "article {$article['id_article']} : lien Wordpress non converti $url";
		}
	}
}

if ($echecs) {
	echo "ECHEC\n- " . join("\n- ", $echecs) . "\n";
	exit(1);
}
echo "OK\n";
