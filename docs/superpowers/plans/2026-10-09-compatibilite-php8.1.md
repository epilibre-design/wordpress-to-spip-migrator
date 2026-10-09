# wp2spip — compatibilité PHP 8.1 à 8.4 — plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Faire fonctionner wp2spip et ses extensions `wp2spip_acf` et `wp2spip_yoast` de PHP 8.1 à 8.4, chaîne de tests comprise, sans changer la sortie de la conversion HTML → SPIP sous PHP 8.4.

**Architecture:** Un nouveau fichier `inc/wp2spip_html_arbre.php` est la seule partie du code qui connaît le parseur : `Dom\HTMLDocument` à partir de PHP 8.4, sinon Masterminds HTML5-PHP, dont une copie est livrée dans `lib/masterminds-html5/`. Il fournit le `body` analysé, la reconnaissance des nœuds et la sérialisation HTML5 des médias. Une sous-classe du constructeur d'arbre de Masterminds (`inc/wp2spip_html_masterminds.php`) ajoute les règles HTML5 dont l'absence changerait le texte converti. Le convertisseur `inc/wp2spip_html.php` garde ses règles et passe par ces fonctions.

**Tech Stack:** PHP 8.1 à 8.4, extension DOM ; Masterminds HTML5-PHP 2.11 (MIT) ; SPIP 4.4 et SPIP-Cli ; PHPUnit 10.5 ; Composer.

Spec : `docs/superpowers/specs/2026-10-09-compatibilite-php8.1-design.md`.

## Global Constraints

- Cible basse : **PHP 8.1** ; repli documenté sur 8.2 seulement si un blocage technique réel est constaté.
- Sous PHP 8.4, la sortie de `wp2spip_html_spip()` ne change pas : les 59 cas actuels de `tests/unit/HtmlTest.php` passent sans modification de leur valeur attendue.
- Le parseur est choisi automatiquement : aucune option utilisateur de sélection.
- Aucune perte de texte sur la branche Masterminds ; une différence de mise en forme due à du HTML mal formé est acceptée seulement si elle est documentée par un cas de test.
- Masterminds : `masterminds/html5` `^2.10` (verrou : 2.11.0), option `disable_html_ns` ; copie dans `lib/masterminds-html5/` (`src/`, `LICENSE.txt`, `VERSION`), suivie par Git. Le plugin installé ne dépend ni de `vendor/` ni d'un `composer install`.
- PHPUnit `^10.5` dans les trois dépôts ; `config.platform.php` = `8.1.0` dans les trois `composer.json`. Jamais `--ignore-platform-reqs`.
- Ne pas modifier les règles de conversion HTML → SPIP ; pas d'intégration continue (hors périmètre).
- Messages de commit en français, sans trailer. Ne jamais nommer le site WordPress réel de test dans un fichier versionné ni un commit.
- Dépôts : `wp2spip` se livre par PR sur GitHub (`epilibre`, branche `main`), puis `main` est poussée sur `compat-spip-4.4` de git.spip.net ; les extensions se poussent sur git.spip.net. Aucun push sans demande explicite du mainteneur.
- PHP disponibles sur la machine : `php8.1`, `php8.2`, `php8.3`, `php8.4` (avec dom, xml, xmlwriter, mbstring, sqlite3, pdo_sqlite, mysqli, pdo_mysql, zip, curl).

## Constats du prototype (2026-10-09)

Un prototype de la frontière a été exécuté sur les cas de `HtmlTest` avec chaque PHP. Ce plan reprend son code.

- Sous 8.4, frontière comprise : 59 cas sur 59 inchangés.
- Le sérialiseur de Masterminds diffère de `saveHtml()` (`controls` au lieu de `controls=""`, `<use … />` avec un `xmlns:xlink` ajouté) : il n'est **pas** utilisé. Un sérialiseur HTML5 de wp2spip (algorithme du standard) donne la même chaîne que `saveHtml()` sur 16 cas de médias.
- `DOMAttr::$name` renvoie `href` pour `xlink:href` ; `nodeName` renvoie `xlink:href` sur les deux parseurs.
- Masterminds n'insère pas le `tbody` et le `tr` implicites, ne crée pas de paragraphe pour un `</p>` orphelin (« a</p>b » devenait « ab ») et laisse dans le tableau le texte égaré (perdu à la conversion). Ces trois points sont corrigés, deux dans la sous-classe du constructeur d'arbre, un dans le convertisseur.
- Reste 2 écarts acceptés, sans perte de texte : la mise en forme rouverte après des balises croisées (`<b><i>c</b> d</i>`) ou non fermées (`<p>a <b>b<p>c`) n'est pas reconstruite.
- Résolution Composer sur 8.1 avec ces contraintes : PHPUnit 10.5.66, Masterminds 2.11.0, `spip/tests` et `spip/spip-cli` dev-master ; PHPUnit 10.5.66 et SPIP-Cli 2.0.1 démarrent sous 8.1, 8.2, 8.3 et 8.4.

## Structure des fichiers

| Fichier | Rôle |
| --- | --- |
| `inc/wp2spip_html_arbre.php` (créé) | Choix du parseur, chargement de Masterminds, `body`, reconnaissance des nœuds, sérialisation HTML5, vérification et description du parseur |
| `inc/wp2spip_html_masterminds.php` (créé) | `Wp2spipArbreMasterminds` : sous-classe du `DOMTreeBuilder` de Masterminds |
| `inc/wp2spip_html.php` (modifié) | Convertisseur : passe par l'arbre ; détection des tableaux complexes et contenu égaré des tableaux |
| `lib/masterminds-html5/` (créé) | Copie de Masterminds : `src/`, `LICENSE.txt`, `VERSION` |
| `outils/copier_masterminds.sh` (créé) | Recopie `vendor/masterminds/html5` dans `lib/` à chaque mise à jour du verrou |
| `spip-cli/WordpressImporter.php` (modifié) | Arrêt si aucun parseur ; parseur affiché en mode verbeux |
| `tests/unit/HtmlArbreTest.php` (créé) | Nœuds, sérialisation, règles ajoutées, messages d'erreur |
| `tests/unit/MastermindsCopieTest.php` (créé) | Copie de `lib/` identique à la version verrouillée |
| `tests/unit/HtmlTest.php` (modifié) | Nouveaux cas ; chaque cas aussi converti par Masterminds |
| `tests/matrice_php.sh` (créé) | Suites sous chaque PHP, import complet en option |
| `tests/integration/valider.sh` (modifié) | `VERSION_SPIP` transmis à `outils/preparer_spip.sh` |
| `tests/preparation/tester_preparer_spip.sh` (modifié) | La copie de wp2spip garde `lib/masterminds-html5/` |
| `composer.json`, `composer.lock`, `paquet.xml`, `readme.md`, `docs/site/…` | Prérequis PHP 8.1 |

---

### Task 1 : dépendances Composer de wp2spip

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock` (régénéré)

**Interfaces:**
- Produces : `vendor/masterminds/html5` (2.11.0) chargé par l'autoload de Composer dans les tests ; `vendor/bin/phpunit` en 10.5.

- [ ] **Step 1 : modifier `composer.json`**

Dans `require`, remplacer `"php": ">=8.4"` par :

```json
        "php": ">=8.1",
        "ext-dom": "*",
        "masterminds/html5": "^2.10"
```

Dans `require-dev`, remplacer `"phpunit/phpunit": "^13.0"` par `"phpunit/phpunit": "^10.5"`.

Dans `config`, ajouter la plateforme :

```json
    "config": {
        "sort-packages": true,
        "process-timeout": 0,
        "platform": {
            "php": "8.1.0"
        }
    }
```

- [ ] **Step 2 : régénérer le verrou sous PHP 8.1**

Run : `php8.1 $(command -v composer) update --no-interaction`
Expected : `Locking masterminds/html5 (2.11.0)`, `phpunit/phpunit (10.5.…)`, aucune erreur de plateforme.

- [ ] **Step 3 : contrôler les prérequis de plateforme**

Run : `php8.1 $(command -v composer) check-platform-reqs && php8.1 $(command -v composer) check-platform-reqs --no-dev`
Expected : toutes les lignes `success`, code 0.

- [ ] **Step 4 : réinstaller le SPIP de test (SPIP-Cli peut avoir été réinstallé sans son correctif)**

Run : `rm -rf .phpunit.cache && composer install-spip-test`
Expected : se termine sans `ERREUR`.

- [ ] **Step 5 : les suites passent toujours sous 8.4 (code inchangé)**

Run : `php8.4 vendor/bin/phpunit --testsuite unit && php8.4 vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : `OK`, `phpunit --version` en 10.5. Si PHPUnit 10.5 refuse une construction des tests, corriger seulement cet usage.

