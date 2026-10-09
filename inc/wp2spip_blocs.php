<?php

/**
 * Conversion des blocs de l'éditeur Wordpress (Gutenberg) et des galeries
 *
 * wp2spip_convertir_blocs() reçoit le contenu brut, avant la conversion du HTML (wp2spip_html_spip()) : les blocs sont convertis d'après leurs
 * commentaires <!-- wp:… --> et leurs attributs JSON. Ce qui est déjà au format SPIP (raccourcis, balises
 * de structure gardées) est remplacé par un marqueur wp2spipbloc<N> le temps de cette conversion,
 * puis réinséré par wp2spip_restaurer_blocs().
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');
include_spip('inc/wp2spip');
include_spip('inc/wp2spip_html');

/**
 * Contexte de conversion d'un contenu Wordpress
 *
 * @param object $command commande d'import (base, output)
 * @param array $wp_post ligne de wp_posts
 * @param string $url_wordpress
 * @return array
 */
function wp2spip_contexte_blocs($command, $wp_post, $url_wordpress) {
	$contenu = $wp_post['post_content'];
	return array(
		'command' => $command,
		'base' => $command->base,
		'url_wordpress' => $url_wordpress,
		'id_wordpress' => intval($wp_post['ID']),
		'titre' => $wp_post['post_title'],
		'date' => $wp_post['post_date'],
		// Galeries du contenu : blocs, et raccourcis [gallery] hors des blocs gallery
		'nb_galeries' => substr_count($contenu, '<!-- wp:gallery') + preg_match_all('/\[gallery\b/', $contenu),
		'galerie' => 0,
		'albums' => array(),
		'protections' => array(),
		'bilan' => array('convertis' => array(), 'dynamiques' => array(), 'inconnus' => array(), 'medias_introuvables' => 0, 'images_retirees' => 0),
	);
}

/**
 * Convertit les blocs et les galeries d'un contenu Wordpress, avant la conversion du HTML
 *
 * @param string $contenu
 * @param array $contexte de wp2spip_contexte_blocs(), complété (marqueurs, albums créés, bilan)
 * @return string texte à passer à wp2spip_html_spip(), puis à wp2spip_restaurer_blocs()
 */
function wp2spip_convertir_blocs($contenu, &$contexte) {
	if (strpos($contenu, '<!-- wp:') !== false) {
		$contenu = wp2spip_blocs_interieur(wp2spip_analyser_blocs($contenu), $contexte);
	}
	// Raccourcis [gallery] de l'éditeur classique (ou d'un bloc shortcode)
	return preg_replace_callback('/\[gallery\b([^\]]*)\]/', function ($raccourci) use (&$contexte) {
		return wp2spip_convertir_raccourci_gallery($raccourci[0], $raccourci[1], $contexte);
	}, $contenu);
}

/**
 * Réinsère ce que les marqueurs protégeaient, après la conversion du HTML
 *
 * @param string $texte
 * @param array $contexte
 * @return string
 */
function wp2spip_restaurer_blocs($texte, $contexte) {
	return preg_replace_callback('/wp2spipbloc(\d+)/', function ($marqueur) use ($contexte) {
		return $contexte['protections'][intval($marqueur[1])] ?? $marqueur[0];
	}, $texte);
}

/**
 * Remplace un texte déjà au format SPIP par un marqueur, sur sa propre ligne
 *
 * @param string $texte
 * @param array $contexte
 * @return string
 */
function wp2spip_proteger($texte, &$contexte) {
	$n = count($contexte['protections']);
	$contexte['protections'][$n] = $texte;
	return "\n\nwp2spipbloc$n\n\n";
}

/**
 * Découpe un contenu en arbre de blocs, comme parse_blocks() de Wordpress
 *
 * Chaque bloc : array('nom' => 'core/paragraph', 'attributs' => array(…), 'morceaux' => array(…)),
 * les morceaux étant son HTML propre (chaînes) et ses blocs enfants (tableaux), dans l'ordre.
 * Le HTML hors bloc est un morceau de la racine (nom vide).
 *
 * @param string $contenu
 * @return array bloc racine
 */
