# wp2spip — sous-projet 7 : tests automatisés — plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tests de wp2spip lançables depuis un clone neuf : PHPUnit (unitaires sans SPIP, intégration dans un SPIP de `vendor/` sur une base WordPress SQLite de test) et import complet du contenu *Theme Unit Test* comparé à une référence versionnée.

**Architecture:** Outillage du skill `spip-testing` : `composer.json` (PHPUnit, SPIP-Cli, `spip/tests`), `phpunit.xml` à deux suites, `scripts/install-spip-test.sh` qui installe SPIP 4.4 en SQLite dans `vendor/spip/spip` avec les plugins requis et wp2spip lié. Les tests d'intégration lisent une base WordPress SQLite construite par `WordpressTestCase` à partir de `tests/integration/data/`. `tests/integration/valider.sh` prépare un SPIP depuis un dossier vide, importe un vrai WordPress et compare l'export normalisé à `tests/integration/references/theme-unit-test.tsv`.

**Tech Stack:** PHP 8.4 (PHPUnit 13 ; wp2spip reste compatible PHP 8.1), Composer, SPIP 4.4, SPIP-Cli (`dev-master` + correctif), `spip/tests` (`SquelettesTestCase`), SQLite, bash.

Spec : `docs/superpowers/specs/2026-10-09-wp2spip-tests-automatises-design.md`.

## Global Constraints

- Messages de commit sans trailer (ni `Co-Authored-By`, ni `Claude-Session`) ; le message s'arrête après le corps.
- Ne jamais nommer le site WordPress réel (nom, domaine, base, chemins, titres) dans les fichiers versionnés ni les commits.
- `tests/integration/environnement.sh` (accès locaux) n'est jamais versionné ni copié dans un SPIP.
- Le SPIP-Cli du projet (`/src/spip-cli`) n'est pas modifié : celui de `vendor/` reçoit `tests/spip-cli.patch`.
- Les tests d'intégration se relancent avec le même résultat : tout ce qu'un test crée dans le SPIP de test est retiré ou rétabli.
- Écart assumé à la spec (§ 5.2) : les fichiers des médias de test sont sous `tests/integration/data/wp-content/uploads/` (le dossier `tests/integration/data/` joue le rôle du dossier WordPress, l'import lit `<dossier>/wp-content/uploads/…` d'après le guid) ; les données des tables sont dans `tests/integration/data/wordpress/<table sans préfixe>.php`, le schéma dans `schema.sql` avec `{prefixe}`, pour servir aussi au sous-projet 6.
- Les étiquettes (prévues au § 5.2 de la spec) sont ajoutées au jeu de test par le sous-projet 5, qui les importe.
- PHPUnit 13 (version du skill) : les tests demandent PHP 8.4 ; `composer.json` déclare `php >= 8.1` pour le plugin.

Variables des commandes : `source tests/integration/environnement.sh` (non versionné) depuis la racine du dépôt.

---

### Task 1 : outillage Composer et PHPUnit, tests unitaires

**Files:**
- Create: `composer.json`, `composer.lock` (produit par `composer update`), `phpunit.xml`, `tests/bootstrap.php`, `tests/unit/Wp2spipTest.php`, `tests/unit/ImporterArticlesTest.php`, `tests/unit/BlocsTest.php`
- Modify: `.gitignore`, `.gitattributes`

**Interfaces:**
- Produces : `composer tests-unit` ; autoload-dev `Wp2spip\Tests\Unit\` → `tests/unit/`, `Wp2spip\Tests\Integration\` → `tests/integration/` ; scripts composer `install-spip-test`, `tests-integration`, `tests-import` (leurs fichiers arrivent aux tâches 2 à 4).

- [ ] **Step 1 : `composer.json`**

```json
{
    "name": "technova69/wp2spip",
    "description": "Import d'un site WordPress dans SPIP (plugin SPIP et commande SPIP-Cli)",
    "type": "spip-plugin",
    "license": "GPL-3.0-or-later",
    "require": {
        "php": ">=8.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^13.0",
        "spip/spip-cli": "dev-master",
        "spip/tests": "dev-master"
    },
    "repositories": [
        {
            "name": "spip",
            "type": "composer",
            "url": "https://get.spip.net/composer"
        },
        {
            "name": "spip-tests",
            "type": "vcs",
            "url": "https://git.spip.net/spip/tests.git"
        }
    ],
    "autoload-dev": {
        "psr-4": {
            "Wp2spip\\Tests\\Unit\\": "tests/unit/",
            "Wp2spip\\Tests\\Integration\\": "tests/integration/"
        }
    },
    "scripts": {
        "install-spip-test": "bash scripts/install-spip-test.sh",
        "tests-unit": "phpunit --testsuite unit",
        "tests-integration": "phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php",
        "tests-import": "bash -c 'source tests/integration/environnement.sh && tests/integration/valider.sh \"$WP6\" && tests/integration/valider.sh \"$WP7\"'"
    },
    "config": {
        "sort-packages": true,
        "process-timeout": 0
    }
}
```

- [ ] **Step 2 : `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
	bootstrap="tests/bootstrap.php"
	cacheDirectory=".phpunit.cache"
	colors="true"
>
	<testsuites>
		<testsuite name="unit">
			<directory suffix="Test.php">tests/unit</directory>
		</testsuite>
		<testsuite name="integration">
			<directory suffix="Test.php">tests/integration</directory>
		</testsuite>
	</testsuites>
</phpunit>
```

- [ ] **Step 3 : `.gitignore` et `.gitattributes`**

Ajouter à `.gitignore` :

```
/vendor/
/.phpunit.cache/
```

Ajouter à `.gitattributes` (archives du plugin sans l'outillage des tests ; `composer.json` reste, il sert à installer le plugin par Composer) :

```
/scripts export-ignore
/phpunit.xml export-ignore
/composer.lock export-ignore
```

- [ ] **Step 4 : installer**

Run: `composer update --no-interaction` (crée `composer.lock`), puis `ls vendor/bin`
Expected: `phpunit` et `spip` présents ; `vendor/spip/spip-cli` et `vendor/spip/tests` installés.

- [ ] **Step 5 : `tests/bootstrap.php`**

```php
<?php
/**
 * Amorce des tests unitaires : fonctions de wp2spip sans SPIP
 *
 * Seules les fonctions SPIP appelées par les fonctions testées sont imitées, avec garde function_exists().
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('_ECRIRE_INC_VERSION')) {
	define('_ECRIRE_INC_VERSION', 'test');
}

if (!function_exists('include_spip')) {
	function include_spip($fichier) {
		return true;
	}
}

// Copie de la fonction de SPIP 4.4 (ecrire/inc/utils.php)
if (!function_exists('tester_url_absolue')) {
	function tester_url_absolue($url) {
		$url = trim($url ?? '');
		if ($url && preg_match(';^([a-z]{3,7}:)?//;Uims', $url, $m)) {
			if (
				isset($m[1])
				&& ($p = strtolower(rtrim($m[1], ':')))
				&& in_array($p, ['file', 'php', 'zlib', 'glob', 'phar', 'ssh2', 'rar', 'ogg', 'expect', 'zip'])
			) {
				return false;
			}
			return true;
		}
		return false;
	}
}
```

- [ ] **Step 6 : tests unitaires**

`tests/unit/Wp2spipTest.php` :

```php
<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip.php';

/**
 * Fonctions de inc/wp2spip.php
 */
final class Wp2spipTest extends TestCase
{
	#[DataProvider('entites')]
	public function testDecoderEntites(string $attendu, string $texte): void
	{
		$this->assertSame($attendu, wp2spip_decoder_entites($texte));
	}

	public static function entites(): array
	{
		return array(
			'entité nommée' => array('Été', '&Eacute;t&eacute;'),
			'entité numérique' => array('é', '&#233;'),
			'entité hexadécimale' => array('é', '&#xe9;'),
			'chevrons et esperluette gardés' => array('&lt;b&gt; &amp; &#60;', '&lt;b&gt; &amp; &#60;'),
			'guillemets' => array('« l’été »', '&laquo; l&rsquo;été &raquo;'),
			'texte sans entité' => array('Texte', 'Texte'),
		);
	}
}
```

`tests/unit/ImporterArticlesTest.php` :

```php
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
```

`tests/unit/BlocsTest.php` :

```php
<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip_blocs.php';

/**
 * Fonctions sans base de inc/wp2spip_blocs.php : analyse des blocs, nettoyage du HTML, types de blocs
 */
final class BlocsTest extends TestCase
{
	public function testAnalyserBlocsImbriques(): void
	{
		$contenu = "<p>Avant</p>\n<!-- wp:columns {\"align\":\"wide\"} --><div class=\"wp-block-columns\"><!-- wp:column --><div>A</div><!-- /wp:column --></div><!-- /wp:columns -->";
		$this->assertSame(
			array('nom' => '', 'attributs' => array(), 'morceaux' => array(
				"<p>Avant</p>\n",
				array('nom' => 'core/columns', 'attributs' => array('align' => 'wide'), 'morceaux' => array(
					'<div class="wp-block-columns">',
					array('nom' => 'core/column', 'attributs' => array(), 'morceaux' => array('<div>A</div>')),
					'</div>',
				)),
			)),
			wp2spip_analyser_blocs($contenu)
		);
	}

