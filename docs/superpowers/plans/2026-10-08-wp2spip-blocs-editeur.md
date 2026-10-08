# Sous-projet 3 — blocs de l'éditeur : plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** convertir les blocs de l'éditeur WordPress et les galeries en texte SPIP propre (structure gardée, albums pour les galeries, documents pour les médias), et faire télécharger et activer par l'import les plugins que le contenu demande (Albums, Accès restreint, Forum).

**Architecture :** `inc/wp2spip_blocs.php` analyse le contenu en arbre de blocs (comme `parse_blocks()` de WordPress) et convertit chaque bloc **avant sale** ; ce qui est déjà au format SPIP est protégé par des marqueurs `wp2spipbloc<N>` pendant le passage par sale, puis réinséré. `inc/wp2spip_plugins.php` détecte les plugins requis avec les critères des traitements ; `WordpressImporter::verifier_plugins()` les installe par des sous-commandes SPIP-Cli, puis relance l'import dans un processus neuf.

**Tech Stack :** PHP 8, SPIP 4.4, sale, Albums 4.4, Accès restreint, SPIP-Cli (version actuelle, sans nouveau correctif), bash pour les validations.

**Spec :** `docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md`. Plan général : `2026-10-08-wp2spip-developpement.md`, Task 13.

## Global Constraints

- Conversion **avant sale** ; un contenu sans `<!-- wp:` n'est modifié que pour ses raccourcis `[gallery]`.
- Un média se retrouve **d'abord par son fichier**, puis par son identifiant ; introuvable : HTML d'origine gardé, compté au bilan.
- Galerie : un album par galerie, toujours écrit `<albumN>` ; titre du contenu, suivi de « (galerie n) » s'il en a plusieurs ; statut `publie`, date du contenu ; documents dans l'ordre (`rang_lien`) ; album lié à l'article.
- Mise en page : balise d'origine avec ses seules classes `wp-block-…` ; styles, autres classes, `aria-*`, `role`, `data-*` retirés.
- Contenu embarqué : URL seule sur sa ligne, puis la légende.
- Plugins requis (Albums si galerie, Accès restreint si contenu privé ou protégé, Forum si commentaire) installés **avant tout traitement, quels que soient les traitements demandés** ; un appel de `plugins:svp:telecharger` par plugin ; méta `<préfixe>_base_version` effacée après téléchargement ; une seule relance (`WP2SPIP_RELANCE=1`) ; échec : code `1`, aucun traitement, commandes à lancer à la main.
- SPIP-Cli n'est pas modifié.
- Ne jamais nommer le site réel de test dans les fichiers versionnés ou les messages de commit ; messages de commit sans trailer.
- Style du code : celui de wp2spip (tabulations, `array()`, commentaires en français).

## Ce que le prototype a établi

Le code de ce plan a été mis au point dans un prototype, sur des SPIP 4.4.28 préparés par `outils/preparer_spip.sh` : WordPress 6.9 et 7.1 importés complets avec installation automatique des plugins, vérificateur et test des conversions à OK, échec simulé (dépôt absent) correct. Constats intégrés au plan et à la spec :

1. **SVP note la version du schéma sans créer les tables.** `plugins:svp:telecharger` installe le plugin dans son propre processus, qui ne connaît pas encore ses tables : la méta `<préfixe>_base_version` est écrite, `maj_tables` ne crée rien (constaté pour Albums, Accès restreint, polyhier ; `plugins:maj:bdd` ne fait ensuite plus rien). Le script de préparation n'y échappait que parce que l'installation de wp2spip met à jour toutes les tables. Remède sans toucher à SPIP-Cli : effacer la méta, puis `plugins:maj:bdd` dans un processus neuf, qui installe vraiment le plugin.
2. **Les plugins de `plugins-dist` sont toujours actifs en SPIP 4** : Forum ne peut pas être désactivé ; le scénario « Forum désactivé » de la spec est retiré.
3. **`proc_open()` avec `STDOUT`** fait écrire le sous-processus au début d'un fichier de sortie redirigé, par-dessus ce qui précède : les sous-commandes héritent des descripteurs (aucun n'est passé).
4. Couvertures à l'ancien format : texte dans le HTML propre du bloc (`<p class="wp-block-cover-text">`), pas dans un enfant. Légende d'un tableau collée à sa dernière ligne : syntaxe de tableau SPIP cassée. Le vérificateur ne peut pas interdire `style=` (blocs « HTML personnalisé », contenus classiques) ; `<!-- wp:` peut apparaître légitimement dans un bloc de code.
5. Les SPIP de test de `tests/integration/` n'ont pas de dépôt SVP : l'import y échouerait désormais (galeries du contenu de test). Nouveaux états vierges v3, avec le dépôt.

## Environnement

Variables de `tests/integration/environnement.sh` (plan général, « Environnement de test ») ; `ESSAIS` et `BASE_PREP_MYSQL` (plan du sous-projet 11). Exporter d'abord `WP2SPIP`, puis `source "$WP2SPIP/tests/integration/environnement.sh"`. Les commandes longues (import du site réel, préparations) se lancent de préférence en arrière-plan, sortie dans un journal sous `$ESSAIS` ou `$SAUVEGARDES`.

---

### Task 1 : références d'avant et états vierges v3 des SPIP de test

**Files :** aucun fichier versionné (sauvegardes sous `$SAUVEGARDES`).

**Interfaces :**
- Produces : `$SAUVEGARDES/export-reel-sqlite-avant-blocs.tsv` (export du site réel importé par la version actuelle) ; `$SAUVEGARDES/vierge-wp6-v3.sql.gz` et `vierge-wp7-v3.sql.gz` (états v2 plus le dépôt SVP).