function wp2spip_analyser_blocs($contenu) {
	$motif = '/<!--\s+(?P<fermant>\/)?wp:(?P<espace>[a-z][a-z0-9_-]*\/)?(?P<nom>[a-z][a-z0-9_-]*)\s+(?P<attributs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<vide>\/)?-->/s';
	$racine = array('nom' => '', 'attributs' => array(), 'morceaux' => array());
	$pile = array(&$racine);
	$position = 0;
	while (preg_match($motif, $contenu, $m, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $position)) {
		$courant = &$pile[count($pile) - 1];
		if ($m[0][1] > $position) {
			$courant['morceaux'][] = substr($contenu, $position, $m[0][1] - $position);
		}
		if ($m['fermant'][0] !== null) {
			// Fermeture du bloc courant (un commentaire fermant en trop est ignoré)
			if (count($pile) > 1) {
				array_pop($pile);
			}
		}
		else {
			$courant['morceaux'][] = array(
				'nom' => ($m['espace'][0] ?? 'core/') . $m['nom'][0],
				'attributs' => ($m['attributs'][0] !== null) ? (json_decode($m['attributs'][0], true) ?: array()) : array(),
				'morceaux' => array(),
			);
			if ($m['vide'][0] === null) {
				$pile[] = &$courant['morceaux'][count($courant['morceaux']) - 1];
			}
		}
		unset($courant);
		$position = $m[0][1] + strlen($m[0][0]);
	}
	if ($position < strlen($contenu)) {
		$pile[count($pile) - 1]['morceaux'][] = substr($contenu, $position);
	}
	return $racine;
}

/**
 * HTML propre d'un bloc, sans ses enfants (innerHTML de Wordpress)
 *
 * @param array $bloc
 * @return string
 */
function wp2spip_bloc_html($bloc) {
	return join('', array_filter($bloc['morceaux'], 'is_string'));
}

/**
 * Contenu d'un bloc : son HTML propre, et ses enfants convertis
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_blocs_interieur($bloc, &$contexte) {
	$texte = '';
	foreach ($bloc['morceaux'] as $morceau) {
		$texte .= is_string($morceau) ? $morceau : wp2spip_convertir_bloc($morceau, $contexte);
	}
	return $texte;
}

/**
 * Convertit un bloc selon son type
 *
 * Le pipeline wp2spip_bloc reçoit le bloc (args) et le texte produit (data, null si aucune conversion
 * ne prend ce type en charge) : une extension peut ajouter ou remplacer une conversion.
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_convertir_bloc($bloc, &$contexte) {
	$type = preg_replace('#^core/#', '', $bloc['nom']);
	if (strpos($type, 'core-embed/') === 0) {
		$type = 'embed';
	}
	$texte = null;
	if ($fonction = wp2spip_conversion_bloc($type)) {
		$texte = $fonction($bloc, $contexte);
	}
	$texte = pipeline('wp2spip_bloc', array(
		'args' => array('bloc' => $bloc, 'type' => $type, 'id_wordpress' => $contexte['id_wordpress']),
		'data' => $texte,
	));
	if ($texte === null) {
		// Bloc inconnu : son contenu est gardé
		$contexte['bilan']['inconnus'][$bloc['nom']] = ($contexte['bilan']['inconnus'][$bloc['nom']] ?? 0) + 1;
		return wp2spip_blocs_interieur($bloc, $contexte);
	}
	if ($fonction and !in_array($fonction, array('wp2spip_bloc_retirer', 'wp2spip_bloc_laisser'))) {
		$contexte['bilan']['convertis'][$type] = ($contexte['bilan']['convertis'][$type] ?? 0) + 1;
	}
	return $texte;
}

/**
 * Fonction de conversion d'un type de bloc
 *
 * @param string $type nom du bloc sans core/ (embed pour core-embed/…)
 * @return string nom de fonction, ou '' si le type n'est pas pris en charge
 */
