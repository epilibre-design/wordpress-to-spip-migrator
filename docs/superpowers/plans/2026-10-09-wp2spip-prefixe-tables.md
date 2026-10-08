# wp2spip — sous-projet 6 : préfixe des tables — plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Importer un WordPress quel que soit le préfixe de ses tables : préfixe lu dans `wp-config.php` ou donné par `--prefixe`, contrôlé avant tout traitement, et utilisé partout par `wp2spip_table()`.

**Architecture:** `inc/wp2spip.php` fournit `wp2spip_prefixe_tables()` (méta `wp2spip_prefixe_tables`, `wp_` par défaut), `wp2spip_table($nom)`, et les fonctions de lecture et de contrôle du préfixe. La commande détermine le préfixe juste après la lecture de la version, le contrôle (format, neuf tables, SPIP déjà importé depuis un autre préfixe), puis l'écrit dans la méta avant la vérification des plugins requis. Tous les noms `wp_…` du code passent par `wp2spip_table()`. Le script de préparation accepte tout préfixe valide et le transmet par `--prefixe`.

**Tech Stack:** PHP (SPIP 4.4, SPIP-Cli, Symfony Console), PHPUnit 13 (tests du sous-projet 7), bash, MySQL.

Spec : `docs/superpowers/specs/2026-10-09-wp2spip-prefixe-tables-design.md`.

## Global Constraints

- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`) ; le message s'arrête après le corps.
- Ne jamais nommer le site WordPress réel (nom, domaine, base, chemins, titres) dans les fichiers versionnés ni les commits.
- `tests/integration/environnement.sh` (accès locaux) n'est jamais versionné ni copié dans un SPIP ; le SPIP-Cli du projet n'est pas modifié.
- Format du préfixe : `^[A-Za-z0-9_]+$` (règle de WordPress, `wp-admin/setup-config.php`).
- Les neuf tables lues par wp2spip : `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`, `options`, `users`, `usermeta`, `comments`.
- Messages, mot pour mot : `Préfixe des tables : wp_ (wp-config.php).` ; `Préfixe des tables : wpx_ (option --prefixe ; wp-config.php annonce wp_).` ; `Préfixe des tables Wordpress introuvable dans wp-config.php : indiquer --prefixe.` ; `Ce SPIP a été importé depuis les tables wp_ ; pour importer depuis wpx_, remettre le SPIP à zéro.`
- Décision prise à la réalisation : la détermination et les contrôles du préfixe ont lieu avant le retour de `--info`, qui affiche le préfixe (lecture seule) ; la méta n'est écrite qu'au-delà de `--info`. Les tests de la commande l'utilisent pour ne lancer aucun traitement.
- Écart à la spec (§ 5, cas « Option » et « Préfixe introuvable ») : ces cas sont couverts par les tests d'intégration sur la base SQLite `wpx_` ; la copie MySQL `wpx_` sert à l'import complet.

Variables des commandes : `source tests/integration/environnement.sh` (non versionné) depuis la racine du dépôt. Tests : `composer tests-unit`, `composer tests-integration` (SPIP de test déjà installé par `composer install-spip-test`).

---

### Task 1 : fonctions du préfixe

**Files:**
- Modify: `inc/wp2spip.php` (fonctions ajoutées à la fin), `tests/unit/Wp2spipTest.php`

**Interfaces:**
- Produces : `wp2spip_tables_wordpress(): array` (neuf noms sans préfixe) ; `wp2spip_prefixe_tables(): string` (`$GLOBALS['meta']['wp2spip_prefixe_tables'] ?? 'wp_'`) ; `wp2spip_table(string $nom): string` ; `wp2spip_prefixe_valide(string $prefixe): bool` ; `wp2spip_prefixe_wp_config(string $source): ?string` (affectation littérale unique de `$table_prefix`, sinon `null`) ; `wp2spip_tables_wordpress_absentes(string $base, string $prefixe): array` (noms complets absents, par `sql_showtable(…, true, $base)`).

- [ ] **Step 1 : tests unitaires, en échec**

Ajouter à la classe `Wp2spipTest` (`tests/unit/Wp2spipTest.php`), avant l'accolade fermante de la classe :

```php
	#[DataProvider('wpConfigs')]
	public function testPrefixeWpConfig(?string $attendu, string $source): void
	{
		$this->assertSame($attendu, wp2spip_prefixe_wp_config($source));
	}

	public static function wpConfigs(): array
	{
		return array(
			'guillemets simples' => array('wp_', "<?php\n\$table_prefix = 'wp_';\n"),
			'guillemets doubles, espaces' => array('wpx_', "<?php\n\$table_prefix  =  \"wpx_\" ;\n"),
			'commentaire ignoré' => array('wpx_', "<?php\n// \$table_prefix = 'autre_';\n/* \$table_prefix = 'x_'; */\n\$table_prefix = 'wpx_';\n"),
			'absent' => array(null, "<?php\ndefine('DB_NAME', 'base');\n"),
			'deux affectations' => array(null, "<?php\nif (getenv('A')) { \$table_prefix = 'a_'; } else { \$table_prefix = 'b_'; }\n"),
			'valeur calculée' => array(null, "<?php\n\$table_prefix = getenv('PREFIXE');\n"),
			'concaténation' => array(null, "<?php\n\$table_prefix = 'wp_' . 'x';\n"),
			'comparaison seule' => array(null, "<?php\nif (\$table_prefix == 'wp_') {}\n"),
		);
	}

	#[DataProvider('prefixes')]
	public function testPrefixeValide(bool $attendu, string $prefixe): void
	{
		$this->assertSame($attendu, wp2spip_prefixe_valide($prefixe));
	}

	public static function prefixes(): array
	{
		return array(
			'wp_' => array(true, 'wp_'),
			'majuscules et chiffres' => array(true, 'WP2x_'),
			'vide' => array(false, ''),
			'point-virgule' => array(false, 'wp_;x'),
			'tiret' => array(false, 'wp-x_'),
			'espace' => array(false, 'wp x'),
		);
	}

	public function testTableSelonLaMeta(): void
	{
		unset($GLOBALS['meta']['wp2spip_prefixe_tables']);
		$this->assertSame('wp_posts', wp2spip_table('posts'));
		$GLOBALS['meta']['wp2spip_prefixe_tables'] = 'wpx_';
		$this->assertSame('wpx_', wp2spip_prefixe_tables());
		$this->assertSame('wpx_postmeta', wp2spip_table('postmeta'));
		unset($GLOBALS['meta']['wp2spip_prefixe_tables']);
	}