	public function testAnalyserBlocsAutoFermantsEtEspaceDeNom(): void
	{
		$racine = wp2spip_analyser_blocs('<!-- wp:latest-posts {"postsToShow":3} /--><!-- wp:mon-extension/encart --><p>E</p><!-- /wp:mon-extension/encart -->');
		$this->assertSame(
			array(
				array('nom' => 'core/latest-posts', 'attributs' => array('postsToShow' => 3), 'morceaux' => array()),
				array('nom' => 'mon-extension/encart', 'attributs' => array(), 'morceaux' => array('<p>E</p>')),
			),
			$racine['morceaux']
		);
	}

	public function testAnalyserBlocsAttributsAvecAccolades(): void
	{
		$racine = wp2spip_analyser_blocs('<!-- wp:paragraph {"style":{"color":{"text":"#000"}},"content":"a } b"} --><p>T</p><!-- /wp:paragraph -->');
		$this->assertSame(array('style' => array('color' => array('text' => '#000')), 'content' => 'a } b'), $racine['morceaux'][0]['attributs']);
	}

	public function testAnalyserBlocsFermantEnTrop(): void
	{
		$racine = wp2spip_analyser_blocs('<p>A</p><!-- /wp:paragraph --><p>B</p>');
		$this->assertSame(array('<p>A</p>', '<p>B</p>'), $racine['morceaux']);
	}

	#[DataProvider('balises')]
	public function testNettoyerBalises(string $attendu, string $html): void
	{
		$this->assertSame($attendu, wp2spip_nettoyer_balises($html));
	}

	public static function balises(): array
	{
		return array(
			'classes wp-block gardées, style retiré' => array('<div class="wp-block-group">', '<div class="wp-block-group is-layout-flow has-background" style="color:red">'),
			'lien : href gardé' => array('<a href="/x">', '<a class="lien" href="/x" target="_blank">'),
			'image auto-fermante' => array('<img src="a.jpg" alt="A" />', '<img src="a.jpg" alt="A" width="10"/>'),
			'cellule fusionnée' => array('<td colspan="2">', '<td colspan="2" style="x">'),
			'majuscules' => array('<p>', '<P CLASS="x">'),
		);
	}

	public function testBaliseNettoyee(): void
	{
		$this->assertSame('<figure class="wp-block-table">', wp2spip_balise_nettoyee('FIGURE', ' class="wp-block-table is-style-stripes" id="t"'));
	}

	#[DataProvider('legendes')]
	public function testBlocLegende(string $attendu, string $html, string $classe): void
	{
		$this->assertSame($attendu, wp2spip_bloc_legende($html, $classe));
	}

	public static function legendes(): array
	{
		$galerie = '<figure><figcaption class="blocks-gallery-item__caption">Image</figcaption><figcaption class="blocks-gallery-caption"> Galerie </figcaption></figure>';
		return array(
			'première légende' => array('Image', $galerie, ''),
			'légende d’une classe' => array('Galerie', $galerie, 'blocks-gallery-caption'),
			'sans légende' => array('', '<figure><img src="a.jpg"></figure>', ''),
		);
	}

	#[DataProvider('types')]
	public function testConversionBloc(string $attendu, string $type): void
	{
		$this->assertSame($attendu, wp2spip_conversion_bloc($type));
	}

	public static function types(): array
	{
		return array(
			'image' => array('wp2spip_bloc_image', 'image'),
			'galerie' => array('wp2spip_bloc_gallery', 'gallery'),
			'colonnes' => array('wp2spip_bloc_structure', 'columns'),
			'paragraphe' => array('wp2spip_bloc_laisser', 'paragraph'),
			'espaceur' => array('wp2spip_bloc_vide', 'spacer'),
			'contenu embarqué' => array('wp2spip_bloc_embed', 'embed'),
			'dynamique listé' => array('wp2spip_bloc_retirer', 'latest-posts'),
			'dynamique par préfixe' => array('wp2spip_bloc_retirer', 'post-title'),
			'inconnu' => array('', 'mon-bloc'),
		);
	}

	#[DataProvider('dynamiques')]
	public function testBlocDynamique(bool $attendu, string $type): void
	{
		$this->assertSame($attendu, wp2spip_bloc_dynamique($type));
	}