function wp2spip_conversion_bloc($type) {
	$conversions = array(
		'image' => 'wp2spip_bloc_image',
		'gallery' => 'wp2spip_bloc_gallery',
		'cover' => 'wp2spip_bloc_cover',
		'media-text' => 'wp2spip_bloc_media_text',
		'button' => 'wp2spip_bloc_button',
		'audio' => 'wp2spip_bloc_media',
		'video' => 'wp2spip_bloc_media',
		'file' => 'wp2spip_bloc_media',
		'embed' => 'wp2spip_bloc_embed',
		'spacer' => 'wp2spip_bloc_vide',
		'more' => 'wp2spip_bloc_vide',
		'nextpage' => 'wp2spip_bloc_vide',
	);
	foreach (array('columns', 'column', 'group', 'buttons', 'pullquote', 'table', 'quote') as $structure) {
		$conversions[$structure] = 'wp2spip_bloc_structure';
	}
	foreach (array('paragraph', 'heading', 'list', 'list-item', 'code', 'preformatted', 'verse', 'html', 'separator', 'shortcode', 'freeform') as $texte) {
		$conversions[$texte] = 'wp2spip_bloc_laisser';
	}
	if (isset($conversions[$type])) {
		return $conversions[$type];
	}
	if (wp2spip_bloc_dynamique($type)) {
		return 'wp2spip_bloc_retirer';
	}
	return '';
}

/**
 * Bloc dynamique : son contenu est produit par Wordpress à l'affichage, rien n'est enregistré
 *
 * @param string $type
 * @return bool
 */
function wp2spip_bloc_dynamique($type) {
	$dynamiques = array(
		'query', 'latest-posts', 'latest-comments', 'archives', 'categories', 'calendar', 'tag-cloud', 'rss',
		'search', 'page-list', 'navigation', 'social-links', 'social-link', 'loginout', 'avatar', 'read-more',
		'term-description', 'template-part', 'pattern', 'widget-group', 'legacy-widget', 'home-link',
		'navigation-link', 'navigation-submenu',
	);
	return in_array($type, $dynamiques) or preg_match('/^(post-|comment|query-|site-)/', $type);
}

function wp2spip_bloc_laisser($bloc, &$contexte) {
	return wp2spip_blocs_interieur($bloc, $contexte);
}

function wp2spip_bloc_vide($bloc, &$contexte) {
	return '';
}

/**
 * Bloc dynamique : retiré s'il n'a rien enregistré, sinon son contenu enregistré est gardé
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_bloc_retirer($bloc, &$contexte) {
	$interieur = wp2spip_blocs_interieur($bloc, $contexte);
	// Contenu enregistré : du texte, ou un média, une fois les marqueurs réinsérés (des balises de structure vides ne comptent pas)
	$apercu = wp2spip_restaurer_blocs($interieur, $contexte);
	if (
		trim(html_entity_decode(strip_tags($apercu))) !== ''
		or preg_match('/<(?:img|doc|emb|album)\d+|<(?:img|iframe|video|audio)\b/i', $apercu)
	) {
		return $interieur;
	}
	$contexte['bilan']['dynamiques'][$bloc['nom']] = ($contexte['bilan']['dynamiques'][$bloc['nom']] ?? 0) + 1;
	return '';
}

/**
 * Balise ouvrante nettoyée : seules les classes wp-block-… sont gardées, sans les autres attributs
 *
 * @param string $balise nom de la balise
 * @param string $attributs attributs d'origine
 * @return string
 */
function wp2spip_balise_nettoyee($balise, $attributs) {
	$classes = array();
	if (preg_match('/\bclass=["\']([^"\']*)["\']/i', $attributs, $trouve)) {
		$classes = array_filter(preg_split('/\s+/', $trouve[1]), fn($classe) => strpos($classe, 'wp-block-') === 0);
	}
	return '<' . strtolower($balise) . ($classes ? ' class="' . join(' ', $classes) . '"' : '') . '>';
}

/**
 * Balises ouvrantes d'un HTML nettoyées : seules restent les classes wp-block-…, et les attributs
 * qui portent du contenu (lien, image, fusion de cellules)
 *
 * @param string $html
 * @return string
 */
