# Sous-projet 4 — hiérarchie des pages : plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** garder dans SPIP la hiérarchie des pages WordPress (page parente, ordre des sous-pages) par des liens a2a de type `sous_page` entre pages uniques, a2a étant installé par l'import quand le contenu a des pages enfants.

**Architecture :** `inc/wp2spip_plugins.php` gagne le critère `wp2spip_where_pages_enfants()` et déclare a2a parmi les plugins requis : le mécanisme du sous-projet 3 (`WordpressImporter::verifier_plugins()`) le télécharge, l'active et relance l'import. Un nouveau traitement, `importer_hierarchie_pages`, placé juste après `importer_articles`, ajoute le type `sous_page` à la configuration d'a2a et crée un lien de chaque page parente vers chacune de ses pages enfants, dans l'ordre WordPress, par la fonction d'a2a ; il échoue (code `1`) si une page manque ou si a2a n'a pas créé un lien. Aucun squelette n'est fourni.

**Tech Stack :** PHP 8, SPIP 4.4, a2a 4.2.1, SPIP-Cli (version actuelle, sans nouveau correctif), bash pour les validations.

**Spec :** `docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md`. Plan général : `2026-10-08-wp2spip-developpement.md`, Task 14.

## Global Constraints

- wp2spip ne fournit ni ne modifie de squelette : il crée la structure de données ; le readme donne deux boucles d'exemple, vérifiées sur un SPIP de test.
- Les pages restent des pages uniques (plugin Pages), avec leur identifiant (= ID WordPress) et leur adresse.
- Un lien par page enfant, de la page parente (`id_article`) vers l'enfant (`id_article_lie`), `type_liaison = sous_page` ; rang = ordre WordPress (`menu_order`, puis `post_title`, puis `ID`).
- Type `sous_page` ajouté à `a2a/types_liaisons` avec le libellé « Sous-page (WordPress) », sans toucher aux autres types ni aux autres réglages d'a2a.
- a2a requis dès qu'une page a pour parent une page : téléchargé, activé, puis relance, par le mécanisme existant ; `<utilise nom="a2a" compatibilite="[4.2.0;]" />`.
- Échec (code `1`) si une page enfant ou parente manque dans SPIP, ou si un lien `sous_page` n'existe pas après l'appel d'a2a ; tous les liens possibles sont créés avant.
- SPIP-Cli n'est pas modifié.
- Ne jamais nommer le site réel de test dans les fichiers versionnés ou les messages de commit ; messages de commit sans trailer.
- Style du code : celui de wp2spip (tabulations, `array()`, commentaires en français).

## Ce que le prototype a établi

Le code de ce plan a été mis au point sur un prototype : SPIP 4.4.28 préparé par `outils/preparer_spip.sh` avec le WordPress 6.9.

1. L'import installe a2a (`Plugin requis : A2A (a2a), pour 13 pages enfants.`), crée 13 liens pour 5 pages parentes, et les rangs suivent l'ordre WordPress (page 1725 : 1133, 1134, 501, 155, 156). Relancé, il ne crée rien (`13 liens de sous-pages déjà présents.`).
2. a2a crée le lien par `action_a2a_lier_article_dist($id_article_cible, $id_article_source, $type_liaison)` (`action/a2a.php`) ; il n'en crée pas, sans le dire, si les deux pages sont déjà liées par un autre type et que `a2a/types_differents` est désactivé (réglage par défaut : non défini). Le traitement le détecte : code `1`, page signalée.
3. Les boucles d'exemple de la spec, calculées dans un squelette d'essai, donnent les sous-pages dans l'ordre et la page parente.
4. Le critère `post_parent IN (SELECT ID FROM wp_posts WHERE post_type = "page")` fonctionne en MySQL (bases WordPress) et en SQLite (base temporaire du test « sans page enfant »). `_DIR_DB` n'est défini qu'à la première connexion SQLite : le test retombe sur `_DIR_ETC . 'bases/'`.
5. Dépôt injoignable : `Plugins requis par le contenu Wordpress non installés (dépôt de plugins impossible à ajouter) : albums accesrestreint a2a.`, code `1`, aucun traitement lancé.
6. Le `wp-config.php` du WordPress réel de test contient des accès refusés par le MySQL local : la préparation depuis un dossier vide passe par un miroir du WordPress dont seul le `wp-config.php` change (Task 4, Step 4).

## Environnement

Variables de `tests/integration/environnement.sh` (plan général, « Environnement de test ») ; `ESSAIS` (plan du sous-projet 11). Exporter d'abord `WP2SPIP`, puis `source "$WP2SPIP/tests/integration/environnement.sh"`. Les commandes longues (préparations, import du site réel) se lancent de préférence en arrière-plan, sortie dans un journal sous `$ESSAIS` ou `$SAUVEGARDES`.

Références d'avant le sous-projet, produites à la validation du sous-projet 3 : `$SAUVEGARDES/export-wp6-reference.tsv` (WordPress 6.9, SPIP de test) et `$SAUVEGARDES/export-reel-blocs.tsv` (site réel, SPIP de test en SQLite).

---

### Task 1 : détection d'a2a et traitement `importer_hierarchie_pages`

