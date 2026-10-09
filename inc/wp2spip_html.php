<?php
/**
 * Conversion du HTML de Wordpress en raccourcis SPIP
 *
 * Le HTML est analysé en arbre HTML5 (Dom\HTMLDocument, PHP 8.4), puis parcouru nœud par nœud : aucune expression
 * régulière n'est appliquée au HTML. La sortie a la forme qu'attendent les traitements de wp2spip qui suivent
 * (images, lecteurs, liens internes, raccourcis Wordpress) : balises de médias gardées en HTML, liens en [texte->url].
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Éléments de bloc gardés en HTML : leur contenu est converti en paragraphes
 */
const WP2SPIP_HTML_BLOCS = array(
	'address', 'article', 'aside', 'center', 'details', 'dialog', 'div', 'fieldset', 'figcaption',
	'figure', 'footer', 'form', 'header', 'hgroup', 'main', 'nav', 'section', 'summary',
);

/**
 * Éléments gardés tels quels, contenu compris (médias, contenus embarqués)
 */
const WP2SPIP_HTML_TELS_QUELS = array('img', 'video', 'audio', 'iframe', 'object', 'embed', 'source', 'track', 'svg', 'math', 'canvas', 'picture', 'map', 'input', 'select', 'textarea', 'button');

/**
 * Éléments retirés avec leur contenu
 */
const WP2SPIP_HTML_RETIRES = array('script', 'style', 'template', 'noscript', 'head', 'title', 'meta', 'link', 'base');

/**
 * Éléments vides (sans balise fermante)
 */
const WP2SPIP_HTML_VIDES = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr');

/**
 * HTML de Wordpress en raccourcis SPIP
 *
 * @param string $html
 * @param array $options autop : true pour un texte auquel Wordpress applique wpautop() (éditeur classique,
 *     commentaires, descriptions) : ligne vide = paragraphe, retour simple = saut de ligne ; false pour un
 *     contenu à blocs : blancs réduits comme par un navigateur
 * @return string
 */
function wp2spip_html_spip($html, $options = array()) {
	$html = str_replace(array("\r\n", "\r"), "\n", (string) $html);
	if (trim($html) === '') {
		return '';
	}
	$document = Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);
	$contexte = array('autop' => $options['autop'] ?? true, 'pre' => false, 'gras' => false, 'italique' => false, 'listes' => '');
	return wp2spip_html_blocs($document->body, $contexte);
}

/**
 * Contenu d'un élément converti en paragraphes séparés par une ligne vide
 *
 * @param Dom\Node $parent
 * @param array $contexte
 * @return string
 */
function wp2spip_html_blocs($parent, $contexte) {
	$sortie = wp2spip_html_sortie();
	wp2spip_html_enfants($parent, $sortie, $contexte);
	return join("\n\n", wp2spip_html_paragraphes($sortie));
}

/**
 * Contenu d'un élément converti sur une ligne logique : ses paragraphes joints par des sauts de ligne
 *
 * @param Dom\Node $parent
 * @param array $contexte
 * @return string
 */
function wp2spip_html_en_ligne($parent, $contexte) {
	$sortie = wp2spip_html_sortie();
	wp2spip_html_enfants($parent, $sortie, $contexte);
	return join("\n_ ", wp2spip_html_paragraphes($sortie));
}

/**
 * @param Dom\Node $parent
 * @param array $sortie
 * @param array $contexte
 */
function wp2spip_html_enfants($parent, &$sortie, $contexte) {
	foreach ($parent->childNodes as $noeud) {
		wp2spip_html_noeud($noeud, $sortie, $contexte);
	}
}

/**
 * @param Dom\Node $noeud
 * @param array $sortie
 * @param array $contexte
 */
