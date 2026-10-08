# wp2spip — sous-projet 5 : étiquettes — plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Importer les étiquettes WordPress (`post_tag`) en mots-clés d'un groupe « Étiquettes », de même identifiant, liés aux articles et pages qu'elles étiquettent.

**Architecture:** Nouveau traitement `wp2spip/importer_mots.php`, placé entre `importer_hierarchie_pages` et `importer_acces`. Il crée (ou retrouve par la méta `wp2spip_groupe_etiquettes`) le groupe, vérifie que les identifiants sont libres, crée chaque mot par `objet_inserer('mot', $id_groupe, $set)` avec `id_mot` = `id_wordpress` = `term_id`, puis lie les mots aux articles par `objet_associer()` et contrôle chaque lien. Le vérificateur et l'export couvrent les étiquettes ; la référence versionnée de `tests-import` s'enrichit des lignes `mot` et `mot_article`.

**Tech Stack:** PHP (SPIP 4.4, plugin `mots` de `plugins-dist`), PHPUnit 13 (sous-projet 7), bash.

Spec : `docs/superpowers/specs/2026-10-09-wp2spip-etiquettes-design.md`. Prérequis : sous-projets 6 (`wp2spip_table()`) et 7 (tests) réalisés.

## Global Constraints

- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`) ; le message s'arrête après le corps.
- Ne jamais nommer le site WordPress réel (nom, domaine, base, chemins, titres) dans les fichiers versionnés ni les commits.
- `tests/integration/environnement.sh` (accès locaux) n'est jamais versionné ni copié dans un SPIP ; le SPIP-Cli du projet n'est pas modifié.
- Tables WordPress toujours nommées par `wp2spip_table()` (sous-projet 6).
- Groupe : titre `Étiquettes`, `tables_liees` = `articles`, `unseul` = `non`, `obligatoire` = `non`, `minirezo` = `oui`, `comite` = `oui`, `forum` = `non` ; méta `wp2spip_groupe_etiquettes`.
- Bilan, mot pour mot : `N étiquettes importées en mots-clés (groupe « Étiquettes »), K déjà présentes ; M liens avec les articles.`
- Les tests d'intégration retirent ce qu'ils créent (mots, groupes, liens, articles, métas rétablies).

Variables des commandes : `source tests/integration/environnement.sh` depuis la racine du dépôt.

---

### Task 1 : traitement `importer_mots`

**Files:**
- Create: `wp2spip/importer_mots.php`, `tests/integration/MotsTest.php`, `tests/integration/data/wordpress/terms.php`, `term_taxonomy.php`, `term_relationships.php`
- Modify: `spip-cli/WordpressImporter.php` (liste des traitements)

**Interfaces:**
- Consumes : `wp2spip_table()`, `wp2spip_verifier_identifiants()`, `wp2spip_erreur_insertion()`, `wp2spip_decoder_entites()` (`inc/wp2spip.php`) ; `WordpressTestCase` (`BASE`, `commande()`, `construireBase()`, `retirerBase()`).
- Produces : `wp2spip_importer_mots_dist($command)` (null sans étiquette, false en échec) ; `wp2spip_groupe_etiquettes($command): int` (0 en échec) ; méta `wp2spip_groupe_etiquettes` ; méta `articles_mots` = `oui` s'il y a des étiquettes.

Étiquettes du WordPress de test : 30 « Caf&eacute; » (description `Une <em>boisson</em>`), 31 sans contenu, 32 et 33 « Doublon » (slugs différents) ; `term_taxonomy_id` = `term_id` + 100 ; relations : article 1 (30, 32), brouillon 22 (30), privé 20 (32), page 10 (33), média 611 (30, non importé), et la catégorie 1 de l'article 1. Attendu : 4 mots, 5 liens.

- [ ] **Step 1 : données de test**

`tests/integration/data/wordpress/terms.php` :

```php
<?php
/**
 * Termes : une catégorie, et quatre étiquettes (une avec entité et description, une sans contenu, deux de même nom)
 */