	public static function dynamiques(): array
	{
		return array(
			'query' => array(true, 'query'),
			'comment-template' => array(true, 'comment-template'),
			'site-title' => array(true, 'site-title'),
			'paragraph' => array(false, 'paragraph'),
			'block (réutilisable)' => array(false, 'block'),
		);
	}
}
```

- [ ] **Step 7 : lancer**

Run: `composer tests-unit`
Expected: `OK (55 tests, 55 assertions)`.

- [ ] **Step 8 : sabotage**

Dans `inc/wp2spip_blocs.php`, `wp2spip_conversion_bloc()`, remplacer `'spacer' => 'wp2spip_bloc_vide'` par `'spacer' => 'wp2spip_bloc_laisser'`.
Run: `composer tests-unit`
Expected: 1 échec, `BlocsTest::testConversionBloc with data set "espaceur"`. Annuler (`git checkout inc/wp2spip_blocs.php`) ; `composer tests-unit` de nouveau à OK.

- [ ] **Step 9 : commit**

```bash
git add composer.json composer.lock phpunit.xml .gitignore .gitattributes tests/bootstrap.php tests/unit
git commit -m "Tests unitaires PHPUnit : outillage Composer (spip-testing) et fonctions sans base"
```

### Task 2 : SPIP de test dans `vendor/`

**Files:**
- Create: `scripts/install-spip-test.sh`, `tests/spip-cli.patch`, `tests/bootstrap_integration.php`, `tests/integration/EnvironnementTest.php`
- Modify: `outils/preparer_spip.sh` (copie de wp2spip sans l'outillage des tests), `tests/preparation/tester_preparer_spip.sh` (contrôle de la copie)

**Interfaces:**
- Consumes : `composer.json` (tâche 1), `wp2spip_tables_manquantes()` (`inc/wp2spip_plugins.php`).
- Produces : `vendor/spip/spip` installé (SQLite, admin au mot de passe aléatoire), plugins `sale pages polyhier albums a2a accesrestreint wp2spip` actifs, `plugins/wp2spip` lien vers la racine du dépôt ; `tests/bootstrap_integration.php` qui charge ce SPIP ; `composer tests-integration`.

- [ ] **Step 1 : correctif de SPIP-Cli**

`tests/spip-cli.patch` (les deux commits du correctif de `plugins:svp:telecharger`, appliqués avec `patch -p1` depuis `vendor/spip/spip-cli`) :

```diff
diff --git a/src/Command/PluginsSvpTelecharger.php b/src/Command/PluginsSvpTelecharger.php
index 8e74cee..4bac939 100644
--- a/src/Command/PluginsSvpTelecharger.php
+++ b/src/Command/PluginsSvpTelecharger.php
@@ -20,12 +20,15 @@ class PluginsSvpTelecharger extends PluginsActiver
 
         foreach ($prefixes as $prefix) {
             $this->io->comment("Plugin en cours d'installation : " . $prefix);
-            $infos = $decideur->infos_courtes('UPPER(pl.prefixe) = LOWER("' . strtoupper($prefix) . '")');
+            $infos = $decideur->infos_courtes('UPPER(pl.prefixe) = UPPER("' . $prefix . '")');
             if (empty($infos['i'])) {
                 $this->io->error('Le plugin ' . $prefix . " n'est pas référencé");
                 continue;
             }
-            $a_installer[key($infos['i'])] = 'geton';
+            // $a_installer doit repartir de zéro à chaque préfixe : sinon il accumule
+            // les plugins des tours précédents, verifier_dependances() replanifie leur
+            // installation déjà faite, et l'actionneur échoue sur « Impossible de déballer ».
+            $a_installer = [key($infos['i']) => 'geton'];
             $decideur->erreur_sur_maj_introuvable = false;
             $res = $decideur->verifier_dependances($a_installer);
 
@@ -52,9 +55,33 @@ class PluginsSvpTelecharger extends PluginsActiver
             $actionneur->verrouiller();
             $actionneur->sauver_actions();
 
+            // Le type d'autorisation est normalisé deux fois sur ce chemin :
+            // autoriser() normalise « _plugins » en « plugins », puis passe ce type
+            // déjà normalisé à autoriser_exception() qui le normalise une seconde fois
+            // en « plugin » (singulier). La vérification de l'exception porte donc sur
+            // « plugin » alors que le garde-fou préalable porte sur « plugins ».
+            // Les deux formes sont nécessaires pour que l'exception soit réellement prise
+            // en compte par action/teleporter.php.
             autoriser_exception('ajouter', '_plugins', '*');
+            autoriser_exception('ajouter', '_plugin', '*');
+
+            // one_action() renvoie la description de l'action *avant* son exécution :
+            // c'est la dernière entrée de $actionneur->done qui porte le résultat, et les
+            // messages d'erreur sont empilés dans $actionneur->err.
+            $nb_erreurs = count($actionneur->err);
             while ($res = $actionneur->one_action()) {
-                $this->io->comment($res['n'] . ' action réalisée : ' . $res['todo']);
+                $derniere_action = $actionneur->done ? $actionneur->done[array_key_last($actionneur->done)] : [];
+
+                if (!empty($derniere_action['done'])) {
+                    $this->io->comment($res['n'] . ' action réalisée : ' . $res['todo']);
+                    continue;
+                }
+
+                $this->io->error($res['n'] . ' action en échec : ' . $res['todo']);
+                foreach (array_slice($actionneur->err, $nb_erreurs) as $erreur) {
+                    $this->io->error($erreur);
+                }
+                $nb_erreurs = count($actionneur->err);
             }
 
             $actionneur->deverrouiller();
```

Run: `patch -d vendor/spip/spip-cli -p1 --dry-run < tests/spip-cli.patch`
Expected: `checking file src/Command/PluginsSvpTelecharger.php`, sans rejet.

- [ ] **Step 2 : `scripts/install-spip-test.sh`**

```bash
#!/bin/bash
# Installe le SPIP des tests d'intégration dans vendor/spip/spip : SPIP 4.4 en SQLite, plugins requis
# par wp2spip et par ses tests, wp2spip lié depuis la racine du dépôt. Les étapes déjà faites sont sautées.
set -euo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
spip_racine="$racine/vendor/spip/spip"
spip_cli="$racine/vendor/bin/spip"
depot=https://plugins.spip.net/depots/principal.xml
# Dépendances de wp2spip (sale, pages, polyhier), puis plugins requis par les contenus des tests
plugins="sale pages polyhier albums a2a accesrestreint"

erreur() {
	echo "ERREUR : $*" >&2
	exit 1
}

spip() {
	(cd "$spip_racine" && "$spip_cli" --no-ansi "$@")
}

plugin_actif() {
	spip plugins:lister --short --raw --no-dist | grep -qx "[[:space:]]*$1"
}

[ -x "$spip_cli" ] || erreur "SPIP-Cli absent de vendor/bin : lancer composer install"

# 1. SPIP 4.4, préparé et installé en SQLite
if [ ! -f "$spip_racine/ecrire/inc_version.php" ]; then
	mkdir -p "$(dirname "$spip_racine")"
	(cd "$(dirname "$spip_racine")" && "$spip_cli" --no-ansi core:telecharger spip -R 4.4 -d "$spip_racine")
fi
spip core:preparer
mkdir -p "$spip_racine/plugins/auto"
if [ ! -f "$spip_racine/config/connect.php" ]; then
	spip core:installer --db-server sqlite3 --db-database spip --db-prefix spip \
		--admin-login admin --admin-email admin@example.test \
		"--admin-pass=$(php -r 'echo bin2hex(random_bytes(12));')" --adresse-site http://localhost
fi
[ -f "$spip_racine/config/connect.php" ] || erreur "SPIP non installé (config/connect.php absent)"

# 2. Correctif de plugins:svp:telecharger (sélection du plugin, autorisation, erreurs, liste remise à zéro)
fichier_svp="$racine/vendor/spip/spip-cli/src/Command/PluginsSvpTelecharger.php"
if ! grep -q 'UPPER(pl.prefixe) = UPPER' "$fichier_svp"; then
	patch -d "$racine/vendor/spip/spip-cli" -p1 <"$racine/tests/spip-cli.patch" || erreur "correctif de SPIP-Cli non appliqué"
fi

# 3. Plugins, un appel de plugins:svp:telecharger par plugin ; la méta de schéma notée par SVP sans les tables est effacée
if ! spip php:eval 'echo sql_countsel("spip_depots");' | grep -qv '^0$'; then
	spip plugins:svp:depoter "$depot"
fi
for prefixe in $plugins; do
	plugin_actif "$prefixe" && continue
	spip plugins:svp:telecharger "$prefixe" -y
	grep -rqs --include=paquet.xml "prefix=\"$prefixe\"" "$spip_racine/plugins/auto" \
		|| erreur "plugin $prefixe absent de plugins/auto après plugins:svp:telecharger"
	(export PREFIXE=$prefixe; spip php:eval 'include_spip("inc/meta"); effacer_meta(getenv("PREFIXE") . "_base_version");')
done

# 4. wp2spip, lié depuis la racine du dépôt
[ -e "$spip_racine/plugins/wp2spip" ] || ln -s "$racine" "$spip_racine/plugins/wp2spip"
spip plugins:activer $plugins wp2spip -y
spip plugins:maj:bdd

# 5. Contrôles
for prefixe in $plugins wp2spip; do
	plugin_actif "$prefixe" || erreur "plugin $prefixe inactif"
done
manquantes=$(spip php:eval 'include_spip("inc/wp2spip_plugins"); echo join(", ", wp2spip_tables_manquantes());')
[ -z "$manquantes" ] || erreur "tables ou champs absents de la base : $manquantes"
echo "SPIP de test prêt : $spip_racine"
```

`chmod +x scripts/install-spip-test.sh`.

- [ ] **Step 3 : `tests/bootstrap_integration.php`**

```php
<?php
/**
 * Amorce des tests d'intégration : le SPIP installé dans vendor/spip/spip par scripts/install-spip-test.sh
 */
declare(strict_types=1);

$racine_spip = dirname(__DIR__) . '/vendor/spip/spip';
if (!is_file($racine_spip . '/config/connect.php')) {
	fwrite(STDERR, "SPIP de test absent : lancer composer install-spip-test\n");
	exit(1);
}
if (!defined('_SPIP_TEST_INC')) {
	define('_SPIP_TEST_INC', $racine_spip);
}
if (!defined('_SPIP_TEST_CHDIR')) {
	define('_SPIP_TEST_CHDIR', $racine_spip);
}

putenv('APP_ENV=test');
chdir($racine_spip);
require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($racine_spip . '/vendor/autoload.php')) {
	require_once $racine_spip . '/vendor/autoload.php';
}
require_once $racine_spip . '/ecrire/inc_version.php';

include_spip('inc/plugin');
actualise_plugins_actifs();
// Ne pas charger tests/bootstrap.php : ses imitations de fonctions SPIP entreraient en conflit avec SPIP
```

- [ ] **Step 4 : test de l'environnement, en échec**

`tests/integration/EnvironnementTest.php` :

```php
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
		return array_map(fn($prefixe) => array($prefixe), array('wp2spip', 'sale', 'pages', 'polyhier', 'albums', 'a2a', 'accesrestreint'));
	}

	public function testAucuneTableManquante(): void
	{
		include_spip('inc/wp2spip_plugins');
		$this->assertSame(array(), wp2spip_tables_manquantes());
	}
}
```

Run: `composer tests-integration`
Expected: échec, `SPIP de test absent : lancer composer install-spip-test`.

- [ ] **Step 5 : installer le SPIP de test**

Run: `composer install-spip-test`
Expected: dernière ligne `SPIP de test prêt : …/vendor/spip/spip` (environ une minute, réseau requis). Relancé : mêmes étapes sautées (ni téléchargement de SPIP ni de plugin), même dernière ligne.

- [ ] **Step 6 : lancer**

Run: `composer tests-integration`
Expected: `OK (8 tests, 8 assertions)`.

- [ ] **Step 7 : copie de wp2spip par la préparation**

Dans `outils/preparer_spip.sh`, remplacer :

```bash
	# Ni .git, ni docs, ni tests (tests/integration/environnement.sh contient des accès locaux)
	mkdir "$spip/plugins/wp2spip"
	tar -C "$WP2SPIP_DIR" --exclude=./.git --exclude=./docs --exclude=./tests -cf - . \
```

par :

```bash
	# Ni .git, ni docs, ni tests (tests/integration/environnement.sh contient des accès locaux), ni outillage des tests
	mkdir "$spip/plugins/wp2spip"
	tar -C "$WP2SPIP_DIR" --exclude=./.git --exclude=./docs --exclude=./tests --exclude=./vendor --exclude=./scripts \
		--exclude=./composer.json --exclude=./composer.lock --exclude=./phpunit.xml --exclude=./.phpunit.cache -cf - . \
```

Dans `tests/preparation/tester_preparer_spip.sh`, cas « MySQL distincte », remplacer :

```bash
[ -f "$spip/plugins/wp2spip/paquet.xml" ] && [ ! -L "$spip/plugins/wp2spip" ] && [ ! -e "$spip/plugins/wp2spip/tests" ]
resultat "MySQL distincte : wp2spip copié, sans tests/" $?
```

par :

```bash
[ -f "$spip/plugins/wp2spip/paquet.xml" ] && [ ! -L "$spip/plugins/wp2spip" ] \
	&& [ -z "$(cd "$spip/plugins/wp2spip" && ls -d tests vendor scripts composer.json composer.lock phpunit.xml .phpunit.cache 2>/dev/null)" ]