- [ ] **Step 6 : commit**

```bash
git add composer.json composer.lock
git commit -m "Composer : PHP 8.1, Masterminds HTML5-PHP et PHPUnit 10.5

Le verrou est résolu pour PHP 8.1 (config.platform.php) : il s'installe
de 8.1 à 8.4. Le plugin garde PHP 8.4 dans paquet.xml jusqu'à la
validation de la matrice."
```

---

### Task 2 : copie de Masterminds dans `lib/`

**Files:**
- Create: `outils/copier_masterminds.sh`
- Create: `lib/masterminds-html5/` (par le script)
- Create: `tests/unit/MastermindsCopieTest.php`
- Modify: `tests/preparation/tester_preparer_spip.sh:147-149`

**Interfaces:**
- Consumes : `vendor/masterminds/html5` (Task 1).
- Produces : `lib/masterminds-html5/src/HTML5.php` et l'arborescence PSR-4 `Masterminds\` → `src/` ; `lib/masterminds-html5/VERSION` (une ligne : `2.11.0`).

- [ ] **Step 1 : écrire le test**

`tests/unit/MastermindsCopieTest.php` :

```php
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
```

- [ ] **Step 2 : vérifier qu'il échoue**

Run : `php8.4 vendor/bin/phpunit --testsuite unit --filter MastermindsCopieTest`
Expected : 3 échecs (VERSION vide, empreintes différentes, licence absente).

- [ ] **Step 3 : écrire `outils/copier_masterminds.sh`**

```bash
#!/bin/bash
# Recopie Masterminds HTML5-PHP de vendor/ dans lib/masterminds-html5/, livré avec le plugin
# (analyseur HTML5 sous PHP 8.1 à 8.3). À relancer après chaque mise à jour de masterminds/html5 dans composer.lock :
# tests/unit/MastermindsCopieTest.php échoue tant que la copie diffère du verrou.
set -euo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
source="$racine/vendor/masterminds/html5"
copie="$racine/lib/masterminds-html5"

[ -f "$source/src/HTML5.php" ] || { echo "Masterminds absent de vendor/ : lancer composer install" >&2; exit 1; }
version=$(php -r '
	$verrou = json_decode(file_get_contents($argv[1]), true);
	foreach ($verrou["packages"] as $paquet) {
		if ($paquet["name"] === "masterminds/html5") {
			echo ltrim($paquet["version"], "v");
		}
	}' "$racine/composer.lock")
[ -n "$version" ] || { echo "masterminds/html5 absent de composer.lock" >&2; exit 1; }

rm -rf "$copie"
mkdir -p "$copie"
cp -R "$source/src" "$copie/src"
cp "$source/LICENSE.txt" "$copie/LICENSE.txt"
echo "$version" >"$copie/VERSION"
echo "Masterminds $version copié dans $copie"
```

Run : `chmod +x outils/copier_masterminds.sh && outils/copier_masterminds.sh`
Expected : `Masterminds 2.11.0 copié dans …/lib/masterminds-html5`.

- [ ] **Step 4 : le test passe**

Run : `php8.4 vendor/bin/phpunit --testsuite unit --filter MastermindsCopieTest`
Expected : `OK (3 tests, 3 assertions)`.

- [ ] **Step 5 : la copie par le préparateur garde `lib/`**

`outils/preparer_spip.sh` n'exclut pas `lib/` (ligne 241) : rien à y changer. Dans `tests/preparation/tester_preparer_spip.sh`, remplacer les lignes 147 à 149 par :

```bash
[ -f "$spip/plugins/wp2spip/paquet.xml" ] && [ ! -L "$spip/plugins/wp2spip" ] \
	&& [ -z "$(cd "$spip/plugins/wp2spip" && ls -d tests vendor scripts composer.json composer.lock phpunit.xml .phpunit.cache 2>/dev/null)" ] \
	&& [ -f "$spip/plugins/wp2spip/lib/masterminds-html5/src/HTML5.php" ]
resultat "MySQL distincte : wp2spip copié avec lib/, sans tests/, vendor/, scripts/ ni fichiers de Composer et PHPUnit" $?
```

Ce test est exécuté à la Task 6 (il demande la base MySQL jetable).

- [ ] **Step 6 : commit**

```bash
git add outils/copier_masterminds.sh lib/masterminds-html5 tests/unit/MastermindsCopieTest.php tests/preparation/tester_preparer_spip.sh
git commit -m "Masterminds HTML5-PHP 2.11.0 livré dans lib/

Copie de la version verrouillée (sources et licence MIT), recopiée par
outils/copier_masterminds.sh ; un test unitaire échoue si elle diffère du
verrou. Le test du préparateur vérifie que la copie du plugin la garde."
```

---

### Task 3 : arbre HTML5 et sérialiseur

**Files:**
- Create: `inc/wp2spip_html_arbre.php`
- Create: `inc/wp2spip_html_masterminds.php`
- Test: `tests/unit/HtmlArbreTest.php`

**Interfaces:**
- Consumes : `lib/masterminds-html5/src/` (Task 2) ; en tests, Masterminds est aussi chargé par l'autoload de Composer.
- Produces (utilisées par les Tasks 4 et 5) :
  - `wp2spip_html_arbre_body(string $html): Dom\Element|DOMElement` : `body` du HTML enveloppé dans `<!DOCTYPE html><html><body>…</body></html>` ; `RuntimeException` si aucun parseur.
  - `wp2spip_html_arbre_body_masterminds(string $html): DOMElement` : même chose, toujours par Masterminds (tests).
  - `wp2spip_html_est_texte($noeud): bool`, `wp2spip_html_est_element($noeud): bool`.
  - `wp2spip_html_serialiser($noeud): string` : HTML d'un nœud, contenu compris, identique à `saveHtml()`.
  - `wp2spip_html_arbre_erreur(bool $dom_natif, bool $dom, bool $masterminds): string` et `wp2spip_html_arbre_verifier(): string` : message, ou `''`.
  - `wp2spip_html_arbre_parseur(): string` : description du parseur utilisé.

- [ ] **Step 1 : écrire le test**

`tests/unit/HtmlArbreTest.php` :