return array(
	array('term_id' => 1, 'name' => 'Non classé', 'slug' => 'non-classe'),
	array('term_id' => 30, 'name' => 'Caf&eacute;', 'slug' => 'cafe'),
	array('term_id' => 31, 'name' => 'Sans contenu', 'slug' => 'sans-contenu'),
	array('term_id' => 32, 'name' => 'Doublon', 'slug' => 'doublon'),
	array('term_id' => 33, 'name' => 'Doublon', 'slug' => 'doublon-2'),
);
```

`tests/integration/data/wordpress/term_taxonomy.php` :

```php
<?php
/**
 * Taxonomies : term_taxonomy_id différent de term_id, comme sur un site où des termes ont été partagés puis séparés
 */
return array(
	array('term_taxonomy_id' => 101, 'term_id' => 1, 'taxonomy' => 'category', 'count' => 1),
	array('term_taxonomy_id' => 130, 'term_id' => 30, 'taxonomy' => 'post_tag', 'description' => 'Une <em>boisson</em>', 'count' => 1),
	array('term_taxonomy_id' => 131, 'term_id' => 31, 'taxonomy' => 'post_tag', 'count' => 0),
	array('term_taxonomy_id' => 132, 'term_id' => 32, 'taxonomy' => 'post_tag', 'count' => 1),
	array('term_taxonomy_id' => 133, 'term_id' => 33, 'taxonomy' => 'post_tag', 'count' => 1),
);
```

`tests/integration/data/wordpress/term_relationships.php` :

```php
<?php
/**
 * Relations : étiquettes d'un article publié, d'un brouillon, d'un contenu privé et d'une page ; une étiquette
 * posée sur un média (non importée) ; la catégorie de l'article
 */
return array(
	array('object_id' => 1, 'term_taxonomy_id' => 101),
	array('object_id' => 1, 'term_taxonomy_id' => 130),
	array('object_id' => 1, 'term_taxonomy_id' => 132),
	array('object_id' => 22, 'term_taxonomy_id' => 130),
	array('object_id' => 20, 'term_taxonomy_id' => 132),
	array('object_id' => 10, 'term_taxonomy_id' => 133),
	array('object_id' => 611, 'term_taxonomy_id' => 130),
);
```

- [ ] **Step 2 : tests, en échec**

`tests/integration/MotsTest.php` :

```php
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
```

Run: `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter MotsTest`
Expected: erreurs `Call to undefined function wp2spip_importer_mots_dist()` (le fichier n'existe pas encore : `include_spip()` ne charge rien).

- [ ] **Step 3 : le traitement**

`wp2spip/importer_mots.php` :

```php
<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Noms des tables WordPress (wp2spip_table())
include_spip('inc/wp2spip');

/**
 * Étiquettes Wordpress (taxonomie post_tag) importées en mots-clés du groupe « Étiquettes », liés aux articles
 *
 * Chaque mot prend l'identifiant de son terme (id_mot = term_id), comme les rubriques pour les catégories.
 * Toutes les étiquettes sont importées, même sans contenu. Les liens viennent des relations post_tag des contenus
 * importés (articles et pages, quel que soit leur statut) : le traitement suit importer_articles.
 */