**Files :**
- Create : `tests/integration/tester_hierarchie_pages.php`
- Create : `wp2spip/importer_hierarchie_pages.php`
- Modify : `inc/wp2spip_plugins.php` (avant le bloc de `wp2spip_plugins_requis()` ; dans cette fonction)
- Modify : `spip-cli/WordpressImporter.php` (liste `$traitements_disponibles`)
- Modify : `paquet.xml` (après `<utilise nom="oembed" />`)

**Interfaces :**
- Consumes : `wp2spip_plugins_requis(string $base): array` et le mécanisme `WordpressImporter::verifier_plugins()` (sous-projet 3).
- Produces : `wp2spip_where_pages_enfants(): array` (conditions sur `wp_posts`) ; entrée `a2a` de `wp2spip_plugins_requis()` (`nom` A2A, `table` spip_articles_lies, `dist` false, `raison` « N pages enfants ») ; traitement `wp2spip_importer_hierarchie_pages_dist($command)` : `null` si rien à faire ou réussite, `false` en échec ; lit `$command->base`, écrit sur `$command->output`.

- [ ] **Step 1 : écrire le test « sans page enfant »**

`tests/integration/tester_hierarchie_pages.php` :

```php
<?php
/**
 * Teste, sur un SPIP où wp2spip est actif, la hiérarchie des pages quand le Wordpress n'a pas de page enfant
 *
 * Une base SQLite temporaire, déclarée comme base externe, imite un Wordpress dont les pages n'ont pas
 * de parent (et dont une page a pour parent un article) : a2a ne doit pas être requis, et le traitement
 * importer_hierarchie_pages, appelé directement, ne doit rien faire. La base est retirée à la fin.
 *
 * Usage, depuis le site SPIP :
 *   spip php:eval 'include "<wp2spip>/tests/integration/tester_hierarchie_pages.php";'
 * Affiche OK, ou ECHEC et la liste des écarts avec le code de sortie 1.
 */
include_spip('inc/wp2spip_plugins');
include_spip('wp2spip/importer_hierarchie_pages');

$connexion = 'wp2spip_test_sans_enfant';
// Dossier des bases SQLite : _DIR_DB n'est défini qu'à la première connexion SQLite
$fichier_base = (defined('_DIR_DB') ? _DIR_DB : _DIR_ETC . 'bases/') . $connexion . '.sqlite';
$fichier_connexion = _DIR_CONNECT . $connexion . '.php';
$echecs = array();

$pdo = new PDO('sqlite:' . $fichier_base);
$pdo->exec('CREATE TABLE wp_posts (ID INTEGER PRIMARY KEY, post_type TEXT, post_status TEXT, post_password TEXT, post_parent INTEGER, menu_order INTEGER, post_title TEXT, post_content TEXT)');
$pdo->exec('CREATE TABLE wp_comments (comment_ID INTEGER PRIMARY KEY, comment_approved TEXT, comment_type TEXT)');
$pdo->exec("INSERT INTO wp_posts VALUES (1, 'post', 'publish', '', 0, 0, 'Article', ''), (2, 'page', 'publish', '', 0, 0, 'Page', ''), (3, 'page', 'publish', '', 1, 0, 'Page sous un article', '')");
$pdo = null;
file_put_contents($fichier_connexion, "<?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\nspip_connect_db('', '', '', '', '$connexion', 'sqlite3', 'spip', '', '');\n");

try {
	$requis = wp2spip_plugins_requis($connexion);
	echo (isset($requis['a2a']) ? 'ECHEC' : 'ok   ') . " a2a non requis sans page enfant\n";
	if (isset($requis['a2a'])) {
		$echecs[] = 'a2a requis alors qu’aucune page n’a pour parent une page';
	}

	$commande = new stdClass();
	$commande->base = $connexion;
	$commande->output = new Symfony\Component\Console\Output\BufferedOutput();
	$compter_liens = fn() => sql_showtable('spip_articles_lies', true) ? sql_countsel('spip_articles_lies') : 0;
	$liens_avant = $compter_liens();
	$retour = wp2spip_importer_hierarchie_pages_dist($commande);
	$ok = ($retour === null and $commande->output->fetch() === '' and $compter_liens() == $liens_avant);
	echo ($ok ? 'ok   ' : 'ECHEC') . " traitement sans effet sans page enfant\n";
	if (!$ok) {
		$echecs[] = 'importer_hierarchie_pages a agi alors qu’aucune page n’a pour parent une page';
	}
}
finally {
	@unlink($fichier_connexion);
	@unlink($fichier_base);
}

if ($echecs) {
	echo "ECHEC\n- " . join("\n- ", $echecs) . "\n";
	exit(1);
}
echo "OK\n";
```

- [ ] **Step 2 : lancer le test, qui échoue**

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_hierarchie_pages.php';"); echo "code $?"`
(`$ESSAIS/spip-blocs` : SPIP du sous-projet 3, wp2spip en lien vers le dépôt.)
Expected : erreur fatale `Call to undefined function wp2spip_where_pages_enfants()`, code non nul ; `ls "$ESSAIS/spip-blocs/config"` ne montre plus `wp2spip_test_sans_enfant.php` (retiré par le `finally`).