- [ ] **Step 1 : export du site réel avec la version actuelle**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
cd "$SPIP_REEL_SQLITE"
"$SPIP_CLI" wordpress:importer "$WP_REEL" --no-ansi > "$SAUVEGARDES/import-reel-avant-blocs.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-reel-sqlite-avant-blocs.tsv"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1
cd "$WP2SPIP"
```

Expected : `code 0`, `OK`.

- [ ] **Step 2 : états vierges v3 des SPIP des WordPress 6.9 et 7.1, avec le dépôt SVP**

```bash
for n in 6 7; do
	site=SPIP_WP$n; base=BASE_WP$n
	"$WP2SPIP/tests/integration/remise_a_zero.sh" "${!site}" "$SAUVEGARDES/vierge-wp$n-v2.sql.gz" "${!base}"
	# Dossier où SVP télécharge les plugins : ces sites ont été montés à la main, sans lui
	mkdir -p "${!site}/plugins/auto"
	(cd "${!site}" && "$SPIP_CLI" plugins:svp:depoter https://plugins.spip.net/depots/principal.xml)
	mysqldump --no-tablespaces $MYSQL_OPTIONS "${!base}" $(mysql $MYSQL_OPTIONS -N "${!base}" -e "show tables like 'spip\\_%'") | gzip > "$SAUVEGARDES/vierge-wp$n-v3.sql.gz"
	(cd "${!site}" && "$SPIP_CLI" php:eval 'echo sql_countsel("spip_depots"), " dépôt\n";')
done
```

Expected : `1 dépôt` deux fois. Désormais, ces deux sites se remettent à zéro avec les états v3 (`remise_a_zero.sh` ne touche pas à `plugins/auto` : un plugin déjà téléchargé y reste, et l'import n'a plus qu'à l'activer).

---

### Task 2 : script de préparation — schémas réellement installés

**Files :**
- Modify : `outils/preparer_spip.sh` (après la boucle `plugins:svp:telecharger`)

**Interfaces :**
- Produces : après téléchargement, les méta `<préfixe>_base_version` de sale, pages et polyhier sont effacées, et `plugins:maj:bdd` les installe dans un processus neuf.

- [ ] **Step 1 : constater le défaut**

Sur un SPIP préparé par le script dans l'état actuel, `$ESSAIS/spip-sqlite` (tests du sous-projet 11) : le journal `$ESSAIS/sqlite.log` ne montre pas `Installation du plugin PolyHierarchy` ni `Installation du plugin Pages` dans la sortie de `plugins:maj:bdd` (leurs tables n'existent que grâce à l'installation de wp2spip) :

```bash
sed 's/<[^>]*>//g' "$ESSAIS/sqlite.log" | grep -c "Installation du plugin PolyHierarchy\|Installation du plugin Pages"
```

Expected : `0`.

- [ ] **Step 2 : effacer les méta notées par SVP**

Dans `outils/preparer_spip.sh`, après :

```bash
		|| erreur_plugins "plugin $prefixe absent de plugins/auto après plugins:svp:telecharger"
done
```

ajouter :

```bash
# SVP installe chaque plugin dans un processus qui ne connaît pas encore ses tables : la version de son schéma
# est notée sans que ses tables soient créées. Effacée, elle fait installer le plugin par plugins:maj:bdd, dans un processus neuf.
(
	export PREPARER_PLUGINS="${plugins_svp[*]}"
	spip_cli php:eval 'include_spip("inc/meta"); foreach (explode(" ", getenv("PREPARER_PLUGINS")) as $prefixe) { effacer_meta($prefixe . "_base_version"); }'
) || erreur "effacement des versions de schéma notées par plugins:svp:telecharger"
```

- [ ] **Step 3 : tests de la préparation**

Run : `tests/preparation/tester_preparer_spip.sh --complet` (réseau, une dizaine de minutes ; en arrière-plan)
Expected : `0 échec(s)`, code `0` ; et cette fois :

```bash
sed 's/<[^>]*>//g' "$ESSAIS/sqlite.log" | grep -c "Installation du plugin PolyHierarchy\|Installation du plugin Pages"
```

Expected : `2`.

- [ ] **Step 4 : commit**

```bash
git add outils/preparer_spip.sh
git commit -m "Préparation : installer réellement les plugins téléchargés par SVP, qui notait leur schéma sans créer leurs tables"
```

---

### Task 3 : plugins requis par le contenu, téléchargés et activés par l'import

**Files :**
- Create : `inc/wp2spip_plugins.php`
- Modify : `spip-cli/WordpressImporter.php` (propriété, début de `execute()`, avant la boucle des traitements, nouvelles méthodes)
- Modify : `wp2spip/importer_acces.php` (début de `wp2spip_importer_acces_dist()`)
- Modify : `wp2spip/importer_commentaires.php` (début de `wp2spip_importer_commentaires_dist()`)
- Modify : `paquet.xml`

**Interfaces :**
- Produces : `wp2spip_where_contenus_restreints(): array`, `wp2spip_where_commentaires(): array`, `wp2spip_where_galeries(): array` (conditions SQL sur `wp_posts` / `wp_comments`) ; `wp2spip_plugins_requis(string $base): array` (préfixe => `nom`, `table`, `dist`, `raison`), pipeline `wp2spip_plugins_requis` ; `wp2spip_plugin_pret(string $prefixe, array $plugin): bool` ; `wp2spip_plugin_present(string $prefixe): bool` ; `WordpressImporter::verifier_plugins(): ?int`, `echec_plugins()`, `lancer_spip_cli(array $arguments, array $environnement = array()): int`.

- [ ] **Step 1 : constater l'état actuel**

Préparer un SPIP sans import (il n'a ni Albums ni Accès restreint), le sauvegarder, puis importer les seuls articles :

```bash
rm -rf "$ESSAIS/spip-plugins"
SPIP_ADMIN_PASS=Essai-Plugins-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-plugins" --wordpress "$WP6" --spip-cli "$SPIP_CLI" --wp2spip lien > "$ESSAIS/plugins-preparation.log" 2>&1; echo "code $?"
tar czf "$ESSAIS/plugins-vierge.tgz" -C "$ESSAIS/spip-plugins" config/bases IMG local
cd "$ESSAIS/spip-plugins"
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" -t importer_articles > "$ESSAIS/plugins-import.log" 2>&1; echo "code $?"
grep -c "Plugin requis" "$ESSAIS/plugins-import.log"; ls plugins/auto
cd "$WP2SPIP"
```

Expected : `code 0` deux fois, `0`, et seulement `pages polyhier sale` sous `plugins/auto`.

Pour revenir à l'état sauvegardé avant chaque essai (le dossier est vérifié avant toute suppression) :

```bash
remettre_plugins() {
	local s="$ESSAIS/spip-plugins"
	[ -f "$s/ecrire/inc_version.php" ] || { echo "pas un SPIP : $s"; return 1; }
	rm -rf "$s/config/bases" "$s/IMG" "$s/local" "$s/plugins/auto/albums" "$s/plugins/auto/accesrestreint"
	find "$s/tmp/cache" -mindepth 1 -delete
	tar xzf "$ESSAIS/plugins-vierge.tgz" -C "$s"
}
```

- [ ] **Step 2 : détection des plugins requis**

`inc/wp2spip_plugins.php` :

```php
<?php

/**
 * Plugins requis par le contenu Wordpress
 *
 * La commande d'import les télécharge et les active avant le premier traitement
 * (WordpressImporter::verifier_plugins()). Les critères sont ceux des traitements qui en ont besoin.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Contenus privés, ou protégés par mot de passe et publiés ou programmés : ceux que publie importer_acces
 *
 * @return array conditions sur wp_posts
 */
function wp2spip_where_contenus_restreints() {
	return array(
		sql_in('post_type', array('post', 'page')),
		'(post_status = "private" or (post_password != "" and ' . sql_in('post_status', array('publish', 'future')) . '))',
	);
}

/**
 * Commentaires approuvés ou en attente, de type comment ou vide (Wordpress < 5.5) : ceux que importe importer_commentaires
 *
 * @return array conditions sur wp_comments
 */
function wp2spip_where_commentaires() {
	return array(
		sql_in('comment_approved', array('1', '0')),
		sql_in('comment_type', array('', 'comment')),
	);
}

/**
 * Contenus qui ont une galerie : bloc gallery ou raccourci [gallery]
 *
 * @return array conditions sur wp_posts
 */
function wp2spip_where_galeries() {
	return array(
		sql_in('post_type', array('post', 'page')),
		'(post_content like ' . sql_quote('%<!-- wp:gallery%') . ' or post_content like ' . sql_quote('%[gallery%') . ')',
	);
}

/**
 * Plugins requis par le contenu Wordpress
 *
 * Le pipeline wp2spip_plugins_requis reçoit la base Wordpress (args) et la liste (data) :
 * une extension de wp2spip peut y déclarer les siens.
 *
 * @param string $base
 * @return array préfixe => array('nom' => …, 'table' => table créée par le plugin, 'dist' => livré avec SPIP,
 *     'raison' => ce qui le rend nécessaire), pour les seuls plugins requis
 */
function wp2spip_plugins_requis($base) {
	$requis = array();
	if ($nb = sql_countsel('wp_posts', wp2spip_where_galeries(), '', '', $base)) {
		$requis['albums'] = array('nom' => 'Albums', 'table' => 'spip_albums', 'dist' => false, 'raison' => "$nb contenus avec une galerie");
	}
	if ($nb = sql_countsel('wp_posts', wp2spip_where_contenus_restreints(), '', '', $base)) {
		$requis['accesrestreint'] = array('nom' => 'Accès restreint', 'table' => 'spip_zones', 'dist' => false, 'raison' => "$nb contenus privés ou protégés");
	}
	if ($nb = sql_countsel('wp_comments', wp2spip_where_commentaires(), '', '', $base)) {
		$requis['forum'] = array('nom' => 'Forum', 'table' => 'spip_forum', 'dist' => true, 'raison' => "$nb commentaires");
	}
	return pipeline('wp2spip_plugins_requis', array('args' => array('base' => $base), 'data' => $requis));
}

/**
 * Plugin requis prêt : actif, et sa table créée
 *
 * @param string $prefixe
 * @param array $plugin description de wp2spip_plugins_requis()
 * @return bool
 */
function wp2spip_plugin_pret($prefixe, $plugin) {
	include_spip('inc/plugin');
	return test_plugin_actif($prefixe) and (empty($plugin['table']) or sql_showtable($plugin['table'], true));
}

/**
 * Plugin présent sur le disque (plugins/ ou plugins-dist/)
 *
 * @param string $prefixe
 * @return bool
 */
function wp2spip_plugin_present($prefixe) {
	foreach (array(_DIR_PLUGINS, _DIR_PLUGINS_DIST) as $dossier) {
		if (!is_dir($dossier)) {
			continue;
		}
		$fichiers = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
		);
		$fichiers->setMaxDepth(3);
		foreach ($fichiers as $fichier) {
			if (
				$fichier->getFilename() == 'paquet.xml'
				and preg_match('/\bprefix=["\']' . preg_quote($prefixe, '/') . '["\']/i', (string) file_get_contents($fichier->getPathname()))
			) {
				return true;
			}
		}
	}
	return false;
}
```

- [ ] **Step 3 : critères partagés avec importer_acces, échec si Accès restreint manque**

Dans `wp2spip/importer_acces.php`, remplacer le début de `wp2spip_importer_acces_dist()` :

```php
function wp2spip_importer_acces_dist($command) {
	include_spip('inc/plugin');
	if (!test_plugin_actif('accesrestreint')) {
		$command->output->writeln('Accès restreint n’est pas actif : les contenus privés ou protégés par mot de passe restent non publiés.');
		return;
	}

	// Les contenus Wordpress concernés
	$wp_posts = sql_allfetsel(
		'ID, post_status, post_password, post_date, post_modified',
		'wp_posts',
		array(
			sql_in('post_type', array('post', 'page')),
			'(post_status = "private" or (post_password != "" and ' . sql_in('post_status', array('publish', 'future')) . '))',
		),
		'',
		'ID',
		'',
		'',
		$command->base
	);
	$wp_posts = array_column($wp_posts, null, 'ID');
```

par :

```php
function wp2spip_importer_acces_dist($command) {
	include_spip('inc/wp2spip_plugins');

	// Les contenus Wordpress concernés
	$wp_posts = sql_allfetsel(
		'ID, post_status, post_password, post_date, post_modified',
		'wp_posts',
		wp2spip_where_contenus_restreints(),
		'',
		'ID',
		'',
		'',
		$command->base
	);
	if (!$wp_posts) {
		return;
	}
	$wp_posts = array_column($wp_posts, null, 'ID');

	// La commande active Accès restreint quand il est requis : inactif malgré tout, l'import ne continue pas
	// en laissant ces contenus non publiés sans le dire
	include_spip('inc/plugin');
	if (!test_plugin_actif('accesrestreint')) {
		$command->output->writeln('<error>' . count($wp_posts) . ' contenus privés ou protégés à publier en zone restreinte, mais Accès restreint n’est pas actif.</error>');
		return false;
	}
```

Mettre aussi à jour le commentaire de la fonction : « si le plugin Accès restreint est actif, on les publie ici » devient « la commande active Accès restreint quand ils existent ; on les publie ici ».

- [ ] **Step 4 : critères partagés avec importer_commentaires, échec si Forum manque**

Dans `wp2spip/importer_commentaires.php`, remplacer :

```php
function wp2spip_importer_commentaires_dist($command) {
	include_spip('inc/plugin');
	if (!test_plugin_actif('forum')) {
		$command->output->writeln('Le plugin Forum n’est pas actif : les commentaires ne sont pas importés.');
		return;
	}

	$correspondance_statuts = array(
		'1' => 'publie',
		'0' => 'prop',
	);

	$wp_comments = sql_allfetsel(
		'*',
		'wp_comments',
		array(
			sql_in('comment_approved', array_keys($correspondance_statuts)),
			sql_in('comment_type', array('', 'comment')),
		),
		'',
		'comment_ID',
		'',
		'',
		$command->base
	);
	if (!$wp_comments) {
		$command->output->writeln('Aucun commentaire à importer.');
		return;
	}
```

par :

```php
function wp2spip_importer_commentaires_dist($command) {
	include_spip('inc/wp2spip_plugins');

	$correspondance_statuts = array(
		'1' => 'publie',
		'0' => 'prop',
	);

	$wp_comments = sql_allfetsel(
		'*',
		'wp_comments',
		wp2spip_where_commentaires(),
		'',
		'comment_ID',
		'',
		'',
		$command->base
	);
	if (!$wp_comments) {
		$command->output->writeln('Aucun commentaire à importer.');
		return;
	}

	// La commande active Forum quand il est requis : inactif malgré tout, l'import ne continue pas
	// sans les commentaires sans le dire
	include_spip('inc/plugin');
	if (!test_plugin_actif('forum')) {
		$command->output->writeln('<error>' . count($wp_comments) . ' commentaires à importer, mais le plugin Forum n’est pas actif.</error>');
		return false;
	}
```

- [ ] **Step 5 : vérification et installation par la commande**

Dans `spip-cli/WordpressImporter.php` :

1. après `public $garder_adresse = false;` :

```php
	// Exécutable SPIP-Cli en cours, pour lancer des sous-commandes
	protected $spip_cli = '';
```

2. après `$this->output = $output;` (début de `execute()`) :

```php
		$this->spip_cli = realpath($_SERVER['argv'][0] ?? '') ?: ($_SERVER['argv'][0] ?? 'spip');
```

3. juste avant `// Un traitement en échec arrête l'import : les suivants dépendent de lui (les articles des rubriques…)` :

```php
		// Plugins requis par le contenu : téléchargés et activés avant le premier traitement, quels que soient
		// les traitements demandés ; l'import est alors relancé, et son code devient celui de la commande
		if (($code = $this->verifier_plugins()) !== null) {
			return $code;
		}
		
```

4. entre la fin de `appliquer_traitement()` et l'accolade fermante de la classe :

```php
	/**
	 * Télécharge et active les plugins requis par le contenu Wordpress, puis relance l'import
	 *
	 * Le processus en cours ne connaît pas un plugin activé après son démarrage (tables, API, pipelines) :
	 * l'import est relancé dans un processus neuf, une seule fois (variable d'environnement WP2SPIP_RELANCE).
	 *
	 * @return int|null null pour continuer l'import, sinon le code de sortie de la commande
	 */
	protected function verifier_plugins(): ?int {
		include_spip('inc/wp2spip_plugins');
		include_spip('inc/meta');
		$manquants = array_filter(
			wp2spip_plugins_requis($this->base),
			fn($plugin, $prefixe) => !wp2spip_plugin_pret($prefixe, $plugin),
			ARRAY_FILTER_USE_BOTH
		);
		if (!$manquants) {
			return null;
		}
		if (getenv('WP2SPIP_RELANCE')) {
			return $this->echec_plugins($manquants, 'toujours inactifs, ou sans leurs tables, après leur installation');
		}
		foreach ($manquants as $prefixe => $plugin) {
			$this->output->writeln("<info>Plugin requis : {$plugin['nom']} ($prefixe), pour {$plugin['raison']}.</info>");
		}
		
		// Un appel de plugins:svp:telecharger par plugin : dans un même appel, SPIP-Cli retente les téléchargements
		// des plugins précédents. Son code de sortie ne dit pas si le téléchargement a réussi : le plugin est cherché sur le disque.
		foreach ($manquants as $prefixe => $plugin) {
			if (wp2spip_plugin_present($prefixe)) {
				continue;
			}
			$this->lancer_spip_cli(array('plugins:svp:telecharger', $prefixe, '-y'));
			if (!wp2spip_plugin_present($prefixe)) {
				return $this->echec_plugins($manquants, "$prefixe absent après plugins:svp:telecharger");
			}
			// SVP l'a installé dans un processus qui ne connaissait pas encore ses tables : la version de son schéma
			// est notée sans que ses tables soient créées. Effacée, elle fait installer le plugin par plugins:maj:bdd.
			effacer_meta($prefixe . '_base_version');
		}
		if (
			$this->lancer_spip_cli(array_merge(array('plugins:activer'), array_keys($manquants), array('-y'))) !== 0
			or $this->lancer_spip_cli(array('plugins:maj:bdd')) !== 0
		) {
			return $this->echec_plugins($manquants, 'échec de plugins:activer ou de plugins:maj:bdd');
		}
		
		$this->output->writeln("\n<info>Plugins requis installés : l’import est relancé.</info>\n");
		return $this->lancer_spip_cli(array_slice($_SERVER['argv'], 1), array('WP2SPIP_RELANCE' => '1'));
	}
	
	/**
	 * Échec de l'installation des plugins requis : les commandes à lancer à la main
	 *
	 * @param array $manquants préfixe => description (wp2spip_plugins_requis())
	 * @param string $raison
	 * @return int
	 */
	protected function echec_plugins(array $manquants, string $raison): int {
		$prefixes = join(' ', array_keys($manquants));
		$lignes = array(
			"<error>Plugins requis par le contenu Wordpress non installés ($raison) : $prefixes.</error>",
			'Aucun traitement n’a été lancé. Pour les installer à la main, depuis le dossier du SPIP :',
			'  spip plugins:svp:depoter https://plugins.spip.net/depots/principal.xml    (si aucun dépôt n’est déclaré)',
		);
		foreach ($manquants as $prefixe => $plugin) {
			if (empty($plugin['dist']) and !wp2spip_plugin_present($prefixe)) {
				$lignes[] = "  spip plugins:svp:telecharger $prefixe -y";
				$lignes[] = "  spip php:eval 'include_spip(\"inc/meta\"); effacer_meta(\"{$prefixe}_base_version\");'";
			}
		}
		$lignes[] = "  spip plugins:activer $prefixes -y";
		$lignes[] = '  spip plugins:maj:bdd';
		$lignes[] = 'puis relancer l’import. plugins:svp:telecharger demande une version de SPIP-Cli qui comporte ses correctifs (sélection du plugin, autorisation).';
		$this->output->writeln($lignes);
		return Command::FAILURE;
	}
	
	/**
	 * Lance une commande SPIP-Cli dans un processus neuf, qui hérite des entrée et sorties de la commande
	 *
	 * Aucun descripteur n'est passé à proc_open() : le sous-processus hérite directement de ceux du processus en cours.
	 * Passer STDOUT ferait écrire le sous-processus au début d'un fichier de sortie, par-dessus ce qui précède.
	 *
	 * @param array $arguments
	 * @param array $environnement variables ajoutées à l'environnement
	 * @return int code de sortie
	 */
	protected function lancer_spip_cli(array $arguments, array $environnement = array()): int {
		$processus = proc_open(
			array_merge(array(PHP_BINARY, $this->spip_cli), $arguments),
			array(),
			$tubes,
			null,
			$environnement ? array_merge(getenv(), $environnement) : null
		);
		return is_resource($processus) ? proc_close($processus) : 1;
	}
```

- [ ] **Step 6 : pipelines et plugins utilisés**

Dans `paquet.xml`, après `<pipeline nom="w2spip_traitements" action="" />` :

```xml
	<!-- Conversion d'un bloc de l'éditeur, plugins requis par le contenu Wordpress -->
	<pipeline nom="wp2spip_bloc" action="" />
	<pipeline nom="wp2spip_plugins_requis" action="" />
```

et après `<utilise nom="accesrestreint" />` :

```xml
	<utilise nom="albums" compatibilite="[4.0.0;]" />
	<utilise nom="oembed" />
```

Puis vider le cache des plugins du SPIP d'essai : `(cd "$ESSAIS/spip-plugins" && "$SPIP_CLI" cache:vider)`.

- [ ] **Step 7 : installation automatique, puis relance**

```bash
remettre_plugins
cd "$ESSAIS/spip-plugins"
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" -t importer_articles > "$ESSAIS/plugins-import.log" 2>&1; echo "code $?"
grep -n "C’est parti\|Plugin requis\|Installation du plugin\|relancé\|Lancement" "$ESSAIS/plugins-import.log" | sed 's/<[^>]*>//g'
"$SPIP_CLI" php:eval 'echo count(sql_alltable("spip_albums%")), " ", count(sql_alltable("spip_zones%")), "\n";'
cd "$WP2SPIP"
```

Expected : `code 0` ; dans l'ordre, `C’est parti…`, `Plugin requis : Albums (albums), pour 6 contenus avec une galerie.`, `Plugin requis : Accès restreint (accesrestreint), pour 1 contenus privés ou protégés.`, `Installation du plugin Albums`, `Installation du plugin Acces Restreint`, `Plugins requis installés : l’import est relancé.`, un second `C’est parti…`, puis `Lancement du traitement « importer_articles »…` ; enfin `2 2` (tables des deux plugins créées).

- [ ] **Step 8 : échec simulé, dépôt SVP absent**

```bash
remettre_plugins
cd "$ESSAIS/spip-plugins"
"$SPIP_CLI" php:eval 'include_spip("inc/svp_depoter_distant"); foreach (sql_allfetsel("id_depot", "spip_depots") as $depot) { svp_supprimer_depot($depot["id_depot"]); }'
"$SPIP_CLI" --no-ansi wordpress:importer "$WP6" > "$ESSAIS/plugins-echec.log" 2>&1; echo "code $?"
grep -c "Lancement du traitement" "$ESSAIS/plugins-echec.log"
grep -A9 "non installés" "$ESSAIS/plugins-echec.log"
cd "$WP2SPIP"
```

Expected : `code 1`, `0` traitement lancé, et le message : `Plugins requis par le contenu Wordpress non installés (albums absent après plugins:svp:telecharger) : albums accesrestreint.`, suivi des commandes à lancer à la main (`plugins:svp:depoter`, `plugins:svp:telecharger albums -y`, effacement de la méta, …, `plugins:activer albums accesrestreint -y`, `plugins:maj:bdd`).

- [ ] **Step 9 : non-régression sur un SPIP de test (plugins déjà prêts)**

Le SPIP du site réel en SQLite a Accès restreint et Forum ; le site réel n'a pas de galerie : rien n'est installé.

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
cd "$SPIP_REEL_SQLITE"
"$SPIP_CLI" wordpress:importer "$WP_REEL" --no-ansi > "$SAUVEGARDES/import-reel-plugins.log" 2>&1; echo "code $?"
grep -c "Plugin requis" "$SAUVEGARDES/import-reel-plugins.log"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-reel-plugins.tsv"
diff "$SAUVEGARDES/export-reel-sqlite-avant-blocs.tsv" "$SAUVEGARDES/export-reel-plugins.tsv" && echo IDENTIQUE
cd "$WP2SPIP"
```

Expected : `code 0`, `0`, `IDENTIQUE`.

- [ ] **Step 10 : commit**

```bash
git add inc/wp2spip_plugins.php spip-cli/WordpressImporter.php wp2spip/importer_acces.php wp2spip/importer_commentaires.php paquet.xml
git commit -m "Import : télécharger et activer les plugins requis par le contenu (Albums, Accès restreint, Forum), puis relancer"
```

---

### Task 4 : analyse et conversion des blocs

**Files :**
- Create : `tests/integration/tester_blocs.php`
- Create : `inc/wp2spip_blocs.php`

**Interfaces :**
- Consumes : `wp2spip_chercher_document($url, $url_wordpress, $base): int` (`wp2spip/importer_articles.php`) ; Albums actif.
- Produces : `wp2spip_contexte_blocs($command, array $wp_post, string $url_wordpress): array` ; `wp2spip_convertir_blocs(string $contenu, array &$contexte): string` (avant sale) ; `wp2spip_restaurer_blocs(string $texte, array $contexte): string` (après sale) ; `wp2spip_lier_albums(array $contexte, int $id_article)` ; `wp2spip_cumuler_bilan_blocs(array $bilan, array $contexte): array` ; `wp2spip_afficher_bilan_blocs($command, array $bilan)` ; le contexte porte `albums` (identifiants créés) et `bilan` (`convertis`, `dynamiques`, `inconnus` : type => nombre ; `medias_introuvables`) ; pipeline `wp2spip_bloc`.

- [ ] **Step 1 : SPIP d'essai des conversions**

Le test s'appuie sur les documents importés (identifiant = ID WordPress) et sur Albums : un SPIP préparé et importé par le script, qui installe Albums (Task 3) :

```bash
rm -rf "$ESSAIS/spip-blocs"
SPIP_ADMIN_PASS=Essai-Blocs-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-blocs" --wordpress "$WP6" --spip-cli "$SPIP_CLI" --wp2spip lien --importer > "$ESSAIS/blocs-preparation.log" 2>&1; echo "code $?"
```

Expected : `code 0`.

- [ ] **Step 2 : écrire le test des conversions**

`tests/integration/tester_blocs.php` :

```php
<?php
/**
 * Tests de la conversion des blocs de l'éditeur (inc/wp2spip_blocs.php)
 *
 * Fragments tirés du contenu Theme Unit Test (WordPress 6.9), et texte attendu après conversion, sale et
 * réinsertion des marqueurs. Les documents sont ceux de l'import (identifiant = ID Wordpress) : à lancer
 * depuis un SPIP où le WordPress de test a été importé, avec Albums actif. Les albums créés sont supprimés à la fin.
 *
 * Usage : spip php:eval "include '/chemin/vers/wp2spip/tests/integration/tester_blocs.php';"
 * Affiche ok ou ECHEC par cas, puis OK ; code 1 en cas d'écart.
 */

include_spip('inc/wp2spip_blocs');
include_spip('sale_fonctions');

$cas = array(
	array(
		'nom' => 'image centrée, retrouvée par son fichier (attribut id faux)',
		'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"center"} -->
<div class="wp-block-image"><figure class="aligncenter"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" class="wp-image-906"/></figure></div>
<!-- /wp:image -->
HTML,
		'attendu' => '<img967|center>',
	),
	array(
		'nom' => 'image liée et légendée : lien gardé, légende en descriptif',
		'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"left"} -->
<div class="wp-block-image"><figure class="alignleft"><a href="https://en.support.wordpress.com/images/image-settings/"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="" class="wp-image-906"/></a><figcaption>Une <strong>légende</strong></figcaption></figure></div>
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
<figure class="wp-block-image size-large"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" class="wp-image-755"/><figcaption class="wp-element-caption">Golden Gate Bridge</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":617,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" class="wp-image-617"/></figure>
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
<figure class="wp-block-gallery columns-3 is-cropped alignfull"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/"><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" class="wp-image-611"/></a></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/cep00032.jpg" alt="Sunburst Over River" data-id="756" class="wp-image-756"/><figcaption class="blocks-gallery-item__caption">Sunburst over the Clinch River</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption"><em>(gallery caption)</em> 3 columns</figcaption></figure>
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
		'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(611, 616, 617, 754, 755, 756, 757, 758, 759, 760, 761, 762, 764, 765, 766, 767, 768, 769, 770, 771, 807, 1687, 1691))),
	),
	array(
		'nom' => 'galerie dont aucune image n’est retrouvée : pas d’album, HTML gardé',
		'contenu' => '[gallery ids="999999"]',
		'attendu' => '[gallery ids="999999"]',
	),
	array(
		'nom' => 'couverture, ancien format : texte dans le HTML du bloc',
		'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","align":"left","id":761} -->
<div class="wp-block-cover has-background-dim alignleft" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg)"><p class="wp-block-cover-text">This is a left aligned cover block.</p></div>
<!-- /wp:cover -->
HTML,
		'attendu' => "<div class=\"wp-block-cover\">\n\n<doc761>\n\nThis is a left aligned cover block.\n\n</div>",
	),
	array(
		'nom' => 'couverture, format actuel : texte dans un bloc enfant',
		'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","id":761,"dimRatio":50} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-761" alt="Wind Farm" src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
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
<figure class="wp-block-video"><video controls src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure>
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
		'nom' => 'bloc inconnu : contenu gardé, passé par sale',
		'contenu' => "<!-- wp:mon-extension/encart {\"couleur\":\"rouge\"} -->\n<div class=\"encart\"><p>Un encart</p></div>\n<!-- /wp:mon-extension/encart -->",
		'attendu' => "<div class=\"encart\">Un encart\n\n</div>",
	),
	array(
		'nom' => 'contenu sans bloc : inchangé',
		'contenu' => '<p>Texte <em>classique</em></p>',
		'attendu' => 'Texte {classique}',
	),
);

$command = new stdClass();
$command->base = 'wordpress';
$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name = "siteurl"', '', '', '', '', 'wordpress');
$echecs = 0;
$albums_crees = array();
// Descriptifs des documents, modifiés par les légendes des essais : rétablis à la fin
$descriptifs_avant = array_column(sql_allfetsel('id_document, descriptif', 'spip_documents', 'id_wordpress > 0'), 'descriptif', 'id_document');
foreach ($cas as $test) {
	$wp_post = array(
		'ID' => $test['id_wordpress'] ?? 0,
		'post_title' => 'Essai',
		'post_date' => '2020-01-02 03:04:05',
		'post_content' => $test['contenu'],
	);
	$contexte = wp2spip_contexte_blocs($command, $wp_post, $url_wordpress);
	$obtenu = trim(wp2spip_restaurer_blocs(sale(wp2spip_convertir_blocs($test['contenu'], $contexte)), $contexte));
	$albums_crees = array_merge($albums_crees, $contexte['albums']);
	$ecarts = array();

	if (preg_replace('/<album\d+>/', '<albumN>', $obtenu) !== $test['attendu']) {
		$ecarts[] = "texte obtenu :\n$obtenu\n--- attendu :\n{$test['attendu']}";
	}
	foreach ($test['albums'] ?? array() as $n => $album) {
		$id_album = $contexte['albums'][$n] ?? 0;
		$ligne = sql_fetsel('titre, descriptif, statut, date', 'spip_albums', 'id_album = ' . intval($id_album));
		$documents = array_map('intval', array_column(sql_allfetsel('id_document', 'spip_documents_liens', array('objet = "album"', 'id_objet = ' . intval($id_album)), '', 'rang_lien'), 'id_document'));
		if (
			!$ligne
			or $ligne['titre'] !== $album['titre']
			or $ligne['descriptif'] !== $album['descriptif']
			or $ligne['statut'] !== 'publie'
			or $ligne['date'] !== '2020-01-02 03:04:05'
			or $documents !== $album['documents']
		) {
			$ecarts[] = "album $n : " . json_encode($ligne, JSON_UNESCAPED_UNICODE) . ' documents ' . join(',', $documents);
		}
	}
	if (count($contexte['albums']) != count($test['albums'] ?? array())) {
		$ecarts[] = count($contexte['albums']) . ' albums créés';
	}
	foreach ($test['descriptifs'] ?? array() as $id_document => $descriptif) {
		if (($obtenu_descriptif = sql_getfetsel('descriptif', 'spip_documents', 'id_document = ' . $id_document)) !== $descriptif) {
			$ecarts[] = "descriptif du document $id_document : « $obtenu_descriptif »";
		}
	}

	echo ($ecarts ? 'ECHEC ' : 'ok    ') . $test['nom'] . "\n";
	foreach ($ecarts as $ecart) {
		echo "      $ecart\n";
	}
	$echecs += count($ecarts) ? 1 : 0;
}

// Les albums et les légendes des essais ne restent pas dans le site
foreach ($descriptifs_avant as $id_document => $descriptif) {
	sql_updateq('spip_documents', array('descriptif' => $descriptif), 'id_document = ' . intval($id_document));
}
if ($albums_crees) {
	sql_delete('spip_documents_liens', array('objet = "album"', sql_in('id_objet', $albums_crees)));
	sql_delete('spip_albums_liens', sql_in('id_album', $albums_crees));
	sql_delete('spip_albums', sql_in('id_album', $albums_crees));
}

if ($echecs) {
	echo "ECHEC : $echecs cas en écart\n";
	exit(1);
}
echo "OK\n";
```

- [ ] **Step 3 : lancer le test, qui échoue**

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_blocs.php';")`
Expected : erreur fatale `Call to undefined function wp2spip_contexte_blocs()` (`inc/wp2spip_blocs.php` n'existe pas), code non nul.

- [ ] **Step 4 : module de conversion**

`inc/wp2spip_blocs.php` :

```php
<?php

/**
 * Conversion des blocs de l'éditeur Wordpress (Gutenberg) et des galeries
 *
 * wp2spip_convertir_blocs() reçoit le contenu brut, avant sale : les blocs sont convertis d'après leurs
 * commentaires <!-- wp:… --> et leurs attributs JSON. Ce qui est déjà au format SPIP (raccourcis, balises
 * de structure gardées) est remplacé par un marqueur wp2spipbloc<N> le temps du passage par sale,
 * puis réinséré par wp2spip_restaurer_blocs().
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/filtres');

/**
 * Contexte de conversion d'un contenu Wordpress
 *
 * @param object $command commande d'import (base, output)
 * @param array $wp_post ligne de wp_posts
 * @param string $url_wordpress
 * @return array
 */
function wp2spip_contexte_blocs($command, $wp_post, $url_wordpress) {
	$contenu = $wp_post['post_content'];
	return array(
		'command' => $command,
		'base' => $command->base,
		'url_wordpress' => $url_wordpress,
		'id_wordpress' => intval($wp_post['ID']),
		'titre' => $wp_post['post_title'],
		'date' => $wp_post['post_date'],
		// Galeries du contenu : blocs, et raccourcis [gallery] hors des blocs gallery
		'nb_galeries' => substr_count($contenu, '<!-- wp:gallery') + preg_match_all('/\[gallery\b/', $contenu),
		'galerie' => 0,
		'albums' => array(),
		'protections' => array(),
		'bilan' => array('convertis' => array(), 'dynamiques' => array(), 'inconnus' => array(), 'medias_introuvables' => 0),
	);
}

/**
 * Convertit les blocs et les galeries d'un contenu Wordpress, avant sale
 *
 * @param string $contenu
 * @param array $contexte de wp2spip_contexte_blocs(), complété (marqueurs, albums créés, bilan)
 * @return string texte à passer à sale, puis à wp2spip_restaurer_blocs()
 */
function wp2spip_convertir_blocs($contenu, &$contexte) {
	if (strpos($contenu, '<!-- wp:') !== false) {
		$contenu = wp2spip_blocs_interieur(wp2spip_analyser_blocs($contenu), $contexte);
	}
	// Raccourcis [gallery] de l'éditeur classique (ou d'un bloc shortcode)
	return preg_replace_callback('/\[gallery\b([^\]]*)\]/', function ($raccourci) use (&$contexte) {
		return wp2spip_convertir_raccourci_gallery($raccourci[0], $raccourci[1], $contexte);
	}, $contenu);
}

/**
 * Réinsère ce que les marqueurs protégeaient, après sale
 *
 * @param string $texte
 * @param array $contexte
 * @return string
 */
function wp2spip_restaurer_blocs($texte, $contexte) {
	return preg_replace_callback('/wp2spipbloc(\d+)/', function ($marqueur) use ($contexte) {
		return $contexte['protections'][intval($marqueur[1])] ?? $marqueur[0];
	}, $texte);
}

/**
 * Remplace un texte déjà au format SPIP par un marqueur, sur sa propre ligne
 *
 * @param string $texte
 * @param array $contexte
 * @return string
 */
function wp2spip_proteger($texte, &$contexte) {
	$n = count($contexte['protections']);
	$contexte['protections'][$n] = $texte;
	return "\n\nwp2spipbloc$n\n\n";
}

/**
 * Découpe un contenu en arbre de blocs, comme parse_blocks() de Wordpress
 *
 * Chaque bloc : array('nom' => 'core/paragraph', 'attributs' => array(…), 'morceaux' => array(…)),
 * les morceaux étant son HTML propre (chaînes) et ses blocs enfants (tableaux), dans l'ordre.
 * Le HTML hors bloc est un morceau de la racine (nom vide).
 *
 * @param string $contenu
 * @return array bloc racine
 */
function wp2spip_analyser_blocs($contenu) {
	$motif = '/<!--\s+(?P<fermant>\/)?wp:(?P<espace>[a-z][a-z0-9_-]*\/)?(?P<nom>[a-z][a-z0-9_-]*)\s+(?P<attributs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<vide>\/)?-->/s';
	$racine = array('nom' => '', 'attributs' => array(), 'morceaux' => array());
	$pile = array(&$racine);
	$position = 0;
	while (preg_match($motif, $contenu, $m, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL, $position)) {
		$courant = &$pile[count($pile) - 1];
		if ($m[0][1] > $position) {
			$courant['morceaux'][] = substr($contenu, $position, $m[0][1] - $position);
		}
		if ($m['fermant'][0] !== null) {
			// Fermeture du bloc courant (un commentaire fermant en trop est ignoré)
			if (count($pile) > 1) {
				array_pop($pile);
			}
		}
		else {
			$courant['morceaux'][] = array(
				'nom' => ($m['espace'][0] ?? 'core/') . $m['nom'][0],
				'attributs' => ($m['attributs'][0] !== null) ? (json_decode($m['attributs'][0], true) ?: array()) : array(),
				'morceaux' => array(),
			);
			if ($m['vide'][0] === null) {
				$pile[] = &$courant['morceaux'][count($courant['morceaux']) - 1];
			}
		}
		unset($courant);
		$position = $m[0][1] + strlen($m[0][0]);
	}
	if ($position < strlen($contenu)) {
		$pile[count($pile) - 1]['morceaux'][] = substr($contenu, $position);
	}
	return $racine;
}

/**
 * HTML propre d'un bloc, sans ses enfants (innerHTML de Wordpress)
 *
 * @param array $bloc
 * @return string
 */
function wp2spip_bloc_html($bloc) {
	return join('', array_filter($bloc['morceaux'], 'is_string'));
}

/**
 * Contenu d'un bloc : son HTML propre, et ses enfants convertis
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_blocs_interieur($bloc, &$contexte) {
	$texte = '';
	foreach ($bloc['morceaux'] as $morceau) {
		$texte .= is_string($morceau) ? $morceau : wp2spip_convertir_bloc($morceau, $contexte);
	}
	return $texte;
}

/**
 * Convertit un bloc selon son type
 *
 * Le pipeline wp2spip_bloc reçoit le bloc (args) et le texte produit (data, null si aucune conversion
 * ne prend ce type en charge) : une extension peut ajouter ou remplacer une conversion.
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_convertir_bloc($bloc, &$contexte) {
	$type = preg_replace('#^core/#', '', $bloc['nom']);
	if (strpos($type, 'core-embed/') === 0) {
		$type = 'embed';
	}
	$texte = null;
	if ($fonction = wp2spip_conversion_bloc($type)) {
		$texte = $fonction($bloc, $contexte);
	}
	$texte = pipeline('wp2spip_bloc', array(
		'args' => array('bloc' => $bloc, 'type' => $type, 'id_wordpress' => $contexte['id_wordpress']),
		'data' => $texte,
	));
	if ($texte === null) {
		// Bloc inconnu : son contenu est gardé
		$contexte['bilan']['inconnus'][$bloc['nom']] = ($contexte['bilan']['inconnus'][$bloc['nom']] ?? 0) + 1;
		return wp2spip_blocs_interieur($bloc, $contexte);
	}
	if ($fonction and !in_array($fonction, array('wp2spip_bloc_retirer', 'wp2spip_bloc_laisser'))) {
		$contexte['bilan']['convertis'][$type] = ($contexte['bilan']['convertis'][$type] ?? 0) + 1;
	}
	return $texte;
}

/**
 * Fonction de conversion d'un type de bloc
 *
 * @param string $type nom du bloc sans core/ (embed pour core-embed/…)
 * @return string nom de fonction, ou '' si le type n'est pas pris en charge
 */
function wp2spip_conversion_bloc($type) {
	$conversions = array(
		'image' => 'wp2spip_bloc_image',
		'gallery' => 'wp2spip_bloc_gallery',
		'cover' => 'wp2spip_bloc_cover',
		'media-text' => 'wp2spip_bloc_media_text',
		'button' => 'wp2spip_bloc_button',
		'audio' => 'wp2spip_bloc_media',
		'video' => 'wp2spip_bloc_media',
		'file' => 'wp2spip_bloc_media',
		'embed' => 'wp2spip_bloc_embed',
		'spacer' => 'wp2spip_bloc_vide',
		'more' => 'wp2spip_bloc_vide',
		'nextpage' => 'wp2spip_bloc_vide',
	);
	foreach (array('columns', 'column', 'group', 'buttons', 'pullquote', 'table', 'quote') as $structure) {
		$conversions[$structure] = 'wp2spip_bloc_structure';
	}
	foreach (array('paragraph', 'heading', 'list', 'list-item', 'code', 'preformatted', 'verse', 'html', 'separator', 'shortcode', 'freeform') as $texte) {
		$conversions[$texte] = 'wp2spip_bloc_laisser';
	}
	if (isset($conversions[$type])) {
		return $conversions[$type];
	}
	if (wp2spip_bloc_dynamique($type)) {
		return 'wp2spip_bloc_retirer';
	}
	return '';
}

/**
 * Bloc dynamique : son contenu est produit par Wordpress à l'affichage, rien n'est enregistré
 *
 * @param string $type
 * @return bool
 */
function wp2spip_bloc_dynamique($type) {
	$dynamiques = array(
		'query', 'latest-posts', 'latest-comments', 'archives', 'categories', 'calendar', 'tag-cloud', 'rss',
		'search', 'page-list', 'navigation', 'social-links', 'social-link', 'loginout', 'avatar', 'read-more',
		'term-description', 'block', 'template-part', 'pattern', 'widget-group', 'legacy-widget', 'home-link',
		'navigation-link', 'navigation-submenu',
	);
	return in_array($type, $dynamiques) or preg_match('/^(post-|comment|query-|site-)/', $type);
}

function wp2spip_bloc_laisser($bloc, &$contexte) {
	return wp2spip_blocs_interieur($bloc, $contexte);
}

function wp2spip_bloc_vide($bloc, &$contexte) {
	return '';
}

function wp2spip_bloc_retirer($bloc, &$contexte) {
	$contexte['bilan']['dynamiques'][$bloc['nom']] = ($contexte['bilan']['dynamiques'][$bloc['nom']] ?? 0) + 1;
	return '';
}

/**
 * Balise ouvrante nettoyée : seules les classes wp-block-… sont gardées, sans les autres attributs
 *
 * @param string $balise nom de la balise
 * @param string $attributs attributs d'origine
 * @return string
 */
function wp2spip_balise_nettoyee($balise, $attributs) {
	$classes = array();
	if (preg_match('/\bclass=["\']([^"\']*)["\']/i', $attributs, $trouve)) {
		$classes = array_filter(preg_split('/\s+/', $trouve[1]), fn($classe) => strpos($classe, 'wp-block-') === 0);
	}
	return '<' . strtolower($balise) . ($classes ? ' class="' . join(' ', $classes) . '"' : '') . '>';
}

/**
 * Balises ouvrantes d'un HTML nettoyées : seules restent les classes wp-block-…, et les attributs
 * qui portent du contenu (lien, image, fusion de cellules)
 *
 * @param string $html
 * @return string
 */
function wp2spip_nettoyer_balises($html) {
	return preg_replace_callback('#<([a-z][a-z0-9]*)\b([^>]*?)(/?)>#i', function ($balise) {
		$garder = array('a' => array('href'), 'img' => array('src', 'alt'), 'td' => array('colspan', 'rowspan'), 'th' => array('colspan', 'rowspan', 'scope'));
		$nettoyee = rtrim(wp2spip_balise_nettoyee($balise[1], $balise[2]), '>');
		foreach ($garder[strtolower($balise[1])] ?? array() as $attribut) {
			if (preg_match('#\b' . $attribut . '=(["\'])(.*?)\1#is', $balise[2], $valeur)) {
				$nettoyee .= " $attribut=\"{$valeur[2]}\"";
			}
		}
		return $nettoyee . ($balise[3] ? ' />' : '>');
	}, $html);
}

/**
 * Bloc de mise en page : sa balise englobante est gardée, nettoyée, et son contenu converti
 *
 * @param array $bloc
 * @param array $contexte
 * @return string
 */
function wp2spip_bloc_structure($bloc, &$contexte) {
	// HTML propre nettoyé (classes de présentation, styles…), enfants convertis
	$interieur = '';
	foreach ($bloc['morceaux'] as $morceau) {
		$interieur .= is_string($morceau) ? wp2spip_nettoyer_balises($morceau) : wp2spip_convertir_bloc($morceau, $contexte);
	}
	// Une légende (tableau…) dans son propre paragraphe : collée au tableau, elle casserait sa dernière ligne
	$interieur = preg_replace('#<figcaption\b#i', "\n\n<figcaption", $interieur);
	if (!preg_match('#^\s*<([a-z][a-z0-9]*)\b([^>]*)>(.*)</\1>\s*$#is', $interieur, $trouve)) {
		return $interieur;
	}
	return wp2spip_proteger(wp2spip_balise_nettoyee($trouve[1], $trouve[2]), $contexte)
		. $trouve[3]
		. wp2spip_proteger('</' . strtolower($trouve[1]) . '>', $contexte);
}

/**
 * Document SPIP d'un média, d'après son fichier puis d'après son identifiant Wordpress
 *
 * @param string $url URL du fichier ('' si inconnue)
 * @param int $id_wordpress identifiant du média (0 si inconnu)
 * @param array $contexte
 * @return int id_document, ou 0
 */
function wp2spip_bloc_document($url, $id_wordpress, $contexte) {
	include_spip('wp2spip/importer_articles');
	if ($url and $id_document = wp2spip_chercher_document($url, $contexte['url_wordpress'], $contexte['base'])) {
		return $id_document;
	}
	if ($id_wordpress = intval($id_wordpress)) {
		return intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . $id_wordpress));
	}
	return 0;
}

/**
 * HTML d'une légende (figcaption)
 *
 * @param string $html
 * @param string $classe classe de la légende cherchée ('' : la première)
 * @return string
 */
function wp2spip_bloc_legende($html, $classe = '') {
	$motif = $classe
		? '#<figcaption\b[^>]*\bclass=["\'][^"\']*\b' . preg_quote($classe, '#') . '\b[^"\']*["\'][^>]*>(.*?)</figcaption>#is'
		: '#<figcaption\b[^>]*>(.*?)</figcaption>#is';
	if (!preg_match($motif, $html, $trouve)) {
		return '';
	}
	return trim($trouve[1]);
}

/**
 * La légende d'une image devient le descriptif de son document
 *
 * @param int $id_document
 * @param string $legende HTML de la légende
 */
function wp2spip_bloc_legender($id_document, $legende) {
	if ($legende !== '') {
		sql_updateq('spip_documents', array('descriptif' => trim(sale($legende))), 'id_document = ' . intval($id_document));
	}
}

function wp2spip_bloc_image($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve) ? $trouve[1] : '';
	if (!$id_document = wp2spip_bloc_document($src, $bloc['attributs']['id'] ?? 0, $contexte)) {
		$contexte['bilan']['medias_introuvables']++;
		return $html;
	}
	$alignements = array('left' => 'left', 'right' => 'right', 'center' => 'center', 'wide' => 'center', 'full' => 'center');
	$align = $alignements[$bloc['attributs']['align'] ?? ''] ?? '';
	$image = "<img$id_document" . ($align ? "|$align" : '') . '>';
	if (preg_match('#<a\b[^>]*\bhref=["\']([^"\']+)["\'][^>]*>\s*<img#i', $html, $trouve)) {
		$image = "[$image->" . html_entity_decode($trouve[1]) . ']';
	}
	wp2spip_bloc_legender($id_document, wp2spip_bloc_legende($html));
	return wp2spip_proteger($image, $contexte);
}

/**
 * Images d'un bloc galerie, dans l'ordre : blocs image enfants (Wordpress 5.9 et après),
 * ou liste blocks-gallery-item (avant)
 *
 * @param array $bloc
 * @param array $contexte
 * @return array liste de array(id_document, légende) des images retrouvées
 */
function wp2spip_bloc_gallery_images($bloc, $contexte) {
	$images = array();
	$enfants = array_filter($bloc['morceaux'], fn($morceau) => is_array($morceau) and $morceau['nom'] == 'core/image');
	if ($enfants) {
		foreach ($enfants as $enfant) {
			$html = wp2spip_bloc_html($enfant);
			$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve) ? $trouve[1] : '';
			$images[] = array(wp2spip_bloc_document($src, $enfant['attributs']['id'] ?? 0, $contexte), wp2spip_bloc_legende($html));
		}
	}
	else {
		preg_match_all('#<li\b[^>]*blocks-gallery-item[^>]*>(.*?)</li>#is', wp2spip_bloc_html($bloc), $items);
		foreach ($items[1] as $item) {
			$src = preg_match('#<img\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $item, $trouve) ? $trouve[1] : '';
			$id = preg_match('#\bdata-id=["\'](\d+)["\']#i', $item, $trouve) ? $trouve[1] : 0;
			$images[] = array(wp2spip_bloc_document($src, $id, $contexte), wp2spip_bloc_legende($item));
		}
	}
	return $images;
}

function wp2spip_bloc_gallery($bloc, &$contexte) {
	$images = wp2spip_bloc_gallery_images($bloc, $contexte);
	$legende = wp2spip_bloc_legende(wp2spip_bloc_html($bloc), 'blocks-gallery-caption');
	if ($id_album = wp2spip_creer_album($images, ($legende !== '') ? trim(sale($legende)) : '', $contexte)) {
		return wp2spip_proteger("<album$id_album>", $contexte);
	}
	$contexte['bilan']['medias_introuvables']++;
	return wp2spip_blocs_interieur($bloc, $contexte);
}

/**
 * Raccourci [gallery] : images de l'attribut ids, sinon images rattachées au contenu
 *
 * @param string $raccourci
 * @param string $attributs
 * @param array $contexte
 * @return string
 */
function wp2spip_convertir_raccourci_gallery($raccourci, $attributs, &$contexte) {
	if (preg_match('/\bids=(["\']?)([\d,\s]+)\1/', $attributs, $trouve)) {
		$ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $trouve[2])));
	}
	else {
		$ids = array_column(sql_allfetsel(
			'ID',
			'wp_posts',
			array('post_type = "attachment"', 'post_parent = ' . $contexte['id_wordpress'], 'post_mime_type like "image/%"'),
			'',
			'menu_order, ID',
			'',
			'',
			$contexte['base']
		), 'ID');
	}
	$images = array();
	foreach ($ids as $id) {
		$images[] = array(wp2spip_bloc_document('', $id, $contexte), '');
	}
	if ($id_album = wp2spip_creer_album($images, '', $contexte)) {
		$contexte['bilan']['convertis']['[gallery]'] = ($contexte['bilan']['convertis']['[gallery]'] ?? 0) + 1;
		return wp2spip_proteger("<album$id_album>", $contexte);
	}
	$contexte['bilan']['medias_introuvables']++;
	return $raccourci;
}

/**
 * Crée l'album d'une galerie ; il sera lié à l'article par wp2spip_lier_albums()
 *
 * @param array $images liste de array(id_document, légende), 0 pour une image introuvable
 * @param string $legende légende de la galerie
 * @param array $contexte
 * @return int id_album, ou 0 si aucune image n'est retrouvée
 */
function wp2spip_creer_album($images, $legende, &$contexte) {
	$images = array_values(array_filter($images, fn($image) => $image[0] > 0));
	if (!$images) {
		return 0;
	}
	include_spip('action/editer_objet');
	include_spip('action/editer_liens');
	$contexte['galerie']++;
	$titre = $contexte['titre'] . (($contexte['nb_galeries'] > 1) ? " (galerie {$contexte['galerie']})" : '');
	$id_album = intval(objet_inserer('album', null, array(
		'titre' => $titre,
		'descriptif' => $legende,
		'date' => $contexte['date'],
		'statut' => 'publie',
	)));
	if (!$id_album) {
		return 0;
	}
	foreach ($images as $rang => $image) {
		objet_associer(array('document' => $image[0]), array('album' => $id_album), array('rang_lien' => $rang + 1));
		wp2spip_bloc_legender($image[0], $image[1]);
	}
	$contexte['albums'][] = $id_album;
	return $id_album;
}

/**
 * Lie à l'article les albums créés pendant la conversion de son texte
 *
 * @param array $contexte
 * @param int $id_article
 */
function wp2spip_lier_albums($contexte, $id_article) {
	if ($contexte['albums']) {
		include_spip('action/editer_liens');
		objet_associer(array('album' => $contexte['albums']), array('article' => $id_article));
	}
}

/**
 * Bloc couverture ou média et texte : le document du média, puis le texte du bloc, dans la balise du bloc
 *
 * @param array $bloc
 * @param array $contexte
 * @param string $url
 * @param int $id_wordpress
 * @return string
 */
function wp2spip_bloc_avec_media($bloc, &$contexte, $url, $id_wordpress) {
	// Le texte : HTML propre (ancien format) et blocs enfants, sans l'enveloppe ni le média, remplacé par son document
	// (les balises des enfants convertis sont déjà protégées par des marqueurs : celles qui restent sont celles du bloc)
	$texte = preg_replace(
		array(
			'#<figure\b[^>]*wp-block-media-text__media[^>]*>.*?</figure>#is',
			'#<video\b.*?</video>#is',
			'#<img\b[^>]*>#i',
			'#<span\b[^>]*>\s*</span>#i',
			'#</?div\b[^>]*>#i',
		),
		'',
		wp2spip_blocs_interieur($bloc, $contexte)
	);
	$classe = 'wp-block-' . preg_replace('#^core/#', '', $bloc['nom']);
	if ($id_document = wp2spip_bloc_document($url, $id_wordpress, $contexte)) {
		$texte = wp2spip_proteger("<doc$id_document>", $contexte) . $texte;
	}
	elseif ($url or $id_wordpress) {
		$contexte['bilan']['medias_introuvables']++;
	}
	return wp2spip_proteger("<div class=\"$classe\">", $contexte) . $texte . wp2spip_proteger('</div>', $contexte);
}

function wp2spip_bloc_cover($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['url'] ?? '';
	if (!$url and preg_match('#(?:\bsrc=["\']|background-image:\s*url\()([^"\')]+)#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	return wp2spip_bloc_avec_media($bloc, $contexte, $url, $bloc['attributs']['id'] ?? 0);
}

function wp2spip_bloc_media_text($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['mediaUrl'] ?? '';
	if (!$url and preg_match('#<(?:img|video)\b[^>]*\bsrc=["\']([^"\']+)["\']#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	return wp2spip_bloc_avec_media($bloc, $contexte, $url, $bloc['attributs']['mediaId'] ?? 0);
}

function wp2spip_bloc_button($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	if (!preg_match('#<a\b([^>]*)>(.*?)</a>#is', $html, $trouve)) {
		return wp2spip_blocs_interieur($bloc, $contexte);
	}
	$texte = trim(supprimer_tags($trouve[2]));
	if (preg_match('#\bhref=["\']([^"\']+)["\']#i', $trouve[1], $lien)) {
		$texte = "[$texte->" . html_entity_decode($lien[1]) . ']';
	}
	return wp2spip_proteger("<div class=\"wp-block-button\">$texte</div>", $contexte);
}

function wp2spip_bloc_media($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['href'] ?? '';
	if (!$url and preg_match('#\b(?:src|href)=["\']([^"\']+)["\']#i', $html, $trouve)) {
		$url = $trouve[1];
	}
	if ($id_document = wp2spip_bloc_document($url, $bloc['attributs']['id'] ?? 0, $contexte)) {
		return wp2spip_proteger("<doc$id_document>", $contexte);
	}
	// Média hors de la médiathèque (lecteur d'un autre site…) : HTML d'origine
	return $html;
}

function wp2spip_bloc_embed($bloc, &$contexte) {
	$html = wp2spip_bloc_html($bloc);
	$url = $bloc['attributs']['url'] ?? '';
	if (!$url and preg_match('#https?://[^\s<"\']+#', strip_tags($html), $trouve)) {
		$url = $trouve[0];
	}
	if (!$url) {
		return $html;
	}
	// L'URL seule sur sa ligne : oEmbed en fait un lecteur, sinon SPIP en fait un lien ; la légende passera par sale
	$legende = wp2spip_bloc_legende($html);
	return wp2spip_proteger($url, $contexte) . ($legende !== '' ? "\n\n$legende\n\n" : '');
}

/**
 * Ajoute le bilan d'un contenu au bilan de l'import
 *
 * @param array $bilan
 * @param array $contexte
 * @return array
 */
function wp2spip_cumuler_bilan_blocs($bilan, $contexte) {
	foreach (array('convertis', 'dynamiques', 'inconnus') as $cle) {
		foreach ($contexte['bilan'][$cle] as $type => $nb) {
			$bilan[$cle][$type] = ($bilan[$cle][$type] ?? 0) + $nb;
		}
	}
	$bilan['medias_introuvables'] += $contexte['bilan']['medias_introuvables'];
	$bilan['albums'] += count($contexte['albums']);
	return $bilan;
}

/**
 * Affiche le bilan des blocs à la fin de importer_articles
 *
 * @param object $command
 * @param array $bilan
 */
function wp2spip_afficher_bilan_blocs($command, $bilan) {
	$liste = function ($nombres) {
		ksort($nombres);
		return join(', ', array_map(fn($type, $nb) => "$type ($nb)", array_keys($nombres), $nombres));
	};
	if ($bilan['convertis']) {
		$command->output->writeln('Blocs convertis : ' . $liste($bilan['convertis']) . '.');
	}
	if ($bilan['albums']) {
		$command->output->writeln("{$bilan['albums']} albums créés pour les galeries.");
	}
	if ($bilan['dynamiques']) {
		$command->output->writeln(array_sum($bilan['dynamiques']) . ' blocs dynamiques retirés (rien n’est enregistré dans le contenu) : ' . $liste($bilan['dynamiques']) . '.');
	}
	if ($bilan['inconnus']) {
		$command->output->writeln(array_sum($bilan['inconnus']) . ' blocs inconnus, contenu gardé (détail avec -v) : ' . $liste($bilan['inconnus']) . '.');
	}
	if ($bilan['medias_introuvables']) {
		$command->output->writeln("{$bilan['medias_introuvables']} médias de blocs ou de galeries introuvables dans la médiathèque, HTML d’origine gardé.");
	}
}
```

- [ ] **Step 5 : lancer le test**

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_blocs.php';"); echo "code $?"`
Expected : 17 lignes `ok`, puis `OK`, `code 0`. Relancé une seconde fois : même résultat (les albums et légendes des essais sont retirés à la fin).

- [ ] **Step 6 : commit**

```bash
git add inc/wp2spip_blocs.php tests/integration/tester_blocs.php
git commit -m "Analyse et conversion des blocs de l'éditeur et des galeries, avec leur test"
```

---

### Task 5 : conversion des blocs dans importer_articles

**Files :**
- Modify : `wp2spip/importer_articles.php`

**Interfaces :**
- Consumes : fonctions de `inc/wp2spip_blocs.php` (Task 4).

- [ ] **Step 1 : textes actuels**

Sur `$ESSAIS/spip-blocs` (importé à la Task 4 par la version sans conversion) :

```bash
(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval 'echo sql_countsel("spip_articles", "texte like \"%<figure%\""), " ", sql_countsel("spip_albums"), "\n";')
```

Expected : `10 0` (dix articles gardent des `<figure` : relevé sur le SPIP de test du WordPress 6.9, importé par la version actuelle ; aucun album).

- [ ] **Step 2 : appel de la conversion**

Dans `wp2spip/importer_articles.php` :

1. après `include_spip('inc/wp2spip');` (début de `wp2spip_importer_articles_dist()`) :

```php
		include_spip('inc/wp2spip_blocs');
```

2. après `$nb_liens_non_convertis = 0;` :

```php
		$bilan_blocs = array('convertis' => array(), 'dynamiques' => array(), 'inconnus' => array(), 'medias_introuvables' => 0, 'albums' => 0);
```

3. remplacer :

```php
			// On passe déjà sale() en premier pour y voir plus clair
			$texte = sale($wp_post['post_content']);
```

par :

```php
			// Les blocs de l'éditeur et les galeries sont convertis avant sale(), qui ne voit pas ce qu'ils produisent en raccourcis SPIP
			$contexte_blocs = wp2spip_contexte_blocs($command, $wp_post, $url_wordpress);
			$texte = wp2spip_convertir_blocs($wp_post['post_content'], $contexte_blocs);
			$texte = wp2spip_restaurer_blocs(sale($texte), $contexte_blocs);
			$bilan_blocs = wp2spip_cumuler_bilan_blocs($bilan_blocs, $contexte_blocs);
			if ($command->output->isVerbose() and $contexte_blocs['bilan']['inconnus']) {
				$command->output->writeln("\nArticle Wordpress $id_wordpress : blocs inconnus, contenu gardé : " . join(', ', array_keys($contexte_blocs['bilan']['inconnus'])));
			}
```

4. remplacer :

```php
			// Associer les docs
			wp2spip_importer_articles_documents($command, $id_wordpress, $id_article);
```

par :

```php
			// Associer les docs, et les albums des galeries
			wp2spip_importer_articles_documents($command, $id_wordpress, $id_article);
			wp2spip_lier_albums($contexte_blocs, $id_article);
```

5. à la fin de la fonction, après le bloc `if ($nb_liens_non_convertis) { … }` :

```php
		wp2spip_afficher_bilan_blocs($command, $bilan_blocs);
```

- [ ] **Step 3 : import complet**

```bash
rm -rf "$ESSAIS/spip-blocs"
SPIP_ADMIN_PASS=Essai-Blocs-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-blocs" --wordpress "$WP6" --spip-cli "$SPIP_CLI" --wp2spip lien --importer > "$ESSAIS/blocs-preparation.log" 2>&1; echo "code $?"
grep "Blocs convertis\|albums créés\|blocs dynamiques\|blocs inconnus\|médias de blocs" "$ESSAIS/blocs-preparation.log" | cut -c1-200
(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval 'echo sql_countsel("spip_articles", "texte like \"%<!-- wp:%\""), " ", sql_countsel("spip_albums"), "\n";')
```

Expected : `code 0` ; `Blocs convertis : [gallery] (12), audio (2), button (12), buttons (1), column (38), columns (12), cover (21), embed (5), file (3), gallery (10), group (24), image (14), media-text (6), more (2), nextpage (2), pullquote (4), quote (8), spacer (4), table (4), video (3).` ; `22 albums créés pour les galeries.` ; `54 blocs dynamiques retirés …` ; aucune ligne de blocs inconnus ni de médias introuvables ; puis `1 22` (seul reste un `<!-- wp:code` écrit dans le texte d'un bloc de code, contenu 1779).

- [ ] **Step 4 : relecture des textes**

```bash
cd "$ESSAIS/spip-blocs"
for id in 1783 1784 1785 1787 1788 1781 21 24 8 1779; do
	echo "===== $id"; "$SPIP_CLI" php:eval "echo sql_getfetsel('texte', 'spip_articles', 'id_article = $id');" | head -40
done
cd "$WP2SPIP"
```

Expected (relecture) : colonnes et groupes en `<div class="wp-block-…">` sans autre classe ni style ; couvertures avec `<docN>` puis leur texte ; boutons en `[texte->url]` ; galeries en `<albumN>` ; images en `<imgN|alignement>` (liens en `[<imgN|…>->url]`) ; embarqués en URL seule sur sa ligne, puis la légende ; tableaux avec leur légende dans un paragraphe à part.

- [ ] **Step 5 : commit**

```bash
git add wp2spip/importer_articles.php
git commit -m "importer_articles : blocs de l'éditeur et galeries convertis avant sale, albums liés, bilan"
```

---

### Task 6 : vérificateur et export

**Files :**
- Modify : `tests/integration/verifier_identifiants.php` (avant le bloc des contenus privés ou protégés)
- Modify : `tests/integration/exporter_import.php` (avant le tri final)

- [ ] **Step 1 : contrôles des blocs dans le vérificateur**

Dans `tests/integration/verifier_identifiants.php`, juste avant la ligne `// Contenus privés ou protégés par mot de passe : jamais publiés hors d'une zone ;` :

```php
// Blocs de l'éditeur : plus de commentaire <!-- wp: hors des blocs de code, plus de classe de présentation
// has-… ou is-… ; chaque <albumN> désigne un album existant, lié à l'article et qui contient un document
$avec_albums = (bool) sql_showtable('spip_albums', true);
foreach (sql_allfetsel('id_article, texte', 'spip_articles', 'id_wordpress > 0', '', 'id_article') as $article) {
	$id = intval($article['id_article']);
	$hors_code = preg_replace('#<(code|cadre)\b.*?</\1>#is', '', $article['texte']);
	if (strpos($hors_code, '<!-- wp:') !== false) {
		$echecs[] = "article $id : commentaire de bloc <!-- wp: restant";
	}
	if (preg_match('/\bclass=["\'][^"\']*\b(?:has|is)-[a-z0-9-]+/i', $hors_code, $trouve)) {
		$echecs[] = "article $id : classe de présentation restante ($trouve[0])";
	}
	preg_match_all('/<album(\d+)>/', $article['texte'], $trouves);
	foreach (array_unique($trouves[1]) as $id_album) {
		if (
			!$avec_albums
			or !sql_countsel('spip_albums_liens', array('id_album = ' . intval($id_album), 'objet = "article"', 'id_objet = ' . $id))
			or !sql_countsel('spip_documents_liens', array('objet = "album"', 'id_objet = ' . intval($id_album)))
		) {
			$echecs[] = "article $id : album $id_album absent, non lié à l'article, ou vide";
		}
	}
}
```

- [ ] **Step 2 : le vérificateur détecte un reste**

```bash
cd "$ESSAIS/spip-blocs"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1
"$SPIP_CLI" php:eval 'sql_updateq("spip_articles", array("texte" => sql_getfetsel("texte", "spip_articles", "id_article = 1783") . "\n\n<!-- wp:paragraph -->\n<p class=\"has-text-color\">x</p>\n\n<album999999>"), "id_article = 1783");'
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 4; echo "code ${PIPESTATUS[0]}"
cd "$WP2SPIP"
```

Expected : `OK` ; puis `ECHEC` avec les trois écarts de l'article 1783 (commentaire de bloc, classe de présentation `has-text-color`, album 999999 absent), code `1`. Le SPIP d'essai est ensuite réimporté à la Task 7 (préparation depuis un dossier vide).

- [ ] **Step 3 : albums dans l'export**

Dans `tests/integration/exporter_import.php`, juste avant `$lignes = array_map(function ($ligne) { return join("\t", $ligne); }, $lignes);` :

```php
if (test_plugin_actif('albums')) {
	// Albums des galeries : article lié et documents dans leur ordre
	foreach (sql_allfetsel('*', 'spip_albums', '', '', 'id_album') as $album) {
		$id_album = intval($album['id_album']);
		$lies = sql_allfetsel('id_objet', 'spip_albums_liens', array('id_album = ' . $id_album, 'objet = "article"'), '', 'id_objet');
		$images = sql_allfetsel('id_document', 'spip_documents_liens', array('objet = "album"', 'id_objet = ' . $id_album), '', 'rang_lien');
		$lignes[] = array(
			'album',
			'spip' . $id_album,
			$album['titre'],
			$normaliser($album['descriptif']),
			$album['statut'],
			$album['date'],
			join(',', array_map(fn($id) => 'article#' . $wp($articles, $id), array_column($lies, 'id_objet'))),
			join(',', array_map(fn($id) => $wp($documents, $id), array_column($images, 'id_document'))),
		);
	}
}
```

Run : `(cd "$ESSAIS/spip-blocs" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" | grep -c "^album")`
Expected : `22`.

- [ ] **Step 4 : commit**

```bash
git add tests/integration/verifier_identifiants.php tests/integration/exporter_import.php
git commit -m "Vérificateur : restes de blocs et albums ; export des albums"
```

---

### Task 7 : validation

**Files :** aucun (journaux sous `$ESSAIS` et `$SAUVEGARDES`).

- [ ] **Step 1 : WordPress 6.9 et 7.1, depuis un dossier vide**

```bash
for n in 6 7; do
	wordpress=WP$n
	rm -rf "$ESSAIS/spip-blocs-wp$n"
	SPIP_ADMIN_PASS=Essai-Blocs-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-blocs-wp$n" --wordpress "${!wordpress}" --spip-cli "$SPIP_CLI" --importer > "$ESSAIS/blocs-wp$n.log" 2>&1; echo "wp$n : code $?"
	grep -c "Plugin requis" "$ESSAIS/blocs-wp$n.log"
	(cd "$ESSAIS/spip-blocs-wp$n" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1)
	(cd "$ESSAIS/spip-blocs-wp$n" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/tester_blocs.php';" | tail -n 1)
done
```

Expected, pour chacun : `code 0`, `2` plugins requis (Albums, Accès restreint), `OK`, `OK`.

- [ ] **Step 2 : SPIP de test des WordPress 6.9 et 7.1 (états v3, dépôt SVP)**

L'import y installe Albums (et Accès restreint pour le 7.1) depuis le dépôt :

```bash
for n in 6 7; do
	site=SPIP_WP$n; base=BASE_WP$n; wordpress=WP$n
	"$WP2SPIP/tests/integration/remise_a_zero.sh" "${!site}" "$SAUVEGARDES/vierge-wp$n-v3.sql.gz" "${!base}"
	(cd "${!site}" && "$SPIP_CLI" wordpress:importer "${!wordpress}" --no-ansi > "$SAUVEGARDES/import-wp$n-blocs.log" 2>&1; echo "wp$n : code $?")
	(cd "${!site}" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1)
	(cd "${!site}" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp$n-blocs.tsv")
done
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6-blocs.tsv" | grep -c "^>"
```

Expected : `code 0` et `OK` pour chacun. Le diff avec la référence porte sur les textes des contenus à blocs, les descriptifs de documents (légendes), et ajoute les lignes `album` ; le relire, puis faire de cet export la nouvelle référence, en gardant l'ancienne :

```bash
cp "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6-reference-avant-blocs.tsv"
cp "$SAUVEGARDES/export-wp6-blocs.tsv" "$SAUVEGARDES/export-wp6-reference.tsv"
```

- [ ] **Step 3 : site réel, sur son SPIP de test**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
cd "$SPIP_REEL_SQLITE"
"$SPIP_CLI" wordpress:importer "$WP_REEL" --no-ansi > "$SAUVEGARDES/import-reel-blocs.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-reel-blocs.tsv"
diff "$SAUVEGARDES/export-reel-sqlite-avant-blocs.tsv" "$SAUVEGARDES/export-reel-blocs.tsv"
cd "$WP2SPIP"
```

Expected : `code 0`, `OK` ; le diff ne porte que sur la ligne `article` du contenu qui a un bloc (`<!-- wp:shortcode -->`), ou est vide si sale produisait déjà le même texte.

- [ ] **Step 4 : site réel, depuis un dossier vide**

```bash
rm -rf "$ESSAIS/spip-reel"
SPIP_ADMIN_PASS=Essai-Blocs-1 outils/preparer_spip.sh --spip "$ESSAIS/spip-reel" --wordpress "$WP_REEL" --spip-cli "$SPIP_CLI" --importer > "$ESSAIS/reel.log" 2>&1; echo "code $?"
grep "Plugin requis" "$ESSAIS/reel.log"
(cd "$ESSAIS/spip-reel" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 2)
```

Expected : `code 0` ; `Plugin requis : Accès restreint (accesrestreint), pour 98 contenus privés ou protégés.` (pas d'Albums : aucune galerie) ; `98 contenus privés ou protégés vérifiés` puis `OK`.

- [ ] **Step 5 : tests de la préparation**

Run : `tests/preparation/tester_preparer_spip.sh --complet`
Expected : `0 échec(s)` (les préparations y importent désormais avec installation automatique des plugins requis).

---

### Task 8 : documentation

**Files :**
- Modify : `readme.md` (nouvelle section avant « Refaire un import »)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md` (`Statut`)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 6, ligne du sous-projet 3)
- Modify : `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 13)

- [ ] **Step 1 : readme**

Dans `readme.md`, juste avant `## Refaire un import` :

````markdown
## Blocs de l'éditeur et galeries
Les blocs de l'éditeur Wordpress sont convertis : images en `<imgN>` avec leur alignement (la légende devient le descriptif du document), médias en `<docN>`, mise en page (colonnes, groupes, couvertures, boutons…) gardée avec ses seules classes `wp-block-…`, que le squelette peut styler, contenus embarqués en URL seule sur sa ligne (le plugin oEmbed en fait un lecteur). Chaque galerie (bloc ou raccourci `[gallery]`) devient un album du plugin Albums, inséré par `<albumN>`. Les blocs dynamiques (derniers articles, recherche…), qui n'enregistrent rien dans le contenu, sont retirés. Le bilan de `importer_articles` détaille ces conversions.

## Plugins requis
Avant le premier traitement, l'import télécharge et active les plugins dont le contenu a besoin : Albums s'il y a une galerie, Accès restreint s'il y a des contenus privés ou protégés, Forum s'il y a des commentaires ; puis il se relance. Il faut un dépôt de plugins déclaré (`spip plugins:svp:depoter https://plugins.spip.net/depots/principal.xml`, déjà fait par `outils/preparer_spip.sh`) et SPIP-Cli avec les correctifs de `plugins:svp:telecharger`. En cas d'échec, rien n'est importé et les commandes à lancer à la main sont affichées.
````

Et dans la section « Pour les devs », ajouter :

```markdown
Les pipelines `wp2spip_bloc` (conversion d'un type de bloc : `args` le bloc, `data` le texte produit ou `null`) et `wp2spip_plugins_requis` (plugins requis par le contenu) permettent à une extension de compléter ces deux étapes.
```

- [ ] **Step 2 : statuts**

- spec du sous-projet 3 : `Statut : design validé, à planifier.` devient `Statut : réalisé (plan : docs/superpowers/plans/2026-10-08-wp2spip-blocs-editeur.md).` ;
- spec d'ensemble, § 6 : ligne du sous-projet 3 marquée **réalisé** ;
- plan général, Task 13 : cocher la spec, le plan, la réalisation et la validation, avec le résultat (WordPress 6.9 et 7.1 depuis un dossier vide et sur leurs SPIP de test, site réel ; vérificateur et test des conversions à OK).

- [ ] **Step 3 : commit**

```bash
git add readme.md docs/superpowers
git commit -m "Sous-projet 3 réalisé : documentation des blocs de l'éditeur et des plugins requis"
```