function wp2spip_importer_mots_dist($command) {
	$etiquettes = sql_allfetsel(
		'term.term_id, term.name, tax.description',
		wp2spip_table('term_taxonomy') . ' as tax join ' . wp2spip_table('terms') . ' as term on tax.term_id = term.term_id',
		'tax.taxonomy = ' . sql_quote('post_tag'),
		'',
		'term.term_id',
		'',
		'',
		$command->base
	);
	if (!$etiquettes) {
		return;
	}

	include_spip('action/editer_objet');
	include_spip('action/editer_liens');
	include_spip('inc/meta');
	include_spip('sale_fonctions');

	if (!$id_groupe = wp2spip_groupe_etiquettes($command)) {
		return false;
	}

	// Déjà importés : seulement les mots du groupe ; un mot d'un autre groupe avec cet id_wordpress est une incohérence
	$ids_wordpress = array_map('intval', array_column($etiquettes, 'term_id'));
	$existants = sql_allfetsel('id_mot, id_groupe, id_wordpress', 'spip_mots', sql_in('id_wordpress', $ids_wordpress));
	if ($ailleurs = array_filter($existants, fn($mot) => intval($mot['id_groupe']) !== $id_groupe)) {
		$command->output->writeln('<error>Mots-clés venant d’étiquettes Wordpress, mais hors du groupe « Étiquettes » : ' . join(', ', array_column($ailleurs, 'id_mot')) . '.</error>');
		return false;
	}
	$deja = array_map('intval', array_column($existants, 'id_wordpress'));
	$a_creer = array_values(array_filter($etiquettes, fn($etiquette) => !in_array(intval($etiquette['term_id']), $deja)));

	// Chaque mot prend l'identifiant de son étiquette : ils doivent tous être libres
	if (!wp2spip_verifier_identifiants($command, 'mot', array_column($a_creer, 'term_id'))) {
		return false;
	}
	foreach ($a_creer as $etiquette) {
		$id_mot = intval($etiquette['term_id']);
		$set = array(
			'id_mot' => $id_mot,
			'id_wordpress' => $id_mot,
			'titre' => wp2spip_decoder_entites($etiquette['name']),
			'descriptif' => ($etiquette['description'] !== '') ? trim(sale($etiquette['description'])) : '',
		);
		if (objet_inserer('mot', $id_groupe, $set) != $id_mot) {
			return wp2spip_erreur_insertion($command, 'mot', $id_mot);
		}
	}

	// Liens : relations post_tag des contenus importés
	$relations = sql_allfetsel(
		'rel.object_id, tax.term_id',
		wp2spip_table('term_relationships') . ' as rel'
			. ' join ' . wp2spip_table('term_taxonomy') . ' as tax on tax.term_taxonomy_id = rel.term_taxonomy_id'
			. ' join ' . wp2spip_table('posts') . ' as post on post.ID = rel.object_id',
		array('tax.taxonomy = ' . sql_quote('post_tag'), sql_in('post.post_type', array('post', 'page'))),
		'',
		'tax.term_id, rel.object_id',
		'',
		'',
		$command->base
	);
	$nb_liens = 0;
	$absents = array();
	$non_crees = array();
	foreach ($relations as $relation) {
		$id_mot = intval($relation['term_id']);
		$id_article = intval($relation['object_id']);
		if (!sql_countsel('spip_articles', 'id_article = ' . $id_article)) {
			$absents[] = "$id_mot → $id_article";
			continue;
		}
		$lien = array('id_mot = ' . $id_mot, 'objet = ' . sql_quote('article'), 'id_objet = ' . $id_article);
		if (sql_countsel('spip_mots_liens', $lien)) {
			continue;
		}
		objet_associer(array('mot' => $id_mot), array('article' => $id_article));
		if (!sql_countsel('spip_mots_liens', $lien)) {
			$non_crees[] = "$id_mot → $id_article";
			continue;
		}
		$nb_liens++;
	}

	// Mots-clés visibles sur les articles
	ecrire_meta('articles_mots', 'oui');

	$command->output->writeln(count($a_creer) . ' étiquettes importées en mots-clés (groupe « Étiquettes »), ' . count($deja) . " déjà présentes ; $nb_liens liens avec les articles.");
	if ($absents) {
		$command->output->writeln('<error>Contenus étiquetés absents de SPIP, pas de lien (étiquette → contenu) : ' . join(', ', $absents) . '.</error>');
	}
	if ($non_crees) {
		$command->output->writeln('<error>Liens étiquette → article non créés : ' . join(', ', $non_crees) . '.</error>');
	}
	if ($absents or $non_crees) {
		return false;
	}
}