function wp2spip_nettoyer_balises($html) {
	return preg_replace_callback('#<([a-z][a-z0-9]*)\b([^>]*?)(/?)>#i', function ($balise) {
		$garder = array('a' => array('href'), 'img' => array('src', 'alt'), 'td' => array('colspan', 'rowspan'), 'th' => array('colspan', 'rowspan', 'scope'));
		$nettoyee = rtrim(wp2spip_balise_nettoyee($balise[1], $balise[2]), '>');
		foreach ($garder[strtolower($balise[1])] ?? array() as $attribut) {
			if (preg_match('#\b' . $attribut . '=(["\'])(.*?)\1#is', $balise[2], $valeur)) {
				$nettoyee .= " $attribut=\"{$valeur[2]}\"";
			}
		}
		return $nettoyee . ($balise[3] ? ' />' : '>');
	}, $html);
}

/**
 * Bloc de mise en page : sa balise englobante est gardée, nettoyée, et son contenu converti
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_bloc_structure($bloc, &$contexte) {
	// HTML propre nettoyé (classes de présentation, styles…), enfants convertis
	$interieur = '';
	foreach ($bloc['morceaux'] as $morceau) {
		$interieur .= is_string($morceau) ? wp2spip_nettoyer_balises($morceau) : wp2spip_convertir_bloc($morceau, $contexte);
	}
	// Une légende (tableau…) dans son propre paragraphe : collée au tableau, elle casserait sa dernière ligne
	$interieur = preg_replace('#<figcaption\b#i', "\n\n<figcaption", $interieur);
	if (!preg_match('#^\s*<([a-z][a-z0-9]*)\b([^>]*)>(.*)</\1>\s*$#is', $interieur, $trouve)) {
		return $interieur;
	}
	return wp2spip_proteger(wp2spip_balise_nettoyee($trouve[1], $trouve[2]), $contexte)
		. $trouve[3]
		. wp2spip_proteger('</' . strtolower($trouve[1]) . '>', $contexte);
}

/**
 * Document SPIP d'un média, d'après son fichier puis d'après son identifiant Wordpress
 *
 * @param string $url URL du fichier ('' si inconnue)
 * @param int $id_wordpress identifiant du média (0 si inconnu)
 * @param array $contexte
 * @return int id_document, ou 0
 */
function wp2spip_bloc_document($url, $id_wordpress, $contexte) {
	include_spip('wp2spip/importer_articles');
	if ($url and $id_document = wp2spip_chercher_document($url, $contexte['url_wordpress'], $contexte['base'])) {
		return $id_document;
	}
	if ($id_wordpress = intval($id_wordpress)) {
		return intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . $id_wordpress));
	}
	return 0;
}

/**
 * HTML d'une légende (figcaption)
 *
 * @param string $html
 * @param string $classe classe de la légende cherchée ('' : la première)
 * @return string
 */
function wp2spip_bloc_legende($html, $classe = '') {
	$motif = $classe
		? '#<figcaption\b[^>]*\bclass=["\'][^"\']*\b' . preg_quote($classe, '#') . '\b[^"\']*["\'][^>]*>(.*?)</figcaption>#is'
		: '#<figcaption\b[^>]*>(.*?)</figcaption>#is';
	if (!preg_match($motif, $html, $trouve)) {
		return '';
	}
	return trim($trouve[1]);
}

/**
 * La légende d'une image devient le descriptif de son document
 *
 * @param int $id_document
 * @param string $legende HTML de la légende
 */
function wp2spip_bloc_legender($id_document, $legende) {
	if ($legende !== '') {
		sql_updateq('spip_documents', array('descriptif' => wp2spip_html_spip($legende, array('autop' => false))), 'id_document = ' . intval($id_document));
	}
}