```php
<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip_html_arbre.php';

/**
 * Arbre HTML5 du convertisseur : par Masterminds (PHP 8.1 à 8.3), mêmes nœuds et même HTML que par Dom\HTMLDocument
 * (PHP 8.4). Les valeurs attendues sont celles de Dom\HTMLDocument::saveHtml().
 */
final class HtmlArbreTest extends TestCase
{
	public static function medias(): array
	{
		return array(
			'attribut booléen' => array('<audio controls src="a.mp3"></audio>', '<audio controls="" src="a.mp3"></audio>'),
			'guillemets et esperluette' => array('<img src="a.jpg" alt="x &amp; &quot;y&quot;" class="c">', '<img src="a.jpg" alt="x &amp; &quot;y&quot;" class="c">'),
			'adresse à paramètres' => array('<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen width="5"></iframe>', '<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen="" width="5"></iframe>'),
			'svg et attribut à préfixe' => array('<svg viewBox="0 0 1 1"><use xlink:href="#i"></use><foreignObject><p>x</p></foreignObject></svg>', '<svg viewBox="0 0 1 1"><use xlink:href="#i"></use><foreignObject><p>x</p></foreignObject></svg>'),
			'élément vide' => array('<video controls><source src="v.mp4" type="video/mp4"></video>', '<video controls=""><source src="v.mp4" type="video/mp4"></video>'),
			'picture' => array('<picture><source srcset="a.webp"><img src="a.jpg"></picture>', '<picture><source srcset="a.webp"><img src="a.jpg"></picture>'),
			'mathml' => array('<math><mi>x</mi></math>', '<math><mi>x</mi></math>'),
			'espace insécable' => array('<img src="a.jpg" alt="a&nbsp;b">', '<img src="a.jpg" alt="a&nbsp;b">'),
			'chevrons dans un attribut' => array('<img alt="a<b>c">', '<img alt="a<b>c">'),
			'texte brut d’une iframe' => array('<iframe>x &amp; <b></iframe>', '<iframe>x &amp; <b></iframe>'),
			'texte échappé' => array('<svg><title>a &lt; b</title></svg>', '<svg><title>a &lt; b</title></svg>'),
			'zone de texte' => array('<textarea>a &lt; b</textarea>', '<textarea>a &lt; b</textarea>'),
			'option choisie' => array('<select><option selected>x</option></select>', '<select><option selected="">x</option></select>'),
			'commentaire' => array('<audio><!-- c --></audio>', '<audio><!-- c --></audio>'),
			'object et param' => array('<object data="a.swf"><param name="x" value="1"></object>', '<object data="a.swf"><param name="x" value="1"></object>'),
			'texte de repli' => array('<canvas>Texte &amp; repli</canvas>', '<canvas>Texte &amp; repli</canvas>'),
		);
	}

	#[DataProvider('medias')]
	public function testSerialisationMasterminds(string $html, string $attendu): void
	{
		$this->assertSame($attendu, wp2spip_html_serialiser(wp2spip_html_arbre_body_masterminds($html)->firstChild));
	}

	#[DataProvider('medias')]
	public function testSerialisationDuParseurChoisi(string $html, string $attendu): void
	{
		$this->assertSame($attendu, wp2spip_html_serialiser(wp2spip_html_arbre_body($html)->firstChild));
	}

	public function testNoeudsMasterminds(): void
	{
		$noeuds = iterator_to_array(wp2spip_html_arbre_body_masterminds('a<B>b</B><!-- c -->')->childNodes);
		$this->assertTrue(wp2spip_html_est_texte($noeuds[0]));
		$this->assertTrue(wp2spip_html_est_element($noeuds[1]));
		$this->assertSame('b', $noeuds[1]->localName);
		$this->assertFalse(wp2spip_html_est_texte($noeuds[2]));
		$this->assertFalse(wp2spip_html_est_element($noeuds[2]));
	}

	public static function reglesAjoutees(): array
	{
		return array(
			'section et rangée implicites' => array('<table><td>a</td></table>', '<table><tbody><tr><td>a</td></tr></tbody></table>'),
			'rangée implicite' => array('<table><tr><td>a</td></tr></table>', '<table><tbody><tr><td>a</td></tr></tbody></table>'),
			'paragraphe pour un </p> orphelin' => array('a</p>b', 'a<p></p>b'),
		);
	}

	#[DataProvider('reglesAjoutees')]
	public function testReglesHtml5Ajoutees(string $html, string $attendu): void
	{
		$body = wp2spip_html_arbre_body_masterminds($html);
		$this->assertSame($attendu, join('', array_map('wp2spip_html_serialiser', iterator_to_array($body->childNodes))));
	}

	public static function erreurs(): array
	{
		return array(
			'PHP 8.4' => array(true, true, false, ''),
			'Masterminds' => array(false, true, true, ''),
			'sans DOM' => array(false, false, true, 'Extension PHP DOM absente'),
			'sans Masterminds' => array(false, true, false, 'Bibliothèque Masterminds HTML5-PHP introuvable'),
		);
	}

	#[DataProvider('erreurs')]
	public function testErreur(bool $dom_natif, bool $dom, bool $masterminds, string $debut): void
	{
		$erreur = wp2spip_html_arbre_erreur($dom_natif, $dom, $masterminds);
		if ($debut === '') {
			$this->assertSame('', $erreur);
			return;
		}
		$this->assertStringStartsWith($debut, $erreur);
		$this->assertStringContainsString(PHP_VERSION, $erreur);
	}

	public function testParseurDecrit(): void
	{
		$this->assertMatchesRegularExpression('/^(Dom\\\\HTMLDocument|Masterminds HTML5-PHP) \(PHP /', wp2spip_html_arbre_parseur());
	}
}
```

- [ ] **Step 2 : vérifier qu'il échoue**

Run : `php8.4 vendor/bin/phpunit --testsuite unit --filter HtmlArbreTest`
Expected : erreur `Failed opening required '…/inc/wp2spip_html_arbre.php'`.

- [ ] **Step 3 : écrire `inc/wp2spip_html_masterminds.php`**

```php
<?php
/**
 * Constructeur d'arbre de Masterminds HTML5-PHP complété des règles HTML5 qu'il n'applique pas et dont l'absence
 * changerait le texte converti : section et rangée implicites d'un tableau, paragraphe vide pour un </p> orphelin
 *
 * Chargé par wp2spip_html_arbre_body_masterminds(), une fois Masterminds disponible.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

class Wp2spipArbreMasterminds extends Masterminds\HTML5\Parser\DOMTreeBuilder
{
	public function startTag($name, $attributes = array(), $selfClosing = false) {
		$nom = strtolower($name);
		$parent = ($this->current instanceof DOMElement) ? $this->current->localName : '';
		// Rangée ou cellule directement dans le tableau : dans un tbody implicite
		if (in_array($nom, array('tr', 'td', 'th')) and $parent === 'table') {
			parent::startTag('tbody');
			$parent = 'tbody';
		}
		// Cellule directement dans une section : dans une rangée implicite
		if (in_array($nom, array('td', 'th')) and in_array($parent, array('tbody', 'thead', 'tfoot'))) {
			parent::startTag('tr');
		}
		return parent::startTag($name, $attributes, $selfClosing);
	}

	public function endTag($name) {
		// </p> sans paragraphe ouvert : un paragraphe vide, qui sépare le texte qui l'entoure
		if (strtolower($name) === 'p' and !$this->isAncestor('p')) {
			parent::startTag('p');
		}
		parent::endTag($name);
	}
}
```

- [ ] **Step 4 : écrire `inc/wp2spip_html_arbre.php`**