/**
 * Groupe « Étiquettes » : créé au premier passage, retrouvé ensuite par la méta wp2spip_groupe_etiquettes
 *
 * @param object $command
 * @return int id_groupe, ou 0 en cas d'échec
 */
function wp2spip_groupe_etiquettes($command) {
	if ($id_groupe = intval($GLOBALS['meta']['wp2spip_groupe_etiquettes'] ?? 0)) {
		if (sql_countsel('spip_groupes_mots', 'id_groupe = ' . $id_groupe)) {
			return $id_groupe;
		}
		$command->output->writeln("<error>Le groupe de mots-clés « Étiquettes » ($id_groupe, méta wp2spip_groupe_etiquettes) n’existe plus : remettre le SPIP à zéro, puis relancer un import complet.</error>");
		return 0;
	}
	$id_groupe = intval(objet_inserer('groupe_mots', null, array(
		'titre' => 'Étiquettes',
		'tables_liees' => 'articles',
		'unseul' => 'non',
		'obligatoire' => 'non',
		'minirezo' => 'oui',
		'comite' => 'oui',
		'forum' => 'non',
	)));
	if (!$id_groupe) {
		$command->output->writeln('<error>Impossible de créer le groupe de mots-clés « Étiquettes ».</error>');
		return 0;
	}
	ecrire_meta('wp2spip_groupe_etiquettes', $id_groupe);
	return $id_groupe;
}
```

Dans `spip-cli/WordpressImporter.php`, liste `$traitements_disponibles` : ajouter `'importer_mots',` entre `'importer_hierarchie_pages',` et `'importer_acces',`.

Run: `composer tests-integration`
Expected: `OK` (MotsTest : 7 tests), deux fois de suite ; puis `(cd vendor/spip/spip && ../../bin/spip php:eval 'echo sql_countsel("spip_mots"), " ", sql_countsel("spip_groupes_mots"), " ", sql_countsel("spip_articles");')` → `0 0 0`.

- [ ] **Step 4 : sabotage**

```bash
cp wp2spip/importer_mots.php "$ESSAIS/importer_mots.php"
sed -i "s/array('tax.taxonomy = ' . sql_quote('post_tag'), sql_in('post.post_type', array('post', 'page'))),/array('tax.taxonomy = ' . sql_quote('post_tag')),/" wp2spip/importer_mots.php
vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter MotsTest
cp "$ESSAIS/importer_mots.php" wp2spip/importer_mots.php
```

Expected: 3 échecs (`testEtiquettesEtLiens`, `testRelanceSansDoublon`, `testContenuAbsent` : la relation du média 611 vise un contenu absent de SPIP) ; fichier rétabli, `--filter MotsTest` de nouveau à `OK (7 tests, 26 assertions)`.

- [ ] **Step 5 : commit**

```bash
git add wp2spip/importer_mots.php spip-cli/WordpressImporter.php tests/integration/MotsTest.php tests/integration/data/wordpress
git commit -m "Étiquettes importées en mots-clés du groupe « Étiquettes », liés aux articles (importer_mots)"
```

### Task 2 : vérificateur, export, référence, validation et documentation

**Files:**
- Modify: `tests/integration/verifier_identifiants.php`, `tests/integration/exporter_import.php`, `tests/integration/references/theme-unit-test.tsv` (régénérée), `readme.md`, `docs/superpowers/specs/2026-10-09-wp2spip-etiquettes-design.md` (statut), `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 2.2, § 6 ligne 5), `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 15)

**Interfaces:**
- Consumes : `importer_mots` (tâche 1), `tests/integration/valider.sh` (sous-projet 7).
- Produces : lignes d'export `mot <id WordPress> <titre> <descriptif>` et `mot_article <wp…> article#<wp…>`.