function wp2spip_bloc_image($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve) ? $trouve[1] : '';
	if (!$id_document = wp2spip_bloc_document($src, $bloc['attributs']['id'] ?? 0, $contexte)) {
		$contexte['bilan']['medias_introuvables']++;
		return $html;
	}
	$alignements = array('left' => 'left', 'right' => 'right', 'center' => 'center', 'wide' => 'center', 'full' => 'center');
	$align = $alignements[$bloc['attributs']['align'] ?? ''] ?? '';
	$image = "<img$id_document" . ($align ? "|$align" : '') . '>';
	if (preg_match('#<a\b[^>]*\bhref=["\']([^"\']+)["\'][^>]*>\s*<img#i', $html, $trouve)) {
		$image = "[$image->" . html_entity_decode($trouve[1]) . ']';
	}
	wp2spip_bloc_legender($id_document, wp2spip_bloc_legende($html));
	return wp2spip_proteger($image, $contexte);
}

/**
 * Images d'un bloc galerie, dans l'ordre : blocs image enfants (Wordpress 5.9 et après),
 * ou liste blocks-gallery-item (avant)
 *
 * @param array $bloc
 * @param array $contexte
 * @return array liste de array(id_document, légende) des images retrouvées
 */
function wp2spip_bloc_gallery_images($bloc, $contexte) {
	$images = array();
	$enfants = array_filter($bloc['morceaux'], fn($morceau) => is_array($morceau) and $morceau['nom'] == 'core/image');
	if ($enfants) {
		foreach ($enfants as $enfant) {
			$html = wp2spip_bloc_html($enfant);
			$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve) ? $trouve[1] : '';
			$images[] = array(wp2spip_bloc_document($src, $enfant['attributs']['id'] ?? 0, $contexte), wp2spip_bloc_legende($html));
		}
	}
	else {
		preg_match_all('#<li\b[^>]*blocks-gallery-item[^>]*>(.*?)</li>#is', wp2spip_bloc_html($bloc), $items);
		foreach ($items[1] as $item) {
			$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $item, $trouve) ? $trouve[1] : '';
			$id = preg_match('#\bdata-id=["\'](\d+)["\']#i', $item, $trouve) ? $trouve[1] : 0;
			$images[] = array(wp2spip_bloc_document($src, $id, $contexte), wp2spip_bloc_legende($item));
		}
	}
	return $images;
}

function wp2spip_bloc_gallery($bloc, &$contexte) {
	$images = wp2spip_bloc_gallery_images($bloc, $contexte);
	$legende = wp2spip_bloc_legende(wp2spip_bloc_html($bloc), 'blocks-gallery-caption');
	if ($id_album = wp2spip_creer_album($images, ($legende !== '') ? wp2spip_html_spip($legende, array('autop' => false)) : '', $contexte)) {
		return wp2spip_proteger("<album$id_album>", $contexte);
	}
	$contexte['bilan']['medias_introuvables']++;
	return wp2spip_blocs_interieur($bloc, $contexte);
}

/**
 * Raccourci [gallery] : images de l'attribut ids, sinon images rattachées au contenu
 *
 * @param string $raccourci
 * @param string $attributs
 * @param array $contexte
 * @return string
 */
function wp2spip_convertir_raccourci_gallery($raccourci, $attributs, &$contexte) {
	if (preg_match('/\bids=(["\']?)([\d,\s]+)\1/', $attributs, $trouve)) {
		$ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $trouve[2])));
	}
	else {
		$ids = array_column(sql_allfetsel(
			'ID',
			wp2spip_table('posts'),
			array('post_type = "attachment"', 'post_parent = ' . $contexte['id_wordpress'], 'post_mime_type like "image/%"'),
			'',
			'menu_order, ID',
			'',
			'',
			$contexte['base']
		), 'ID');
	}
	$images = array();
	foreach ($ids as $id) {
		$images[] = array(wp2spip_bloc_document('', $id, $contexte), '');
	}
	if ($id_album = wp2spip_creer_album($images, '', $contexte)) {
		$contexte['bilan']['convertis']['[gallery]'] = ($contexte['bilan']['convertis']['[gallery]'] ?? 0) + 1;
		return wp2spip_proteger("<album$id_album>", $contexte);
	}
	$contexte['bilan']['medias_introuvables']++;
	return $raccourci;
}