```php
<?php
/**
 * Arbre HTML5 du convertisseur HTML → SPIP
 *
 * Dom\HTMLDocument à partir de PHP 8.4 ; sinon Masterminds HTML5-PHP (PHP 8.1 à 8.3), dont une copie est livrée
 * dans lib/masterminds-html5/. Seules ces fonctions connaissent les classes du parseur : inc/wp2spip_html.php
 * manipule les nœuds par elles et par les propriétés communes aux deux familles de classes (localName, nodeName,
 * childNodes, textContent, data, attributes, getAttribute(), hasAttribute()).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Éléments sans balise fermante
 */
const WP2SPIP_HTML_ARBRE_VIDES = array(
	'area', 'base', 'basefont', 'bgsound', 'br', 'col', 'embed', 'frame', 'hr', 'img', 'input', 'keygen', 'link',
	'meta', 'param', 'source', 'track', 'wbr',
);

/**
 * Éléments dont le texte est sérialisé sans échappement
 */
const WP2SPIP_HTML_ARBRE_TEXTE_BRUT = array('style', 'script', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext', 'noscript');

/**
 * Body du HTML analysé en document HTML5
 *
 * @param string $html
 * @return Dom\Element|DOMElement
 * @throws RuntimeException aucun parseur HTML5 disponible
 */
function wp2spip_html_arbre_body($html) {
	if (class_exists('Dom\HTMLDocument')) {
		return Dom\HTMLDocument::createFromString(wp2spip_html_arbre_source($html), LIBXML_NOERROR)->body;
	}
	if ($erreur = wp2spip_html_arbre_verifier()) {
		throw new RuntimeException($erreur);
	}
	return wp2spip_html_arbre_body_masterminds($html);
}

/**
 * Body du HTML analysé par Masterminds, quelle que soit la version de PHP (tests des deux parseurs)
 *
 * @param string $html
 * @return DOMElement
 */
function wp2spip_html_arbre_body_masterminds($html) {
	wp2spip_html_arbre_charger_masterminds();
	include_once __DIR__ . '/wp2spip_html_masterminds.php';
	$arbre = new Wp2spipArbreMasterminds(false, array('disable_html_ns' => true));
	$scanner = new Masterminds\HTML5\Parser\Scanner(wp2spip_html_arbre_source($html), 'UTF-8');
	(new Masterminds\HTML5\Parser\Tokenizer($scanner, $arbre, Masterminds\HTML5\Parser\Tokenizer::CONFORMANT_HTML))->parse();
	return $arbre->document()->getElementsByTagName('body')->item(0);
}

/**
 * @param string $html
 * @return string
 */
function wp2spip_html_arbre_source($html) {
	return '<!DOCTYPE html><html><body>' . $html . '</body></html>';
}

/**
 * Dossier de la copie de Masterminds livrée avec le plugin
 *
 * @return string
 */
function wp2spip_html_arbre_dossier_masterminds() {
	return dirname(__DIR__) . '/lib/masterminds-html5';
}

/**
 * Rend les classes Masterminds disponibles : une copie déjà chargée (autre plugin, Composer) est utilisée telle
 * quelle, sinon celle de lib/
 */
function wp2spip_html_arbre_charger_masterminds() {
	static $enregistre = false;
	if ($enregistre or class_exists('Masterminds\HTML5')) {
		return;
	}
	$enregistre = true;
	spl_autoload_register('wp2spip_html_arbre_autoload');
}

/**
 * @param string $classe
 */
function wp2spip_html_arbre_autoload($classe) {
	if (str_starts_with($classe, 'Masterminds\\')) {
		$fichier = wp2spip_html_arbre_dossier_masterminds() . '/src/' . str_replace('\\', '/', substr($classe, strlen('Masterminds\\'))) . '.php';
		if (is_file($fichier)) {
			require $fichier;
		}
	}
}

/**
 * @param mixed $noeud
 * @return bool
 */
function wp2spip_html_est_texte($noeud) {
	return ($noeud instanceof Dom\Text or $noeud instanceof DOMText);
}

/**
 * @param mixed $noeud
 * @return bool
 */
function wp2spip_html_est_element($noeud) {
	return ($noeud instanceof Dom\Element or $noeud instanceof DOMElement);
}

/**
 * HTML d'un nœud, contenu compris
 *
 * Dom\HTMLDocument::saveHtml() pour l'arbre natif ; sinon l'algorithme de sérialisation du standard HTML5, qui donne
 * le même résultat (le sérialiseur de Masterminds écrit autrement les attributs booléens et le SVG).
 *
 * @param mixed $noeud
 * @return string
 */
function wp2spip_html_serialiser($noeud) {
	if ($noeud instanceof Dom\Node) {
		return $noeud->ownerDocument->saveHtml($noeud);
	}
	if (wp2spip_html_est_texte($noeud)) {
		$parent = $noeud->parentNode;
		if (wp2spip_html_est_element($parent) and in_array($parent->localName, WP2SPIP_HTML_ARBRE_TEXTE_BRUT)) {
			return $noeud->data;
		}
		return str_replace(array('&', "\u{a0}", '<', '>'), array('&amp;', '&nbsp;', '&lt;', '&gt;'), $noeud->data);
	}
	if (!wp2spip_html_est_element($noeud)) {
		return ($noeud->nodeType === XML_COMMENT_NODE) ? '<!--' . $noeud->data . '-->' : '';
	}
	$html = '<' . $noeud->localName;
	foreach ($noeud->attributes as $attribut) {
		$html .= ' ' . $attribut->nodeName . '="' . str_replace(array('&', "\u{a0}", '"'), array('&amp;', '&nbsp;', '&quot;'), $attribut->value) . '"';
	}
	$html .= '>';
	if (in_array($noeud->localName, WP2SPIP_HTML_ARBRE_VIDES)) {
		return $html;
	}
	foreach ($noeud->childNodes as $enfant) {
		$html .= wp2spip_html_serialiser($enfant);
	}
	return $html . '</' . $noeud->localName . '>';
}

/**
 * Message si l'arbre HTML5 ne peut pas être construit
 *
 * @param bool $dom_natif Dom\HTMLDocument présent (PHP 8.4)
 * @param bool $dom extension DOM chargée
 * @param bool $masterminds Masterminds chargé, ou sa copie présente dans lib/
 * @return string message, ou '' si un parseur est disponible
 */
function wp2spip_html_arbre_erreur($dom_natif, $dom, $masterminds) {
	if ($dom_natif) {
		return '';
	}
	if (!$dom) {
		return 'Extension PHP DOM absente (PHP ' . PHP_VERSION . ') : elle est nécessaire à la conversion du HTML de Wordpress.';
	}
	if (!$masterminds) {
		return 'Bibliothèque Masterminds HTML5-PHP introuvable (lib/masterminds-html5/ du plugin) : elle est nécessaire à la conversion du HTML de Wordpress sous PHP ' . PHP_VERSION . ', avant PHP 8.4.';
	}
	return '';
}

/**
 * @return string message, ou '' si un parseur est disponible
 */
function wp2spip_html_arbre_verifier() {
	return wp2spip_html_arbre_erreur(
		class_exists('Dom\HTMLDocument'),
		class_exists('DOMDocument'),
		class_exists('Masterminds\HTML5') or is_file(wp2spip_html_arbre_dossier_masterminds() . '/src/HTML5.php')
	);
}

/**
 * Parseur utilisé, pour le mode verbeux de l'import
 *
 * @return string
 */
function wp2spip_html_arbre_parseur() {
	if (class_exists('Dom\HTMLDocument')) {
		return 'Dom\HTMLDocument (PHP ' . PHP_VERSION . ')';
	}
	wp2spip_html_arbre_charger_masterminds();
	$fichier = realpath((new ReflectionClass('Masterminds\HTML5'))->getFileName());
	$copie = realpath(wp2spip_html_arbre_dossier_masterminds());
	if ($copie and str_starts_with($fichier, $copie . DIRECTORY_SEPARATOR)) {
		return 'Masterminds HTML5-PHP (PHP ' . PHP_VERSION . '), copie du plugin, version ' . trim((string) file_get_contents($copie . '/VERSION'));
	}
	return 'Masterminds HTML5-PHP (PHP ' . PHP_VERSION . '), copie déjà chargée : ' . $fichier;
}
```

- [ ] **Step 5 : le test passe sous chaque PHP**

Run : `for v in 8.1 8.2 8.3 8.4; do php$v vendor/bin/phpunit --testsuite unit --filter HtmlArbreTest || break; done`
Expected : `OK (41 tests, …)` pour chaque version (16 + 16 + 1 + 3 + 4 + 1). Sous 8.4, `testSerialisationMasterminds` compare le sérialiseur de wp2spip aux valeurs de `saveHtml()`.

- [ ] **Step 6 : commit**

```bash
git add inc/wp2spip_html_arbre.php inc/wp2spip_html_masterminds.php tests/unit/HtmlArbreTest.php
git commit -m "Arbre HTML5 : Dom\\HTMLDocument sous PHP 8.4, Masterminds avant

Seul inc/wp2spip_html_arbre.php connaît le parseur. Masterminds est
complété des règles HTML5 qui changeraient le texte (tbody et tr
implicites, </p> orphelin) ; les médias sont sérialisés par l'algorithme
du standard, qui donne le même HTML que saveHtml()."
```

---

### Task 4 : convertisseur branché sur l'arbre

**Files:**
- Modify: `inc/wp2spip_html.php`
- Test: `tests/unit/HtmlTest.php`

