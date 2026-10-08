<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip.php';

/**
 * Fonctions de inc/wp2spip.php
 */
final class Wp2spipTest extends TestCase
{
	#[DataProvider('entites')]
	public function testDecoderEntites(string $attendu, string $texte): void
	{
		$this->assertSame($attendu, wp2spip_decoder_entites($texte));
	}

	public static function entites(): array
	{
		return array(
			'entité nommée' => array('Été', '&Eacute;t&eacute;'),
			'entité numérique' => array('é', '&#233;'),
			'entité hexadécimale' => array('é', '&#xe9;'),
			'chevrons et esperluette gardés' => array('&lt;b&gt; &amp; &#60;', '&lt;b&gt; &amp; &#60;'),
			'guillemets' => array('« l’été »', '&laquo; l&rsquo;été &raquo;'),
			'texte sans entité' => array('Texte', 'Texte'),
		);
	}

	#[DataProvider('wpConfigs')]
	public function testPrefixeWpConfig(?string $attendu, string $source): void
	{
		$this->assertSame($attendu, wp2spip_prefixe_wp_config($source));
	}

	public static function wpConfigs(): array
	{
		return array(
			'guillemets simples' => array('wp_', "<?php\n\$table_prefix = 'wp_';\n"),
			'guillemets doubles, espaces' => array('wpx_', "<?php\n\$table_prefix  =  \"wpx_\" ;\n"),
			'commentaire ignoré' => array('wpx_', "<?php\n// \$table_prefix = 'autre_';\n/* \$table_prefix = 'x_'; */\n\$table_prefix = 'wpx_';\n"),
			'absent' => array(null, "<?php\ndefine('DB_NAME', 'base');\n"),
			'deux affectations' => array(null, "<?php\nif (getenv('A')) { \$table_prefix = 'a_'; } else { \$table_prefix = 'b_'; }\n"),
			'valeur calculée' => array(null, "<?php\n\$table_prefix = getenv('PREFIXE');\n"),
			'concaténation' => array(null, "<?php\n\$table_prefix = 'wp_' . 'x';\n"),
			'comparaison seule' => array(null, "<?php\nif (\$table_prefix == 'wp_') {}\n"),
		);
	}

	#[DataProvider('prefixes')]
	public function testPrefixeValide(bool $attendu, string $prefixe): void
	{
		$this->assertSame($attendu, wp2spip_prefixe_valide($prefixe));
	}

	public static function prefixes(): array
	{
		return array(
			'wp_' => array(true, 'wp_'),
			'majuscules et chiffres' => array(true, 'WP2x_'),
			'vide' => array(false, ''),
			'point-virgule' => array(false, 'wp_;x'),
			'tiret' => array(false, 'wp-x_'),
			'espace' => array(false, 'wp x'),
		);
	}

	public function testTableSelonLaMeta(): void
	{
		unset($GLOBALS['meta']['wp2spip_prefixe_tables']);
		$this->assertSame('wp_posts', wp2spip_table('posts'));
		$GLOBALS['meta']['wp2spip_prefixe_tables'] = 'wpx_';
		$this->assertSame('wpx_', wp2spip_prefixe_tables());
		$this->assertSame('wpx_postmeta', wp2spip_table('postmeta'));
		unset($GLOBALS['meta']['wp2spip_prefixe_tables']);
	}
}