resultat "MySQL distincte : wp2spip copié, sans tests/, vendor/, scripts/ ni fichiers de Composer et PHPUnit" $?
```

Run: `bash tests/preparation/tester_preparer_spip.sh`
Expected: `0 échec(s)` (cas d'erreur ; la préparation complète est lancée à la tâche 4).

- [ ] **Step 8 : commit**

```bash
git add scripts/install-spip-test.sh tests/spip-cli.patch tests/bootstrap_integration.php tests/integration/EnvironnementTest.php outils/preparer_spip.sh tests/preparation/tester_preparer_spip.sh
git commit -m "SPIP de test dans vendor/ (SQLite, plugins requis, wp2spip lié) et amorce des tests d'intégration"
```

### Task 3 : base WordPress de test et tests d'intégration

**Files:**
- Create: `tests/integration/data/wordpress/schema.sql`, `posts.php`, `postmeta.php`, `comments.php`, `options.php` ; les 11 fichiers de `tests/integration/data/wp-content/uploads/` ; `tests/integration/WordpressTestCase.php`, `BlocsTest.php`, `HierarchiePagesTest.php`, `PluginsRequisTest.php`, `LiensTest.php`, `SquelettesHierarchieTest.php`
- Delete: `tests/integration/tester_blocs.php`, `tests/integration/tester_hierarchie_pages.php`

**Interfaces:**
- Consumes : SPIP de test (tâche 2) ; `wp2spip_importer_documents_dist()`, `wp2spip_contexte_blocs()`, `wp2spip_convertir_blocs()`, `wp2spip_restaurer_blocs()`, `wp2spip_importer_hierarchie_pages_dist()`, `wp2spip_plugins_requis()`, `wp2spip_plugin_pret()`, `wp2spip_chercher_lien()`, `wp2spip_chercher_document()`.
- Produces : `Wp2spip\Tests\Integration\WordpressTestCase` : constantes `BASE = 'wp2spip_tests'`, `DOSSIER` ; `construireBase(string $connexion, string $prefixe = 'wp_', ?array $tables = null): void` (une fois par identifiant et par processus ; `$tables` : nom sans préfixe => lignes, sinon les fichiers de `data/wordpress`), `retirerBase(string $connexion): void`, `fichierBase(string $connexion): string`, `commande(string $connexion = self::BASE): stdClass` (`base`, `dir_wordpress`, `output` `BufferedOutput`), `importerDocuments(): void`. Les sous-projets suivants ajoutent leurs lignes dans `data/wordpress/` et leurs tests sur cette classe.

Contenu du WordPress de test : articles 1 « Bonjour », 555 « Galerie » (`[gallery]`), 1177 « Alignements » ; pages 10 « Parent », enfants 12 (ordre 1), 13 « Enfant B » et 11 « Enfant C » (ordre 2, par titre), 14 enfant de 12, 15 enfant de l'article 1 ; 20 privé, 21 protégé par mot de passe, 22 brouillon ; médias 611, 617, 755, 756, 757 (ordre 1), 761, 770, 771 rattachés à 555, 967 et 968 à 1177, vidéo 1690 ; un commentaire approuvé et un indésirable ; adresse `http://wordpress.test`.

- [ ] **Step 1 : schéma et données**

`tests/integration/data/wordpress/schema.sql` :

```sql
-- Tables WordPress lues par wp2spip, en SQLite ; {prefixe} est remplacé par le préfixe des tables
CREATE TABLE {prefixe}posts (
	ID INTEGER PRIMARY KEY,
	post_author INTEGER NOT NULL DEFAULT 0,
	post_date TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_date_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_content TEXT NOT NULL DEFAULT '',
	post_title TEXT NOT NULL DEFAULT '',
	post_excerpt TEXT NOT NULL DEFAULT '',
	post_status TEXT NOT NULL DEFAULT 'publish',
	comment_status TEXT NOT NULL DEFAULT 'open',
	ping_status TEXT NOT NULL DEFAULT 'open',
	post_password TEXT NOT NULL DEFAULT '',
	post_name TEXT NOT NULL DEFAULT '',
	to_ping TEXT NOT NULL DEFAULT '',
	pinged TEXT NOT NULL DEFAULT '',
	post_modified TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_modified_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	post_content_filtered TEXT NOT NULL DEFAULT '',
	post_parent INTEGER NOT NULL DEFAULT 0,
	guid TEXT NOT NULL DEFAULT '',
	menu_order INTEGER NOT NULL DEFAULT 0,
	post_type TEXT NOT NULL DEFAULT 'post',
	post_mime_type TEXT NOT NULL DEFAULT '',
	comment_count INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}postmeta (
	meta_id INTEGER PRIMARY KEY,
	post_id INTEGER NOT NULL DEFAULT 0,
	meta_key TEXT DEFAULT NULL,
	meta_value TEXT
);
CREATE TABLE {prefixe}comments (
	comment_ID INTEGER PRIMARY KEY,
	comment_post_ID INTEGER NOT NULL DEFAULT 0,
	comment_author TEXT NOT NULL DEFAULT '',
	comment_author_email TEXT NOT NULL DEFAULT '',
	comment_author_url TEXT NOT NULL DEFAULT '',
	comment_author_IP TEXT NOT NULL DEFAULT '',
	comment_date TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	comment_date_gmt TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	comment_content TEXT NOT NULL DEFAULT '',
	comment_karma INTEGER NOT NULL DEFAULT 0,
	comment_approved TEXT NOT NULL DEFAULT '1',
	comment_agent TEXT NOT NULL DEFAULT '',
	comment_type TEXT NOT NULL DEFAULT 'comment',
	comment_parent INTEGER NOT NULL DEFAULT 0,
	user_id INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}terms (
	term_id INTEGER PRIMARY KEY,
	name TEXT NOT NULL DEFAULT '',
	slug TEXT NOT NULL DEFAULT '',
	term_group INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}term_taxonomy (
	term_taxonomy_id INTEGER PRIMARY KEY,
	term_id INTEGER NOT NULL DEFAULT 0,
	taxonomy TEXT NOT NULL DEFAULT '',
	description TEXT NOT NULL DEFAULT '',
	parent INTEGER NOT NULL DEFAULT 0,
	count INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE {prefixe}term_relationships (
	object_id INTEGER NOT NULL DEFAULT 0,
	term_taxonomy_id INTEGER NOT NULL DEFAULT 0,
	term_order INTEGER NOT NULL DEFAULT 0,
	PRIMARY KEY (object_id, term_taxonomy_id)
);
CREATE TABLE {prefixe}options (
	option_id INTEGER PRIMARY KEY,
	option_name TEXT NOT NULL DEFAULT '' UNIQUE,
	option_value TEXT NOT NULL DEFAULT '',
	autoload TEXT NOT NULL DEFAULT 'yes'
);
CREATE TABLE {prefixe}users (
	ID INTEGER PRIMARY KEY,
	user_login TEXT NOT NULL DEFAULT '',
	user_pass TEXT NOT NULL DEFAULT '',
	user_nicename TEXT NOT NULL DEFAULT '',
	user_email TEXT NOT NULL DEFAULT '',
	user_url TEXT NOT NULL DEFAULT '',
	user_registered TEXT NOT NULL DEFAULT '0000-00-00 00:00:00',
	user_activation_key TEXT NOT NULL DEFAULT '',
	user_status INTEGER NOT NULL DEFAULT 0,
	display_name TEXT NOT NULL DEFAULT ''
);
CREATE TABLE {prefixe}usermeta (
	umeta_id INTEGER PRIMARY KEY,
	user_id INTEGER NOT NULL DEFAULT 0,
	meta_key TEXT DEFAULT NULL,
	meta_value TEXT
);
```

`tests/integration/data/wordpress/posts.php` :