- [ ] **Step 1 : vérificateur**

Dans `tests/integration/verifier_identifiants.php`, ajouter à la liste du commentaire d'en-tête ` * - étiquettes : un mot-clé du groupe « Étiquettes » par étiquette, liés aux mêmes contenus.` (après la ligne sur la hiérarchie des pages), et insérer avant la ligne `$url_wordpress = sql_getfetsel('option_value', wp2spip_table('options'), …` :

```php
// Étiquettes : chacune a son mot-clé, de même identifiant, dans le groupe « Étiquettes » ; aucun autre mot n'a
// d'id_wordpress ; les liens mot–article sont exactement les relations post_tag des contenus importés
$etiquettes = array_map('intval', array_column(sql_allfetsel('term_id', wp2spip_table('term_taxonomy'), 'taxonomy = "post_tag"', '', '', '', '', $base), 'term_id'));
$id_groupe = intval($GLOBALS['meta']['wp2spip_groupe_etiquettes'] ?? 0);
$mots = array_map('intval', array_column(sql_allfetsel('id_mot', 'spip_mots', array('id_wordpress > 0', 'id_mot = id_wordpress', 'id_groupe = ' . $id_groupe)), 'id_mot'));
sort($etiquettes);
sort($mots);
if ($etiquettes !== $mots or sql_countsel('spip_mots', 'id_wordpress > 0') != count($mots)) {
	$echecs[] = 'mots-clés : ' . count($mots) . ' étiquettes importées dans le groupe ' . $id_groupe . ' pour ' . count($etiquettes) . ' étiquettes Wordpress, ou des mots hors du groupe';
}
$relations = array_map(
	fn($relation) => $relation['term_id'] . '-' . $relation['object_id'],
	sql_allfetsel(
		'tax.term_id, rel.object_id',
		wp2spip_table('term_relationships') . ' as rel join ' . wp2spip_table('term_taxonomy') . ' as tax on tax.term_taxonomy_id = rel.term_taxonomy_id join ' . wp2spip_table('posts') . ' as post on post.ID = rel.object_id',
		array('tax.taxonomy = "post_tag"', sql_in('post.post_type', array('post', 'page'))),
		'', '', '', '', $base
	)
);
$liens = array_map(fn($lien) => $lien['id_mot'] . '-' . $lien['id_objet'], sql_allfetsel('id_mot, id_objet', 'spip_mots_liens', 'objet = "article"'));
sort($relations);
sort($liens);
if ($relations !== $liens) {
	$echecs[] = 'mots-clés : ' . count($liens) . ' liens mot–article pour ' . count($relations) . ' relations post_tag ('
		. join(', ', array_slice(array_merge(array_diff($relations, $liens), array_diff($liens, $relations)), 0, 10)) . ')';
}
if ($etiquettes and ($GLOBALS['meta']['articles_mots'] ?? '') !== 'oui') {
	$echecs[] = 'mots-clés : articles_mots n’est pas activé';
}
echo count($etiquettes) . ' étiquettes et ' . count($relations) . " liens vérifiés\n";
```

- [ ] **Step 2 : export**

Dans `tests/integration/exporter_import.php`, avant `if (test_plugin_actif('accesrestreint')) {` :

```php
// Étiquettes : mots-clés et leurs liens aux articles
$mots = $correspondances('spip_mots', 'id_mot');
foreach (sql_allfetsel('*', 'spip_mots', 'id_wordpress > 0') as $m) {
	$lignes[] = array('mot', $m['id_wordpress'], $m['titre'], $normaliser($m['descriptif']));
}
foreach (sql_allfetsel('*', 'spip_mots_liens', 'objet = "article"') as $l) {
	$lignes[] = array('mot_article', $wp($mots, $l['id_mot']), 'article#' . $wp($articles, $l['id_objet']));
}
```

- [ ] **Step 3 : WordPress 6.9, référence**