- [ ] **Step 3 : critère et plugin requis**

Dans `inc/wp2spip_plugins.php`, juste avant le bloc de documentation de `wp2spip_plugins_requis()` (`/**` puis ` * Plugins requis par le contenu Wordpress`, ` *`, ` * Le pipeline wp2spip_plugins_requis …`) :

```php
/**
 * Pages dont le parent est une page : celles que lie importer_hierarchie_pages
 *
 * @return array conditions sur wp_posts
 */
function wp2spip_where_pages_enfants() {
	return array(
		'post_type = "page"',
		'post_parent > 0',
		'post_parent IN (SELECT ID FROM wp_posts WHERE post_type = "page")',
	);
}
```

et dans `wp2spip_plugins_requis()`, après le bloc `if ($nb = sql_countsel('wp_comments', …)) { $requis['forum'] = …; }` :

```php
	if ($nb = sql_countsel('wp_posts', wp2spip_where_pages_enfants(), '', '', $base)) {
		$requis['a2a'] = array('nom' => 'A2A', 'table' => 'spip_articles_lies', 'dist' => false, 'raison' => "$nb pages enfants");
	}
```

- [ ] **Step 4 : traitement**

`wp2spip/importer_hierarchie_pages.php` :

```php
<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Relie chaque page enfant à sa page parente par un lien a2a de type sous_page
 *
 * Dans Wordpress, une page a une page parente (post_parent) et un ordre parmi ses sœurs (menu_order, puis titre).
 * importer_articles en fait des pages uniques, sans hiérarchie : on garde ici la parenté et l'ordre par un lien
 * de la page parente vers la page enfant, dont le rang suit l'ordre Wordpress. La parente d'une page se retrouve
 * en lisant le lien à l'envers. Aucun squelette n'est fourni : le site exploite ces liens à sa façon.
 */
function wp2spip_importer_hierarchie_pages_dist($command) {
	include_spip('inc/wp2spip_plugins');

	// Pages enfants, dans l'ordre Wordpress des pages d'un même parent
	$wp_pages = sql_allfetsel(
		'ID, post_parent',
		'wp_posts',
		wp2spip_where_pages_enfants(),
		'',
		'post_parent, menu_order, post_title, ID',
		'',
		'',
		$command->base
	);
	if (!$wp_pages) {
		return;
	}

	// La commande active a2a quand il est requis : inactif malgré tout, l'import ne continue pas
	// en perdant la hiérarchie des pages sans le dire
	include_spip('inc/plugin');
	if (!test_plugin_actif('a2a')) {
		$command->output->writeln('<error>' . count($wp_pages) . ' pages enfants à relier à leur page parente, mais le plugin a2a n’est pas actif.</error>');
		return false;
	}

	// Le type de liaison est ajouté à la configuration d'a2a : il reste défini après la désinstallation de wp2spip
	include_spip('inc/config');
	$types = lire_config('a2a/types_liaisons');
	if (!is_array($types)) {
		$types = array();
	}
	if (!isset($types['sous_page'])) {
		$types['sous_page'] = 'Sous-page (WordPress)';
		ecrire_config('a2a/types_liaisons', $types);
	}

	include_spip('action/a2a');
	$nb_crees = 0;
	$nb_deja = 0;
	$parents = array();
	$absentes = array();
	$conflits = array();
	foreach ($wp_pages as $wp_page) {
		$id_wordpress = intval($wp_page['ID']);
		$id_wordpress_parent = intval($wp_page['post_parent']);
		$id_page = intval(sql_getfetsel('id_article', 'spip_articles', 'id_wordpress = ' . $id_wordpress));
		$id_parent = intval(sql_getfetsel('id_article', 'spip_articles', 'id_wordpress = ' . $id_wordpress_parent));
		if (!$id_page or !$id_parent) {
			$absentes[] = "$id_wordpress (parent $id_wordpress_parent)";
			continue;
		}
		$lien = array('id_article = ' . $id_parent, 'id_article_lie = ' . $id_page, 'type_liaison = ' . sql_quote('sous_page'));
		if (sql_countsel('spip_articles_lies', $lien)) {
			$nb_deja++;
			$parents[$id_parent] = true;
			continue;
		}
		// a2a ne dit pas s'il a créé le lien : sans liaisons multiples, il n'en crée pas
		// si les deux pages sont déjà liées par un autre type
		action_a2a_lier_article_dist($id_page, $id_parent, 'sous_page');
		if (!sql_countsel('spip_articles_lies', $lien)) {
			$conflits[] = "$id_wordpress (parent $id_wordpress_parent)";
			continue;
		}
		$nb_crees++;
		$parents[$id_parent] = true;
	}

	$command->output->writeln("$nb_crees liens de sous-pages créés (a2a, type sous_page), pour " . count($parents) . ' pages parentes.');
	if ($nb_deja) {
		$command->output->writeln("$nb_deja liens de sous-pages déjà présents.");
	}
	if ($absentes) {
		$command->output->writeln('<error>Pages enfants ou parentes absentes de SPIP, pas de lien : ' . join(', ', $absentes) . '.</error>');
	}
	if ($conflits) {
		$command->output->writeln('<error>Liens sous_page non créés par a2a, les pages étant déjà liées par un autre type (liaisons multiples désactivées dans la configuration d’a2a) : ' . join(', ', $conflits) . '.</error>');
	}
	if ($absentes or $conflits) {
		return false;
	}
}
```