/**
 * Crée l'album d'une galerie ; il sera lié à l'article par wp2spip_lier_albums()
 *
 * @param array $images liste de array(id_document, légende), 0 pour une image introuvable
 * @param string $legende légende de la galerie
 * @param array $contexte
 * @return int id_album, ou 0 si aucune image n'est retrouvée
 */
function wp2spip_creer_album($images, $legende, &$contexte) {
	$trouvees = array_values(array_filter($images, fn($image) => $image[0] > 0));
	if (!$trouvees) {
		return 0;
	}
	// Images introuvables dans la médiathèque : l'album est créé sans elles, chacune est signalée au bilan
	$contexte['bilan']['images_retirees'] += count($images) - count($trouvees);
	$images = $trouvees;
	include_spip('action/editer_objet');
	include_spip('action/editer_liens');
	include_spip('action/editer_document');
	$contexte['galerie']++;
	$titre = $contexte['titre'] . (($contexte['nb_galeries'] > 1) ? " (galerie {$contexte['galerie']})" : '');
	$id_album = intval(objet_inserer('album', null, array(
		'titre' => $titre,
		'descriptif' => $legende,
		'date' => $contexte['date'],
		'statut' => 'publie',
	)));
	if (!$id_album) {
		return 0;
	}
	foreach ($images as $rang => $image) {
		objet_associer(array('document' => $image[0]), array('album' => $id_album), array('rang_lien' => $rang + 1));
		// Le lien ne recalcule pas le statut du document : publié par son album, sans quoi l'album public serait vide
		document_instituer($image[0]);
		wp2spip_bloc_legender($image[0], $image[1]);
	}
	$contexte['albums'][] = $id_album;
	return $id_album;
}

/**
 * Lie à l'article les albums créés pendant la conversion de son texte
 *
 * @param array $contexte
 * @param int $id_article
 */
function wp2spip_lier_albums($contexte, $id_article) {
	if ($contexte['albums']) {
		include_spip('action/editer_liens');
		objet_associer(array('album' => $contexte['albums']), array('article' => $id_article));
	}
}

/**
 * Bloc couverture ou média et texte : le document du média, puis le texte du bloc, dans la balise du bloc
 *
 * @param array $bloc
 * @param array $contexte
 * @param string $url
 * @param int $id_wordpress
 * @return string
 */
function wp2spip_bloc_avec_media($bloc, &$contexte, $url, $id_wordpress) {
	// Le texte : HTML propre (ancien format) et blocs enfants, sans l'enveloppe ni le média, remplacé par son document
	// (les balises des enfants convertis sont déjà protégées par des marqueurs : celles qui restent sont celles du bloc)
	$texte = preg_replace(
		array(
			'#<figure\b[^>]*wp-block-media-text__media[^>]*>.*?</figure>#is',
			'#<video\b.*?</video>#is',
			'#<img\b[^>]*>#i',
			'#<span\b[^>]*>\s*</span>#i',
			'#</?div\b[^>]*>#i',
		),
		'',
		wp2spip_blocs_interieur($bloc, $contexte)
	);
	$classe = 'wp-block-' . preg_replace('#^core/#', '', $bloc['nom']);
	if ($id_document = wp2spip_bloc_document($url, $id_wordpress, $contexte)) {
		$texte = wp2spip_proteger("<doc$id_document>", $contexte) . $texte;
	}
	elseif ($url or $id_wordpress) {
		$contexte['bilan']['medias_introuvables']++;
	}
	return wp2spip_proteger("<div class=\"$classe\">", $contexte) . $texte . wp2spip_proteger('</div>', $contexte);
}

