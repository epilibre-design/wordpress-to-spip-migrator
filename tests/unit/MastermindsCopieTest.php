<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * La copie de Masterminds livrée dans lib/ est celle du verrou Composer (outils/copier_masterminds.sh la met à jour)
 */
final class MastermindsCopieTest extends TestCase
{
	private const RACINE = __DIR__ . '/../..';
	private const COPIE = self::RACINE . '/lib/masterminds-html5';

	public function testVersionDuVerrou(): void
	{
		$verrou = json_decode(file_get_contents(self::RACINE . '/composer.lock'), true);
		$versions = array_column($verrou['packages'], 'version', 'name');
		$this->assertSame(ltrim($versions['masterminds/html5'], 'v'), trim((string) @file_get_contents(self::COPIE . '/VERSION')));
	}

	public function testSourcesIdentiquesAuVerrou(): void
	{
		$this->assertSame($this->empreintes(self::RACINE . '/vendor/masterminds/html5/src'), $this->empreintes(self::COPIE . '/src'));
	}

	public function testLicence(): void
	{
		$this->assertFileExists(self::COPIE . '/LICENSE.txt');
	}

	/**
	 * @return array<string, string> chemin relatif => empreinte
	 */
	private function empreintes(string $dossier): array
	{
		$empreintes = array();
		if (is_dir($dossier)) {
			$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS));
			foreach ($fichiers as $fichier) {
				$empreintes[substr($fichier->getPathname(), strlen($dossier) + 1)] = hash_file('sha256', $fichier->getPathname());
			}
		}
		ksort($empreintes);
		return $empreintes;
	}
}