```php
<?php
/**
 * Contenus du WordPress de test : articles, pages (dont une hiérarchie), contenus privés, médias
 *
 * Colonnes absentes : valeurs par défaut de schema.sql. Médias : fichiers sous data/wp-content/uploads.
 */
$media = fn($id, $titre, $fichier, $parent, $ordre = 0, $type = 'image/jpeg') => array(
	'ID' => $id, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_title' => $titre,
	'post_name' => strtolower(str_replace(' ', '-', $titre)), 'post_parent' => $parent, 'menu_order' => $ordre,
	'post_mime_type' => $type, 'guid' => "http://wordpress.test/wp-content/uploads/$fichier",
	'post_date' => '2013-03-15 10:00:00', 'post_modified' => '2013-03-15 10:00:00',
);
$contenu = fn($id, $type, $titre, $nom, $champs = array()) => $champs + array(
	'ID' => $id, 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $titre, 'post_name' => $nom,
	'post_content' => "<p>Contenu de $titre</p>", 'post_date' => '2020-01-02 03:04:05', 'post_modified' => '2020-01-02 03:04:05',
);

return array(
	$contenu(1, 'post', 'Bonjour', 'bonjour'),
	$contenu(555, 'post', 'Galerie', 'galerie', array('post_content' => '[gallery]')),
	$contenu(1177, 'post', 'Alignements', 'alignements', array('post_content' => '<!-- wp:image {"id":967} --><figure class="wp-block-image"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="" class="wp-image-967"/></figure><!-- /wp:image -->')),
	// Hiérarchie : 10 a pour enfants 12 (ordre 1), puis 13 et 11 (ordre 2, par titre) ; 12 a pour enfant 14 ; 15 a pour parent un article
	$contenu(10, 'page', 'Parent', 'parent'),
	$contenu(11, 'page', 'Enfant C', 'enfant-c', array('post_parent' => 10, 'menu_order' => 2)),
	$contenu(12, 'page', 'Enfant A', 'enfant-a', array('post_parent' => 10, 'menu_order' => 1)),
	$contenu(13, 'page', 'Enfant B', 'enfant-b', array('post_parent' => 10, 'menu_order' => 2)),
	$contenu(14, 'page', 'Petite-fille', 'petite-fille', array('post_parent' => 12)),
	$contenu(15, 'page', 'Sous un article', 'sous-un-article', array('post_parent' => 1)),
	// Contenus restreints, et un brouillon
	$contenu(20, 'post', 'Privé', 'prive', array('post_status' => 'private')),
	$contenu(21, 'post', 'Protégé', 'protege', array('post_password' => 'secret')),
	$contenu(22, 'post', 'Brouillon', 'brouillon', array('post_status' => 'draft')),
	// Médias : ceux de la galerie [gallery] de 555 dans l'ordre menu_order, puis ID (757 en dernier)
	$media(611, 'canola2', '2008/06/canola2.jpg', 555),
	$media(617, 'dsc20050813_115856_52', '2008/06/dsc20050813_115856_52.jpg', 555),
	$media(755, 'Golden Gate Bridge', '2008/06/100_5540.jpg', 555),
	$media(756, 'Sunburst Over River', '2008/06/cep00032.jpg', 555),
	$media(757, 'Boardwalk', '2008/06/dcp_2082.jpg', 555, 1),
	$media(761, 'Wind Farm', '2008/06/dsc20050102_192118_51.jpg', 555),
	$media(770, 'Huatulco Coastline', '2008/06/img_0767.jpg', 555),
	$media(771, 'Boat Barco Texture', '2008/06/img_8399.jpg', 555),
	$media(967, 'Image Alignment 580x300', '2013/03/image-alignment-580x300-1.jpg', 1177),
	$media(968, 'Image Alignment 150x150', '2013/03/image-alignment-150x150-1.jpg', 1177),
	$media(1690, '2014-slider-mobile-behavior', '2013/12/2014-slider-mobile-behavior.mov', 0, 0, 'video/quicktime'),
);
```

`tests/integration/data/wordpress/postmeta.php` :

```php
<?php
/**
 * Métadonnées des médias : fichier réel de chacun, tailles dérivées d'une image
 */
$fichiers = array(
	611 => '2008/06/canola2.jpg',
	617 => '2008/06/dsc20050813_115856_52.jpg',
	755 => '2008/06/100_5540.jpg',
	756 => '2008/06/cep00032.jpg',
	757 => '2008/06/dcp_2082.jpg',
	761 => '2008/06/dsc20050102_192118_51.jpg',
	770 => '2008/06/img_0767.jpg',
	771 => '2008/06/img_8399.jpg',
	967 => '2013/03/image-alignment-580x300-1.jpg',
	968 => '2013/03/image-alignment-150x150-1.jpg',
	1690 => '2013/12/2014-slider-mobile-behavior.mov',
);
$metas = array();
foreach ($fichiers as $id => $fichier) {
	$metas[] = array('post_id' => $id, 'meta_key' => '_wp_attached_file', 'meta_value' => $fichier);
}
$metas[] = array('post_id' => 967, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize(array(
	'width' => 580, 'height' => 300, 'file' => '2013/03/image-alignment-580x300-1.jpg',
	'sizes' => array('thumbnail' => array('file' => 'image-alignment-580x300-1-150x150.jpg', 'width' => 150, 'height' => 150)),
)));
return $metas;
```

`tests/integration/data/wordpress/comments.php` :

```php
<?php
/**
 * Commentaires : un approuvé, un indésirable (non importé)
 */
return array(
	array('comment_ID' => 1, 'comment_post_ID' => 1, 'comment_author' => 'Lecteur', 'comment_content' => 'Merci', 'comment_date' => '2020-01-03 10:00:00', 'comment_approved' => '1'),
	array('comment_ID' => 2, 'comment_post_ID' => 1, 'comment_author' => 'Robot', 'comment_content' => 'Publicité', 'comment_date' => '2020-01-03 11:00:00', 'comment_approved' => 'spam'),
);
```

`tests/integration/data/wordpress/options.php` :

```php
<?php
return array(
	array('option_name' => 'siteurl', 'option_value' => 'http://wordpress.test'),
	array('option_name' => 'home', 'option_value' => 'http://wordpress.test'),
	array('option_name' => 'blogname', 'option_value' => 'WordPress de test'),
);
```

- [ ] **Step 2 : fichiers des médias**

Images JPEG de 8×6 pixels et un en-tête QuickTime, générés une fois puis versionnés :

```bash
cd tests/integration/data && php -r '
foreach (array("2008/06/canola2.jpg","2008/06/dsc20050813_115856_52.jpg","2008/06/100_5540.jpg","2008/06/cep00032.jpg","2008/06/dcp_2082.jpg","2008/06/dsc20050102_192118_51.jpg","2008/06/img_0767.jpg","2008/06/img_8399.jpg","2013/03/image-alignment-580x300-1.jpg","2013/03/image-alignment-150x150-1.jpg") as $i => $f) {
	$c = "wp-content/uploads/$f"; @mkdir(dirname($c), 0777, true);
	$im = imagecreatetruecolor(8, 6); imagefill($im, 0, 0, imagecolorallocate($im, 40 * $i % 255, 120, 200)); imagejpeg($im, $c, 80);
}
@mkdir("wp-content/uploads/2013/12", 0777, true);
file_put_contents("wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov", "\x00\x00\x00\x14ftypqt  \x00\x00\x00\x00qt  ");
'; cd -
find tests/integration/data/wp-content -type f | wc -l
```

Expected: `11`.

- [ ] **Step 3 : `WordpressTestCase`**

`tests/integration/WordpressTestCase.php` :

```php
<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base des tests qui lisent un WordPress : une base SQLite construite à partir de data/wordpress,
 * déclarée comme base externe du SPIP de test, et les médias importés en documents (identifiant = ID WordPress)
 */
abstract class WordpressTestCase extends TestCase
{
	/** Identifiant de la base WordPress de test dans SPIP */
	public const BASE = 'wp2spip_tests';

	/** Dossier WordPress de test : ses médias sont sous wp-content/uploads */
	public const DOSSIER = __DIR__ . '/data/';

	/** Bases construites dans ce processus : SPIP garde leur connexion ouverte */
	private static array $construites = array();

	public static function setUpBeforeClass(): void
	{
		if (!isset(self::$construites[self::BASE])) {
			self::construireBase(self::BASE);
			self::importerDocuments();
		}
	}

	/**
	 * Construit une base WordPress SQLite et la déclare comme base externe de SPIP
	 *
	 * Une seule fois par identifiant et par processus : SPIP garde la connexion ouverte, sur l'ancien fichier.
	 *
	 * @param string $connexion identifiant de la base dans SPIP
	 * @param string $prefixe préfixe des tables
	 * @param array $tables tables à remplir (nom sans préfixe => lignes) ; par défaut, les fichiers de data/wordpress
	 */
	public static function construireBase(string $connexion, string $prefixe = 'wp_', ?array $tables = null): void
	{
		if (isset(self::$construites[$connexion])) {
			throw new \LogicException("Base $connexion déjà construite dans ce processus : choisir un autre identifiant");
		}
		self::$construites[$connexion] = true;
		$fichier = self::fichierBase($connexion);
		@unlink($fichier);
		$pdo = new PDO('sqlite:' . $fichier);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->exec(str_replace('{prefixe}', $prefixe, file_get_contents(__DIR__ . '/data/wordpress/schema.sql')));
		if ($tables === null) {
			$tables = array();
			foreach (glob(__DIR__ . '/data/wordpress/*.php') as $donnees) {
				$tables[basename($donnees, '.php')] = require $donnees;
			}
		}
		foreach ($tables as $table => $lignes) {
			foreach ($lignes as $ligne) {
				$colonnes = array_keys($ligne);
				$requete = $pdo->prepare(
					"INSERT INTO $prefixe$table (" . join(', ', $colonnes) . ') VALUES (' . join(', ', array_fill(0, count($colonnes), '?')) . ')'
				);
				$requete->execute(array_values($ligne));
			}
		}
		$pdo = null;
		file_put_contents(
			_DIR_CONNECT . $connexion . '.php',
			"<?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\nspip_connect_db('', '', '', '', '$connexion', 'sqlite3', 'spip', '', '');\n"
		);
	}

	/**
	 * Retire les fichiers d'une base construite par construireBase() (sa connexion reste ouverte jusqu'à la fin du processus)
	 */
	public static function retirerBase(string $connexion): void
	{
		@unlink(_DIR_CONNECT . $connexion . '.php');
		@unlink(self::fichierBase($connexion));
	}

	public static function fichierBase(string $connexion): string
	{
		// _DIR_DB n'est défini qu'à la première connexion SQLite
		return (defined('_DIR_DB') ? _DIR_DB : _DIR_ETC . 'bases/') . $connexion . '.sqlite';
	}

	/**
	 * Commande d'import imitée, telle que la reçoivent les traitements
	 */
	public static function commande(string $connexion = self::BASE): stdClass
	{
		$commande = new stdClass();
		$commande->base = $connexion;
		$commande->dir_wordpress = self::DOSSIER;
		$commande->output = new BufferedOutput();
		return $commande;
	}

	/**
	 * Médias du WordPress de test importés en documents, s'ils ne le sont pas déjà
	 */
	public static function importerDocuments(): void
	{
		include_spip('wp2spip/importer_documents');
		$commande = self::commande();
		if (wp2spip_importer_documents_dist($commande) === false) {
			throw new \RuntimeException("Import des documents de test impossible :\n" . $commande->output->fetch());
		}
	}
}
```