Run: `tests/integration/valider.sh "$WP6"`
Expected: `ECHEC : export différent de la référence (302 lignes, …)` ; le diff (`ecarts.diff` du dossier indiqué) ne contient que des lignes ajoutées : `grep '^[<>]' <ecarts.diff> | cut -f1 | sort | uniq -c` → `114 > mot`, `188 > mot_article`. Vérificateur à OK (`… 114 étiquettes et 188 liens vérifiés`).
Puis : `tests/integration/valider.sh "$WP6" --mettre-a-jour` et `tests/integration/valider.sh "$WP7"` → `OK`. Contrôles sur la référence : 114 lignes `mot`, dont deux de même titre (`awk -F'\t' '$1 == "mot" {print $3}' tests/integration/references/theme-unit-test.tsv | sort | uniq -d` non vide), les 50 étiquettes sans contenu présentes (`comm -23` des identifiants `mot` et des étiquettes des lignes `mot_article` : 50), liens du contenu programmé et du brouillon présents.

- [ ] **Step 4 : relance, échecs, site réel**

1. **Relance**, sur le SPIP de test du 6.9 (`remise_a_zero.sh "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"`, import complet) : `wordpress:importer "$WP6" -t importer_mots` → code 0, `0 étiquettes importées … 114 déjà présentes ; 0 liens avec les articles.`
2. **Échecs**, sur ce même SPIP : un mot d'un autre groupe à l'identifiant d'une étiquette (`php:eval` : groupe « Autre », `sql_updateq("spip_mots", array("id_groupe" => <autre>), "id_mot = 70")`) → `-t importer_mots` code 1, `… hors du groupe « Étiquettes » : 70.` ; rétablir ; un article étiqueté supprimé (`sql_delete("spip_articles", "id_article = <un contenu étiqueté>")`) → code 1, couple `étiquette → contenu` affiché. Remettre le SPIP à zéro ensuite.
3. **Site réel**, sur son SPIP de test (`remise_a_zero.sh "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"`, import, vérificateur, export) : 1 mot lié au contenu privé ; l'export ne diffère de `$SAUVEGARDES/export-reel-hierarchie.tsv` que par une ligne `mot` et une ligne `mot_article` (sauvegarder le nouvel export en `$SAUVEGARDES/export-reel-etiquettes.tsv`).
4. Tests : `composer tests-unit`, `composer tests-integration`, `bash tests/preparation/tester_preparer_spip.sh --complet` → OK, OK, `0 échec(s)`.

- [ ] **Step 5 : documentation**

- `readme.md`, avant `## Hiérarchie des pages` :

```markdown
## Étiquettes
Les étiquettes Wordpress deviennent des mots-clés du groupe « Étiquettes » (créé par l'import), avec l'identifiant de l'étiquette, y compris celles qui n'étiquettent aucun contenu. Ils sont liés aux articles et pages étiquetés, quel que soit leur statut, et les mots-clés sont activés sur les articles. Si un contenu étiqueté manque dans SPIP, ou si un mot venant d'une étiquette est hors du groupe, le traitement `importer_mots` échoue et la commande retourne le code de sortie 1.
```

- Spec du sous-projet : `Statut : réalisé.` ; spec d'ensemble : § 2.2, liste des traitements avec `importer_mots` entre `importer_hierarchie_pages` et `importer_acces` (si elle ne l'a pas déjà), § 6 ligne 5 `| 5 | Étiquettes — **réalisé** | …` ; plan principal, Task 15 : `- [x] Plan : docs/superpowers/plans/2026-10-09-wp2spip-etiquettes.md` et `- [x] Réalisation ; validation : …` avec les résultats constatés.

- [ ] **Step 6 : commit**

```bash
git add tests/integration/verifier_identifiants.php tests/integration/exporter_import.php tests/integration/references readme.md docs
git commit -m "Étiquettes : vérificateur, export et référence ; documentation"
```