function wp2spip_html_noeud($noeud, &$sortie, $contexte) {
	if ($noeud instanceof Dom\Text) {
		wp2spip_html_texte($noeud->data, $sortie, $contexte);
		return;
	}
	if (!$noeud instanceof Dom\Element) {
		// Commentaires, instructions : retirés
		return;
	}
	$nom = strtolower($noeud->localName);
	switch ($nom) {
		case 'p':
			wp2spip_html_fin_paragraphe($sortie);
			wp2spip_html_enfants($noeud, $sortie, $contexte);
			wp2spip_html_fin_paragraphe($sortie);
			return;
		case 'br':
			// Dans un texte préformaté, les lignes sont gardées telles quelles
			$contexte['pre'] ? wp2spip_html_ecrire($sortie, "\n") : $sortie['sauts']++;
			return;
		case 'strong':
		case 'b':
			wp2spip_html_ecrire($sortie, wp2spip_html_entourer($noeud, $contexte, 'gras', '{{', '}}'));
			return;
		case 'em':
		case 'i':
			wp2spip_html_ecrire($sortie, wp2spip_html_entourer($noeud, $contexte, 'italique', '{', '}'));
			return;
		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			$titre = wp2spip_html_une_ligne(wp2spip_html_en_ligne($noeud, array('gras' => true) + $contexte));
			if ($titre !== '') {
				wp2spip_html_bloc($sortie, in_array($nom, array('h1', 'h2', 'h3')) ? wp2spip_html_marquer($titre, '{{{', '}}}') : wp2spip_html_marquer($titre, '{{', '}}'));
			}
			return;
		case 'a':
			wp2spip_html_ecrire($sortie, wp2spip_html_bords($noeud, wp2spip_html_lien($noeud, $contexte)));
			return;
		case 'ul':
		case 'ol':
			wp2spip_html_bloc($sortie, wp2spip_html_liste($noeud, $contexte));
			return;
		case 'li':
			// Élément de liste hors liste (HTML mal formé) : une liste d'un élément
			wp2spip_html_bloc($sortie, '-* ' . wp2spip_html_une_ligne(wp2spip_html_en_ligne($noeud, $contexte)));
			return;
		case 'table':
			if ($contexte['tableau_html'] ?? false) {
				// Tableau dans une cellule d'un tableau gardé en HTML : en HTML lui aussi
				wp2spip_html_ecrire($sortie, wp2spip_html_tableau_html($noeud, $contexte));
				return;
			}
			wp2spip_html_bloc($sortie, wp2spip_html_tableau($noeud, $contexte));
			return;
		case 'blockquote':
			$citation = wp2spip_html_blocs($noeud, $contexte);
			if ($citation !== '') {
				wp2spip_html_bloc($sortie, "<quote>\n$citation\n</quote>");
			}
			return;
		case 'pre':
			wp2spip_html_bloc($sortie, wp2spip_html_pre($noeud, $contexte));
			return;
		case 'code':
			wp2spip_html_ecrire($sortie, '<code>' . $noeud->textContent . '</code>');
			return;
		case 'hr':
			wp2spip_html_bloc($sortie, '----');
			return;
		case 'dl':
			// Liste de définitions gardée en HTML, un terme ou une définition par ligne
			$lignes = array();
			foreach (wp2spip_html_elements($noeud) as $element) {
				$lignes[] = wp2spip_html_ouvrante($element) . wp2spip_html_une_ligne(wp2spip_html_en_ligne($element, $contexte)) . '</' . strtolower($element->localName) . '>';
			}
			wp2spip_html_bloc($sortie, wp2spip_html_ouvrante($noeud) . "\n" . join("\n", $lignes) . "\n</dl>");
			return;
	}
	if (in_array($nom, WP2SPIP_HTML_RETIRES)) {
		return;
	}
	if (in_array($nom, WP2SPIP_HTML_TELS_QUELS)) {
		wp2spip_html_ecrire($sortie, $noeud->ownerDocument->saveHtml($noeud));
		return;
	}
	if (in_array($nom, WP2SPIP_HTML_BLOCS)) {
		// Gardé en HTML : sur une ligne si son contenu tient en un paragraphe, sinon balises sur leurs propres lignes
		// et contenu en paragraphes
		$contenu = wp2spip_html_blocs($noeud, $contexte);
		if (!str_contains($contenu, "\n\n")) {
			wp2spip_html_bloc($sortie, wp2spip_html_ouvrante($noeud) . $contenu . "</$nom>");
			return;
		}
		wp2spip_html_bloc($sortie, wp2spip_html_ouvrante($noeud) . "\n\n$contenu\n\n</$nom>");
		return;
	}
	// Élément en ligne inconnu (span, sup, sub, u, del, abbr…) : gardé en HTML, contenu converti
	if (in_array($nom, WP2SPIP_HTML_VIDES)) {
		wp2spip_html_ecrire($sortie, wp2spip_html_ouvrante($noeud));
		return;
	}
	wp2spip_html_ecrire($sortie, wp2spip_html_bords($noeud, wp2spip_html_ouvrante($noeud) . trim(wp2spip_html_en_ligne($noeud, $contexte)) . "</$nom>"));
}

