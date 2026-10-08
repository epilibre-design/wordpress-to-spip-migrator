<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/wp2spip/importer_articles.php';

/**
 * Fonctions sans base de wp2spip/importer_articles.php : slugs, adresses, chemins des médias
 */
final class ImporterArticlesTest extends TestCase
{
	#[DataProvider('slugs')]
	public function testNormaliserSlug(string $attendu, string $slug): void
	{
		$this->assertSame($attendu, wp2spip_normaliser_slug($slug));
	}

	public static function slugs(): array
	{
		return array(
			'ASCII' => array('bonjour', 'bonjour'),
			'encodé en minuscules' => array('%c3%a9t%c3%a9', '%c3%a9t%c3%a9'),
			'encodé en majuscules' => array('%c3%a9t%c3%a9', '%C3%A9t%C3%A9'),
			'en clair' => array('%c3%a9t%c3%a9', 'été'),
		);
	}

	#[DataProvider('adresses')]
	public function testUrlDuSite(bool $attendu, string $url): void
	{
		$this->assertSame($attendu, wp2spip_url_du_site($url, 'https://www.wordpress.test'));
	}

	public static function adresses(): array
	{
		return array(
			'relative' => array(true, '/bonjour/'),
			'même site' => array(true, 'https://www.wordpress.test/bonjour/'),
			'http et sans www' => array(true, 'http://wordpress.test/bonjour/'),
			'majuscules' => array(true, 'HTTP://WordPress.Test/'),
			'autre site' => array(false, 'https://ailleurs.test/bonjour/'),
			'sous-domaine' => array(false, 'https://blog.wordpress.test/'),
		);
	}

	#[DataProvider('uploads')]
	public function testCheminUpload(string $attendu, string $url): void
	{
		$this->assertSame($attendu, wp2spip_chemin_upload($url));
	}

	public static function uploads(): array
	{
		return array(
			'fichier' => array('2008/06/canola2.jpg', 'http://wordpress.test/wp-content/uploads/2008/06/canola2.jpg'),
			'accents encodés' => array('2020/01/été.jpg', 'http://wordpress.test/wp-content/uploads/2020/01/%C3%A9t%C3%A9.jpg'),
			'paramètres et entités' => array('2008/06/canola2.jpg', 'http://wordpress.test/wp-content/uploads/2008/06/canola2.jpg?ver=1&amp;x=2'),
			'hors uploads' => array('', 'http://wordpress.test/wp-content/themes/a.jpg'),
		);
	}

	#[DataProvider('chemins')]
	public function testChercherSlug(int $attendu, string $chemin): void
	{
		// Deux pages « contact » (sous 10 et sous 20), un article et une page « actualites »
		$contenus = array(
			'slugs' => array('contact' => array(11, 21), 'parent' => array(10), 'autre' => array(20), 'actualites' => array(30, 31), 'bonjour' => array(1)),
			'chemins' => array(11 => 'parent/contact', 21 => 'autre/contact', 10 => 'parent', 20 => 'autre', 30 => 'actualites', 31 => 'actualites', 1 => 'bonjour'),
		);
		$this->assertSame($attendu, wp2spip_chercher_slug($chemin, $contenus));
	}

	public static function chemins(): array
	{
		return array(
			'slug unique' => array(1, 'bonjour'),
			'slug unique sous une date' => array(1, '2020/01/02/bonjour'),
			'page enfant par son chemin' => array(21, 'autre/contact'),
			'chemin plus long' => array(11, 'fr/parent/contact'),
			'slug ambigu sans parent' => array(0, 'contact'),
			'slug ambigu de même chemin' => array(0, 'actualites'),
			'slug inconnu' => array(0, 'inconnu'),
		);
	}

	public function testLiensMediasRestants(): void
	{
		$texte = '<img src="http://wordpress.test/wp-content/uploads/a.jpg"> [lien->/wp-content/uploads/b.pdf] '
			. 'http://ailleurs.test/wp-content/uploads/c.jpg <img src="http://wordpress.test/wp-content/uploads/a.jpg">';
		$this->assertSame(
			array('http://wordpress.test/wp-content/uploads/a.jpg', '/wp-content/uploads/b.pdf'),
			wp2spip_liens_medias_restants($texte, 'http://wordpress.test')
		);
	}
}
