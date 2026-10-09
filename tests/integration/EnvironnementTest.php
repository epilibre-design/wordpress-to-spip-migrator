<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Le SPIP de test est prêt : wp2spip et les plugins requis par les tests actifs, aucune table manquante
 */
final class EnvironnementTest extends TestCase
{
	#[DataProvider('plugins')]
	public function testPluginActif(string $prefixe): void
	{
		include_spip('inc/plugin');
		$this->assertTrue(test_plugin_actif($prefixe), "plugin $prefixe inactif : relancer composer install-spip-test");
	}

	public static function plugins(): array
	{
		return array_map(fn($prefixe) => array($prefixe), array('wp2spip', 'pages', 'polyhier', 'albums', 'a2a', 'accesrestreint'));
	}

	public function testAucuneTableManquante(): void
	{
		include_spip('inc/wp2spip_plugins');
		$this->assertSame(array(), wp2spip_tables_manquantes());
	}
}