- [ ] **Step 4 : conversions des blocs**

Les 20 cas de `tests/integration/tester_blocs.php`, adresses `http://localhost:8766` remplacées par `http://wordpress.test`, et images rattachées à 555 réduites à celles du jeu de test. `tests/integration/BlocsTest.php` :

```php
<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Conversion des blocs de l'éditeur et des galeries (inc/wp2spip_blocs.php), sur les médias du WordPress de test
 *
 * Chaque cas : contenu Wordpress, texte attendu après conversion, sale et réinsertion des marqueurs (<albumN> pour
 * un album créé), albums créés, légendes devenues descriptifs, bilan. Albums, légendes et statuts des documents sont rétablis après chaque cas.
 */
final class BlocsTest extends WordpressTestCase
{
	private array $albums = array();
	private array $documents = array();

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('inc/wp2spip_blocs');
		include_spip('sale_fonctions');
	}

	protected function setUp(): void
	{
		$this->albums = array();
		// Descriptif (légendes) et statut (publié par un album) des documents, rétablis après le cas
		$this->documents = sql_allfetsel('id_document, descriptif, statut', 'spip_documents', 'id_wordpress > 0');
	}

	protected function tearDown(): void
	{
		foreach ($this->documents as $document) {
			sql_updateq('spip_documents', array('descriptif' => $document['descriptif'], 'statut' => $document['statut']), 'id_document = ' . intval($document['id_document']));
		}
		if ($this->albums) {
			sql_delete('spip_documents_liens', array('objet = "album"', sql_in('id_objet', $this->albums)));
			sql_delete('spip_albums_liens', sql_in('id_album', $this->albums));
			sql_delete('spip_albums', sql_in('id_album', $this->albums));
		}
	}

	#[DataProvider('cas')]
	public function testConversion(array $test): void
	{
		$wp_post = array(
			'ID' => $test['id_wordpress'] ?? 0,
			'post_title' => 'Essai',
			'post_date' => '2020-01-02 03:04:05',
			'post_content' => $test['contenu'],
		);
		$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name = "siteurl"', '', '', '', '', self::BASE);
		$contexte = wp2spip_contexte_blocs(self::commande(), $wp_post, $url_wordpress);
		$obtenu = trim(wp2spip_restaurer_blocs(sale(wp2spip_convertir_blocs($test['contenu'], $contexte)), $contexte));
		$this->albums = $contexte['albums'];

		$this->assertSame($test['attendu'], preg_replace('/<album\d+>/', '<albumN>', $obtenu), 'texte converti');
		$this->assertCount(count($test['albums'] ?? array()), $contexte['albums'], 'albums créés');
		foreach ($test['albums'] ?? array() as $n => $album) {
			$id_album = intval($contexte['albums'][$n]);
			$ligne = sql_fetsel('titre, descriptif, statut, date', 'spip_albums', 'id_album = ' . $id_album);
			$this->assertSame(
				array('titre' => $album['titre'], 'descriptif' => $album['descriptif'], 'statut' => 'publie', 'date' => '2020-01-02 03:04:05'),
				$ligne,
				"album $n"
			);
			$documents = sql_allfetsel('id_document', 'spip_documents_liens', array('objet = "album"', 'id_objet = ' . $id_album), '', 'rang_lien');
			$this->assertSame($album['documents'], array_map('intval', array_column($documents, 'id_document')), "documents de l’album $n");
			$this->assertSame(
				array('publie'),
				array_values(array_unique(array_column(sql_allfetsel('statut', 'spip_documents', sql_in('id_document', $album['documents'])), 'statut'))),
				"documents de l’album $n publiés"
			);
		}
		$this->assertSame($test['introuvables'] ?? 0, $contexte['bilan']['medias_introuvables'], 'médias introuvables au bilan');
		$this->assertSame($test['retirees'] ?? 0, $contexte['bilan']['images_retirees'], 'images retirées de leur album au bilan');
		if (isset($test['inconnus'])) {
			$this->assertSame($test['inconnus'], $contexte['bilan']['inconnus'], 'blocs inconnus au bilan');
		}
		foreach ($test['descriptifs'] ?? array() as $id_document => $descriptif) {
			$this->assertSame($descriptif, sql_getfetsel('descriptif', 'spip_documents', 'id_document = ' . $id_document), "descriptif du document $id_document");
		}
	}

	public static function cas(): array
	{
		return array_column(array_map(fn($test) => array($test['nom'], array($test)), array(
			array(
				'nom' => 'image centrée, retrouvée par son fichier (attribut id faux)',
				'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"center"} -->
<div class="wp-block-image"><figure class="aligncenter"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" class="wp-image-906"/></figure></div>
<!-- /wp:image -->
HTML,
				'attendu' => '<img967|center>',
			),
			array(
				'nom' => 'image liée et légendée : lien gardé, légende en descriptif',
				'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"left"} -->
<div class="wp-block-image"><figure class="alignleft"><a href="https://en.support.wordpress.com/images/image-settings/"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="" class="wp-image-906"/></a><figcaption>Une <strong>légende</strong></figcaption></figure></div>
<!-- /wp:image -->
HTML,
				'attendu' => '[<img968|left>->https://en.support.wordpress.com/images/image-settings/]',
				'descriptifs' => array(968 => 'Une {{légende}}'),
			),
			array(
				'nom' => 'galerie depuis Wordpress 5.9 (blocs image enfants)',
				'contenu' => <<<'HTML'
<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped"><!-- wp:image {"id":755,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://wordpress.test/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" class="wp-image-755"/><figcaption class="wp-element-caption">Golden Gate Bridge</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":617,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://wordpress.test/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" class="wp-image-617"/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery -->
HTML,
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(755, 617))),
				'descriptifs' => array(755 => 'Golden Gate Bridge'),
			),
			array(
				'nom' => 'galerie avant Wordpress 5.9 (liste blocks-gallery-item), légende de galerie',
				'contenu' => <<<'HTML'
<!-- wp:gallery {"ids":[],"linkTo":"attachment","className":"alignfull"} -->
<figure class="wp-block-gallery columns-3 is-cropped alignfull"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/"><img src="http://wordpress.test/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" class="wp-image-611"/></a></figure></li><li class="blocks-gallery-item"><figure><img src="http://wordpress.test/wp-content/uploads/2008/06/cep00032.jpg" alt="Sunburst Over River" data-id="756" class="wp-image-756"/><figcaption class="blocks-gallery-item__caption">Sunburst over the Clinch River</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption"><em>(gallery caption)</em> 3 columns</figcaption></figure>
<!-- /wp:gallery -->
HTML,
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '{(gallery caption)} 3 columns', 'documents' => array(611, 756))),
				'descriptifs' => array(756 => 'Sunburst over the Clinch River'),
			),
			array(
				'nom' => 'deux galeries dans un contenu : titres numérotés',
				'contenu' => '[gallery ids="770,771"]' . "\n\n" . '[gallery columns=2 ids="757"]',
				'attendu' => "<albumN>\n\n<albumN>",
				'albums' => array(
					array('titre' => 'Essai (galerie 1)', 'descriptif' => '', 'documents' => array(770, 771)),
					array('titre' => 'Essai (galerie 2)', 'descriptif' => '', 'documents' => array(757)),
				),
			),
			array(
				'nom' => 'raccourci [gallery] sans ids : images rattachées au contenu, par menu_order puis ID',
				'id_wordpress' => 555,
				'contenu' => '[gallery columns="9"]',
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(611, 617, 755, 756, 761, 770, 771, 757))),
			),
			array(
				'nom' => 'galerie dont une image manque : album des autres, image manquante signalée au bilan',
				'contenu' => '[gallery ids="770,999999,771"]',
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(770, 771))),
				'retirees' => 1,
			),
			array(
				'nom' => 'galerie dont aucune image n’est retrouvée : pas d’album, HTML gardé',
				'contenu' => '[gallery ids="999999"]',
				'attendu' => '[gallery ids="999999"]',
				'introuvables' => 1,
			),
			array(
				'nom' => 'couverture, ancien format : texte dans le HTML du bloc',
				'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","align":"left","id":761} -->
<div class="wp-block-cover has-background-dim alignleft" style="background-image:url(http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg)"><p class="wp-block-cover-text">This is a left aligned cover block.</p></div>
<!-- /wp:cover -->
HTML,
				'attendu' => "<div class=\"wp-block-cover\">\n\n<doc761>\n\nThis is a left aligned cover block.\n\n</div>",
			),
			array(
				'nom' => 'couverture, format actuel : texte dans un bloc enfant',
				'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","id":761,"dimRatio":50} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-761" alt="Wind Farm" src="http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Cover <strong>block</strong></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
HTML,
				'attendu' => "<div class=\"wp-block-cover\">\n\n<doc761>\n\nCover {{block}}\n\n</div>",
			),
			array(
				'nom' => 'colonnes : structure gardée, classes de présentation retirées',
				'contenu' => <<<'HTML'
<!-- wp:columns {"style":{"spacing":{"blockGap":"2em"}}} -->
<div class="wp-block-columns is-layout-flex" style="gap:2em"><!-- wp:column -->
<div class="wp-block-column has-background" style="background-color:#eee"><!-- wp:paragraph -->
<p>Première colonne</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Seconde colonne</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
				'attendu' => "<div class=\"wp-block-columns\">\n\n<div class=\"wp-block-column\">\n\nPremière colonne\n\n</div>\n\n<div class=\"wp-block-column\">\n\nSeconde colonne\n\n</div>\n\n</div>",
			),
			array(
				'nom' => 'tableau légendé : légende dans son propre paragraphe',
				'contenu' => <<<'HTML'
<!-- wp:table {"className":"is-style-regular"} -->
<figure class="wp-block-table is-style-regular"><table><tbody><tr><td>a</td><td>b</td></tr></tbody></table><figcaption class="wp-element-caption">Table caption</figcaption></figure>
<!-- /wp:table -->
HTML,
				'attendu' => "<figure class=\"wp-block-table\">\n\n|  a | b |\n\n<figcaption>Table caption</figcaption>\n\n</figure>",
			),
			array(
				'nom' => 'bouton : lien SPIP dans la balise du bouton',
				'contenu' => <<<'HTML'
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link" href="https://wordpress.org/gutenberg/handbook/" style="border-radius:5px">Read <em>more</em></a></div>
<!-- /wp:button -->
HTML,
				'attendu' => '<div class="wp-block-button">[Read more->https://wordpress.org/gutenberg/handbook/]</div>',
			),
			array(
				'nom' => 'contenu embarqué : URL seule sur sa ligne, puis la légende',
				'contenu' => <<<'HTML'
<!-- wp:core-embed/youtube {"url":"https://youtu.be/ex8fMxXJDJw","type":"video","providerNameSlug":"youtube"} -->
<figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper">
		https://youtu.be/ex8fMxXJDJw
</div><figcaption>Une vidéo</figcaption></figure>
<!-- /wp:core-embed/youtube -->
HTML,
				'attendu' => "https://youtu.be/ex8fMxXJDJw\n\nUne vidéo",
			),
			array(
				'nom' => 'vidéo de la médiathèque : document',
				'contenu' => <<<'HTML'
<!-- wp:video {"id":1690} -->
<figure class="wp-block-video"><video controls src="http://wordpress.test/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure>
<!-- /wp:video -->
HTML,
				'attendu' => '<doc1690>',
			),
			array(
				'nom' => 'blocs dynamiques retirés, espaceur et suite retirés',
				'contenu' => "<!-- wp:latest-posts /-->\n\n<!-- wp:paragraph -->\n<p>Texte</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:spacer {\"height\":70} -->\n<div style=\"height:70px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->\n\n<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->\n\n<!-- wp:query {\"queryId\":1} -->\n<div class=\"wp-block-query\"><!-- wp:post-title /--></div>\n<!-- /wp:query -->",
				'attendu' => 'Texte',
			),
			array(
				'nom' => 'bloc dynamique qui a du contenu enregistré : contenu gardé',
				'contenu' => "<!-- wp:query {\"queryId\":2} -->\n<div class=\"wp-block-query\"><!-- wp:post-title /-->\n\n<!-- wp:query-no-results -->\n<!-- wp:paragraph -->\n<p>Aucun résultat</p>\n<!-- /wp:paragraph -->\n<!-- /wp:query-no-results --></div>\n<!-- /wp:query -->",
				'attendu' => "<div class=\"wp-block-query\">\n\nAucun résultat\n\n</div>",
			),
			array(
				'nom' => 'bloc réutilisable (wp:block) : traité comme un bloc inconnu',
				'contenu' => "<p>Avant</p>\n\n<!-- wp:block {\"ref\":123} /-->",
				'attendu' => 'Avant',
				'inconnus' => array('core/block' => 1),
			),
			array(
				'nom' => 'bloc inconnu : contenu gardé, passé par sale',
				'contenu' => "<!-- wp:mon-extension/encart {\"couleur\":\"rouge\"} -->\n<div class=\"encart\"><p>Un encart</p></div>\n<!-- /wp:mon-extension/encart -->",
				'attendu' => "<div class=\"encart\">Un encart\n\n</div>",
			),
			array(
				'nom' => 'contenu sans bloc : inchangé',
				'contenu' => '<p>Texte <em>classique</em></p>',
				'attendu' => 'Texte {classique}',
			),
		)), 1, 0);
	}
}
```

