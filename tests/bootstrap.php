<?php
/**
 * Amorce des tests unitaires : fonctions de wp2spip sans SPIP
 *
 * Seules les fonctions SPIP appelées par les fonctions testées sont imitées, avec garde function_exists().
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('_ECRIRE_INC_VERSION')) {
	define('_ECRIRE_INC_VERSION', 'test');
}

if (!function_exists('include_spip')) {
	function include_spip($fichier) {
		return true;
	}
}

// Copie de la fonction de SPIP 4.4 (ecrire/inc/utils.php)
if (!function_exists('tester_url_absolue')) {
	function tester_url_absolue($url) {
		$url = trim($url ?? '');
		if ($url && preg_match(';^([a-z]{3,7}:)?//;Uims', $url, $m)) {
			if (
				isset($m[1])
				&& ($p = strtolower(rtrim($m[1], ':')))
				&& in_array($p, ['file', 'php', 'zlib', 'glob', 'phar', 'ssh2', 'rar', 'ogg', 'expect', 'zip'])
			) {
				return false;
			}
			return true;
		}
		return false;
	}
}
