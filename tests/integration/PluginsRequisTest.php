<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

/**
 * Plugins requis par le contenu du WordPress de test (inc/wp2spip_plugins.php)
 */
final class PluginsRequisTest extends WordpressTestCase
{
	public function testPluginsRequisParLeContenu(): void
	{
		include_spip('inc/wp2spip_plugins');
		$requis = wp2spip_plugins_requis(self::BASE);
		$this->assertSame(
			array(
				'albums' => '1 contenus avec une galerie',
				'accesrestreint' => '2 contenus privés ou protégés',
				'forum' => '1 commentaires',
				'a2a' => '4 pages enfants',
			),
			array_map(fn($plugin) => $plugin['raison'], $requis)
		);
	}

	public function testPluginsPrets(): void
	{
		include_spip('inc/wp2spip_plugins');
		foreach (wp2spip_plugins_requis(self::BASE) as $prefixe => $plugin) {
			$this->assertTrue(wp2spip_plugin_pret($prefixe, $plugin), "$prefixe prêt");
		}
	}
}