**Interfaces:**
- Consumes : toutes les fonctions produites par la Task 3.
- Produces : `wp2spip_html_convertir_body($body, array $options): string` (conversion d'un `body` déjà analysé, utilisée par les tests) ; `wp2spip_html_spip()` inchangée pour ses appelants.

- [ ] **Step 1 : écrire les tests**

Dans `tests/unit/HtmlTest.php`, à la fin du tableau de `conversions()`, juste avant `'vingt espaces'`, ajouter :

```php
			'texte égaré dans un tableau' => array('<table><tr><td>a</td></tr>égaré</table>', "égaré\n\n| a |"),
			'texte égaré entre deux rangées' => array('<table><tr><td>a</td></tr>x<tr><td>b</td></tr></table>', "x\n\n| a |\n| b |"),
			'section et rangée implicites' => array('<table><td>a</td></table>', '| a |'),
			'cellules non fermées' => array('<table><tr><td>a<td>b<tr><td>c</table>', "| a | b |\n| c |"),
			'éléments de liste non fermés' => array('<ul><li>a<li>b</ul>', "-* a\n-* b"),
			'paragraphe fermé par un bloc' => array('<p>a<div>b</div>c', "a\n\n<div>b</div>\n\nc"),
			'br fermant' => array('a</br>b', "a\n_ b"),
			'svg et attribut à préfixe' => array('<p><svg viewBox="0 0 1 1"><use xlink:href="#i"></use></svg></p>', '<svg viewBox="0 0 1 1"><use xlink:href="#i"></use></svg>'),
			'iframe et attribut booléen' => array('<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen></iframe>', '<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen=""></iframe>'),
			'attribut à espace insécable et chevrons' => array('<img alt="a&nbsp;&lt;b&gt;">', '<img alt="a&nbsp;<b>">'),
			'commentaire dans un média' => array('<audio><!-- c --></audio>', '<audio><!-- c --></audio>'),
			'attribut à préfixe gardé dans un bloc' => array('<div xml:lang="fr"><p>a</p></div>', '<div xml:lang="fr">a</div>'),
```

Remplacer les deux cas de balises mal formées par leur version à trois valeurs (la troisième : résultat accepté par Masterminds, mise en forme non reconstruite, aucun texte perdu) :

```php
			'balises croisées' => array('<p>a <b>b <i>c</b> d</i></p>', 'a {{b {c} }} {d}', 'a {{b {c} }} d'),
			'balises non fermées' => array('<p>a <b>b<p>c', "a {{b}}\n\n{{c}}", "a {{b}}\n\nc"),
```

Remplacer `testConversion()` par :

```php
	#[DataProvider('conversions')]
	public function testConversion(string $html, string $attendu, ?string $attendu_masterminds = null): void
	{
		$this->assertSame(class_exists('Dom\HTMLDocument') ? $attendu : ($attendu_masterminds ?? $attendu), wp2spip_html_spip($html));
	}

	#[DataProvider('conversions')]
	public function testConversionMasterminds(string $html, string $attendu, ?string $attendu_masterminds = null): void
	{
		$this->assertSame($attendu_masterminds ?? $attendu, trim($html) === '' ? '' : wp2spip_html_convertir_body(wp2spip_html_arbre_body_masterminds($html), array()));
	}
```

- [ ] **Step 2 : vérifier l'échec**

Run : `php8.4 vendor/bin/phpunit --testsuite unit --filter HtmlTest`
Expected : `testConversionMasterminds` en erreur (`Call to undefined function wp2spip_html_arbre_body_masterminds()`) ; `testConversion` passe déjà sous 8.4 pour les 12 nouveaux cas, dont la valeur attendue est la sortie actuelle sous 8.4.

- [ ] **Step 3 : brancher le convertisseur**

Dans `inc/wp2spip_html.php` :

1. En-tête : remplacer la ligne `* Le HTML est analysé en arbre HTML5 (Dom\HTMLDocument, PHP 8.4), puis parcouru nœud par nœud : aucune expression` par `* Le HTML est analysé en arbre HTML5 (inc/wp2spip_html_arbre.php), puis parcouru nœud par nœud : aucune expression`, et ajouter après le bloc `if (!defined('_ECRIRE_INC_VERSION')) { return; }` :

```php

// Arbre HTML5 : seul inc/wp2spip_html_arbre.php connaît les classes du parseur
include_once __DIR__ . '/wp2spip_html_arbre.php';
```

2. Remplacer le corps de `wp2spip_html_spip()` après le test de texte vide :

```php
function wp2spip_html_spip($html, $options = array()) {
	$html = str_replace(array("\r\n", "\r"), "\n", (string) $html);
	if (trim($html) === '') {
		return '';
	}
	return wp2spip_html_convertir_body(wp2spip_html_arbre_body($html), $options);
}

/**
 * Body d'un HTML déjà analysé en raccourcis SPIP
 *
 * @param Dom\Element|DOMElement $body
 * @param array $options voir wp2spip_html_spip()
 * @return string
 */
function wp2spip_html_convertir_body($body, $options) {
	$contexte = array('autop' => $options['autop'] ?? true, 'pre' => false, 'gras' => false, 'italique' => false, 'listes' => '');
	return wp2spip_html_blocs($body, $contexte);
}
```

3. Remplacer chaque test de classe par les fonctions de l'arbre :

| Avant | Après |
| --- | --- |
| `if ($noeud instanceof Dom\Text) {` | `if (wp2spip_html_est_texte($noeud)) {` |
| `if (!$noeud instanceof Dom\Element) {` | `if (!wp2spip_html_est_element($noeud)) {` |
| `if ($enfant instanceof Dom\Text ? trim($enfant->data) === '' : !$enfant instanceof Dom\Element) {` | `if (wp2spip_html_est_texte($enfant) ? trim($enfant->data) === '' : !wp2spip_html_est_element($enfant)) {` |
| `$nom = ($enfant instanceof Dom\Element) ? strtolower($enfant->localName) : '';` | `$nom = wp2spip_html_est_element($enfant) ? strtolower($enfant->localName) : '';` |
| `if ($noeud instanceof Dom\Element and in_array(strtolower($noeud->localName), array('ul', 'ol'))) {` | `if (wp2spip_html_est_element($noeud) and in_array(strtolower($noeud->localName), array('ul', 'ol'))) {` |
| `if (!$partie instanceof Dom\Element) {` | `if (!wp2spip_html_est_element($partie)) {` |
| `return array_values(array_filter(iterator_to_array($parent->childNodes), fn($noeud) => $noeud instanceof Dom\Element));` | `return array_values(array_filter(iterator_to_array($parent->childNodes), fn($noeud) => wp2spip_html_est_element($noeud)));` |
| `wp2spip_html_ecrire($sortie, $noeud->ownerDocument->saveHtml($noeud));` | `wp2spip_html_ecrire($sortie, wp2spip_html_serialiser($noeud));` |
| `$balise .= ' ' . $attribut->name . '="'` (dans `wp2spip_html_ouvrante()`) | `$balise .= ' ' . $attribut->nodeName . '="'` |
| `$simple = !$tableau->querySelector('table, [rowspan]:not([rowspan="1"])');` | `$simple = !wp2spip_html_tableau_complexe($tableau);` |
| `$parties = array_map(fn($partie) => wp2spip_html_tableau_html($partie, $contexte), wp2spip_html_elements($element));` | `$parties = array_map(fn($partie) => wp2spip_html_tableau_html($partie, $contexte), wp2spip_html_parties_tableau($element));` |

Dans les commentaires `@param` et `@return`, remplacer `Dom\Node` et `Dom\Element` par `Dom\Node|DOMNode` et `Dom\Element|DOMElement`.

4. Dans `wp2spip_html_noeud()`, cas `'table'`, remplacer :

```php
			wp2spip_html_bloc($sortie, wp2spip_html_tableau($noeud, $contexte));
			return;
```

par :

```php
			// Contenu égaré dans la structure du tableau : avant le tableau, où le place l'analyse HTML5
			foreach (wp2spip_html_hors_tableau($noeud) as $egare) {
				wp2spip_html_noeud($egare, $sortie, $contexte);
			}
			wp2spip_html_bloc($sortie, wp2spip_html_tableau($noeud, $contexte));
			return;
```

5. Après `wp2spip_html_tableau_html()`, ajouter :

```php
/**
 * Enfants attendus des éléments de structure d'un tableau
 */
const WP2SPIP_HTML_PARTIES_TABLEAU = array(
	'table' => array('caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr'),
	'thead' => array('tr'),
	'tbody' => array('tr'),
	'tfoot' => array('tr'),
	'tr' => array('td', 'th'),
	'colgroup' => array('col'),
);

/**
 * Tableau qui ne s'écrit pas en raccourcis : tableau imbriqué, ou cellule fusionnée sur plusieurs lignes
 *
 * @param Dom\Element|DOMElement $element
 * @return bool
 */
function wp2spip_html_tableau_complexe($element) {
	foreach (wp2spip_html_elements($element) as $enfant) {
		if (
			strtolower($enfant->localName) === 'table'
			or ($enfant->hasAttribute('rowspan') and $enfant->getAttribute('rowspan') !== '1')
			or wp2spip_html_tableau_complexe($enfant)
		) {
			return true;
		}
	}
	return false;
}

/**
 * Éléments enfants d'une partie de tableau, sans le contenu égaré (rendu avant le tableau)
 *
 * @param Dom\Element|DOMElement $element
 * @return array
 */
function wp2spip_html_parties_tableau($element) {
	$attendus = WP2SPIP_HTML_PARTIES_TABLEAU[strtolower($element->localName)] ?? null;
	$elements = wp2spip_html_elements($element);
	return ($attendus === null) ? $elements : array_values(array_filter($elements, fn($enfant) => in_array(strtolower($enfant->localName), $attendus)));
}

/**
 * Contenu égaré dans la structure d'un tableau (texte ou élément hors d'une cellule) : l'analyse HTML5 de PHP 8.4
 * le place avant le tableau, Masterminds le laisse dedans
 *
 * @param Dom\Element|DOMElement $element
 * @return array nœuds, dans l'ordre du document
 */
function wp2spip_html_hors_tableau($element) {
	$attendus = WP2SPIP_HTML_PARTIES_TABLEAU[strtolower($element->localName)] ?? null;
	if ($attendus === null) {
		return array();
	}
	$egares = array();
	foreach ($element->childNodes as $enfant) {
		if (wp2spip_html_est_element($enfant) and in_array(strtolower($enfant->localName), $attendus)) {
			array_push($egares, ...wp2spip_html_hors_tableau($enfant));
		}
		elseif (wp2spip_html_est_element($enfant) or (wp2spip_html_est_texte($enfant) and trim($enfant->data) !== '')) {
			$egares[] = $enfant;
		}
	}
	return $egares;
}
```

6. Contrôle : `grep -n "Dom\\\\" inc/wp2spip_html.php | grep -v '^\s*[0-9]*:\s*\*'` ne renvoie plus que des lignes de commentaire.

- [ ] **Step 4 : les tests passent sous chaque PHP**

Run : `for v in 8.1 8.2 8.3 8.4; do php$v vendor/bin/phpunit --testsuite unit || break; done`
Expected : `OK` pour chaque version. Sous 8.4, `testConversion` compare toujours aux valeurs d'origine (aucune n'a été modifiée), et `testConversionMasterminds` passe les 71 cas par Masterminds.