Run: `vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php --filter BlocsTest`
Expected: `OK (20 tests, …)`.

- [ ] **Step 5 : hiérarchie des pages, plugins requis, liens, squelettes**

`tests/integration/HierarchiePagesTest.php` :

```php
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
	private const PAGES = array(10 => 'Parent', 11 => 'Enfant C', 12 => 'Enfant A', 13 => 'Enfant B', 14 => 'Petite-fille', 15 => 'Sous un article', 1 => 'Bonjour');

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
		// Enfants de 10 : 12 (ordre 1), puis 13 et 11 (ordre 2, par titre) ; enfant de 12 : 14 ; 15 a pour parent un article
		$this->assertSame(array(10 => array(1 => 12, 2 => 13, 3 => 11), 12 => array(1 => 14)), self::liens());
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
		$this->assertSame(array(10 => array(1 => 12, 2 => 13, 3 => 11)), self::liens());
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
```

`tests/integration/PluginsRequisTest.php` :

```php
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
```

`tests/integration/LiensTest.php` :

```php
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
```

`tests/integration/SquelettesHierarchieTest.php` (les boucles sont lues dans le readme : le test échoue si la documentation change sans rester juste) :

````php
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
````

- [ ] **Step 6 : retirer les anciens scripts**

```bash
git rm tests/integration/tester_blocs.php tests/integration/tester_hierarchie_pages.php
```

- [ ] **Step 7 : lancer deux fois**

Run: `composer tests-integration && composer tests-integration`
Expected: deux fois `OK (48 tests, …)`. Puis l'état du SPIP de test :
`(cd vendor/spip/spip && ../../bin/spip php:eval 'echo sql_countsel("spip_albums"), " ", sql_countsel("spip_articles"), " ", join(",", array_column(sql_allfetsel("distinct statut", "spip_documents"), "statut"));')`
Expected: `0 0 prop` (aucun album ni article restant, documents non publiés).

- [ ] **Step 8 : sabotage**

Dans `wp2spip/importer_hierarchie_pages.php`, remplacer l'ordre `'post_parent, menu_order, post_title, ID'` par `'post_parent, menu_order, ID'`.
Run: `composer tests-integration`
Expected: échec de `HierarchiePagesTest::testLiensDansLOrdreWordpress` (rangs 13 et 11 inversés : `2 => 13, 3 => 11` attendus). Annuler (`git checkout wp2spip/importer_hierarchie_pages.php`), relancer : OK.

- [ ] **Step 9 : commit**

```bash
git add tests/integration
git commit -m "Tests d'intégration sur une base WordPress SQLite de test (blocs, hiérarchie des pages, plugins requis, liens, boucles du readme)"
```

### Task 4 : import complet comparé à la référence, documentation

**Files:**
- Create: `tests/integration/valider.sh`, `tests/integration/references/theme-unit-test.tsv` (produit par le script)
- Modify: `readme.md` (section « Tests »), `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 6, ligne 7), `docs/superpowers/specs/2026-10-09-wp2spip-tests-automatises-design.md` (statut), `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 17)

**Interfaces:**
- Consumes : `outils/preparer_spip.sh` (copie sans outillage, tâche 2), `vendor/bin/spip` corrigé (tâche 2), `tests/integration/verifier_identifiants.php`, `tests/integration/exporter_import.php`.
- Produces : `tests/integration/valider.sh <dossier WordPress> [--mettre-a-jour]` (code 0 si conforme) ; `composer tests-import`.

- [ ] **Step 1 : `tests/integration/valider.sh`**

