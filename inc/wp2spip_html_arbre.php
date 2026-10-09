<?php
/**
 * Arbre HTML5 du convertisseur HTML → SPIP
 *
 * Dom\HTMLDocument à partir de PHP 8.4 ; sinon Masterminds HTML5-PHP (PHP 8.1 à 8.3), dont une copie est livrée
 * dans lib/masterminds-html5/. Seules ces fonctions connaissent les classes du parseur : inc/wp2spip_html.php
 * manipule les nœuds par elles et par les propriétés communes aux deux familles de classes (localName, nodeName,
 * childNodes, textContent, data, attributes, getAttribute(), hasAttribute()).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Éléments sans balise fermante
 */
const WP2SPIP_HTML_ARBRE_VIDES = array(
	'area', 'base', 'basefont', 'bgsound', 'br', 'col', 'embed', 'frame', 'hr', 'img', 'input', 'keygen', 'link',
	'meta', 'param', 'source', 'track', 'wbr',
);

/**
 * Éléments dont le texte est sérialisé sans échappement
 */
const WP2SPIP_HTML_ARBRE_TEXTE_BRUT = array('style', 'script', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext', 'noscript');

/**
 * Body du HTML analysé en document HTML5
 *
 * @param string $html
 * @return Dom\Element|DOMElement
 * @throws RuntimeException aucun parseur HTML5 disponible
 */
function wp2spip_html_arbre_body($html) {
	if (class_exists('Dom\HTMLDocument')) {
		return Dom\HTMLDocument::createFromString(wp2spip_html_arbre_source($html), LIBXML_NOERROR)->body;
	}
	if ($erreur = wp2spip_html_arbre_verifier()) {
		throw new RuntimeException($erreur);
	}
	return wp2spip_html_arbre_body_masterminds($html);
}

/**
 * Body du HTML analysé par Masterminds, quelle que soit la version de PHP (tests des deux parseurs)
 *
 * @param string $html
 * @return DOMElement
 */
function wp2spip_html_arbre_body_masterminds($html) {
	wp2spip_html_arbre_charger_masterminds();
	include_once __DIR__ . '/wp2spip_html_masterminds.php';
	$arbre = new Wp2spipArbreMasterminds(false, array('disable_html_ns' => true));
	$scanner = new Masterminds\HTML5\Parser\Scanner(wp2spip_html_arbre_source($html), 'UTF-8');
	(new Masterminds\HTML5\Parser\Tokenizer($scanner, $arbre, Masterminds\HTML5\Parser\Tokenizer::CONFORMANT_HTML))->parse();
	return $arbre->document()->getElementsByTagName('body')->item(0);
}

/**
 * @param string $html
 * @return string
 */
function wp2spip_html_arbre_source($html) {
	return '<!DOCTYPE html><html><body>' . $html . '</body></html>';
}

/**
 * Dossier de la copie de Masterminds livrée avec le plugin
 *
 * @return string
 */
function wp2spip_html_arbre_dossier_masterminds() {
	return dirname(__DIR__) . '/lib/masterminds-html5';
}

/**
 * Rend les classes Masterminds disponibles : une copie déjà chargée (autre plugin, Composer) est utilisée telle
 * quelle, sinon celle de lib/
 */
function wp2spip_html_arbre_charger_masterminds() {
	static $enregistre = false;
	if ($enregistre or class_exists('Masterminds\HTML5')) {
		return;
	}
	$enregistre = true;
	spl_autoload_register('wp2spip_html_arbre_autoload');
}

/**
 * @param string $classe
 */
function wp2spip_html_arbre_autoload($classe) {
	if (str_starts_with($classe, 'Masterminds\\')) {
		$fichier = wp2spip_html_arbre_dossier_masterminds() . '/src/' . str_replace('\\', '/', substr($classe, strlen('Masterminds\\'))) . '.php';
		if (is_file($fichier)) {
			require $fichier;
		}
	}
}

/**
 * @param mixed $noeud
 * @return bool
 */
function wp2spip_html_est_texte($noeud) {
	return ($noeud instanceof Dom\Text or $noeud instanceof DOMText);
}

/**
 * @param mixed $noeud
 * @return bool
 */
function wp2spip_html_est_element($noeud) {
	return ($noeud instanceof Dom\Element or $noeud instanceof DOMElement);
}

/**
 * HTML d'un nœud, contenu compris
 *
 * Dom\HTMLDocument::saveHtml() pour l'arbre natif ; sinon l'algorithme de sérialisation du standard HTML5, qui donne
 * le même résultat (le sérialiseur de Masterminds écrit autrement les attributs booléens et le SVG).
 *
 * @param mixed $noeud
 * @return string
 */
function wp2spip_html_serialiser($noeud) {
	if ($noeud instanceof Dom\Node) {
		return $noeud->ownerDocument->saveHtml($noeud);
	}
	if (wp2spip_html_est_texte($noeud)) {
		$parent = $noeud->parentNode;
		if (wp2spip_html_est_element($parent) and in_array($parent->localName, WP2SPIP_HTML_ARBRE_TEXTE_BRUT)) {
			return $noeud->data;
		}
		return str_replace(array('&', "\u{a0}", '<', '>'), array('&amp;', '&nbsp;', '&lt;', '&gt;'), $noeud->data);
	}
	if (!wp2spip_html_est_element($noeud)) {
		return ($noeud->nodeType === XML_COMMENT_NODE) ? '<!--' . $noeud->data . '-->' : '';
	}
	$html = '<' . $noeud->localName;
	foreach ($noeud->attributes as $attribut) {
		$html .= ' ' . $attribut->nodeName . '="' . str_replace(array('&', "\u{a0}", '"'), array('&amp;', '&nbsp;', '&quot;'), $attribut->value) . '"';
	}
	$html .= '>';
	if (in_array($noeud->localName, WP2SPIP_HTML_ARBRE_VIDES)) {
		return $html;
	}
	foreach ($noeud->childNodes as $enfant) {
		$html .= wp2spip_html_serialiser($enfant);
	}
	return $html . '</' . $noeud->localName . '>';
}

/**
 * Message si l'arbre HTML5 ne peut pas être construit
 *
 * @param bool $dom_natif Dom\HTMLDocument présent (PHP 8.4)
 * @param bool $dom extension DOM chargée
 * @param bool $masterminds Masterminds chargé, ou sa copie présente dans lib/
 * @return string message, ou '' si un parseur est disponible
 */
function wp2spip_html_arbre_erreur($dom_natif, $dom, $masterminds) {
	if ($dom_natif) {
		return '';
	}
	if (!$dom) {
		return 'Extension PHP DOM absente (PHP ' . PHP_VERSION . ') : elle est nécessaire à la conversion du HTML de Wordpress.';
	}
	if (!$masterminds) {
		return 'Bibliothèque Masterminds HTML5-PHP introuvable (lib/masterminds-html5/ du plugin) : elle est nécessaire à la conversion du HTML de Wordpress sous PHP ' . PHP_VERSION . ', avant PHP 8.4.';
	}
	return '';
}

/**
 * @return string message, ou '' si un parseur est disponible
 */
function wp2spip_html_arbre_verifier() {
	return wp2spip_html_arbre_erreur(
		class_exists('Dom\HTMLDocument'),
		class_exists('DOMDocument'),
		class_exists('Masterminds\HTML5') or is_file(wp2spip_html_arbre_dossier_masterminds() . '/src/HTML5.php')
	);
}

/**
 * Parseur utilisé, pour le mode verbeux de l'import
 *
 * @return string
 */
function wp2spip_html_arbre_parseur() {
	if (class_exists('Dom\HTMLDocument')) {
		return 'Dom\HTMLDocument (PHP ' . PHP_VERSION . ')';
	}
	wp2spip_html_arbre_charger_masterminds();
	$fichier = realpath((new ReflectionClass('Masterminds\HTML5'))->getFileName());
	$copie = realpath(wp2spip_html_arbre_dossier_masterminds());
	if ($copie and str_starts_with($fichier, $copie . DIRECTORY_SEPARATOR)) {
		return 'Masterminds HTML5-PHP (PHP ' . PHP_VERSION . '), copie du plugin, version ' . trim((string) file_get_contents($copie . '/VERSION'));
	}
	return 'Masterminds HTML5-PHP (PHP ' . PHP_VERSION . '), copie déjà chargée : ' . $fichier;
}
