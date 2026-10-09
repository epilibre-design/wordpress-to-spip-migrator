<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

/**
 * Traitement importer_mots : étiquettes en mots-clés du groupe « Étiquettes », liés aux articles
 *
 * Étiquettes du WordPress de test : 30 « Caf&eacute; » (description), 31 sans contenu, 32 et 33 « Doublon ».
 * Les contenus étiquetés (1, 10, 20, 22) sont créés dans SPIP par le test ; tout ce que le test crée est retiré.
 */
final class MotsTest extends WordpressTestCase
{
	private const ARTICLES = array(1, 10, 20, 22);

	private array $metas = array();

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('inc/meta');
		include_spip('wp2spip/importer_mots');
	}

	protected function setUp(): void
	{
		$this->metas = array(
			'articles_mots' => $GLOBALS['meta']['articles_mots'] ?? null,
			'wp2spip_groupe_etiquettes' => $GLOBALS['meta']['wp2spip_groupe_etiquettes'] ?? null,
		);
		foreach (self::ARTICLES as $id) {
			sql_insertq('spip_articles', array('id_article' => $id, 'id_wordpress' => $id, 'titre' => "Contenu $id", 'statut' => 'publie', 'date' => '2020-01-02 03:04:05'));
		}
	}

	protected function tearDown(): void
	{
		$groupes = array_column(sql_allfetsel('id_groupe', 'spip_groupes_mots', sql_in('titre', array('Étiquettes', 'Autre groupe'))), 'id_groupe');
		$mots = array_column(sql_allfetsel('id_mot', 'spip_mots', sql_in('id_groupe', $groupes ?: array(0))), 'id_mot');
		sql_delete('spip_mots_liens', sql_in('id_mot', $mots ?: array(0)));
		sql_delete('spip_mots', sql_in('id_mot', $mots ?: array(0)));
		sql_delete('spip_groupes_mots', sql_in('id_groupe', $groupes ?: array(0)));
		sql_delete('spip_articles', sql_in('id_article', self::ARTICLES));
		foreach ($this->metas as $nom => $valeur) {
			$valeur === null ? effacer_meta($nom) : ecrire_meta($nom, $valeur);
		}
	}

	/**
	 * Liens mot → article : liste « id_mot → id_article » triée
	 */
	private static function liens(): array
	{
		$liens = array_map(
			fn($lien) => $lien['id_mot'] . ' → ' . $lien['id_objet'],
			sql_allfetsel('id_mot, id_objet', 'spip_mots_liens', 'objet = "article"', '', 'id_mot, id_objet')
		);
		return $liens;
	}

	public function testEtiquettesEtLiens(): void
	{
		$commande = self::commande();
		$this->assertNotFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('4 étiquettes importées en mots-clés (groupe « Étiquettes »), 0 déjà présentes ; 5 liens avec les articles.', $commande->output->fetch());

		$id_groupe = intval($GLOBALS['meta']['wp2spip_groupe_etiquettes']);
		$this->assertSame(
			array('titre' => 'Étiquettes', 'tables_liees' => 'articles', 'unseul' => 'non', 'obligatoire' => 'non', 'minirezo' => 'oui', 'comite' => 'oui'),
			sql_fetsel('titre, tables_liees, unseul, obligatoire, minirezo, comite', 'spip_groupes_mots', 'id_groupe = ' . $id_groupe)
		);
		$this->assertSame(
			array(
				array('id_mot' => '30', 'id_wordpress' => '30', 'titre' => 'Café', 'descriptif' => 'Une {boisson}', 'id_groupe' => (string) $id_groupe),
				array('id_mot' => '31', 'id_wordpress' => '31', 'titre' => 'Sans contenu', 'descriptif' => '', 'id_groupe' => (string) $id_groupe),
				array('id_mot' => '32', 'id_wordpress' => '32', 'titre' => 'Doublon', 'descriptif' => '', 'id_groupe' => (string) $id_groupe),
				array('id_mot' => '33', 'id_wordpress' => '33', 'titre' => 'Doublon', 'descriptif' => '', 'id_groupe' => (string) $id_groupe),
			),
			array_map(fn($mot) => array_map('strval', $mot), sql_allfetsel('id_mot, id_wordpress, titre, descriptif, id_groupe', 'spip_mots', 'id_wordpress > 0', '', 'id_mot'))
		);
		// Ni la catégorie, ni le média étiqueté
		$this->assertSame(array('30 → 1', '30 → 22', '32 → 1', '32 → 20', '33 → 10'), self::liens());
		$this->assertSame('oui', $GLOBALS['meta']['articles_mots']);
	}

	public function testRelanceSansDoublon(): void
	{
		wp2spip_importer_mots_dist(self::commande());
		$commande = self::commande();
		$this->assertNotFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('0 étiquettes importées en mots-clés (groupe « Étiquettes »), 4 déjà présentes ; 0 liens avec les articles.', $commande->output->fetch());
		$this->assertSame(4, sql_countsel('spip_mots', 'id_wordpress > 0'));
		$this->assertCount(5, self::liens());
		$this->assertSame(1, sql_countsel('spip_groupes_mots', 'titre = "Étiquettes"'));
	}

	public function testContenuAbsent(): void
	{
		sql_delete('spip_articles', 'id_article = 20');
		$commande = self::commande();
		$this->assertFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('Contenus étiquetés absents de SPIP, pas de lien (étiquette → contenu) : 32 → 20.', $commande->output->fetch());
		$this->assertSame(array('30 → 1', '30 → 22', '32 → 1', '33 → 10'), self::liens());
	}

	public function testIdentifiantOccupe(): void
	{
		$id_autre = intval(sql_insertq('spip_groupes_mots', array('titre' => 'Autre groupe', 'tables_liees' => 'articles')));
		sql_insertq('spip_mots', array('id_mot' => 31, 'titre' => 'Mot SPIP', 'id_groupe' => $id_autre));
		$commande = self::commande();
		$this->assertFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('identifiants de mot déjà pris par des objets SPIP qui ne viennent pas de ce Wordpress : 31', $commande->output->fetch());
		$this->assertSame(0, sql_countsel('spip_mots', 'id_wordpress > 0'));
	}

	public function testMotDansUnAutreGroupe(): void
	{
		$id_autre = intval(sql_insertq('spip_groupes_mots', array('titre' => 'Autre groupe', 'tables_liees' => 'articles')));
		sql_insertq('spip_mots', array('id_mot' => 31, 'id_wordpress' => 31, 'titre' => 'Sans contenu', 'id_groupe' => $id_autre));
		$commande = self::commande();
		$this->assertFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('Mots-clés venant d’étiquettes Wordpress, mais hors du groupe « Étiquettes » : 31.', $commande->output->fetch());
	}

	public function testGroupeDisparu(): void
	{
		ecrire_meta('wp2spip_groupe_etiquettes', '999999');
		$commande = self::commande();
		$this->assertFalse(wp2spip_importer_mots_dist($commande));
		$this->assertStringContainsString('Le groupe de mots-clés « Étiquettes » (999999, méta wp2spip_groupe_etiquettes) n’existe plus', $commande->output->fetch());
		$this->assertSame(0, sql_countsel('spip_groupes_mots', 'titre = "Étiquettes"'));
	}

	public function testSansEtiquette(): void
	{
		$connexion = 'wp2spip_tests_sans_etiquette';
		self::construireBase($connexion, 'wp_', array('posts' => array(array('ID' => 1, 'post_title' => 'Article'))));
		try {
			$commande = self::commande($connexion);
			$this->assertNull(wp2spip_importer_mots_dist($commande));
			$this->assertSame('', $commande->output->fetch());
			$this->assertSame(0, sql_countsel('spip_groupes_mots', 'titre = "Étiquettes"'));
			$this->assertArrayNotHasKey('wp2spip_groupe_etiquettes', $GLOBALS['meta']);
		} finally {
			self::retirerBase($connexion);
		}
	}
}
