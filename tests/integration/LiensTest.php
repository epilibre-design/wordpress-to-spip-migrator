<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Liens vers le site WordPress remplacés par des liens internes (wp2spip_chercher_lien(), wp2spip_chercher_document())
 */
final class LiensTest extends WordpressTestCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('wp2spip/importer_articles');
	}

	#[DataProvider('liens')]
	public function testChercherLien(string $attendu, string $lien): void
	{
		$this->assertSame($attendu, wp2spip_chercher_lien($lien, 'http://wordpress.test', self::BASE));
	}

	public static function liens(): array
	{
		return array(
			'fichier d’un média' => array('document967', 'http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg'),
			'taille dérivée déclarée' => array('document967', 'http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1-150x150.jpg'),
			'taille dérivée non déclarée' => array('document611', 'http://wordpress.test/wp-content/uploads/2008/06/canola2-300x200.jpg'),
			'https et www' => array('document611', 'https://www.wordpress.test/wp-content/uploads/2008/06/canola2.jpg'),
			'fichier inconnu' => array('http://wordpress.test/wp-content/uploads/inconnu.jpg', 'http://wordpress.test/wp-content/uploads/inconnu.jpg'),
			'article par ?p=' => array('article1', 'http://wordpress.test/?p=1'),
			'page par ?page_id=, lien relatif' => array('article10', '/?page_id=10'),
			'page d’un média' => array('document611', 'http://wordpress.test/?p=611'),
			'article par son slug' => array('article1', 'http://wordpress.test/bonjour/'),
			'page enfant par son chemin' => array('article12', 'http://wordpress.test/parent/enfant-a/'),
			'autre site' => array('http://ailleurs.test/bonjour/', 'http://ailleurs.test/bonjour/'),
		);
	}

	public function testChercherDocumentHorsSite(): void
	{
		$this->assertSame(0, wp2spip_chercher_document('http://ailleurs.test/wp-content/uploads/2008/06/canola2.jpg', 'http://wordpress.test', self::BASE));
	}
}
