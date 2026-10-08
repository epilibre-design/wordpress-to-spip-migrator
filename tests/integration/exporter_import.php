<?php
/**
 * Exporte le contenu importé, indépendamment des identifiants SPIP, pour comparer deux imports
 *
 * Chaque objet est désigné par son identifiant Wordpress ; dans les textes, les raccourcis
 * SPIP (articleN, docN, imgN…) sont réécrits avec l'identifiant Wordpress de l'objet visé.
 * Une ligne par objet ou par lien, triées.
 *
 * Usage, depuis le site SPIP :
 *   spip php:eval 'include "<wp2spip>/tests/integration/exporter_import.php";' > export.tsv
 */
include_spip('inc/plugin');

$correspondances = function ($table, $cle) {
	return array_column(sql_allfetsel("$cle, id_wordpress", $table, ''), 'id_wordpress', $cle);
};
$articles = $correspondances('spip_articles', 'id_article');
$rubriques = $correspondances('spip_rubriques', 'id_rubrique');
$documents = $correspondances('spip_documents', 'id_document');
$auteurs = $correspondances('spip_auteurs', 'id_auteur');
$forums = $correspondances('spip_forum', 'id_forum');

$wp = function ($ids, $id) {
	return isset($ids[$id]) ? 'wp' . $ids[$id] : 'spip' . $id;
};
$normaliser = function ($texte) use ($articles, $rubriques, $documents, $wp) {
	$texte = str_replace(array("\r", "\n", "\t"), array('', '\n', ' '), (string) $texte);
	return preg_replace_callback('#\b(article|rubrique|document|doc|img|emb)(\d+)#', function ($m) use ($articles, $rubriques, $documents, $wp) {
		$ids = array('article' => $articles, 'rubrique' => $rubriques)[$m[1]] ?? $documents;
		return $m[1] . '#' . $wp($ids, $m[2]);
	}, $texte);
};

$lignes = array();
foreach (sql_allfetsel('*', 'spip_rubriques', 'id_wordpress > 0') as $r) {
	$lignes[] = array('rubrique', $r['id_wordpress'], $r['titre'], $normaliser($r['texte']), 'parent#' . $wp($rubriques, $r['id_parent']), $r['statut']);
}
foreach (sql_allfetsel('*', 'spip_articles', 'id_wordpress > 0') as $a) {
	$lignes[] = array('article', $a['id_wordpress'], $a['titre'], $a['statut'], $a['date'], $a['date_modif'], $a['page'], 'rubrique#' . $wp($rubriques, $a['id_rubrique']), $a['accepter_forum'], $normaliser($a['texte']));
}
foreach (sql_allfetsel('*', 'spip_documents', 'id_wordpress > 0') as $d) {
	$lignes[] = array('document', $d['id_wordpress'], $d['titre'], $normaliser($d['descriptif']), $d['date'], $d['fichier'], $d['mode'], $d['statut']);
}
foreach (sql_allfetsel('*', 'spip_auteurs', 'id_wordpress > 0') as $u) {
	$lignes[] = array('auteur', $u['id_wordpress'], $u['nom'], $u['email'], $u['login'], $u['statut'], $u['webmestre']);
}
foreach (sql_allfetsel('*', 'spip_forum', 'id_wordpress > 0') as $f) {
	$lignes[] = array('forum', $f['id_wordpress'], 'article#' . $wp($articles, $f['id_objet']), $f['statut'], $f['date_heure'], 'parent#' . $wp($forums, $f['id_parent']), 'thread#' . $wp($forums, $f['id_thread']), $f['date_thread'], $normaliser($f['texte']));
}
foreach (sql_allfetsel('*', 'spip_documents_liens', 'objet = "article"') as $l) {
	$lignes[] = array('document_article', $wp($documents, $l['id_document']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_auteurs_liens', 'objet = "article"') as $l) {
	$lignes[] = array('auteur_article', $wp($auteurs, $l['id_auteur']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_rubriques_liens', 'objet = "article"') as $l) {
	$lignes[] = array('rubrique_secondaire', $wp($rubriques, $l['id_parent']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_urls', sql_in('type', array('article', 'document'))) as $u) {
	$ids = ($u['type'] == 'article') ? $articles : $documents;
	$lignes[] = array('url', $u['type'], $wp($ids, $u['id_objet']), $u['url']);
}
if (test_plugin_actif('accesrestreint')) {
	foreach (sql_allfetsel('z.titre, l.id_objet', 'spip_zones_liens as l join spip_zones as z on z.id_zone = l.id_zone', 'l.objet = "article"') as $l) {
		$lignes[] = array('zone', $l['titre'], 'article#' . $wp($articles, $l['id_objet']));
	}
}
if (test_plugin_actif('albums')) {
	// Albums des galeries : article lié et documents dans leur ordre
	foreach (sql_allfetsel('*', 'spip_albums', '', '', 'id_album') as $album) {
		$id_album = intval($album['id_album']);
		$lies = sql_allfetsel('id_objet', 'spip_albums_liens', array('id_album = ' . $id_album, 'objet = "article"'), '', 'id_objet');
		$images = sql_allfetsel('id_document', 'spip_documents_liens', array('objet = "album"', 'id_objet = ' . $id_album), '', 'rang_lien');
		$lignes[] = array(
			'album',
			'spip' . $id_album,
			$album['titre'],
			$normaliser($album['descriptif']),
			$album['statut'],
			$album['date'],
			join(',', array_map(fn($id) => 'article#' . $wp($articles, $id), array_column($lies, 'id_objet'))),
			join(',', array_map(fn($id) => $wp($documents, $id), array_column($images, 'id_document'))),
		);
	}
}

if (test_plugin_actif('a2a')) {
	// Hiérarchie des pages : page parente, page enfant, rang
	foreach (sql_allfetsel('*', 'spip_articles_lies', 'type_liaison = "sous_page"') as $l) {
		$lignes[] = array('sous_page', 'article#' . $wp($articles, $l['id_article']), 'article#' . $wp($articles, $l['id_article_lie']), $l['rang']);
	}
}

$lignes = array_map(function ($ligne) { return join("\t", $ligne); }, $lignes);
sort($lignes);
echo join("\n", $lignes) . "\n";