Si le cas `attribut à préfixe gardé dans un bloc` diffère, vérifier `wp2spip_html_ouvrante()` (`nodeName`) avant de changer la valeur attendue : la sortie 8.4 fait référence.

- [ ] **Step 5 : intégration sous 8.4 et 8.1**

Run : `php8.4 vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php && php8.1 vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php`
Expected : `OK` les deux fois (`HtmlSpipTest`, `BlocsTest`, `LiensTest`…). Un échec sous 8.1 seulement est une différence de parseur : l'isoler en cas de `HtmlTest`, la corriger dans l'arbre ou dans le convertisseur, sans changer la sortie 8.4.

- [ ] **Step 6 : commit**

```bash
git add inc/wp2spip_html.php tests/unit/HtmlTest.php
git commit -m "Convertisseur HTML : passer par l'arbre HTML5 des deux parseurs

Nœuds reconnus et médias sérialisés par inc/wp2spip_html_arbre.php ;
tableaux complexes détectés par un parcours commun ; contenu égaré d'un
tableau écrit avant lui ; attributs recopiés avec leur nom qualifié.
Chaque cas de HtmlTest est aussi converti par Masterminds ; deux cas de
balises mal formées gardent sous Masterminds leur texte sans la mise en
forme rouverte."
```

---

### Task 5 : parseur vérifié par la commande d'import

**Files:**
- Modify: `spip-cli/WordpressImporter.php:75-135`

**Interfaces:**
- Consumes : `wp2spip_html_arbre_verifier()`, `wp2spip_html_arbre_parseur()` (Task 3).

- [ ] **Step 1 : vérifier le parseur avant tout traitement**

Dans `execute()`, juste après le bloc `if (!$spip_loaded) { … }`, ajouter :

```php
		
		// Analyseur HTML5 de la conversion : Dom\HTMLDocument (PHP 8.4), sinon Masterminds (lib/masterminds-html5/)
		include_spip('inc/wp2spip_html_arbre');
		if ($erreur = wp2spip_html_arbre_verifier()) {
			$output->writeln("<error>$erreur</error>");
			return Command::FAILURE;
		}
```

- [ ] **Step 2 : afficher le parseur en mode verbeux**

Juste après l'appel `$output->writeln(array( '<info>C’est parti pour importer ce Wordpress !</info>', … ));`, ajouter :

```php
		if ($output->isVerbose()) {
			$output->writeln(array('* <comment>Analyseur HTML</comment> : ' . wp2spip_html_arbre_parseur(), ''));
		}
```

- [ ] **Step 3 : essayer sur un SPIP préparé sous PHP 8.1**

Depuis la racine du dépôt :

```bash
depot=$(pwd)
source tests/integration/environnement.sh
essai=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-parseur.XXXXXX")
bin=$(mktemp -d); ln -s "$(command -v php8.1)" "$bin/php"
PATH="$bin:$PATH" outils/preparer_spip.sh --spip "$essai/spip" --wordpress "$WP6" --spip-cli "$depot/vendor/bin/spip" >"$essai/preparation.log" 2>&1; echo "préparation : $?"
(cd "$essai/spip" && PATH="$bin:$PATH" "$depot/vendor/bin/spip" --no-ansi wordpress:importer "$WP6" --info -v)
```

Expected : préparation `0` ; la sortie contient `* Analyseur HTML : Masterminds HTML5-PHP (PHP 8.1.34), copie du plugin, version 2.11.0`.

Run : `(cd "$essai/spip" && php8.4 "$depot/vendor/bin/spip" --no-ansi wordpress:importer "$WP6" --info -v | grep Analyseur)`
Expected : `* Analyseur HTML : Dom\HTMLDocument (PHP 8.4.25)`.

- [ ] **Step 4 : essayer sans la bibliothèque**

```bash
mv "$essai/spip/plugins/wp2spip/lib/masterminds-html5" "$essai/masterminds-de-cote"
(cd "$essai/spip" && PATH="$bin:$PATH" "$depot/vendor/bin/spip" --no-ansi wordpress:importer "$WP6"); echo "code : $?"
mv "$essai/masterminds-de-cote" "$essai/spip/plugins/wp2spip/lib/masterminds-html5"
rm -rf "$essai" "$bin"
```

Expected : `Bibliothèque Masterminds HTML5-PHP introuvable (lib/masterminds-html5/ du plugin) : … sous PHP 8.1.34, avant PHP 8.4.` puis `code : 1`, sans traitement lancé.

- [ ] **Step 5 : commit**

```bash
git add spip-cli/WordpressImporter.php
git commit -m "Import : arrêt si aucun analyseur HTML5, analyseur affiché avec -v

Sans Dom\\HTMLDocument ni Masterminds, l'import s'arrête avant tout
traitement avec la version de PHP et la dépendance manquante, au lieu
d'échouer au premier contenu converti."
```

---

### Task 6 : matrice PHP et validation complète

**Files:**
- Create: `tests/matrice_php.sh`
- Modify: `tests/integration/valider.sh:25`
- Modify: `composer.json` (script `tests-matrice`)

**Interfaces:**
- Consumes : tout ce qui précède ; `tests/integration/environnement.sh` (WP6, WP7, WP_REEL, ESSAIS…).

- [ ] **Step 1 : écrire `tests/matrice_php.sh`**