Dans `spip-cli/WordpressImporter.php`, liste `$traitements_disponibles` : ajouter `'importer_hierarchie_pages',` juste après `'importer_articles',`.

Dans `paquet.xml`, après `<utilise nom="oembed" />` :

```xml
	<utilise nom="a2a" compatibilite="[4.2.0;]" />
```

Puis : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" cache:vider)`.

- [ ] **Step 5 : lancer le test**

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_hierarchie_pages.php';"); echo "code $?"`
Expected : `ok    a2a non requis sans page enfant`, `ok    traitement sans effet sans page enfant`, `OK`, `code 0` ; plus de `wp2spip_test_sans_enfant` dans `config/` ni dans `config/bases/`.

- [ ] **Step 6 : commit**

```bash
git add tests/integration/tester_hierarchie_pages.php wp2spip/importer_hierarchie_pages.php inc/wp2spip_plugins.php spip-cli/WordpressImporter.php paquet.xml
git commit -m "Hiérarchie des pages : a2a requis par les pages enfants, traitement importer_hierarchie_pages (liens sous_page dans l'ordre Wordpress)"
```

---

### Task 2 : vérificateur et export

**Files :**
- Modify : `tests/integration/verifier_identifiants.php` (en-tête ; avant `$url_wordpress = …`)
- Modify : `tests/integration/exporter_import.php` (avant le tri final)

**Interfaces :**
- Consumes : `wp2spip_where_pages_enfants()` (Task 1).
- Produces : vérificateur : écarts `page <id> : sous-pages …, attendues …`, `page <id> : rangs de sous-pages en double`, `hiérarchie des pages : N pages enfants, mais a2a n’est pas actif` ; ligne `N pages enfants vérifiées` ; export : lignes `sous_page<TAB>article#wp<parent><TAB>article#wp<enfant><TAB><rang>`.

- [ ] **Step 1 : contrôle de la hiérarchie**

Dans l'en-tête de `tests/integration/verifier_identifiants.php`, après la ligne ` *   si le plugin est actif, publiés dans leur zone.` :

```php
 * - hiérarchie des pages : un lien a2a sous_page de chaque page parente vers chacune de ses
 *   pages enfants, dans l'ordre Wordpress.
```

et juste avant `$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', $base);` :

```php
// Hiérarchie des pages : chaque page dont le parent est une page a exactement un lien a2a sous_page
// depuis sa page parente, et les liens d'une page parente suivent l'ordre Wordpress (menu_order, titre, ID)
include_spip('inc/wp2spip_plugins');
$attendus = array();
foreach (sql_allfetsel('ID, post_parent', 'wp_posts', wp2spip_where_pages_enfants(), '', 'post_parent, menu_order, post_title, ID', '', '', $base) as $wp_page) {
	$attendus[intval($wp_page['post_parent'])][] = intval($wp_page['ID']);
}
if (test_plugin_actif('a2a')) {
	$trouves = array();
	$rangs = array();
	foreach (sql_allfetsel('id_article, id_article_lie, rang', 'spip_articles_lies', 'type_liaison = "sous_page"', '', 'id_article, rang') as $lien) {
		$trouves[intval($lien['id_article'])][] = intval($lien['id_article_lie']);
		$rangs[intval($lien['id_article'])][] = intval($lien['rang']);
	}
	foreach (array_unique(array_merge(array_keys($attendus), array_keys($trouves))) as $id_parent) {
		if (($attendus[$id_parent] ?? array()) !== ($trouves[$id_parent] ?? array())) {
			$echecs[] = "page $id_parent : sous-pages " . join(',', $trouves[$id_parent] ?? array()) . ', attendues ' . join(',', $attendus[$id_parent] ?? array());
		}
		elseif (count(array_unique($rangs[$id_parent] ?? array())) != count($rangs[$id_parent] ?? array())) {
			$echecs[] = "page $id_parent : rangs de sous-pages en double";
		}
	}
}
elseif ($attendus) {
	$echecs[] = 'hiérarchie des pages : ' . array_sum(array_map('count', $attendus)) . ' pages enfants, mais a2a n’est pas actif';
}
echo array_sum(array_map('count', $attendus)) . " pages enfants vérifiées\n";
```

- [ ] **Step 2 : le vérificateur détecte l'absence de liens**

