<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use Spip\Test\SquelettesTestCase;
use Spip\Test\Templating;

/**
 * Boucles d'exemple de la section « Hiérarchie des pages » du readme, lues dans le readme, sur des liens sous_page
 */
final class SquelettesHierarchieTest extends SquelettesTestCase
{
	private const PAGES = array(10 => 'Parent', 11 => 'Enfant B', 12 => 'Enfant A', 13 => 'Enfant C');

	protected function setUp(): void
	{
		foreach (self::PAGES as $id => $titre) {
			sql_insertq('spip_articles', array('id_article' => $id, 'titre' => $titre, 'statut' => 'publie', 'id_rubrique' => -1, 'page' => 'page', 'date' => '2020-01-02 03:04:05'));
		}
		foreach (array(1 => 12, 2 => 11, 3 => 13) as $rang => $id_enfant) {
			sql_insertq('spip_articles_lies', array('id_article' => 10, 'id_article_lie' => $id_enfant, 'rang' => $rang, 'type_liaison' => 'sous_page'));
		}
	}

	protected function tearDown(): void
	{
		sql_delete('spip_articles_lies', sql_in('id_article', array_keys(self::PAGES)));
		sql_delete('spip_articles', sql_in('id_article', array_keys(self::PAGES)));
	}

	private static function rendre(string $code, int $id_article): string
	{
		$html = Templating::fromString()->render($code, array('id_article' => $id_article));
		return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
	}

	public function testSousPages(): void
	{
		[$sous_pages] = self::boucles();
		$this->assertSame('Enfant A Enfant B Enfant C', self::rendre($sous_pages, 10));
		$this->assertSame('', self::rendre($sous_pages, 11));
	}

	public function testPageParente(): void
	{
		[, $parente] = self::boucles();
		$this->assertSame('Parent', self::rendre($parente, 13));
		$this->assertSame('', self::rendre($parente, 10));
	}

	/**
	 * Boucles du bloc html de la section « Hiérarchie des pages » du readme, séparées par leurs commentaires
	 */
	private static function boucles(): array
	{
		$readme = file_get_contents(dirname(__DIR__, 2) . '/readme.md');
		if (!preg_match('/## Hiérarchie des pages.*?```html\n(.*?)```/s', $readme, $bloc)) {
			self::fail('bloc html de la section « Hiérarchie des pages » introuvable dans le readme');
		}
		$boucles = array_values(array_filter(array_map('trim', preg_split('/<!--[^>]*-->/', $bloc[1]))));
		self::assertCount(2, $boucles, 'deux boucles dans le readme');
		return $boucles;
	}
}
