<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

/**
 * Préfixe des tables WordPress : détermination et contrôles de la commande (lancée avec --info, sans traitement),
 * et lecture des tables sous le préfixe noté dans la méta wp2spip_prefixe_tables
 *
 * La base wp2spip_tests_wpx a les données du WordPress de test sous le préfixe wpx_.
 */
final class PrefixeTablesTest extends WordpressTestCase
{
	private const BASE_WPX = 'wp2spip_tests_wpx';

	private static string $dossier = '';

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::construireBase(self::BASE_WPX, 'wpx_');
		// Dossier WordPress minimal : version, et wp-config.php réécrit par chaque test
		self::$dossier = sys_get_temp_dir() . '/wp2spip-prefixe-' . getmypid();
		@mkdir(self::$dossier . '/wp-includes', 0777, true);
		file_put_contents(self::$dossier . '/wp-includes/version.php', "<?php\n\$wp_version = '6.9';\n");
	}

	public static function tearDownAfterClass(): void
	{
		self::retirerBase(self::BASE_WPX);
		@unlink(self::$dossier . '/wp-config.php');
		@unlink(self::$dossier . '/wp-includes/version.php');
		@rmdir(self::$dossier . '/wp-includes');
		@rmdir(self::$dossier);
	}

	protected function tearDown(): void
	{
		include_spip('inc/meta');
		effacer_meta('wp2spip_prefixe_tables');
	}

	/**
	 * Lance wordpress:importer --info sur la base wpx_ avec ce wp-config.php
	 *
	 * @return array code de sortie, sortie
	 */
	private static function lancer(string $wp_config, array $options = array()): array
	{
		file_put_contents(self::$dossier . '/wp-config.php', $wp_config);
		$processus = proc_open(
			array_merge(
				array(PHP_BINARY, dirname(__DIR__, 2) . '/vendor/bin/spip', '--no-ansi', 'wordpress:importer', self::$dossier, '--base', self::BASE_WPX, '--info'),
				$options
			),
			array(1 => array('pipe', 'w'), 2 => array('redirect', 1)),
			$tubes,
			_SPIP_TEST_CHDIR
		);
		$sortie = stream_get_contents($tubes[1]);
		fclose($tubes[1]);
		return array(proc_close($processus), $sortie);
	}

	public function testPrefixeLuDansWpConfig(): void
	{
		[$code, $sortie] = self::lancer("<?php\n\$table_prefix = 'wpx_';\n");
		$this->assertSame(0, $code, $sortie);
		$this->assertStringContainsString('Préfixe des tables : wpx_ (wp-config.php).', $sortie);
	}

	public function testTablesAbsentes(): void
	{
		[$code, $sortie] = self::lancer("<?php\n\$table_prefix = 'wp_';\n");
		$this->assertSame(1, $code, $sortie);
		$this->assertStringContainsString('Tables absentes de la base Wordpress « wp2spip_tests_wpx » : wp_posts, wp_postmeta, wp_terms, wp_term_taxonomy, wp_term_relationships, wp_options, wp_users, wp_usermeta, wp_comments.', $sortie);
	}

	public function testOptionPrefixeRemplaceWpConfig(): void
	{
		[$code, $sortie] = self::lancer("<?php\n\$table_prefix = 'wp_';\n", array('--prefixe', 'wpx_'));
		$this->assertSame(0, $code, $sortie);
		$this->assertStringContainsString('Préfixe des tables : wpx_ (option --prefixe ; wp-config.php annonce wp_).', $sortie);
	}

	public function testPrefixeIntrouvable(): void
	{
		foreach (array('sans affectation' => "<?php\ndefine('DB_NAME', 'x');\n", 'deux affectations' => "<?php\nif (\$a) { \$table_prefix = 'a_'; } else { \$table_prefix = 'wpx_'; }\n", 'valeur calculée' => "<?php\n\$table_prefix = getenv('PREFIXE');\n") as $cas => $wp_config) {
			[$code, $sortie] = self::lancer($wp_config);
			$this->assertSame(1, $code, "$cas : $sortie");
			$this->assertStringContainsString('Préfixe des tables Wordpress introuvable dans wp-config.php : indiquer --prefixe.', $sortie, $cas);
		}
		[$code, $sortie] = self::lancer("<?php\ndefine('DB_NAME', 'x');\n", array('--prefixe', 'wpx_'));
		$this->assertSame(0, $code, $sortie);
	}

	public function testPrefixeInvalide(): void
	{
		[$code, $sortie] = self::lancer("<?php\n\$table_prefix = 'wpx_';\n", array('--prefixe', 'wp_;x'));
		$this->assertSame(1, $code, $sortie);
		$this->assertStringContainsString('Préfixe des tables « wp_;x » invalide', $sortie);
		$this->assertStringNotContainsString('Tables absentes', $sortie);
	}

	public function testSpipImporteDepuisUnAutrePrefixe(): void
	{
		ecrire_meta('wp2spip_prefixe_tables', 'wp_');
		// ecrire_meta() ne réécrit le cache des métas qu'à son premier appel du processus : la commande, lancée
		// dans un autre processus, le lirait sans cette méta
		touch_meta(time() - (_META_CACHE_TIME << 1));
		[$code, $sortie] = self::lancer("<?php\n\$table_prefix = 'wpx_';\n");
		$this->assertSame(1, $code, $sortie);
		$this->assertStringContainsString('Ce SPIP a été importé depuis les tables wp_ ; pour importer depuis wpx_, remettre le SPIP à zéro.', $sortie);
		lire_metas();
		$this->assertSame('wp_', $GLOBALS['meta']['wp2spip_prefixe_tables']);
	}

	public function testTraitementsSousLePrefixe(): void
	{
		include_spip('inc/wp2spip_plugins');
		$attendus = wp2spip_plugins_requis(self::BASE);
		ecrire_meta('wp2spip_prefixe_tables', 'wpx_');
		$this->assertSame('wpx_posts', wp2spip_table('posts'));
		$this->assertSame($attendus, wp2spip_plugins_requis(self::BASE_WPX));
	}
}