```bash
#!/bin/bash
# Import complet d'un WordPress de test, comparé à la référence versionnée
# Usage : valider.sh <dossier WordPress> [--mettre-a-jour]
#   Prépare un SPIP SQLite depuis un dossier vide (outils/preparer_spip.sh --importer, SPIP-Cli de vendor/),
#   lance le vérificateur, puis compare l'export normalisé à tests/integration/references/theme-unit-test.tsv :
#   adresse du WordPress remplacée par @URL_SITE@, date de son installation (contenus créés par l'installation)
#   par @INSTALLATION@. --mettre-a-jour réécrit la référence.
#   Prérequis : WordPress installé en français avec le contenu Theme Unit Test, accès lisibles dans son wp-config.php.
#   Le SPIP préparé est supprimé si tout est conforme, gardé sinon.
set -uo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
reference="$racine/tests/integration/references/theme-unit-test.tsv"
spip_cli="$racine/vendor/bin/spip"
wordpress=${1:?Usage : valider.sh <dossier WordPress> [--mettre-a-jour]}
mettre_a_jour=${2:-}

[ -x "$spip_cli" ] || { echo "SPIP-Cli absent de vendor/bin : lancer composer install" >&2; exit 1; }
grep -q 'UPPER(pl.prefixe) = UPPER' "$racine/vendor/spip/spip-cli/src/Command/PluginsSvpTelecharger.php" \
	|| { echo "SPIP-Cli de vendor/ sans correctif : lancer composer install-spip-test" >&2; exit 1; }

travail=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-valider.XXXXXX")
spip="$travail/spip"
echo "Préparation et import de $wordpress dans $spip"
"$racine/outils/preparer_spip.sh" --spip "$spip" --wordpress "$wordpress" --spip-cli "$spip_cli" --importer >"$travail/import.log" 2>&1
code=$?
if [ "$code" -ne 0 ]; then
	echo "ECHEC : préparation ou import en code $code (journal $travail/import.log)"
	exit 1
fi

(cd "$spip" && "$spip_cli" --no-ansi php:eval "include '$racine/tests/integration/verifier_identifiants.php';") >"$travail/verification.txt" 2>&1
if [ "$(tail -n 1 "$travail/verification.txt")" != OK ]; then
	echo "ECHEC : vérificateur (sortie $travail/verification.txt)"
	exit 1
fi

(cd "$spip" && "$spip_cli" --no-ansi php:eval "include '$racine/tests/integration/exporter_import.php';") >"$travail/export.tsv" 2>&1 \
	|| { echo "ECHEC : export ($travail/export.tsv)"; exit 1; }
# Adresse du WordPress, et date de son installation : celle de son premier article
url_site=$(cd "$spip" && "$spip_cli" --no-ansi php:eval 'echo sql_getfetsel("option_value", "wp_options", "option_name = \"siteurl\"", "", "", "", "", "wordpress");')
installation=$(awk -F'\t' '$1 == "article" && $2 == "1" { print $5 }' "$travail/export.tsv")
[ -n "$url_site" ] && [ -n "$installation" ] || { echo "ECHEC : adresse ou date d'installation introuvable"; exit 1; }
URL_SITE=$url_site INSTALLATION=$installation php -r '
	echo str_replace(array(getenv("URL_SITE"), getenv("INSTALLATION")), array("@URL_SITE@", "@INSTALLATION@"), stream_get_contents(STDIN));
' <"$travail/export.tsv" >"$travail/export-normalise.tsv"

if [ "$mettre_a_jour" = --mettre-a-jour ]; then
	cp "$travail/export-normalise.tsv" "$reference"
	echo "Référence mise à jour : $reference ($(wc -l <"$reference") lignes)"
elif ! diff "$reference" "$travail/export-normalise.tsv" >"$travail/ecarts.diff"; then
	echo "ECHEC : export différent de la référence ($(grep -c '^[<>]' "$travail/ecarts.diff") lignes, détail $travail/ecarts.diff)"
	exit 1
fi
rm -rf "$travail"
echo "OK : $wordpress conforme à la référence"
```

`chmod +x tests/integration/valider.sh`.

- [ ] **Step 2 : référence depuis le WordPress 6.9**

Run: `source tests/integration/environnement.sh && tests/integration/valider.sh "$WP6" --mettre-a-jour`
Expected: `Référence mise à jour : …/theme-unit-test.tsv (N lignes)` (quelques minutes). Contrôles :

```bash
ref=tests/integration/references/theme-unit-test.tsv
grep -c '@URL_SITE@' $ref        # au moins 2
grep -c '@INSTALLATION@' $ref    # au moins 4 : articles 1, 2, 3 et commentaire 1
grep -c 'localhost' $ref         # 0
# Même contenu que l'export local de référence du WordPress 6.9, une fois normalisé de la même façon
url=$(mysql $MYSQL_OPTIONS -N "$BASE_WP6" -e "select option_value from wp_options where option_name = 'siteurl'")
inst=$(awk -F'\t' '$1 == "article" && $2 == "1" { print $5 }' "$SAUVEGARDES/export-wp6-reference.tsv")
sed -e "s#$url#@URL_SITE@#g" -e "s#$inst#@INSTALLATION@#g" "$SAUVEGARDES/export-wp6-reference.tsv" | diff - $ref && echo identique
```

Expected: `identique`.

- [ ] **Step 3 : WordPress 7.1 et écart volontaire**

Run: `tests/integration/valider.sh "$WP7"`
Expected: `OK : …/wordpress-7.1 conforme à la référence`.

Écart volontaire :

```bash
cp $ref "$ESSAIS/reference.tsv"
sed -i '0,/^sous_page/{/^sous_page/d}' $ref
tests/integration/valider.sh "$WP7"; echo "code $?"
cp "$ESSAIS/reference.tsv" $ref
```

Expected: `ECHEC : export différent de la référence (1 lignes, …)` et `code 1` ; référence rétablie.

- [ ] **Step 4 : `composer tests-import`**

Run: `composer tests-import`
Expected: deux lignes `OK : … conforme à la référence`, code 0.

- [ ] **Step 5 : readme, section « Tests »**

Ajouter avant `## Pour les devs` :

````markdown
## Tests
Les tests demandent PHP 8.4 et Composer. Depuis la racine du dépôt :

``` bash
$ composer install
$ composer tests-unit           # tests unitaires, sans SPIP
$ composer install-spip-test    # SPIP 4.4 SQLite dans vendor/spip/spip, plugins requis, wp2spip lié (réseau, une fois)
$ composer tests-integration    # tests dans ce SPIP, sur une base WordPress SQLite de test
```

`composer install-spip-test` applique à SPIP-Cli de `vendor/` les correctifs de `plugins:svp:telecharger` (`tests/spip-cli.patch`). Les tests d'intégration lisent une base WordPress construite à partir de `tests/integration/data/` et retirent ce qu'ils créent : ils se relancent sur le même état.

`composer tests-import` importe complètement, depuis un dossier vide, deux WordPress installés en français avec le contenu *Theme Unit Test* (6.9 et 7.1, dossiers `$WP6` et `$WP7` de `tests/integration/environnement.sh`, à créer d'après `environnement.exemple.sh`), et compare chaque import à `tests/integration/references/theme-unit-test.tsv`, où l'adresse du site et la date d'installation de WordPress sont normalisées. Une évolution de l'import met la référence à jour avec `tests/integration/valider.sh <dossier WordPress> --mettre-a-jour` ; le diff de la référence se relit dans le même commit.
````

- [ ] **Step 6 : préparation complète**

Run: `bash tests/preparation/tester_preparer_spip.sh --complet`
Expected: `0 échec(s)` (dont « wp2spip copié, sans tests/, vendor/, scripts/ ni fichiers de Composer et PHPUnit »).

- [ ] **Step 7 : depuis un clone neuf**

```bash
clone="$ESSAIS/clone-wp2spip"
rm -rf "$clone" && git clone -q "$WP2SPIP" "$clone" && cd "$clone"
composer install --no-interaction && composer tests-unit && composer install-spip-test && composer tests-integration; echo "code $?"
cd - && rm -rf "$clone"
```

Expected: `OK (55 tests, …)`, `SPIP de test prêt`, `OK (48 tests, …)`, `code 0` (le clone porte les commits des tâches 1 à 3 ; `valider.sh` et la référence sont validés aux étapes 2 à 4).

- [ ] **Step 8 : documents de suivi**

- Spec du sous-projet : `Statut : réalisé.`
- Spec d'ensemble, § 6, ligne 7 : `| 7 | Tests automatisés — **réalisé** | cœur | …` (reste de la ligne inchangé).
- Plan principal, Task 17 : `- [x] Plan : docs/superpowers/plans/2026-10-09-wp2spip-tests-automatises.md` et `- [x] Réalisation ; validation : …` avec les résultats constatés (nombre de tests, relance, sabotages, `tests-import`, préparation).

- [ ] **Step 9 : commit**

```bash
git add tests/integration/valider.sh tests/integration/references readme.md docs
git commit -m "Import complet comparé à une référence versionnée (valider.sh, composer tests-import) ; documentation des tests"
```