```bash
#!/bin/bash
# Suites de wp2spip sous chaque version de PHP de la cible
# Usage : tests/matrice_php.sh [--import] [version…]   (défaut : 8.1 8.2 8.3 8.4)
#   Pour chaque version, un dossier temporaire placé en tête du PATH fait de « php » cette version : Composer,
#   PHPUnit, SPIP-Cli (#!/usr/bin/env php) et les scripts qu'ils appellent utilisent tous le même PHP.
#   --import ajoute l'import complet des deux WordPress de test (tests/integration/valider.sh, environnement.sh).
set -uo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
import=
versions=()
for argument in "$@"; do
	case "$argument" in
		--import) import=1 ;;
		*) versions+=("$argument") ;;
	esac
done
[ ${#versions[@]} -gt 0 ] || versions=(8.1 8.2 8.3 8.4)

echecs=()
for version in "${versions[@]}"; do
	executable=$(command -v "php$version") || { echecs+=("$version (php$version absent)"); continue; }
	bin=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-php$version.XXXXXX")
	ln -s "$executable" "$bin/php"
	(
		export PATH="$bin:$PATH"
		cd "$racine" || exit 1
		echo "=== PHP $(php -r 'echo PHP_VERSION;')"
		composer check-platform-reqs --no-interaction >/dev/null || { echo "ECHEC : composer check-platform-reqs"; exit 1; }
		vendor/bin/phpunit --testsuite unit || exit 1
		vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php || exit 1
		if [ -n "$import" ]; then
			source tests/integration/environnement.sh
			tests/integration/valider.sh "$WP6" && tests/integration/valider.sh "$WP7" || exit 1
		fi
	) || echecs+=("$version")
	rm -rf "$bin"
done

if [ ${#echecs[@]} -gt 0 ]; then
	echo "ECHEC : PHP ${echecs[*]}"
	exit 1
fi
echo "OK : PHP ${versions[*]}"
```

Dans `composer.json`, ajouter aux `scripts` : `"tests-matrice": "bash tests/matrice_php.sh --import"`.

- [ ] **Step 2 : transmettre la version de SPIP à la validation**

Dans `tests/integration/valider.sh`, ligne 25, remplacer :

```bash
"$racine/outils/preparer_spip.sh" --spip "$spip" --wordpress "$wordpress" --spip-cli "$spip_cli" --importer >"$travail/import.log" 2>&1
```

par :

```bash
"$racine/outils/preparer_spip.sh" --spip "$spip" --wordpress "$wordpress" --spip-cli "$spip_cli" ${VERSION_SPIP:+--version-spip "$VERSION_SPIP"} --importer >"$travail/import.log" 2>&1
```

et compléter l'en-tête d'usage par la ligne : `#   VERSION_SPIP=X.Y : version de SPIP préparée (défaut de outils/preparer_spip.sh : 4.4).`

- [ ] **Step 3 : lancer la matrice complète**

Run : `chmod +x tests/matrice_php.sh && tests/matrice_php.sh --import`
Expected : `OK : PHP 8.1 8.2 8.3 8.4`. Chaque import WP 6.9 et 7.1 est conforme à `tests/integration/references/theme-unit-test.tsv` : la copie du plugin par le préparateur (sans `vendor/`) charge Masterminds depuis `lib/` sous 8.1 à 8.3.

Un écart d'export sous 8.1 à 8.3 seulement : lire `ecarts.diff`, réduire le cas à un HTML minimal, l'ajouter à `HtmlTest`, corriger (arbre ou convertisseur) et relancer. Ne jamais mettre à jour la référence pour un écart propre à Masterminds.

- [ ] **Step 4 : tests du préparateur sous 8.1**

Run : `bin=$(mktemp -d); ln -s "$(command -v php8.1)" "$bin/php"; PATH="$bin:$PATH" tests/preparation/tester_preparer_spip.sh; rm -rf "$bin"`
Expected : 0 échec, dont `MySQL distincte : wp2spip copié avec lib/, sans tests/, …`.

- [ ] **Step 5 : comparer le corpus réel entre 8.1 et 8.4**

Depuis la racine du dépôt :

```bash
depot=$(pwd)
source tests/integration/environnement.sh
corpus=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-corpus.XXXXXX")
for v in 8.1 8.4; do
	bin=$(mktemp -d); ln -s "$(command -v php$v)" "$bin/php"
	PATH="$bin:$PATH" outils/preparer_spip.sh --spip "$corpus/spip-$v" --wordpress "$WP_REEL" --spip-cli "$depot/vendor/bin/spip" --importer >"$corpus/import-$v.log" 2>&1; echo "import $v : $?"
	(cd "$corpus/spip-$v" && PATH="$bin:$PATH" "$depot/vendor/bin/spip" --no-ansi php:eval "include '$depot/tests/integration/exporter_import.php';") >"$corpus/export-$v.tsv"
	rm -rf "$bin"
done
diff "$corpus/export-8.4.tsv" "$corpus/export-8.1.tsv" >"$corpus/ecarts.diff"; echo "lignes d'écart : $(grep -c '^[<>]' "$corpus/ecarts.diff")"
```

Expected : `import 8.1 : 0`, `import 8.4 : 0`. Classer chaque écart : (a) mise en forme non reconstruite sur balises mal formées, accepté ; (b) autre écart, à réduire en cas de `HtmlTest` et à corriger comme au Step 3. Vérifier qu'aucun mot ne manque : pour chaque ligne différente, comparer `tr -cs '[:alnum:]' '\n'` des deux versions. Consigner le classement (nombre d'écarts par catégorie, sans nom de site ni contenu) dans la section « Résultats » de ce plan. Supprimer `$corpus` ensuite.

- [ ] **Step 6 : combinaisons SPIP annoncées**

Le manifeste annonce SPIP `[4.2.0;4.4.*]`. Pour SPIP 4.2 et 4.3, lire la plage PHP du cœur, puis valider chaque combinaison de cette plage avec 8.1 à 8.4 :

```bash
source tests/integration/environnement.sh
for spip in 4.2 4.3; do
	d=$(mktemp -d); vendor/bin/spip --no-ansi core:telecharger spip -R "$spip" -d "$d/spip" >/dev/null 2>&1
	echo "SPIP $spip : $(grep -hoE "define\('_PHP_(MIN|MAX)', '[^']+'\)" "$d/spip/ecrire/inc_version.php" | tr '\n' ' ')"
	rm -rf "$d"
done
```

Pour chaque version PHP comprise dans la plage d'un SPIP : `VERSION_SPIP=<4.2|4.3> tests/matrice_php.sh --import <version PHP>`.
Décision : une combinaison qui échoue sans correction possible dans wp2spip fait réduire `compatibilite` du `paquet.xml` (Task 7) aux versions de SPIP validées. Consigner le résultat dans « Résultats ».

- [ ] **Step 7 : commit**

```bash
git add tests/matrice_php.sh tests/integration/valider.sh composer.json docs/superpowers/plans/2026-10-09-compatibilite-php8.1.md
git commit -m "Tests : matrice PHP 8.1 à 8.4 et version de SPIP de la validation

tests/matrice_php.sh lance les suites et l'import complet avec chaque PHP
(composer tests-matrice) ; VERSION_SPIP choisit le SPIP préparé par
valider.sh. Résultats de la matrice, du corpus réel et des combinaisons
SPIP consignés dans le plan."
```

---

### Task 7 : manifestes et documentation de wp2spip

**Files:**
- Modify: `paquet.xml:26-27`
- Modify: `readme.md:15`, `readme.md:122`
- Modify: `docs/site/installer/prerequis.md:7-8`, `docs/site/installer/installation.md:16`, `docs/site/installer/developpement.md:3`, `docs/site/comprendre/conversion.md:25`, `docs/site/migrer/depanner.md:16`, `docs/site/migrer/preparer.md:47`

- [ ] **Step 1 : `paquet.xml`**

Remplacer :

```xml
	<!-- Conversion du HTML par Dom\HTMLDocument (inc/wp2spip_html.php) -->
	<necessite nom="php" compatibilite="[8.4.0;]" />
```

par :

```xml
	<!-- Conversion du HTML : Dom\HTMLDocument (PHP 8.4), sinon Masterminds HTML5-PHP de lib/ (inc/wp2spip_html_arbre.php) -->
	<necessite nom="php" compatibilite="[8.1.0;]" />
```

Si la Task 6 a réduit les versions de SPIP validées, changer aussi `compatibilite="[4.2.0;4.4.*]"` de la balise `<paquet>`.

- [ ] **Step 2 : `readme.md`**

Ligne 15, remplacer `Il demande PHP 8.4 : le HTML de Wordpress est analysé par l'analyseur HTML5 de PHP (\`Dom\HTMLDocument\`) pour être converti en raccourcis SPIP (voir « Conversion du HTML »).` par :