```

Run: `composer tests-unit`
Expected: erreurs `Call to undefined function wp2spip_prefixe_wp_config()` (et `wp2spip_prefixe_valide`, `wp2spip_table`).

- [ ] **Step 2 : fonctions**

Ajouter à la fin de `inc/wp2spip.php` :

```php

/**
 * Tables WordPress lues par wp2spip, sans leur préfixe
 *
 * @return array
 */
function wp2spip_tables_wordpress() {
	return array('posts', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships', 'options', 'users', 'usermeta', 'comments');
}

/**
 * Préfixe des tables WordPress de l'import : celui noté par la commande, wp_ pour un SPIP qui n'a pas encore importé
 *
 * @return string
 */
function wp2spip_prefixe_tables() {
	return $GLOBALS['meta']['wp2spip_prefixe_tables'] ?? 'wp_';
}

/**
 * Nom complet d'une table WordPress
 *
 * @param string $nom nom sans préfixe : posts, postmeta…
 * @return string
 */
function wp2spip_table($nom) {
	return wp2spip_prefixe_tables() . $nom;
}

/**
 * Préfixe valide : la règle de WordPress (wp-admin/setup-config.php), qui permet de le placer dans les requêtes
 *
 * @param string $prefixe
 * @return bool
 */
function wp2spip_prefixe_valide($prefixe) {
	return (bool) preg_match('/^[A-Za-z0-9_]+$/', (string) $prefixe);
}

/**
 * Préfixe des tables déclaré dans le code d'un wp-config.php, lu sans l'exécuter
 *
 * L'affectation de $table_prefix doit y figurer exactement une fois, avec une valeur littérale : sinon
 * (absente, conditionnelle, calculée) le préfixe n'est pas supposé.
 *
 * @param string $source contenu de wp-config.php
 * @return string|null préfixe, ou null s'il est introuvable
 */
function wp2spip_prefixe_wp_config($source) {
	$jetons = array_values(array_filter(
		token_get_all((string) $source),
		fn($jeton) => !is_array($jeton) or !in_array($jeton[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))
	));
	$prefixes = array();
	$affectations = 0;
	foreach ($jetons as $i => $jeton) {
		if (!is_array($jeton) or $jeton[0] !== T_VARIABLE or $jeton[1] !== '$table_prefix' or ($jetons[$i + 1] ?? null) !== '=') {
			continue;
		}
		$affectations++;
		$valeur = $jetons[$i + 2] ?? null;
		if (is_array($valeur) and $valeur[0] === T_CONSTANT_ENCAPSED_STRING and ($jetons[$i + 3] ?? null) === ';') {
			$prefixes[] = ($valeur[1][0] === "'")
				? strtr(substr($valeur[1], 1, -1), array('\\\\' => '\\', "\\'" => "'"))
				: stripcslashes(substr($valeur[1], 1, -1));
		}
	}
	return ($affectations === 1 and count($prefixes) === 1) ? $prefixes[0] : null;
}

/**
 * Tables lues par wp2spip absentes de la base WordPress
 *
 * @param string $base identifiant de la base WordPress dans SPIP
 * @param string $prefixe
 * @return array noms complets des tables absentes
 */
function wp2spip_tables_wordpress_absentes($base, $prefixe) {
	$absentes = array();
	foreach (wp2spip_tables_wordpress() as $nom) {
		if (!sql_showtable($prefixe . $nom, true, $base)) {
			$absentes[] = $prefixe . $nom;
		}
	}
	return $absentes;
}
```

Run: `composer tests-unit`
Expected: `OK (70 tests, 72 assertions)`.

- [ ] **Step 3 : commit**

```bash
git add inc/wp2spip.php tests/unit/Wp2spipTest.php
git commit -m "Préfixe des tables : lecture dans wp-config.php, format, noms des tables (wp2spip_table())"
```

### Task 2 : noms des tables par `wp2spip_table()`

**Files:**
- Modify: `wp2spip/importer_acces.php`, `importer_articles.php`, `importer_auteurs.php`, `importer_commentaires.php`, `importer_documents.php`, `importer_hierarchie_pages.php`, `importer_metas.php`, `importer_polyhierarchie.php`, `importer_rubriques.php` ; `inc/wp2spip_blocs.php`, `inc/wp2spip_plugins.php` ; `tests/integration/verifier_identifiants.php`, `tests/integration/BlocsTest.php`

**Interfaces:**
- Consumes : `wp2spip_table()`, `wp2spip_prefixe_tables()` (tâche 1).
- Produces : aucun nom de table WordPress écrit en dur dans le code ; chaque fichier qui lit les tables inclut `inc/wp2spip`.

- [ ] **Step 1 : remplacements**

Depuis la racine du dépôt :

```bash
python3 - <<'EOF'
import re, glob
tables = 'posts|postmeta|terms|term_taxonomy|term_relationships|options|users|usermeta|comments'
fichiers = glob.glob('wp2spip/*.php') + ['inc/wp2spip_blocs.php', 'inc/wp2spip_plugins.php', 'tests/integration/verifier_identifiants.php', 'tests/integration/BlocsTest.php']
for f in fichiers:
    s = open(f).read(); avant = s
    s = re.sub(r"'wp_(%s)'" % tables, r"wp2spip_table('\1')", s)
    s = s.replace("'wp_term_taxonomy as tax left join wp_terms as term on tax.term_id=term.term_id'",
        "wp2spip_table('term_taxonomy') . ' as tax left join ' . wp2spip_table('terms') . ' as term on tax.term_id=term.term_id'")
    s = s.replace("'wp_term_relationships as rel join wp_term_taxonomy as tax on tax.term_taxonomy_id=rel.term_taxonomy_id'",
        "wp2spip_table('term_relationships') . ' as rel join ' . wp2spip_table('term_taxonomy') . ' as tax on tax.term_taxonomy_id=rel.term_taxonomy_id'")
    s = s.replace("'wp_term_taxonomy as tax left join wp_term_relationships as rel on tax.term_taxonomy_id=rel.term_taxonomy_id'",
        "wp2spip_table('term_taxonomy') . ' as tax left join ' . wp2spip_table('term_relationships') . ' as rel on tax.term_taxonomy_id=rel.term_taxonomy_id'")
    s = s.replace("""'post_parent IN (SELECT ID FROM wp_posts WHERE post_type = "page")'""",
        """'post_parent IN (SELECT ID FROM ' . wp2spip_table('posts') . ' WHERE post_type = "page")'""")
    s = s.replace("$metas['wp_capabilities']", "$metas[wp2spip_prefixe_tables() . 'capabilities']")
    if s != avant:
        open(f, 'w').write(s); print(f)
EOF
```

Expected: les 13 fichiers listés ci-dessus.

- [ ] **Step 2 : inclusions**

```bash
python3 - <<'EOF'
garde = "if (!defined('_ECRIRE_INC_VERSION')) {\n\treturn;\n}\n"
for f in ['wp2spip/importer_acces.php', 'wp2spip/importer_articles.php', 'wp2spip/importer_auteurs.php', 'wp2spip/importer_commentaires.php', 'wp2spip/importer_documents.php', 'wp2spip/importer_hierarchie_pages.php', 'wp2spip/importer_metas.php', 'wp2spip/importer_polyhierarchie.php', 'wp2spip/importer_rubriques.php', 'inc/wp2spip_plugins.php']:
    s = open(f).read()
    assert garde in s, f
    s = s.replace(garde, garde + "\n// Noms des tables WordPress (wp2spip_table())\ninclude_spip('inc/wp2spip');\n", 1)
    open(f, 'w').write(s)
f = 'inc/wp2spip_blocs.php'; s = open(f).read()
s = s.replace("include_spip('inc/filtres');\n", "include_spip('inc/filtres');\ninclude_spip('inc/wp2spip');\n", 1); open(f, 'w').write(s)
f = 'tests/integration/verifier_identifiants.php'; s = open(f).read()
s = s.replace("include_spip('base/objets');\n", "include_spip('base/objets');\ninclude_spip('inc/wp2spip');\n", 1); open(f, 'w').write(s)
EOF
```

- [ ] **Step 3 : contrôles**

Run :

```bash
grep -rn "wp_\(posts\|postmeta\|terms\|term_taxonomy\|term_relationships\|options\|users\|usermeta\|comments\|capabilities\)" wp2spip inc tests/integration/*.php | grep -v '\$wp_' | grep -v "^\S*:\s*\(\*\|//\)"
for f in wp2spip/*.php inc/*.php tests/integration/*.php; do php -l "$f" >/dev/null || echo "ERREUR $f"; done
composer tests-unit && composer tests-integration
```

Expected: aucune ligne du `grep` (les commentaires et les variables `$wp_…` restent), aucune `ERREUR`, `OK (70 tests, …)` et `OK (48 tests, …)`.

- [ ] **Step 4 : non-régression sur un SPIP de test**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6" && "$SPIP_CLI" wordpress:importer "$WP6" --no-ansi > "$SAUVEGARDES/import-wp6-prefixe.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp6-prefixe.tsv"
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6-prefixe.tsv" && echo identique; cd -
```

Expected: `code 0`, `identique` (la commande n'ayant pas encore `--prefixe`, la méta est absente et `wp_` s'applique).

- [ ] **Step 5 : commit**

```bash
git add wp2spip inc tests/integration/verifier_identifiants.php tests/integration/BlocsTest.php
git commit -m "Noms des tables WordPress par wp2spip_table(), y compris jointures, sous-requête et méta des rôles"
```

### Task 3 : préfixe de la commande

**Files:**
- Modify: `spip-cli/WordpressImporter.php`
- Create: `tests/integration/PrefixeTablesTest.php`

**Interfaces:**
- Consumes : fonctions de la tâche 1 ; `WordpressTestCase::construireBase($connexion, $prefixe)`, `retirerBase()`, `BASE` (sous-projet 7).
- Produces : option `--prefixe <préfixe>` ; propriété publique `WordpressImporter::$prefixe` ; méthode `determiner_prefixe(): ?int` ; méta `wp2spip_prefixe_tables` écrite après `--info`, avant `verifier_plugins()`.

- [ ] **Step 1 : tests, en échec**

`tests/integration/PrefixeTablesTest.php` :

```php
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
```

Run: `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter PrefixeTablesTest`
Expected: échecs (`The "--prefixe" option does not exist.`, préfixe absent de la sortie, code 0 au lieu de 1 pour les tables absentes). `testTraitementsSousLePrefixe` passe déjà (tâches 1 et 2).

- [ ] **Step 2 : la commande**

Dans `spip-cli/WordpressImporter.php` :

1. Après `public $garder_adresse = false;`, ajouter `public $prefixe = 'wp_';`.
2. Avant l'option `garder-adresse` de `configure()`, ajouter :

```php
			->addOption(
				'prefixe',
				null,
				InputOption::VALUE_REQUIRED,
				'Préfixe des tables Wordpress, s’il diffère de celui de wp-config.php ou n’y est pas lisible.'
			)
```

3. Après `$this->wp_version = $wp_version;` dans `execute()`, ajouter :

```php
		
		// Préfixe des tables Wordpress : lu dans wp-config.php ou donné par --prefixe, puis contrôlé
		if (($code = $this->determiner_prefixe()) !== null) {
			return $code;
		}
```

4. Dans le bloc d'informations, après la ligne `'* <comment>Base</comment> : ' . $this->base,`, ajouter `'* <comment>Préfixe des tables</comment> : ' . $this->prefixe,`.
5. Après le retour de `--info` (`return Command::SUCCESS;` du bloc « Si on cherche juste à lire les infos »), ajouter :

```php
		
		// Préfixe contrôlé : noté pour ce SPIP, tous les traitements le lisent (wp2spip_table())
		include_spip('inc/meta');
		ecrire_meta('wp2spip_prefixe_tables', $this->prefixe);
```

6. Avant `protected function appliquer_traitement($traitement): bool {`, ajouter :

```php
	/**
	 * Préfixe des tables Wordpress : option --prefixe, sinon $table_prefix de wp-config.php, puis contrôles
	 *
	 * Format (règle de Wordpress), présence de toutes les tables lues par wp2spip, et cohérence avec un import
	 * déjà commencé dans ce SPIP. Contrôlé avant tout traitement et avant la vérification des plugins requis,
	 * qui lit déjà les tables.
	 *
	 * @return int|null null si le préfixe est utilisable, sinon le code de sortie de la commande
	 */
	protected function determiner_prefixe(): ?int {
		include_spip('inc/wp2spip');
		$fichier_config = $this->dir_wordpress . 'wp-config.php';
		$source = is_readable($fichier_config) ? file_get_contents($fichier_config) : false;
		$prefixe_config = ($source !== false) ? wp2spip_prefixe_wp_config($source) : null;
		
		if (($option = $this->input->getOption('prefixe')) !== null) {
			$this->prefixe = $option;
			$origine = 'option --prefixe' . (($prefixe_config !== null and $prefixe_config !== $option) ? " ; wp-config.php annonce $prefixe_config" : '');
		}
		elseif ($prefixe_config !== null) {
			$this->prefixe = $prefixe_config;
			$origine = 'wp-config.php';
		}
		else {
			$this->output->writeln('<error>Préfixe des tables Wordpress introuvable dans wp-config.php : indiquer --prefixe.</error>');
			return Command::FAILURE;
		}
		
		if (!wp2spip_prefixe_valide($this->prefixe)) {
			$this->output->writeln("<error>Préfixe des tables « {$this->prefixe} » invalide : lettres, chiffres et _ seulement.</error>");
			return Command::FAILURE;
		}
		$this->output->writeln("Préfixe des tables : {$this->prefixe} ($origine).");
		
		if ($absentes = wp2spip_tables_wordpress_absentes($this->base, $this->prefixe)) {
			$this->output->writeln('<error>Tables absentes de la base Wordpress « ' . $this->base . ' » : ' . join(', ', $absentes) . '. Vérifier le préfixe (option --prefixe).</error>');
			return Command::FAILURE;
		}
		
		$prefixe_importe = $GLOBALS['meta']['wp2spip_prefixe_tables'] ?? null;
		if ($prefixe_importe !== null and $prefixe_importe !== $this->prefixe) {
			$this->output->writeln("<error>Ce SPIP a été importé depuis les tables $prefixe_importe ; pour importer depuis {$this->prefixe}, remettre le SPIP à zéro.</error>");
			return Command::FAILURE;
		}
		return null;
	}
```

Run: `composer tests-integration`
Expected: `OK (55 tests, …)`.

- [ ] **Step 3 : commit**

```bash
git add spip-cli/WordpressImporter.php tests/integration/PrefixeTablesTest.php
git commit -m "Commande : préfixe des tables lu dans wp-config.php ou donné par --prefixe, contrôlé avant tout traitement"
```

### Task 4 : préparation, WordPress de test à préfixe `wpx_`, validation et documentation

**Files:**
- Modify: `outils/lire_wp_config.php`, `outils/preparer_spip.sh`, `tests/preparation/tester_preparer_spip.sh`, `tests/integration/valider.sh`, `readme.md`, `docs/superpowers/specs/2026-10-09-wp2spip-prefixe-tables-design.md` (statut), `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 6, ligne 6), `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 16)
- Create: `tests/integration/creer_wordpress_prefixe.sh`

**Interfaces:**
- Consumes : option `--prefixe` (tâche 3), `wp2spip_table()` (tâche 1).
- Produces : `outils/preparer_spip.sh` qui accepte tout préfixe valide et lance `wordpress:importer … --prefixe <préfixe>` ; `tests/integration/creer_wordpress_prefixe.sh` (miroir `$ESSAIS/wp6-wpx`).

- [ ] **Step 1 : lecture de `wp-config.php` par la préparation**

Dans `outils/lire_wp_config.php` : après `$valeurs = array();`, ajouter `$affectations_prefixe = 0;` ; remplacer le bloc `if ( $jeton[0] === T_VARIABLE and $jeton[1] === '$table_prefix' … ) { … }` par :

```php
	if ($jeton[0] === T_VARIABLE and $jeton[1] === '$table_prefix' and ($jetons[$i + 1][1] ?? '') === '=') {
		$affectations_prefixe++;
		if (($jetons[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING and ($jetons[$i + 3][1] ?? '') === ';') {
			$valeurs['table_prefix'] = wp_config_chaine($jetons[$i + 2][1]);
		}
	}
```

et, après la boucle `foreach ($jetons …)`, ajouter :

```php
// Préfixe affecté plusieurs fois (par exemple selon une condition), ou par une valeur calculée : pas de préfixe supposé
if ($affectations_prefixe !== 1) {
	unset($valeurs['table_prefix']);
}
```

- [ ] **Step 2 : préparation**

Dans `outils/preparer_spip.sh` (la variable `prefixe` sert déjà aux boucles sur les plugins : le préfixe des tables est `prefixe_wp`) :

1. Remplacer la ligne `[ "${wp[table_prefix]}" = wp_ ] || erreur "préfixe des tables WordPress « ${wp[table_prefix]} » : seul wp_ est géré pour l'instant"` par :

```bash
prefixe_wp=${wp[table_prefix]}
[[ $prefixe_wp =~ ^[A-Za-z0-9_]+$ ]] || erreur "préfixe des tables WordPress « $prefixe_wp » invalide : lettres, chiffres et _ seulement"
```

2. Dans les deux requêtes `wp_requete "select option_value from wp_options …"`, remplacer `wp_options` par `${prefixe_wp}options`.
3. Remplacer la lecture `siteurl_lue=$(spip_cli php:eval 'echo sql_getfetsel("option_value", "wp_options", …` par :

```bash
siteurl_lue=$(export WP2SPIP_PREFIXE=$prefixe_wp; spip_cli php:eval 'echo sql_getfetsel("option_value", getenv("WP2SPIP_PREFIXE") . "options", "option_name = " . sql_quote("siteurl"), "", "", "", "", "wordpress");') \
```

4. `spip_cli wordpress:importer "$wordpress" || code=$?` devient `spip_cli wordpress:importer "$wordpress" --prefixe "$prefixe_wp" || code=$?`.
5. La ligne du bilan sans import devient `printf 'Import : cd %q && %q wordpress:importer %q --prefixe %q\n' "$spip" "$spip_cli_exe" "$wordpress" "$prefixe_wp"`.

Dans `tests/preparation/tester_preparer_spip.sh`, remplacer :

```bash
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_'/\$table_prefix = 'autre_'/"
erreur_attendue "préfixe autre que wp_" "seul wp_ est géré" --wordpress "$ESSAIS/wp-prefixe"
```

par :

```bash
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_'/\$table_prefix = 'wp-x_'/"
erreur_attendue "préfixe invalide" "invalide : lettres, chiffres et _ seulement" --wordpress "$ESSAIS/wp-prefixe"
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_';/if (getenv('X')) { \$table_prefix = 'a_'; } else { \$table_prefix = 'wp_'; }/"
erreur_attendue "préfixe affecté deux fois" "table_prefix introuvable" --wordpress "$ESSAIS/wp-prefixe"
```

Run: `bash -n outils/preparer_spip.sh && bash tests/preparation/tester_preparer_spip.sh`
Expected: `0 échec(s)`.

- [ ] **Step 3 : adresse lue sous le préfixe par `valider.sh`**

Dans `tests/integration/valider.sh`, la lecture de `url_site` devient :

```bash
url_site=$(cd "$spip" && "$spip_cli" --no-ansi php:eval 'include_spip("inc/wp2spip"); echo sql_getfetsel("option_value", wp2spip_table("options"), "option_name = \"siteurl\"", "", "", "", "", "wordpress");')
```

- [ ] **Step 4 : WordPress de test à préfixe `wpx_`**

`tests/integration/creer_wordpress_prefixe.sh` :

```bash
#!/bin/bash
# Copie du WordPress 6.9 de test sous le préfixe de tables wpx_, dans la base jetable des essais
# Usage : creer_wordpress_prefixe.sh
#   Tables wp_ de $BASE_WP6 copiées dans $BASE_PREP_MYSQL sous le nom wpx_… (la base est d'abord vidée de ses
#   tables wp_ et wpx_), clés préfixées renommées comme le fait un changement de préfixe dans WordPress
#   (méta des utilisateurs wp_…, option wp_user_roles), et miroir du dossier du WordPress dans $ESSAIS/wp6-wpx :
#   liens vers ses fichiers, wp-config.php désignant $BASE_PREP_MYSQL avec $table_prefix = 'wpx_'.
#   Les accès du WordPress 6.9 doivent avoir les droits sur $BASE_PREP_MYSQL.
# Variables : tests/integration/environnement.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/environnement.sh"
miroir="$ESSAIS/wp6-wpx"

requete() { # requete <base> <requête>
	mysql $MYSQL_OPTIONS -N -B "$1" -e "$2"
}

# 1. Tables wp_ et wpx_ retirées de la base jetable, puis tables wp_ du WordPress 6.9 copiées et renommées
anciennes=$(requete "$BASE_PREP_MYSQL" "show tables" | grep -E '^wpx?_' | paste -sd, - || true)
[ -z "$anciennes" ] || requete "$BASE_PREP_MYSQL" "drop table $anciennes"
tables=$(requete "$BASE_WP6" "show tables like 'wp\\_%'")
mysqldump $MYSQL_OPTIONS --no-tablespaces "$BASE_WP6" $tables | mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL"
renommages=$(for table in $tables; do printf '%s to wpx_%s,' "$table" "${table#wp_}"; done)
requete "$BASE_PREP_MYSQL" "rename table ${renommages%,}"

# 2. Clés qui portent le préfixe
requete "$BASE_PREP_MYSQL" "update wpx_usermeta set meta_key = concat('wpx_', substring(meta_key, 4)) where meta_key like 'wp\\_%'"
requete "$BASE_PREP_MYSQL" "update wpx_options set option_name = 'wpx_user_roles' where option_name = 'wp_user_roles'"

# 3. Miroir du dossier : DB_NAME et $table_prefix remplacés chacun exactement une fois
rm -rf "$miroir" && mkdir -p "$miroir"
for fichier in "$WP6"/*; do
	[ "$(basename "$fichier")" = wp-config.php ] || ln -s "$fichier" "$miroir/"
done
BASE=$BASE_PREP_MYSQL php -r '
	$source = file_get_contents($argv[1]);
	$remplacements = array(
		"/define\\(\\s*([\x27\"])DB_NAME\\1\\s*,\\s*([\x27\"]).*?\\2\\s*\\)/" => fn($m) => "define( \x27DB_NAME\x27, " . var_export(getenv("BASE"), true) . " )",
		"/\\\$table_prefix\\s*=\\s*([\x27\"]).*?\\1\\s*;/" => fn($m) => "\$table_prefix = \x27wpx_\x27;",
	);
	foreach ($remplacements as $motif => $remplacement) {
		$source = preg_replace_callback($motif, $remplacement, $source, -1, $nombre);
		if ($nombre !== 1) {
			fwrite(STDERR, "$motif : $nombre remplacements au lieu d’un\n");
			exit(1);
		}
	}
	file_put_contents($argv[2], $source);
' "$WP6/wp-config.php" "$miroir/wp-config.php"

echo "WordPress à préfixe wpx_ : $miroir (base $BASE_PREP_MYSQL, $(echo $tables | wc -w) tables)"
```

`chmod +x tests/integration/creer_wordpress_prefixe.sh`, puis :

Run: `tests/integration/creer_wordpress_prefixe.sh && grep -n "DB_NAME\|table_prefix" "$ESSAIS/wp6-wpx/wp-config.php" && mysql $MYSQL_OPTIONS -N "$BASE_PREP_MYSQL" -e "show tables like 'wp\\_%'; select count(*) from wpx_usermeta where meta_key like 'wpx\\_%'; select option_name from wpx_options where option_name like '%user_roles'"`
Expected: `WordPress à préfixe wpx_ : …/wp6-wpx (base …, 12 tables)` ; `DB_NAME` sur la base jetable et `$table_prefix = 'wpx_';` ; aucune table `wp_` ; des clés `wpx_…` ; `wpx_user_roles`.

- [ ] **Step 5 : validation**

1. **Préfixe `wpx_`, depuis un dossier vide** : `tests/integration/valider.sh "$ESSAIS/wp6-wpx"` → `OK : … conforme à la référence` (export identique à celui du WordPress 6.9 ; les auteurs et leurs statuts viennent de `wpx_capabilities`).
2. **Option sur la copie MySQL** : dans le SPIP conservé d'une préparation sans import (`outils/preparer_spip.sh --spip "$ESSAIS/spip-wpx" --wordpress "$ESSAIS/wp6-wpx" --spip-cli "$SPIP_CLI"`), avec un `wp-config.php` du miroir modifié pour annoncer `wp_` (`sed -i "s/^\$table_prefix = 'wpx_';/\$table_prefix = 'wp_';/" "$ESSAIS/wp6-wpx/wp-config.php"`) : `wordpress:importer "$ESSAIS/wp6-wpx" --info` → code 1, les neuf tables `wp_…` listées ; avec `--prefixe wpx_` → code 0, `Préfixe des tables : wpx_ (option --prefixe ; wp-config.php annonce wp_).` Puis relancer `tests/integration/creer_wordpress_prefixe.sh` (miroir rétabli) et supprimer `$ESSAIS/spip-wpx`.
3. **Non-régression** : `composer tests-import` (WordPress 6.9 et 7.1 depuis un dossier vide) → deux `OK` ; SPIP de test du 7.1 (`remise_a_zero.sh "$SPIP_WP7" "$SAUVEGARDES/vierge-wp7-v2.sql.gz" "$BASE_WP7"`, import, export) identique à `$SAUVEGARDES/export-wp7-hierarchie.tsv` ; site réel sur son SPIP de test (`remise_a_zero.sh "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"`, import, export) identique à `$SAUVEGARDES/export-reel-hierarchie.tsv`, vérificateur à OK ; dans chaque journal, `Préfixe des tables : wp_ (wp-config.php).`
4. **SPIP amorcé**, sur le SPIP de test du 7.1 importé : `wordpress:importer "$WP7" --prefixe wpx_ -t importer_metas` → code 1 (tables `wpx_…` absentes de sa base : le contrôle des tables passe avant), puis, la méta forcée à `wpx_` (`php:eval 'include_spip("inc/meta"); ecrire_meta("wp2spip_prefixe_tables", "wpx_");'`), `wordpress:importer "$WP7" -t importer_metas` → code 1, `Ce SPIP a été importé depuis les tables wpx_ ; pour importer depuis wp_, remettre le SPIP à zéro.`, méta inchangée ; remettre la méta à `wp_`.
5. Tests : `composer tests-unit`, `composer tests-integration`, `bash tests/preparation/tester_preparer_spip.sh --complet` → OK, OK, `0 échec(s)`.

- [ ] **Step 6 : documentation**

- `readme.md` : dans l'aide de la commande, ajouter la ligne `      --prefixe=PREFIXE            Préfixe des tables Wordpress, s’il diffère de celui de wp-config.php ou n’y est pas lisible.` après `--info` ; ajouter la section, après « Préparer un SPIP » :

```markdown
## Préfixe des tables
L'import lit le préfixe des tables Wordpress (`$table_prefix`) dans le `wp-config.php` du dossier fourni, sans l'exécuter. S'il y est absent, calculé ou affecté plusieurs fois, l'option `--prefixe` est exigée ; elle remplace aussi un préfixe lu. Avant tout traitement, l'import vérifie le format du préfixe et la présence des tables qu'il lit, puis le note dans le SPIP : un SPIP importé depuis un préfixe refuse d'importer depuis un autre (le remettre à zéro). `outils/preparer_spip.sh` transmet le préfixe lu à l'import.
```

- Spec du sous-projet : `Statut : réalisé.` ; spec d'ensemble, § 6, ligne 6 : `| 6 | Préfixe des tables — **réalisé** | …` ; plan principal, Task 16 : `- [x] Plan : docs/superpowers/plans/2026-10-09-wp2spip-prefixe-tables.md` et `- [x] Réalisation ; validation : …` avec les résultats constatés.

- [ ] **Step 7 : commit**

```bash
git add outils tests/preparation tests/integration/valider.sh tests/integration/creer_wordpress_prefixe.sh readme.md docs
git commit -m "Préparation et validation pour tout préfixe de tables ; WordPress de test à préfixe wpx_ ; documentation"
```
