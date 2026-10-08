<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

/**
 * Traitement importer_hierarchie_pages : liens a2a sous_page de chaque page parente vers ses pages enfants
 *
 * Les pages du WordPress de test (10 à 15) sont créées dans SPIP par le test, puis retirées avec leurs liens.
 */
final class HierarchiePagesTest extends WordpressTestCase
{
	private const PAGES = array(10 => 'Parent', 11 => 'Enfant B', 12 => 'Enfant A', 13 => 'Enfant C', 14 => 'Petite-fille', 15 => 'Sous un article', 1 => 'Bonjour');

	private mixed $types_liaisons = null;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('inc/config');
		include_spip('wp2spip/importer_hierarchie_pages');
	}

	protected function setUp(): void
	{
		$this->types_liaisons = lire_config('a2a/types_liaisons');
		foreach (self::PAGES as $id => $titre) {
			sql_insertq('spip_articles', array(
				'id_article' => $id, 'id_wordpress' => $id, 'titre' => $titre, 'statut' => 'publie',
				'id_rubrique' => $id == 1 ? 0 : -1, 'page' => $id == 1 ? '' : 'page', 'date' => '2020-01-02 03:04:05',
			));
		}
	}

	protected function tearDown(): void
	{
		$ids = array_keys(self::PAGES);
		sql_delete('spip_articles_lies', sql_in('id_article', $ids) . ' OR ' . sql_in('id_article_lie', $ids));
		sql_delete('spip_articles', sql_in('id_article', $ids));
		if ($this->types_liaisons === null) {
			effacer_config('a2a/types_liaisons');
		} else {
			ecrire_config('a2a/types_liaisons', $this->types_liaisons);
		}
	}

	/**
	 * Liens sous_page de la base, par page parente : id_article => array(rang => id_article_lie)
	 */
	private static function liens(): array
	{
		$liens = array();
		foreach (sql_allfetsel('id_article, id_article_lie, rang', 'spip_articles_lies', 'type_liaison = "sous_page"', '', 'id_article, rang') as $lien) {
			$liens[intval($lien['id_article'])][intval($lien['rang'])] = intval($lien['id_article_lie']);
		}
		return $liens;
	}

	public function testLiensDansLOrdreWordpress(): void
	{
		$commande = self::commande();
		$this->assertNotFalse(wp2spip_importer_hierarchie_pages_dist($commande));
		// Enfants de 10 : 12 (ordre 1), puis 11 et 13 (ordre 2, par titre) ; enfant de 12 : 14 ; 15 a pour parent un article
		$this->assertSame(array(10 => array(1 => 12, 2 => 11, 3 => 13), 12 => array(1 => 14)), self::liens());
		$this->assertStringContainsString('4 liens de sous-pages créés (a2a, type sous_page), pour 2 pages parentes.', $commande->output->fetch());
		$this->assertSame('Sous-page (WordPress)', lire_config('a2a/types_liaisons/sous_page'));
	}

	public function testRelanceSansNouveauLien(): void
	{
		wp2spip_importer_hierarchie_pages_dist(self::commande());
		$commande = self::commande();
		$this->assertNotFalse(wp2spip_importer_hierarchie_pages_dist($commande));
		$sortie = $commande->output->fetch();
		$this->assertStringContainsString('0 liens de sous-pages créés', $sortie);
		$this->assertStringContainsString('4 liens de sous-pages déjà présents.', $sortie);
		$this->assertCount(4, sql_allfetsel('id_article', 'spip_articles_lies', 'type_liaison = "sous_page"'));
	}

	public function testPageAbsenteEnEchec(): void
	{
		sql_delete('spip_articles', 'id_article = 14');
		$commande = self::commande();
		$this->assertFalse(wp2spip_importer_hierarchie_pages_dist($commande));
		$this->assertStringContainsString('Pages enfants ou parentes absentes de SPIP, pas de lien : 14 (parent 12).', $commande->output->fetch());
		$this->assertSame(array(10 => array(1 => 12, 2 => 11, 3 => 13)), self::liens());
	}

	public function testSansPageEnfant(): void
	{
		// Pages sans parent, et une page dont le parent est un article : ni a2a requis, ni lien
		$connexion = 'wp2spip_tests_sans_enfant';
		self::construireBase($connexion, 'wp_', array('posts' => array(
			array('ID' => 1, 'post_type' => 'post', 'post_title' => 'Article'),
			array('ID' => 2, 'post_type' => 'page', 'post_title' => 'Page'),
			array('ID' => 3, 'post_type' => 'page', 'post_title' => 'Page sous un article', 'post_parent' => 1),
		)));
		try {
			include_spip('inc/wp2spip_plugins');
			$this->assertArrayNotHasKey('a2a', wp2spip_plugins_requis($connexion));
			$commande = self::commande($connexion);
			$this->assertNull(wp2spip_importer_hierarchie_pages_dist($commande));
			$this->assertSame('', $commande->output->fetch());
			$this->assertSame(array(), self::liens());
		} finally {
			self::retirerBase($connexion);
		}
	}
}