function wp2spip_bloc_cover($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['url'] ?? '';
	if (!$url and preg_match('#(?:\bsrc=["\']|background-image:\s*url\()([^"\')]+)#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	return wp2spip_bloc_avec_media($bloc, $contexte, $url, $bloc['attributs']['id'] ?? 0);
}

function wp2spip_bloc_media_text($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['mediaUrl'] ?? '';
	if (!$url and preg_match('#<(?:img|video)\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	return wp2spip_bloc_avec_media($bloc, $contexte, $url, $bloc['attributs']['mediaId'] ?? 0);
}

function wp2spip_bloc_button($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	if (!preg_match('#<a\b([^>]*)>(.*?)</a>#is', $html, $trouve)) {
		return wp2spip_blocs_interieur($bloc, $contexte);
	}
	$texte = trim(supprimer_tags($trouve[2]));
	if (preg_match('#\bhref=["\']([^"\']+)["\']#i', $trouve[1], $lien)) {
		$texte = "[$texte->" . html_entity_decode($lien[1]) . ']';
	}
	return wp2spip_proteger("<div class=\"wp-block-button\">$texte</div>", $contexte);
}

function wp2spip_bloc_media($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['href'] ?? '';
	if (!$url and preg_match('#\b(?:src|href)=["\']([^"\']+)["\']#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	if ($id_document = wp2spip_bloc_document($url, $bloc['attributs']['id'] ?? 0, $contexte)) {
		return wp2spip_proteger("<doc$id_document>", $contexte);
	}
	// Média hors de la médiathèque (lecteur d'un autre site…) : HTML d'origine
	return $html;
}

function wp2spip_bloc_embed($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['url'] ?? '';
	if (!$url and preg_match('#https?://[^\s<"\']+#', strip_tags($html), $trouve)) {
		$url = $trouve[0];
	}
	if (!$url) {
		return $html;
	}
	// L'URL seule sur sa ligne : oEmbed en fait un lecteur, sinon SPIP en fait un lien ; la légende passera par la conversion du HTML
	$legende = wp2spip_bloc_legende($html);
	return wp2spip_proteger($url, $contexte) . ($legende !== '' ? "\n\n$legende\n\n" : '');
}

/**
 * Ajoute le bilan d'un contenu au bilan de l'import
 *
 * @param array $bilan
 * @param array $contexte
 * @return array
 */
function wp2spip_cumuler_bilan_blocs($bilan, $contexte) {
	foreach (array('convertis', 'dynamiques', 'inconnus') as $cle) {
		foreach ($contexte['bilan'][$cle] as $type => $nb) {
			$bilan[$cle][$type] = ($bilan[$cle][$type] ?? 0) + $nb;
		}
	}
	$bilan['medias_introuvables'] += $contexte['bilan']['medias_introuvables'];
	$bilan['images_retirees'] += $contexte['bilan']['images_retirees'];
	$bilan['albums'] += count($contexte['albums']);
	return $bilan;
}

/**
 * Affiche le bilan des blocs à la fin de importer_articles
 *
 * @param object $command
 * @param array $bilan
 */
function wp2spip_afficher_bilan_blocs($command, $bilan) {
	$liste = function ($nombres) {
		ksort($nombres);
		return join(', ', array_map(fn($type, $nb) => "$type ($nb)", array_keys($nombres), $nombres));
	};
	if ($bilan['convertis']) {
		$command->output->writeln('Blocs convertis : ' . $liste($bilan['convertis']) . '.');
	}
	if ($bilan['albums']) {
		$command->output->writeln("{$bilan['albums']} albums créés pour les galeries.");
	}
	if ($bilan['dynamiques']) {
		$command->output->writeln(array_sum($bilan['dynamiques']) . ' blocs dynamiques retirés (rien n’est enregistré dans le contenu) : ' . $liste($bilan['dynamiques']) . '.');
	}
	if ($bilan['inconnus']) {
		$command->output->writeln(array_sum($bilan['inconnus']) . ' blocs inconnus, contenu gardé (détail avec -v) : ' . $liste($bilan['inconnus']) . '.');
	}
	if ($bilan['medias_introuvables']) {
		$command->output->writeln("{$bilan['medias_introuvables']} médias de blocs ou de galeries introuvables dans la médiathèque, HTML d’origine gardé.");
	}
	if ($bilan['images_retirees']) {
		$command->output->writeln("{$bilan['images_retirees']} images de galeries introuvables dans la médiathèque, absentes de leur album.");
	}
}
