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