```markdown
Il demande PHP 8.1 ou plus récent, avec l'extension DOM : le HTML de Wordpress est analysé en arbre HTML5 pour être converti en raccourcis SPIP (voir « Conversion du HTML »), par l'analyseur de PHP (`Dom\HTMLDocument`) à partir de PHP 8.4, et avant par la bibliothèque Masterminds HTML5-PHP livrée dans `lib/` (licence MIT).
```

Ligne 122, remplacer `Les tests demandent PHP 8.4 et Composer. Depuis la racine du dépôt :` par :

```markdown
Les tests demandent PHP 8.1 ou plus récent et Composer ; le verrou est résolu pour PHP 8.1 (PHPUnit 10.5). `composer tests-matrice` lance les suites et l'import complet avec chaque PHP de 8.1 à 8.4 installé (`php8.1` à `php8.4`). Après une mise à jour de `masterminds/html5`, `outils/copier_masterminds.sh` recopie la bibliothèque dans `lib/`. Depuis la racine du dépôt :
```

- [ ] **Step 3 : site documentaire**

| Fichier | Remplacer | Par |
| --- | --- | --- |
| `prerequis.md:7` | `**PHP ≥ 8.4**, avec l’extension DOM et \`Dom\HTMLDocument\` pour la conversion HTML5` | `**PHP ≥ 8.1**, avec l’extension DOM ; l’arbre HTML5 vient de \`Dom\HTMLDocument\` à partir de PHP 8.4, et avant de la bibliothèque Masterminds HTML5-PHP livrée avec le plugin` |
| `prerequis.md:8` | `PHP **≥ 8.4.1**, Composer,` | `PHP **≥ 8.1** (PHPUnit 10.5), Composer,` |
| `installation.md:16` | `3. Vérifier PHP 8.4 et son extension DOM,` | `3. Vérifier PHP 8.1 ou plus récent et son extension DOM,` |
| `developpement.md:3` | `Le plugin demande PHP **≥ 8.4** pour \`Dom\HTMLDocument\` ; le verrou documenté contient PHPUnit 13.4.1, qui impose **PHP ≥ 8.4.1** pour les tests.` | `Le plugin demande PHP **≥ 8.1** ; le verrou est résolu pour PHP 8.1 et contient PHPUnit 10.5. \`composer tests-matrice\` rejoue les suites et l’import complet avec chaque PHP de 8.1 à 8.4 installé.` |
| `conversion.md:25` | `à partir de l’arbre HTML5 construit par \`Dom\HTMLDocument\` de PHP 8.4.` | `à partir de l’arbre HTML5 construit par \`Dom\HTMLDocument\` à partir de PHP 8.4, et avant par Masterminds HTML5-PHP. Sur du HTML mal formé, Masterminds peut ne pas rouvrir un gras ou un italique interrompu : le texte est gardé, sa mise en forme est à vérifier.` |
| `depanner.md:16` | `\| Erreur \`Dom\HTMLDocument\` introuvable \| Vérifier PHP ≥ 8.4 et l’extension DOM sur le PHP CLI réellement utilisé ; installer Sale ne remplace pas ce prérequis \|` | `\| Analyseur HTML5 indisponible \| Vérifier l’extension DOM du PHP CLI réellement utilisé (PHP ≥ 8.1) et, avant PHP 8.4, la présence de \`lib/masterminds-html5/\` dans le plugin ; \`-v\` affiche l’analyseur utilisé \|` |
| `preparer.md:47` | `avec PHP 8.4 et DOM.` | `avec PHP 8.1 ou plus récent et l’extension DOM.` |

- [ ] **Step 4 : construire le site**

Run : `/tmp/wp2spip-pages.VsbR1q/docs-venv/bin/python -m mkdocs build --strict -d "$(mktemp -d)"` (ou tout environnement Python ≥ 3.11 avec `requirements-docs.txt`)
Expected : code 0, aucun avertissement.

Run : `grep -rn "8\.4" readme.md paquet.xml docs/site | grep -v "à partir de PHP 8.4\|avant PHP 8.4\|8.1 à 8.4\|4\.4"`
Expected : aucune ligne qui annonce encore PHP 8.4 obligatoire.

- [ ] **Step 5 : commit**

```bash
git add paquet.xml readme.md docs/site
git commit -m "wp2spip demande PHP 8.1

Manifeste et documentation alignés sur la matrice validée : PHP 8.1 à 8.4,
Dom\\HTMLDocument à partir de 8.4, Masterminds HTML5-PHP avant."
```

- [ ] **Step 6 : point d'arrêt — livraison de wp2spip**

Demander au mainteneur : PR sur `epilibre` depuis une branche `php81/wp2spip`, fusion, puis synchronisation de `compat-spip-4.4` sur git.spip.net. La Task 8 en dépend : les extensions résolvent `technova69/wp2spip` `dev-compat-spip-4.4` depuis git.spip.net.

---

### Task 8 : extensions `wp2spip_acf` et `wp2spip_yoast`

**Files (dans chaque dépôt, `/src/wordpress/wp2spip_acf` puis `/src/wordpress/wp2spip_yoast`) :**
- Modify: `composer.json`
- Modify ou Create: `composer.lock`
- Modify: `paquet.xml` (ligne `<necessite nom="php" …>`)

**Interfaces:**
- Consumes : `technova69/wp2spip` `dev-compat-spip-4.4` publié avec `"php": ">=8.1"` (Task 7, Step 6).

- [ ] **Step 1 : `composer.json`**

Dans `require`, remplacer `"php": ">=8.4"` par `"php": ">=8.1"`. Dans `require-dev`, remplacer `"phpunit/phpunit": "^13.0"` par `"phpunit/phpunit": "^10.5"`. Ajouter dans `config` (le créer s'il n'existe pas) :

```json
        "platform": {
            "php": "8.1.0"
        }
```

- [ ] **Step 2 : verrou résolu sous 8.1**

Run : `php8.1 $(command -v composer) update --no-interaction && php8.1 $(command -v composer) check-platform-reqs`
Expected : `phpunit/phpunit (10.5.…)`, `technova69/wp2spip (dev-compat-spip-4.4 …)` au commit synchronisé, tous les prérequis `success`. Pour `wp2spip_yoast`, ce verrou est créé : il rend la résolution vérifiable comme celle de `wp2spip_acf`.

- [ ] **Step 3 : `paquet.xml`**

Remplacer `<necessite nom="php" compatibilite="[8.4.0;]" />` par `<necessite nom="php" compatibilite="[8.1.0;]" />`.

- [ ] **Step 4 : suites sous chaque PHP**

```bash
rm -rf .phpunit.cache && composer install-spip-test
for v in 8.1 8.2 8.3 8.4; do
	bin=$(mktemp -d); ln -s "$(command -v php$v)" "$bin/php"
	(export PATH="$bin:$PATH"; composer check-platform-reqs >/dev/null && vendor/bin/phpunit --testsuite unit && vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php) || echo "ECHEC PHP $v"
	rm -rf "$bin"
done
```

Expected : aucune ligne `ECHEC`. `wp2spip_acf` convertit ses valeurs par `wp2spip_html_spip()` : sous 8.1 à 8.3, Masterminds est chargé depuis `vendor/technova69/wp2spip`.

- [ ] **Step 5 : commit (dans chaque dépôt)**

```bash
git add composer.json composer.lock paquet.xml
git commit -m "PHP 8.1 : manifeste, Composer et PHPUnit 10.5

wp2spip convertit le HTML de PHP 8.1 à 8.4 ; le verrou est résolu pour
PHP 8.1 (config.platform.php) et les suites passent de 8.1 à 8.4."
```

- [ ] **Step 6 : point d'arrêt — livraison des extensions**

Demander au mainteneur l'autorisation de pousser `main` de chaque extension sur git.spip.net (SSH). Ensuite, vérifier sur le SPIP de test de wp2spip qu'une extension copiée s'active sous PHP 8.1 : `spip plugins:activer wp2spip_acf -y` sans erreur de version.

---

## Résultats

_À compléter pendant la Task 6 : matrice, classement des écarts du corpus réel (nombres par catégorie, sans nom de site ni contenu), combinaisons PHP/SPIP validées._