/**
 * Texte d'un nœud : blancs selon le contexte, caractères spéciaux du HTML réécrits en entités, marqueurs de blocs
 * protégés de wp2spip isolés sur leur paragraphe
 *
 * @param string $texte
 * @param array $sortie
 * @param array $contexte
 */
function wp2spip_html_texte($texte, &$sortie, $contexte) {
	if ($contexte['pre']) {
		wp2spip_html_ecrire($sortie, wp2spip_html_echapper_debut(wp2spip_html_echapper_texte($texte)));
		return;
	}
	foreach (preg_split('/(wp2spipbloc\d+)/', $texte, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $morceau) {
		if ($i % 2) {
			wp2spip_html_bloc($sortie, $morceau);
			continue;
		}
		if (!$contexte['autop']) {
			wp2spip_html_ecrire($sortie, wp2spip_html_echapper_texte(preg_replace('/[ \t\n\f]+/', ' ', $morceau)));
			continue;
		}
		// wpautop() : ligne vide = paragraphe, retour simple = saut de ligne
		foreach (preg_split('/\n[ \t\f]*\n\s*/', $morceau) as $j => $paragraphe) {
			if ($j) {
				wp2spip_html_fin_paragraphe($sortie);
			}
			$lignes = explode("\n", $paragraphe);
			foreach ($lignes as $k => $ligne) {
				$ligne = wp2spip_html_echapper_texte(preg_replace('/[ \t\f]+/', ' ', $ligne));
				if ($k and (wp2spip_html_raccourci_bloc($ligne, true) or wp2spip_html_raccourci_bloc($lignes[$k - 1], false))) {
					// Raccourci Wordpress de bloc (légende, lecteur…) sur sa ligne : pas un saut de ligne, il deviendra un document
					$ligne = "\n" . $ligne;
				}
				elseif ($k) {
					$sortie['sauts']++;
				}
				wp2spip_html_ecrire($sortie, $ligne);
			}
		}
	}
}

/**
 * Gras ou italique : marques autour du contenu, blancs de bord sortis ; pas de marque dans une marque de même nature
 *
 * @param Dom\Element $element
 * @param array $contexte
 * @param string $style gras, italique
 * @param string $ouvrant
 * @param string $fermant
 * @return string
 */
function wp2spip_html_entourer($element, $contexte, $style, $ouvrant, $fermant) {
	$contenu = trim(wp2spip_html_en_ligne($element, array($style => true) + $contexte));
	if ($contenu === '') {
		return preg_match('/\s/u', $element->textContent) ? ' ' : '';
	}
	return wp2spip_html_bords($element, $contexte[$style] ? $contenu : wp2spip_html_marquer($contenu, $ouvrant, $fermant));
}

/**
 * Blancs de bord du texte d'origine d'un élément, gardés hors de sa conversion
 *
 * @param Dom\Element $element
 * @param string $conversion
 * @return string
 */
function wp2spip_html_bords($element, $conversion) {
	return (preg_match('/^\s/u', $element->textContent) ? ' ' : '') . $conversion . (preg_match('/\s$/u', $element->textContent) ? ' ' : '');
}

/**
 * Marques autour d'un texte, séparées par un blanc d'une marque qu'il commence ou finit : {{{Titre {x}}}}
 * serait lu {{{Titre {x}}} suivi d'une accolade
 *
 * @param string $texte
 * @param string $ouvrant
 * @param string $fermant
 * @return string
 */
function wp2spip_html_marquer($texte, $ouvrant, $fermant) {
	return $ouvrant . (str_starts_with($texte, '{') ? ' ' : '') . $texte . (str_ends_with($texte, '}') ? ' ' : '') . $fermant;
}

/**
 * Lien : [texte->url], ancre nommée : [nom<-]
 *
 * @param Dom\Element $lien
 * @param array $contexte
 * @return string
 */
function wp2spip_html_lien($lien, $contexte) {
	$texte = wp2spip_html_une_ligne(wp2spip_html_en_ligne($lien, $contexte));
	$url = trim((string) $lien->getAttribute('href'));
	if ($url === '') {
		$ancre = trim((string) ($lien->getAttribute('id') ?: $lien->getAttribute('name')));
		return ($ancre !== '' ? "[$ancre<-]" : '') . $texte;
	}
	return '[' . $texte . '->' . str_replace(array('[', ']', ' '), array('%5B', '%5D', '%20'), $url) . ']';
}

/**
 * Liste SPIP : -* (puces), -# (numéros) ; à la profondeur N, le caractère de la liste répété N fois (-**, -##),
 * quel que soit le type des listes englobantes
 *
 * Contenu hors des éléments (liste non fermée : l'analyseur HTML5 y range la suite du texte) : gardé, en
 * paragraphes après la liste au premier niveau, à la suite de l'élément précédent dans une sous-liste
 *
 * @param Dom\Element $liste ul ou ol
 * @param array $contexte
 * @return string
 */
function wp2spip_html_liste($liste, $contexte) {
	$premier_niveau = ($contexte['listes'] === '');
	$contexte_hors = $contexte;
	$contexte['listes'] .= '*';
	$marque = str_repeat((strtolower($liste->localName) === 'ol') ? '#' : '*', strlen($contexte['listes']));
	$blocs = array();
	$lignes = array();
	$hors = wp2spip_html_sortie();
	foreach ($liste->childNodes as $enfant) {
		if ($enfant instanceof Dom\Text ? trim($enfant->data) === '' : !$enfant instanceof Dom\Element) {
			continue;
		}
		$nom = ($enfant instanceof Dom\Element) ? strtolower($enfant->localName) : '';
		$en_cours_hors = (trim($hors['courant']) !== '' or $hors['paragraphes']);
		if ($nom === 'li' or (in_array($nom, array('ul', 'ol')) and !$en_cours_hors)) {
			if ($en_cours_hors) {
				array_push($blocs, ...wp2spip_html_paragraphes($hors));
				$hors = wp2spip_html_sortie();
			}
			$lignes[] = ($nom === 'li') ? wp2spip_html_element_liste($enfant, $marque, $contexte) : wp2spip_html_liste($enfant, $contexte);
			continue;
		}
		if ($premier_niveau) {
			if ($lignes) {
				$blocs[] = join("\n", array_filter($lignes, fn($ligne) => $ligne !== ''));
				$lignes = array();
			}
			wp2spip_html_noeud($enfant, $hors, $contexte_hors);
			continue;
		}
		$texte = wp2spip_html_sortie();
		wp2spip_html_noeud($enfant, $texte, array('listes' => '') + $contexte);
		$suite = join("\n_ ", wp2spip_html_paragraphes($texte));
		if ($suite !== '') {
			$lignes ? $lignes[array_key_last($lignes)] .= "\n_ $suite" : $lignes[] = "-$marque $suite";
		}
	}
	array_push($blocs, ...wp2spip_html_paragraphes($hors));
	if ($lignes) {
		$blocs[] = join("\n", array_filter($lignes, fn($ligne) => $ligne !== ''));
	}
	return join($premier_niveau ? "\n\n" : "\n", array_filter($blocs, fn($bloc) => $bloc !== ''));
}

/**
 * Élément de liste : son texte sur une ligne (paragraphes joints par des sauts de ligne), ses sous-listes à la suite
 *
 * @param Dom\Element $element li
 * @param string $marque
 * @param array $contexte
 * @return string
 */
function wp2spip_html_element_liste($element, $marque, $contexte) {
	$texte = wp2spip_html_sortie();
	$sous_listes = array();
	foreach ($element->childNodes as $noeud) {
		if ($noeud instanceof Dom\Element and in_array(strtolower($noeud->localName), array('ul', 'ol'))) {
			$sous_listes[] = wp2spip_html_liste($noeud, $contexte);
		}
		else {
			wp2spip_html_noeud($noeud, $texte, array('listes' => '') + $contexte);
		}
	}
	return join("\n", array_filter(array(rtrim("-$marque " . join("\n_ ", wp2spip_html_paragraphes($texte))), ...$sous_listes), fn($ligne) => $ligne !== ''));
}

/**
 * Tableau SPIP ; gardé en HTML s'il ne s'écrit pas en raccourcis (tableau imbriqué, cellule sur plusieurs lignes,
 * fusion de lignes)
 *
 * @param Dom\Element $tableau
 * @param array $contexte
 * @return string
 */
function wp2spip_html_tableau($tableau, $contexte) {
	$lignes = array();
	$legende = '';
	$simple = !$tableau->querySelector('table, [rowspan]:not([rowspan="1"])');
	foreach ($tableau->childNodes as $partie) {
		if (!$partie instanceof Dom\Element) {
			continue;
		}
		$nom = strtolower($partie->localName);
		if ($nom === 'caption') {
			$legende = wp2spip_html_une_ligne(wp2spip_html_en_ligne($partie, $contexte));
			continue;
		}
		$rangees = ($nom === 'tr') ? array($partie) : wp2spip_html_elements($partie);
		foreach ($rangees as $rangee) {
			if (strtolower($rangee->localName) !== 'tr') {
				continue;
			}
			$cellules = array();
			foreach (wp2spip_html_elements($rangee) as $cellule) {
				$nom_cellule = strtolower($cellule->localName);
				if (!in_array($nom_cellule, array('td', 'th'))) {
					continue;
				}
				$contenu = trim(wp2spip_html_en_ligne($cellule, $contexte));
				if (str_contains($contenu, "\n")) {
					$simple = false;
				}
				$contenu = str_replace('|', '&#124;', $contenu);
				$cellules[] = ($nom_cellule === 'th' and $contenu !== '') ? '{{' . $contenu . '}}' : $contenu;
				// Cellule fusionnée avec les suivantes : < dans chacune
				for ($n = intval($cellule->getAttribute('colspan')); $n > 1; $n--) {
					$cellules[] = '<';
				}
			}
			if ($cellules) {
				$lignes[] = '| ' . join(' | ', $cellules) . ' |';
			}
		}
	}
	if (!$simple) {
		return wp2spip_html_tableau_html($tableau, $contexte);
	}
	if ($legende !== '') {
		array_unshift($lignes, '|| ' . str_replace('|', '&#124;', $legende) . ' ||');
	}
	return join("\n", $lignes);
}

/**
 * Tableau gardé en HTML : structure d'origine, une rangée par ligne, contenu des cellules converti
 *
 * @param Dom\Element $element table, ou une de ses parties
 * @param array $contexte
 * @return string
 */
function wp2spip_html_tableau_html($element, $contexte) {
	$nom = strtolower($element->localName);
	if (in_array($nom, array('td', 'th', 'caption'))) {
		return wp2spip_html_ouvrante($element) . trim(wp2spip_html_en_ligne($element, array('tableau_html' => true, 'autop' => false) + $contexte)) . "</$nom>";
	}
	if (in_array($nom, WP2SPIP_HTML_VIDES)) {
		return wp2spip_html_ouvrante($element);
	}
	$parties = array_map(fn($partie) => wp2spip_html_tableau_html($partie, $contexte), wp2spip_html_elements($element));
	return wp2spip_html_ouvrante($element) . ($nom === 'tr' ? join('', $parties) : "\n" . join("\n", $parties) . "\n") . "</$nom>";
}

/**
 * Texte préformaté : <cadre> pour du code, sinon <poesie> (lignes gardées)
 *
 * @param Dom\Element $pre
 * @param array $contexte
 * @return string
 */
function wp2spip_html_pre($pre, $contexte) {
	$elements = wp2spip_html_elements($pre);
	$code = (count($elements) === 1 and strtolower($elements[0]->localName) === 'code' and trim(str_replace($elements[0]->textContent, '', $pre->textContent)) === '');
	if ($code) {
		return "<cadre>\n" . trim($elements[0]->textContent, "\n") . "\n</cadre>";
	}
	$sortie = wp2spip_html_sortie();
	wp2spip_html_enfants($pre, $sortie, array('pre' => true) + $contexte);
	$texte = trim(join("\n\n", wp2spip_html_paragraphes($sortie)), "\n");
	return ($texte === '') ? '' : "<poesie>\n$texte\n</poesie>";
}

/**
 * Éléments enfants
 *
 * @param Dom\Node $parent
 * @return Dom\Element[]
 */
function wp2spip_html_elements($parent) {
	return array_values(array_filter(iterator_to_array($parent->childNodes), fn($noeud) => $noeud instanceof Dom\Element));
}

/**
 * Balise ouvrante d'un élément, avec ses attributs
 *
 * @param Dom\Element $element
 * @return string
 */
function wp2spip_html_ouvrante($element) {
	$balise = '<' . strtolower($element->localName);
	foreach ($element->attributes as $attribut) {
		$balise .= ' ' . $attribut->name . '="' . htmlspecialchars($attribut->value, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '"';
	}
	return $balise . '>';
}

/**
 * Ligne qui commence (ou finit) par un raccourci Wordpress de bloc : légende, galerie, lecteur, contenu embarqué
 *
 * @param string $ligne
 * @param bool $debut true : commence par le raccourci ouvrant ; false : finit par le raccourci fermant
 * @return bool
 */
function wp2spip_html_raccourci_bloc($ligne, $debut) {
	return (bool) preg_match($debut ? '#^\s*\[(?:caption|wp_caption|gallery|audio|video|embed|playlist)\b#i' : '#\[/(?:caption|wp_caption|audio|video|embed|playlist)\]\s*$#i', $ligne);
}

/**
 * Texte de Wordpress échappé pour SPIP : caractères du HTML et des raccourcis SPIP en entités, sauf les raccourcis
 * Wordpress que wp2spip convertit ensuite (le tiret et le souligné de début de ligne : wp2spip_html_echapper_debut())
 *
 * Un texte que Wordpress affiche tel quel ({{a}}, [lien->x], | a |…) est ainsi affiché tel quel par SPIP.
 *
 * @param string $texte
 * @return string
 */
function wp2spip_html_echapper_texte($texte) {
	$morceaux = preg_split('#(\[/?(?:caption|wp_caption|gallery|audio|video|embed|playlist)\b[^\[\]]*\])#i', $texte, -1, PREG_SPLIT_DELIM_CAPTURE);
	foreach ($morceaux as $i => $morceau) {
		if (!($i % 2)) {
			$morceaux[$i] = strtr(wp2spip_html_echapper($morceau), array('{' => '&#123;', '}' => '&#125;', '[' => '&#91;', ']' => '&#93;', '|' => '&#124;', '~' => '&#126;'));
		}
	}
	return join('', $morceaux);
}

/**
 * Tiret ou souligné en début de ligne (liste, filet, saut de ligne pour SPIP) en entité
 *
 * @param string $texte
 * @param bool $toutes_les_lignes false : première ligne seulement (texte déjà converti, dont les lignes suivantes
 *     commencent par un saut de ligne SPIP)
 * @return string
 */
function wp2spip_html_echapper_debut($texte, $toutes_les_lignes = true) {
	return preg_replace_callback($toutes_les_lignes ? '/^(\s*)([-_])/m' : '/^(\s*)([-_])/', fn($debut) => $debut[1] . '&#' . ord($debut[2]) . ';', $texte);
}

/**
 * Caractères spéciaux du HTML d'un texte réécrits en entités : le texte reste du texte dans SPIP
 *
 * @param string $texte
 * @return string
 */
function wp2spip_html_echapper($texte) {
	return str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $texte);
}

/**
 * Texte sur une seule ligne (titres, liens, légendes)
 *
 * @param string $texte
 * @return string
 */
function wp2spip_html_une_ligne($texte) {
	return trim(preg_replace('/\s*\n(_ )?\s*/', ' ', $texte));
}

/**
 * Sortie en cours : paragraphes terminés, paragraphe courant, sauts de ligne en attente
 *
 * @return array
 */
function wp2spip_html_sortie() {
	return array('paragraphes' => array(), 'courant' => '', 'sauts' => 0);
}

/**
 * Texte ajouté au paragraphe courant, après les sauts de ligne en attente (deux sauts ou plus : nouveau paragraphe)
 *
 * @param array $sortie
 * @param string $texte
 */
function wp2spip_html_ecrire(&$sortie, $texte) {
	if ($texte === '') {
		return;
	}
	if ($sortie['sauts'] and trim($texte) === '') {
		// Blanc après un saut de ligne : sans effet
		return;
	}
	if ($sortie['sauts'] >= 2) {
		wp2spip_html_fin_paragraphe($sortie);
	}
	elseif ($sortie['sauts'] and trim($sortie['courant']) !== '') {
		$sortie['courant'] = rtrim($sortie['courant']) . "\n_ ";
		$texte = ltrim($texte);
	}
	$sortie['sauts'] = 0;
	if (trim($sortie['courant']) === '' or str_ends_with($sortie['courant'], ' ')) {
		// Pas de blanc en début de paragraphe, ni de blanc doublé
		$texte = ltrim($texte, ' ');
	}
	// Texte en début de ligne : un tiret ou un souligné y serait lu comme un raccourci (marques et liens générés
	// commencent par { [ ou <, jamais par ces caractères)
	if (trim($sortie['courant']) === '' or str_ends_with($sortie['courant'], "\n")) {
		$texte = wp2spip_html_echapper_debut($texte, false);
	}
	$sortie['courant'] .= $texte;
}

/**
 * Fin du paragraphe courant (les sauts de ligne en attente, en fin de paragraphe, sont sans effet)
 *
 * @param array $sortie
 */
function wp2spip_html_fin_paragraphe(&$sortie) {
	$paragraphe = trim($sortie['courant']);
	if ($paragraphe !== '') {
		$sortie['paragraphes'][] = $paragraphe;
	}
	$sortie['courant'] = '';
	$sortie['sauts'] = 0;
}

/**
 * Bloc déjà formé (liste, tableau, intertitre…), sur son propre paragraphe
 *
 * @param array $sortie
 * @param string $bloc
 */
function wp2spip_html_bloc(&$sortie, $bloc) {
	wp2spip_html_fin_paragraphe($sortie);
	if (trim($bloc) !== '') {
		$sortie['paragraphes'][] = $bloc;
	}
}

/**
 * Paragraphes de la sortie, paragraphe courant compris, sans blanc en fin de ligne
 *
 * @param array $sortie
 * @return array
 */
function wp2spip_html_paragraphes($sortie) {
	wp2spip_html_fin_paragraphe($sortie);
	return array_map(fn($paragraphe) => preg_replace('/[ \t]+\n/', "\n", $paragraphe), $sortie['paragraphes']);
}