`$ESSAIS/spip-blocs` a été importé sans ce sous-projet : 13 pages enfants, a2a absent (il garde aussi l'écart volontaire de l'article 1783, ajouté au sous-projet 3 : seules les lignes de la hiérarchie sont lues ici).

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | grep "pages enfants vérifiées\|hiérarchie des pages\|^ECHEC"); echo "code ${PIPESTATUS[0]}"`
Expected : `13 pages enfants vérifiées`, `ECHEC`, `- hiérarchie des pages : 13 pages enfants, mais a2a n’est pas actif` ; code `1`.

- [ ] **Step 3 : liens dans l'export**

Dans `tests/integration/exporter_import.php`, juste avant `$lignes = array_map(function ($ligne) { return join("\t", $ligne); }, $lignes);` :

```php
if (test_plugin_actif('a2a')) {
	// Hiérarchie des pages : page parente, page enfant, rang
	foreach (sql_allfetsel('*', 'spip_articles_lies', 'type_liaison = "sous_page"') as $l) {
		$lignes[] = array('sous_page', 'article#' . $wp($articles, $l['id_article']), 'article#' . $wp($articles, $l['id_article_lie']), $l['rang']);
	}
}
```

- [ ] **Step 4 : commit**

```bash
git add tests/integration/verifier_identifiants.php tests/integration/exporter_import.php
git commit -m "Vérificateur et export : liens sous_page de la hiérarchie des pages"
```

---

### Task 3 : import, relance et échecs sur un SPIP d'essai

**Files :** aucun fichier versionné (SPIP d'essai et squelette d'essai sous `$ESSAIS`).

- [ ] **Step 1 : SPIP d'essai sans import, et son état sauvegardé**

```bash
rm -rf "$ESSAIS/spip-hierarchie"
SPIP_ADMIN_PASS=Essai-Hierarchie-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-hierarchie" --wordpress "$WP6" --spip-cli "$SPIP_CLI" --wp2spip lien > "$ESSAIS/hierarchie-preparation.log" 2>&1; echo "code $?"
tar czf "$ESSAIS/hierarchie-vierge.tgz" -C "$ESSAIS/spip-hierarchie" config/bases IMG local
```

Expected : `code 0`.

Pour revenir à cet état (le dossier est vérifié avant toute suppression) :

```bash
remettre_hierarchie() {
	local s="$ESSAIS/spip-hierarchie"
	[ -f "$s/ecrire/inc_version.php" ] || { echo "pas un SPIP : $s"; return 1; }
	rm -rf "$s/config/bases" "$s/IMG" "$s/local" "$s/plugins/auto/albums" "$s/plugins/auto/accesrestreint" "$s/plugins/auto/a2a"
	rm -f "$s/config/mes_options.php"
	find "$s/tmp/cache" -mindepth 1 -delete
	tar xzf "$ESSAIS/hierarchie-vierge.tgz" -C "$s"
}
```

- [ ] **Step 2 : import complet, puis relance**

```bash
cd "$ESSAIS/spip-hierarchie"
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" > "$ESSAIS/hierarchie-import.log" 2>&1; echo "code $?"
grep "Plugin requis\|sous-pages\|relancé" "$ESSAIS/hierarchie-import.log" | sed 's/<[^>]*>//g'
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 2
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" | grep "^sous_page.*wp1725"
"$SPIP_CLI" php:eval 'echo json_encode(lire_config("a2a/types_liaisons")), "\n";'
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" -t importer_hierarchie_pages 2>&1 | grep "sous-pages"; echo "code ${PIPESTATUS[0]}"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_hierarchie_pages.php';" | tail -n 1
cd "$WP2SPIP"
```

Expected : `code 0` ; `Plugin requis : Albums (albums), pour 6 contenus avec une galerie.`, `Plugin requis : Accès restreint (accesrestreint), pour 1 contenus privés ou protégés.`, `Plugin requis : A2A (a2a), pour 13 pages enfants.`, `Plugins requis installés : l’import est relancé.`, `13 liens de sous-pages créés (a2a, type sous_page), pour 5 pages parentes.` ; `13 pages enfants vérifiées`, `OK` ; pour la page 1725, les enfants 1133 (rang 1), 1134 (2), 501 (3), 155 (4), 156 (5) ; `{"sous_page":"Sous-page (WordPress)"}` ; à la relance `0 liens de sous-pages créés (a2a, type sous_page), pour 5 pages parentes.`, `13 liens de sous-pages déjà présents.`, `code 0` ; `OK` (test « sans page enfant », a2a actif).

- [ ] **Step 3 : boucles d'exemple**

Squelette d'essai, non versionné, dans le SPIP d'essai :

```bash
mkdir -p "$ESSAIS/spip-hierarchie/squelettes"
cat > "$ESSAIS/spip-hierarchie/squelettes/essai_hierarchie.html" <<'EOF'
<BOUCLE_page(ARTICLES){id_article}{statut?}>
Page : #TITRE
<B_sous_pages>Sous-pages :
<BOUCLE_sous_pages(ARTICLES_LIES){id_article}{type_liaison=sous_page}{par rang}>
	<BOUCLE_sous_page(ARTICLES){id_article=#ID_ARTICLE_LIE}>- #TITRE (rang #_sous_pages:RANG)
</BOUCLE_sous_page>
</BOUCLE_sous_pages>
<BOUCLE_parente(ARTICLES_LIES){id_article_lie=#ID_ARTICLE}{type_liaison=sous_page}>
	<BOUCLE_page_parente(ARTICLES){id_article}>Page parente : #TITRE</BOUCLE_page_parente>
</BOUCLE_parente>
</BOUCLE_page>
EOF
cd "$ESSAIS/spip-hierarchie"
for id in 174 173; do
	"$SPIP_CLI" php:eval "echo trim(preg_replace('/\n\s*\n+/', \"\n\", recuperer_fond('essai_hierarchie', array('id_article' => $id)))), \"\n---\n\";"
done
rm -r squelettes
cd "$WP2SPIP"
```

Expected : `Page : Level 1`, sous-pages `Level 2 (rang 1)`, `Level 2a (rang 2)`, `Level 2b (rang 3)`, pas de page parente ; puis `Page : Level 2`, sous-pages `Level 3`, `Level 3a`, `Level 3b` (rangs 1 à 3), `Page parente : Level 1`.

- [ ] **Step 4 : garde du traitement, appelé directement avec a2a désactivé**

```bash
cd "$ESSAIS/spip-hierarchie"
"$SPIP_CLI" plugins:desactiver a2a -y > /dev/null
"$SPIP_CLI" php:eval 'include_spip("wp2spip/importer_hierarchie_pages"); $c = new stdClass(); $c->base = "wordpress"; $c->output = new Symfony\Component\Console\Output\BufferedOutput(); var_export(wp2spip_importer_hierarchie_pages_dist($c)); echo "\n", strip_tags($c->output->fetch());'
"$SPIP_CLI" plugins:activer a2a -y > /dev/null
"$SPIP_CLI" plugins:lister --short --raw --no-dist | grep -c a2a
cd "$WP2SPIP"
```

Expected : `false`, `13 pages enfants à relier à leur page parente, mais le plugin a2a n’est pas actif.`, puis `1` (a2a réactivé).

- [ ] **Step 5 : conflit avec un lien d'un autre type**

Le lien `sous_page` de 174 vers 173 devient un lien non typé ; les liaisons multiples d'a2a sont désactivées (réglage par défaut).

```bash
cd "$ESSAIS/spip-hierarchie"
"$SPIP_CLI" php:eval 'var_export(lire_config("a2a/types_differents")); echo "\n"; sql_updateq("spip_articles_lies", array("type_liaison" => ""), "id_article = 174 and id_article_lie = 173 and type_liaison = \"sous_page\"");'
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" -t importer_hierarchie_pages > "$ESSAIS/hierarchie-conflit.log" 2>&1; echo "code $?"
grep "sous-pages\|non créés" "$ESSAIS/hierarchie-conflit.log" | sed 's/<[^>]*>//g'
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 2
cd "$WP2SPIP"
```

Expected : `NULL` ; `code 1` ; `12 liens de sous-pages déjà présents.`, `Liens sous_page non créés par a2a, les pages étant déjà liées par un autre type (liaisons multiples désactivées dans la configuration d’a2a) : 173 (parent 174).` ; vérificateur : `ECHEC`, `- page 174 : sous-pages 742,744, attendues 173,742,744`.

- [ ] **Step 6 : page parente absente de SPIP**

```bash
cd "$ESSAIS/spip-hierarchie"
"$SPIP_CLI" php:eval 'sql_updateq("spip_articles_lies", array("type_liaison" => "sous_page"), "id_article = 174 and id_article_lie = 173"); sql_delete("spip_articles_lies", "id_article = 1809"); sql_delete("spip_articles", "id_article = 1809");'
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" -t importer_hierarchie_pages > "$ESSAIS/hierarchie-absente.log" 2>&1; echo "code $?"
grep "absentes" "$ESSAIS/hierarchie-absente.log" | sed 's/<[^>]*>//g'
cd "$WP2SPIP"
```

Expected : `code 1`, `Pages enfants ou parentes absentes de SPIP, pas de lien : 1811 (parent 1809).`

- [ ] **Step 7 : installation impossible, dépôt injoignable**

```bash
remettre_hierarchie
cd "$ESSAIS/spip-hierarchie"
"$SPIP_CLI" php:eval 'include_spip("inc/svp_depoter_distant"); foreach (sql_allfetsel("id_depot", "spip_depots") as $depot) { svp_supprimer_depot($depot["id_depot"]); }'
printf "<?php\ndefine('_WP2SPIP_DEPOT_SVP', 'https://plugins.spip.net/depots/inexistant-wp2spip.xml');\n" > config/mes_options.php
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" > "$ESSAIS/hierarchie-echec.log" 2>&1; echo "code $?"
grep -c "Lancement du traitement" "$ESSAIS/hierarchie-echec.log"
grep "non installés\|telecharger a2a" "$ESSAIS/hierarchie-echec.log"
rm config/mes_options.php
cd "$WP2SPIP"
```

Expected : `code 1`, `0`, `Plugins requis par le contenu Wordpress non installés (dépôt de plugins impossible à ajouter) : albums accesrestreint a2a.`, `  spip plugins:svp:telecharger a2a -y`.

---

### Task 4 : validation

**Files :** aucun (journaux sous `$ESSAIS` et `$SAUVEGARDES`).

- [ ] **Step 1 : WordPress 6.9 et 7.1, depuis un dossier vide**

```bash
for n in 6 7; do
	wordpress=WP$n
	rm -rf "$ESSAIS/spip-hierarchie-wp$n"
	SPIP_ADMIN_PASS=Essai-Hierarchie-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-hierarchie-wp$n" --wordpress "${!wordpress}" --spip-cli "$SPIP_CLI" --importer > "$ESSAIS/hierarchie-wp$n.log" 2>&1; echo "wp$n : code $?"
	grep "Plugin requis : A2A\|sous-pages" "$ESSAIS/hierarchie-wp$n.log" | sed 's/<[^>]*>//g'
	(cd "$ESSAIS/spip-hierarchie-wp$n" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1)
	(cd "$ESSAIS/spip-hierarchie-wp$n" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_hierarchie_pages.php';" | tail -n 1)
	(cd "$ESSAIS/spip-hierarchie-wp$n" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_blocs.php';" | tail -n 1)
done
```

Expected, pour chacun : `code 0`, `Plugin requis : A2A (a2a), pour 13 pages enfants.`, `13 liens de sous-pages créés (a2a, type sous_page), pour 5 pages parentes.`, `OK` trois fois.

- [ ] **Step 2 : SPIP de test des WordPress 6.9 et 7.1**

Leur `plugins/auto` et leur dépôt existent depuis le sous-projet 3 : a2a y est téléchargé.

```bash
for n in 6 7; do
	site=SPIP_WP$n; base=BASE_WP$n; wordpress=WP$n
	"$WP2SPIP/tests/integration/remise_a_zero.sh" "${!site}" "$SAUVEGARDES/vierge-wp$n-v2.sql.gz" "${!base}"
	(cd "${!site}" && "$SPIP_CLI" wordpress:importer "${!wordpress}" --no-ansi > "$SAUVEGARDES/import-wp$n-hierarchie.log" 2>&1; echo "wp$n : code $?")
	grep "Plugin requis : A2A\|sous-pages" "$SAUVEGARDES/import-wp$n-hierarchie.log"
	(cd "${!site}" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1)
	(cd "${!site}" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp$n-hierarchie.tsv")
done
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6-hierarchie.tsv" | grep '^[<>]' | cut -f1 | sort | uniq -c
```

Expected : `code 0`, ligne A2A, 13 liens et `OK` pour chacun ; le diff WordPress 6.9 : seulement `13 > sous_page`. En faire la nouvelle référence, en gardant l'ancienne :

```bash
cp "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6-reference-avant-hierarchie.tsv"
cp "$SAUVEGARDES/export-wp6-hierarchie.tsv" "$SAUVEGARDES/export-wp6-reference.tsv"
```

- [ ] **Step 3 : site réel, sur son SPIP de test**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
cd "$SPIP_REEL_SQLITE"
"$SPIP_CLI" wordpress:importer "$WP_REEL" --no-ansi > "$SAUVEGARDES/import-reel-hierarchie.log" 2>&1; echo "code $?"
grep "Plugin requis\|sous-pages\|Création de plugins/auto\|Aucun dépôt" "$SAUVEGARDES/import-reel-hierarchie.log"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 2
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-reel-hierarchie.tsv"
diff "$SAUVEGARDES/export-reel-blocs.tsv" "$SAUVEGARDES/export-reel-hierarchie.tsv" | grep '^[<>]' | cut -f1 | sort | uniq -c
cd "$WP2SPIP"
```

Expected : `code 0` ; `Plugin requis : A2A (a2a), pour 18 pages enfants.` (avec, au premier import sur ce SPIP, la création de `plugins/auto` et du dépôt) ; `18 liens de sous-pages créés (a2a, type sous_page), pour 5 pages parentes.` ; `18 pages enfants vérifiées`, `OK` ; diff : seulement `18 > sous_page`.

- [ ] **Step 4 : site réel, depuis un dossier vide**

Si les accès MySQL du `wp-config.php` de `$WP_REEL` sont refusés en local, le script de préparation s'arrête à la lecture de la base. Monter alors un miroir du WordPress dont seul le `wp-config.php` change, avec les accès de la base externe déclarée dans `$SPIP_REEL_SQLITE` (rien n'est versionné, aucun accès n'est affiché) :

```bash
m="$ESSAIS/wp-reel-local"
rm -rf "$m"; mkdir "$m"
for f in "$WP_REEL"/* "$WP_REEL"/.htaccess; do
	[ -e "$f" ] && [ "$(basename "$f")" != wp-config.php ] && ln -s "$f" "$m/"
done
php -r '
	define("_ECRIRE_INC_VERSION", 1);
	function spip_connect_db($hote, $port, $login, $pass, $base, ...$reste) { $GLOBALS["acces"] = array("DB_HOST" => $hote, "DB_USER" => $login, "DB_PASSWORD" => $pass, "DB_NAME" => $base); }
	include $argv[1];
	$config = file_get_contents($argv[2]);
	foreach ($GLOBALS["acces"] as $nom => $valeur) {
		$config = preg_replace("/define\(\s*[\x27\"]" . $nom . "[\x27\"]\s*,\s*[\x27\"][^\x27\"]*[\x27\"]\s*\)/", "define(\x27$nom\x27, " . var_export($valeur, true) . ")", $config);
	}
	file_put_contents($argv[3], $config);
' "$SPIP_REEL_SQLITE/config/wordpress.php" "$WP_REEL/wp-config.php" "$m/wp-config.php"
```

Puis :

```bash
wordpress_reel="$WP_REEL"; [ -d "$ESSAIS/wp-reel-local" ] && wordpress_reel="$ESSAIS/wp-reel-local"
rm -rf "$ESSAIS/spip-reel"
SPIP_ADMIN_PASS=Essai-Hierarchie-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-reel" --wordpress "$wordpress_reel" --spip-cli "$SPIP_CLI" --importer > "$ESSAIS/reel.log" 2>&1; echo "code $?"
grep "Plugin requis\|sous-pages" "$ESSAIS/reel.log" | sed 's/<[^>]*>//g'
(cd "$ESSAIS/spip-reel" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 3)
```

Expected : `code 0` ; `Plugin requis : Accès restreint (accesrestreint), pour 98 contenus privés ou protégés.`, `Plugin requis : A2A (a2a), pour 18 pages enfants.` ; `18 liens de sous-pages créés (a2a, type sous_page), pour 5 pages parentes.` ; `98 contenus privés ou protégés vérifiés`, `18 pages enfants vérifiées`, `OK`.

- [ ] **Step 5 : tests de la préparation**

Run : `tests/preparation/tester_preparer_spip.sh --complet`
Expected : `0 échec(s)`.

---

### Task 5 : documentation

**Files :**
- Modify : `readme.md` (« Plugins requis » ; nouvelle section avant « Pour les devs »)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 2.2, § 2.6, nouveau § 3.9, § 6)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md` (`Statut`)
- Modify : `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 14)

- [ ] **Step 1 : readme**

Dans la section « Plugins requis », remplacer `Forum s'il y a des commentaires ; puis il se relance.` par `Forum s'il y a des commentaires, a2a s'il y a des pages enfants ; puis il se relance.`

Juste avant `## Pour les devs` :

````markdown
## Hiérarchie des pages
Les pages Wordpress deviennent des pages uniques (plugin Pages), sans rubrique. Leur hiérarchie est gardée par des liens du plugin [a2a](https://contrib.spip.net/Le-plugin-a2a-pour-lier-des-articles), que l'import installe s'il y a des pages enfants : un lien de type `sous_page` de chaque page parente vers chacune de ses pages enfants, dont le rang suit l'ordre Wordpress (ordre de la page, puis titre). wp2spip ne fournit pas de squelette : à chaque site d'exploiter ces liens, par exemple ainsi :

```html
<!-- Sous-pages d'une page, dans l'ordre Wordpress -->
<BOUCLE_sous_pages(ARTICLES_LIES){id_article}{type_liaison=sous_page}{par rang}>
	<BOUCLE_sous_page(ARTICLES){id_article=#ID_ARTICLE_LIE}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_sous_page>
</BOUCLE_sous_pages>

<!-- Page parente d'une page -->
<BOUCLE_parente(ARTICLES_LIES){id_article_lie=#ID_ARTICLE}{type_liaison=sous_page}>
	<BOUCLE_page_parente(ARTICLES){id_article}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_page_parente>
</BOUCLE_parente>
```

Le type `sous_page` est ajouté à la configuration d'a2a ; il y reste après la désinstallation de wp2spip. Si une page manque dans SPIP, ou si a2a ne crée pas un lien (les deux pages déjà liées par un autre type, liaisons multiples désactivées), le traitement `importer_hierarchie_pages` échoue et la commande retourne le code de sortie 1.

````

- [ ] **Step 2 : spec d'ensemble, spec et plan**

- spec d'ensemble, § 2.2 : insérer `6. importer_hierarchie_pages` après `5. importer_articles`, et renuméroter la suite (`7. importer_acces`, `8. importer_polyhierarchie`, `9. importer_commentaires`) ;
- spec d'ensemble, § 2.6, après la ligne `accesrestreint` : `| a2a | \`utilise\` ; téléchargé et activé par wp2spip si le WordPress a des pages enfants (sous-projet 4) | hiérarchie des pages → liens \`sous_page\` entre pages uniques |` ;
- spec d'ensemble, après le § 3.8 : nouveau `### 3.9 \`importer_hierarchie_pages\`` (exécuté juste après `importer_articles`) : un lien a2a `sous_page` de chaque page parente vers chacune de ses pages enfants, rang = ordre WordPress ; type ajouté à la configuration d'a2a ; échec (code `1`) si une page manque ou si a2a ne crée pas un lien ; détail dans la spec du sous-projet 4 ;
- spec d'ensemble, § 6 : ligne du sous-projet 4 marquée **réalisé** ;
- spec du sous-projet 4 : `Statut : design validé, à planifier.` devient `Statut : réalisé (plan : \`docs/superpowers/plans/2026-10-08-wp2spip-hierarchie-pages.md\`).` ;
- plan général, Task 14 : cocher plan, réalisation et validation, avec le résultat (WordPress 6.9 et 7.1 depuis un dossier vide et sur leurs SPIP de test : 13 liens ; site réel : 18 liens, rangs par titre ; vérificateur et tests à OK).

- [ ] **Step 3 : commit**

```bash
git add readme.md docs/superpowers
git commit -m "Sous-projet 4 réalisé : documentation de la hiérarchie des pages"
```
