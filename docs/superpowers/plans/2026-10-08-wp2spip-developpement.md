# wp2spip — plan de développement

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** mener wp2spip de l'état de `master` (commit `3b9fc8d`) à un import WordPress → SPIP 4.4 fiable, qui conserve les identifiants WordPress, puis dérouler le reste de la feuille de route.

**Architecture :** une commande SPIP-Cli (`spip-cli/WordpressImporter.php`) enchaîne des traitements chargés par `charger_fonction()` depuis `wp2spip/<traitement>.php`. Chaque traitement lit la base WordPress (base externe SPIP) par l'API `sql_*` et crée les objets SPIP par l'API d'édition (`objet_inserer`, `objet_modifier`, `objet_associer`). Les helpers partagés vont dans `inc/wp2spip.php`.

**Tech Stack :** SPIP 4.2 à 4.4, PHP 8.x, SPIP-Cli (Symfony Console 5.4), plugins sale, pages, polyhier, forum, accesrestreint ; MySQL et SQLite ; WP-CLI pour les WordPress de test.

**Référence :** spec d'ensemble `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (cité « spec § n »).

## Global Constraints

- Compatibilité : `compatibilite="[4.2.0;4.4.*]"`, PHP 8.x, bases SPIP MySQL et SQLite, WordPress 4.9 à 7.x.
- WordPress figé pendant la migration ; SPIP vierge avant le premier import ; SPIP hors ligne jusqu'au contrôle des accès (spec § 1).
- Import interrompu ou à refaire : remise à zéro du SPIP, puis import complet. Aucune logique de reprise, aucune mise à jour de contenus déjà importés.
- Les objets déjà importés ne sont pas retouchés, sauf par `importer_metas`, `importer_polyhierarchie` et `importer_commentaires`, qui recalculent (spec § 1).
- Style du code : celui du plugin (tabulations, `array()`, commentaires en français, noms de fonctions `wp2spip_*`).
- Messages de commit en français, **sans aucun trailer** (pas de `Co-Authored-By`, pas de `Claude-Session`).
- Ne jamais nommer le site réel de test (nom, domaine, bases, chemins, catégories) dans un fichier versionné ou un message de commit : écrire « site réel ».
- Pas de `git push` sans demande explicite.
- Les valeurs propres à la machine de test (chemins, bases, mots de passe) restent dans `tests/integration/environnement.sh`, non versionné.

---

## Lot 0 — Réalisé sur la branche `compat-spip-4.4`

Point de départ : `master` au commit `3b9fc8d` (septembre 2022), qui importait déjà métas, auteurs, rubriques, documents, articles et (sommairement) commentaires.

### Task 0.1 : compatibilité SPIP 4.4 et rubriques secondaires — `aee0782`

- [x] `paquet.xml` : version 3.0.0, `compatibilite="[4.2.0;4.4.*]"`, nécessite sale `[1.0.0;]`, pages `[2.0.0;]`, polyhier `[4.0.0;]`.
- [x] `spip-cli/WordpressImporter.php` : `configure(): void`, `execute(...): int` (Symfony Console 5.4), erreur si `wp-includes/version.php` est illisible, version WordPress sans mineure tolérée.
- [x] Corrections PHP 8 : `importer_auteurs` (métas absentes, `unserialize` sans classes, `array_key_first`, mot de passe aléatoire à la création seulement), `importer_documents` (`$distant` réinitialisé), `importer_metas` (`!empty()`), `importer_articles` (`array_column`).
- [x] Rubrique principale = première catégorie WordPress (ordre `term_order`, puis `term_id`) existant dans SPIP.
- [x] Nouveau traitement `importer_polyhierarchie` : autres catégories → rubriques secondaires (`polyhier_set_parents`), puis `calculer_rubriques()`.

### Task 0.2 : catégories perdues — `b2cf90b`

- [x] `wp2spip_enfants_rubriques()` : `$enfants[] = $category` au lieu d'une affectation par clé que `array_merge()` écrasait.

### Task 0.3 : contenus privés et protégés — `897ac83`

- [x] `importer_articles` : `private` → `prepa` ; contenu `publish` protégé par mot de passe → `prepa`, jamais publié en clair.
- [x] Nouveau traitement `importer_acces` : avec Accès restreint, publication à la date WordPress dans les zones « Wordpress : contenus privés » et « … protégés par mot de passe » (publiques, réservées aux visiteurs identifiés), identifiants gardés en configuration `wp2spip/zones/<clé>`.
- [x] `paquet.xml` : `<utilise nom="accesrestreint" />` ; readme : section « Contenus privés et protégés par mot de passe ».

### Task 0.4 : commentaires — `0538b49`

- [x] `importer_commentaires` réécrit : nécessite Forum (`<utilise nom="forum" />`), approuvés → `publie`, en attente → `prop`, `comment_type` vide (WordPress < 5.5) ou `comment`, id_wordpress, auteur, IP, site ; commentaires de contenus non importés ignorés ; fils reconstitués (`id_parent`, `id_thread`, `date_thread`).

### Task 0.5 : liens vers les médias — `55f4c7f`

- [x] `wp2spip_chercher_document()` et index de tous les noms de chaque média (`_wp_attached_file`, tailles, `original_image`, `_wp_attachment_backup_sizes`, `guid`), URL du site comparées sans protocole ni `www.`.
- [x] `<img>` → `<imgN|alignement>` (par le fichier, puis la classe `wp-image-N`) ; `<audio>`, `<video>`, `[audio]`, `[video]` → `<docN>`.
- [x] Bilan des liens restés tels quels (absents de la médiathèque / non convertibles), détail avec `-v`.

### Task 0.6 : sites de test et validation

- [x] SPIP 4.4.21 jetables : site réel importé en SQLite et en MySQL ; WordPress 6.9 et 7.1 standard (contenu *Theme Unit Test*, installés par WP-CLI), chacun importé dans un SPIP partageant sa base MySQL.
- [x] Résultats (spec § 5.2) : SQLite = MySQL, 6.9 = 7.1, rubriques secondaires, zones et fils de commentaires conformes, idempotence des commentaires et des zones.

### Task 0.7 : spec d'ensemble — `a9ae291` à `bf83f4f`

- [x] Spec rédigée, relue par Codex en plusieurs passes, corrigée ; `/docs export-ignore` dans `.gitattributes`.

---

## Environnement de test

Les tâches suivantes se vérifient sur les SPIP de test. Les variables ci-dessous sont définies dans `tests/integration/environnement.sh` (copie locale, non versionnée, de `tests/integration/environnement.exemple.sh`, créé à la Task 1) :

| Variable | Contenu |
|---|---|
| `WP2SPIP` | dépôt wp2spip |
| `SPIP_CLI` | exécutable `spip` de SPIP-Cli |
| `SAUVEGARDES` | dossier des états vierges, journaux et exports de référence |
| `SPIP_WP6`, `WP6`, `BASE_WP6` | SPIP qui importe le WordPress 6.9 de test, dossier de ce WordPress, base MySQL partagée |
| `SPIP_WP7`, `WP7`, `BASE_WP7` | idem pour WordPress 7.1 |
| `SPIP_REEL_SQLITE`, `SPIP_REEL_MYSQL`, `BASE_REEL_MYSQL`, `WP_REEL` | SPIP qui importent le site réel (SQLite, MySQL), base MySQL du second, dossier du WordPress réel |
| `MYSQL_OPTIONS`, `MYSQL_PWD` | accès MySQL (par exemple `-uutilisateur`) |

Chaque commande de vérification commence par `source "$WP2SPIP/tests/integration/environnement.sh"` : exporter d'abord `WP2SPIP` (chemin du dépôt) dans le shell, sans quoi ce `source` échoue. Dans un pipeline (`… | grep`), activer `set -o pipefail` pour garder le code de sortie de la commande.

---

## Lot 1 — Outillage de vérification

### Task 1 : remise à zéro, export comparable, vérification des identifiants

**Files :**
- Create : `tests/integration/environnement.exemple.sh`
- Create : `tests/integration/remise_a_zero.sh`
- Create : `tests/integration/exporter_import.php`
- Create : `tests/integration/verifier_identifiants.php`
- Create : `.gitignore`
- Modify : `.gitattributes` (dernière ligne)

**Interfaces :**
- Produces : `remise_a_zero.sh <site> <état vierge> [base]` ; `exporter_import.php` (export TSV trié, indépendant des identifiants SPIP) ; `verifier_identifiants.php` (affiche `OK` ou `ECHEC` suivi de la liste des écarts) ; états vierges `$SAUVEGARDES/vierge-*-v2.*` avec Accès restreint actif ; exports de référence `$SAUVEGARDES/export-*-reference.tsv`.

- [x] **Step 1 : modèle d'environnement**

`tests/integration/environnement.exemple.sh` :

```bash
# Copier en environnement.sh (non versionné) et renseigner les valeurs de la machine de test
export WP2SPIP=/chemin/vers/wp2spip
export SPIP_CLI=/chemin/vers/spip-cli/bin/spip
export SAUVEGARDES=/chemin/vers/sauvegardes

# WordPress 6.9 et 7.1 de test (contenu Theme Unit Test), et les SPIP qui les importent
export SPIP_WP6=/chemin/vers/spip-wp6
export WP6=/chemin/vers/wordpress-6.9
export BASE_WP6=base_wp6
export SPIP_WP7=/chemin/vers/spip-wp7
export WP7=/chemin/vers/wordpress-7.1
export BASE_WP7=base_wp7

# Site réel, importé dans un SPIP SQLite et dans un SPIP MySQL
export WP_REEL=/chemin/vers/wordpress-reel
export SPIP_REEL_SQLITE=/chemin/vers/spip-reel-sqlite
export SPIP_REEL_MYSQL=/chemin/vers/spip-reel-mysql
export BASE_REEL_MYSQL=base_reel

# Accès MySQL
export MYSQL_OPTIONS=-uutilisateur
export MYSQL_PWD=motdepasse
```

`.gitignore` :

```
/tests/integration/environnement.sh
```

Ajouter à la fin de `.gitattributes` :

```
/tests export-ignore
```

Puis créer localement `tests/integration/environnement.sh` avec les valeurs de la machine de test.

- [x] **Step 2 : script de remise à zéro**

`tests/integration/remise_a_zero.sh` (exécutable, `chmod +x`) :

```bash
#!/bin/bash
# Remet un site SPIP de test dans son état vierge
# Usage : remise_a_zero.sh <dossier du site SPIP> <état vierge> [base MySQL]
#   état vierge .sql.gz : dump des tables spip_* ; toutes les tables spip_* de la base sont d'abord supprimées
#                         (y compris celles créées depuis par un plugin, Accès restreint par exemple)
#   état vierge .tgz : archive de config/bases, IMG et local (site en SQLite)
# Accès MySQL : MYSQL_OPTIONS et MYSQL_PWD
set -euo pipefail
site=$1
vierge=$2
cd "$site"
case "$vierge" in
	*.sql.gz)
		base=${3:?base MySQL attendue}
		tables=$(mysql ${MYSQL_OPTIONS:-} -N "$base" -e "show tables like 'spip\\_%'" | paste -sd, -)
		if [ -n "$tables" ]; then
			mysql ${MYSQL_OPTIONS:-} "$base" -e "drop table $tables"
		fi
		zcat "$vierge" | mysql ${MYSQL_OPTIONS:-} "$base"
		rm -rf IMG/* local/*
		;;
	*.tgz)
		rm -rf config/bases IMG local
		tar xzf "$vierge"
		;;
	*)
		echo "État vierge inconnu : $vierge" >&2
		exit 1
		;;
esac
rm -rf tmp/cache/*
echo "Site remis à zéro : $site"
```

- [x] **Step 3 : export comparable**

`tests/integration/exporter_import.php` :

```php
<?php
/**
 * Exporte le contenu importé, indépendamment des identifiants SPIP, pour comparer deux imports
 *
 * Chaque objet est désigné par son identifiant Wordpress ; dans les textes, les raccourcis
 * SPIP (articleN, docN, imgN…) sont réécrits avec l'identifiant Wordpress de l'objet visé.
 * Une ligne par objet ou par lien, triées.
 *
 * Usage, depuis le site SPIP :
 *   spip php:eval 'include "<wp2spip>/tests/integration/exporter_import.php";' > export.tsv
 */
include_spip('inc/plugin');

$correspondances = function ($table, $cle) {
	return array_column(sql_allfetsel("$cle, id_wordpress", $table, ''), 'id_wordpress', $cle);
};
$articles = $correspondances('spip_articles', 'id_article');
$rubriques = $correspondances('spip_rubriques', 'id_rubrique');
$documents = $correspondances('spip_documents', 'id_document');
$auteurs = $correspondances('spip_auteurs', 'id_auteur');
$forums = $correspondances('spip_forum', 'id_forum');

$wp = function ($ids, $id) {
	return isset($ids[$id]) ? 'wp' . $ids[$id] : 'spip' . $id;
};
$normaliser = function ($texte) use ($articles, $rubriques, $documents, $wp) {
	$texte = str_replace(array("\r", "\n", "\t"), array('', '\n', ' '), (string) $texte);
	return preg_replace_callback('#\b(article|rubrique|document|doc|img|emb)(\d+)#', function ($m) use ($articles, $rubriques, $documents, $wp) {
		$ids = array('article' => $articles, 'rubrique' => $rubriques)[$m[1]] ?? $documents;
		return $m[1] . '#' . $wp($ids, $m[2]);
	}, $texte);
};

$lignes = array();
foreach (sql_allfetsel('*', 'spip_rubriques', 'id_wordpress > 0') as $r) {
	$lignes[] = array('rubrique', $r['id_wordpress'], $r['titre'], $normaliser($r['texte']), 'parent#' . $wp($rubriques, $r['id_parent']), $r['statut']);
}
foreach (sql_allfetsel('*', 'spip_articles', 'id_wordpress > 0') as $a) {
	$lignes[] = array('article', $a['id_wordpress'], $a['titre'], $a['statut'], $a['date'], $a['date_modif'], $a['page'], 'rubrique#' . $wp($rubriques, $a['id_rubrique']), $a['accepter_forum'], $normaliser($a['texte']));
}
foreach (sql_allfetsel('*', 'spip_documents', 'id_wordpress > 0') as $d) {
	$lignes[] = array('document', $d['id_wordpress'], $d['titre'], $normaliser($d['descriptif']), $d['date'], $d['fichier'], $d['mode'], $d['statut']);
}
foreach (sql_allfetsel('*', 'spip_auteurs', 'id_wordpress > 0') as $u) {
	$lignes[] = array('auteur', $u['id_wordpress'], $u['nom'], $u['email'], $u['login'], $u['statut'], $u['webmestre']);
}
foreach (sql_allfetsel('*', 'spip_forum', 'id_wordpress > 0') as $f) {
	$lignes[] = array('forum', $f['id_wordpress'], 'article#' . $wp($articles, $f['id_objet']), $f['statut'], $f['date_heure'], 'parent#' . $wp($forums, $f['id_parent']), 'thread#' . $wp($forums, $f['id_thread']), $f['date_thread'], $normaliser($f['texte']));
}
foreach (sql_allfetsel('*', 'spip_documents_liens', 'objet = "article"') as $l) {
	$lignes[] = array('document_article', $wp($documents, $l['id_document']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_auteurs_liens', 'objet = "article"') as $l) {
	$lignes[] = array('auteur_article', $wp($auteurs, $l['id_auteur']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_rubriques_liens', 'objet = "article"') as $l) {
	$lignes[] = array('rubrique_secondaire', $wp($rubriques, $l['id_parent']), 'article#' . $wp($articles, $l['id_objet']));
}
foreach (sql_allfetsel('*', 'spip_urls', sql_in('type', array('article', 'document'))) as $u) {
	$ids = ($u['type'] == 'article') ? $articles : $documents;
	$lignes[] = array('url', $u['type'], $wp($ids, $u['id_objet']), $u['url']);
}
if (test_plugin_actif('accesrestreint')) {
	foreach (sql_allfetsel('z.titre, l.id_objet', 'spip_zones_liens as l join spip_zones as z on z.id_zone = l.id_zone', 'l.objet = "article"') as $l) {
		$lignes[] = array('zone', $l['titre'], 'article#' . $wp($articles, $l['id_objet']));
	}
}

$lignes = array_map(function ($ligne) { return join("\t", $ligne); }, $lignes);
sort($lignes);
echo join("\n", $lignes) . "\n";
```

- [x] **Step 4 : vérification des identifiants**

`tests/integration/verifier_identifiants.php` :

```php
<?php
/**
 * Vérifie, sur un SPIP importé, que les identifiants Wordpress sont conservés (spec § 6, sous-projet 1)
 *
 * - articles, rubriques et documents importés : identifiant SPIP = id_wordpress ;
 * - tous les contenus et catégories Wordpress sont importés ;
 * - liens internes : chaque [->articleN] désigne un article existant, et aucun lien
 *   ?p= ou ?page_id= vers le site d'origine ne reste dans les textes.
 *
 * Usage, depuis le site SPIP (base Wordpress déclarée sous le nom "wordpress") :
 *   spip php:eval 'include "<wp2spip>/tests/integration/verifier_identifiants.php";'
 * Affiche OK, ou ECHEC et la liste des écarts avec le code de sortie 1.
 */
include_spip('base/objets');
include_spip('wp2spip/importer_articles');
$base = 'wordpress';
$echecs = array();

foreach (array('article', 'rubrique', 'document') as $objet) {
	$table = table_objet_sql($objet);
	$cle = id_table_objet($objet);
	$total = sql_countsel($table, 'id_wordpress > 0');
	$ecarts = sql_countsel($table, "id_wordpress > 0 and $cle != id_wordpress");
	echo "$table : $total objets importés, $ecarts identifiants différents de id_wordpress\n";
	if ($ecarts) {
		$echecs[] = "$table : $ecarts objets dont l'identifiant diffère de id_wordpress";
	}
}

$nb_wp = sql_countsel('wp_posts', sql_in('post_type', array('post', 'page')), '', '', $base);
$nb_spip = sql_countsel('spip_articles', 'id_wordpress > 0');
if ($nb_wp != $nb_spip) {
	$echecs[] = "articles : $nb_spip importés pour $nb_wp contenus Wordpress";
}
$nb_wp = sql_countsel('wp_term_taxonomy', sql_in('taxonomy', array('category', 'link_category')), '', '', $base);
$nb_spip = sql_countsel('spip_rubriques', 'id_wordpress > 0');
if ($nb_wp != $nb_spip) {
	$echecs[] = "rubriques : $nb_spip importées pour $nb_wp catégories Wordpress";
}

$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', $base);
foreach (sql_allfetsel('id_article, texte', 'spip_articles', 'id_wordpress > 0') as $article) {
	preg_match_all('#->article(\d+)\]#', $article['texte'], $trouves);
	foreach (array_unique($trouves[1]) as $id) {
		if (!sql_countsel('spip_articles', 'id_article = ' . intval($id))) {
			$echecs[] = "article {$article['id_article']} : lien vers article$id, inexistant";
		}
	}
	preg_match_all('#->([^\]]*[?&](?:p|page_id)=\d+[^\]]*)\]#', $article['texte'], $trouves);
	foreach ($trouves[1] as $url) {
		if (wp2spip_url_du_site($url, $url_wordpress)) {
			$echecs[] = "article {$article['id_article']} : lien Wordpress non converti $url";
		}
	}
}

if ($echecs) {
	echo "ECHEC\n- " . join("\n- ", $echecs) . "\n";
	exit(1);
}
echo "OK\n";
```

- [x] **Step 5 : états vierges avec Accès restreint**

Les états vierges des SPIP de test ont été pris avant l'activation d'Accès restreint. En refaire un, pour chaque site. Le SPIP du WordPress 7.1 reste **sans** Accès restreint : il couvre le cas où le plugin est absent (contenus privés et protégés non publiés). Le SPIP SQLite du site réel reçoit le plugin (copie du dossier `acces_restreint` du SPIP MySQL), pour que les deux imports du site réel restent comparables.

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
for n in 6 7; do
	site=SPIP_WP$n; base=BASE_WP$n
	plugins="forum"; [ $n = 6 ] && plugins="accesrestreint forum"
	"$WP2SPIP/tests/integration/remise_a_zero.sh" "${!site}" "$SAUVEGARDES/vierge-wp$n.sql.gz" "${!base}"
	(cd "${!site}" && "$SPIP_CLI" plugins:activer $plugins -y && "$SPIP_CLI" plugins:maj:bdd && "$SPIP_CLI" plugins:lister | grep -E "accesrestreint|forum|pages|polyhier|sale|wp2spip")
	mysqldump --no-tablespaces $MYSQL_OPTIONS "${!base}" $(mysql $MYSQL_OPTIONS -N "${!base}" -e "show tables like 'spip\\_%'") | gzip > "$SAUVEGARDES/vierge-wp$n-v2.sql.gz"
done
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_MYSQL" "$SAUVEGARDES/vierge-mysql.sql.gz" "$BASE_REEL_MYSQL"
(cd "$SPIP_REEL_MYSQL" && "$SPIP_CLI" plugins:activer accesrestreint forum -y && "$SPIP_CLI" plugins:maj:bdd)
mysqldump --no-tablespaces $MYSQL_OPTIONS "$BASE_REEL_MYSQL" $(mysql $MYSQL_OPTIONS -N "$BASE_REEL_MYSQL" -e "show tables like 'spip\\_%'") | gzip > "$SAUVEGARDES/vierge-mysql-v2.sql.gz"
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge.tgz"
(cd "$SPIP_REEL_SQLITE" && "$SPIP_CLI" plugins:activer accesrestreint forum -y && "$SPIP_CLI" plugins:maj:bdd && tar czf "$SAUVEGARDES/vierge-sqlite-v2.tgz" config/bases IMG local)
```

Expected : pour chaque site, `plugins:lister` montre les six plugins actifs (cinq pour le SPIP du WordPress 7.1, sans `accesrestreint`) ; les quatre fichiers `vierge-*-v2.*` existent. Dans la suite, « remettre à zéro » = `remise_a_zero.sh` avec ces fichiers `-v2`.

- [x] **Step 6 : exports de référence avec le code actuel**

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
"$SPIP_CLI" wordpress:importer "$WP6" --no-ansi > "$SAUVEGARDES/import-wp6-reference.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp6-reference.tsv"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';"
wc -l "$SAUVEGARDES/export-wp6-reference.tsv"
```

Expected : `code 0` ; export de plusieurs centaines de lignes ; `verifier_identifiants.php` affiche **ECHEC** avec des identifiants différents de id_wordpress, et sort avec le code `1` : c'est le test qui échoue avant le sous-projet 1.

Faire de même pour le site réel en SQLite (`$SPIP_REEL_SQLITE`, `$WP_REEL`, `vierge-sqlite-v2.tgz`, fichiers `import-reel-sqlite-reference.log` et `export-reel-sqlite-reference.tsv`).

- [x] **Step 7 : commit**

```bash
cd "$WP2SPIP"
git add .gitignore .gitattributes tests/integration/environnement.exemple.sh tests/integration/remise_a_zero.sh tests/integration/exporter_import.php tests/integration/verifier_identifiants.php
git status --short   # environnement.sh ne doit pas apparaître
git commit -m "Tests d'intégration : remise à zéro, export comparable, vérification des identifiants"
```

---

## Lot 2 — Sous-projets 1 et 2 : identifiants conservés, corrections et fiabilité

Les deux sous-projets sont réalisés ensemble : la conservation des identifiants a besoin que la commande sache s'arrêter sur un échec (sous-projet 2), et elle réécrit la création des objets d'où l'on retire `--update`. Ordre : échec et codes de sortie, retrait de `--update`, ordre de lecture, puis identifiants objet par objet, liens internes, et enfin les corrections indépendantes.

**Contrôle de non-régression** (cité « contrôle NR » ci-dessous) : remettre à zéro le SPIP WP 6.9, importer, exporter, comparer à la référence.

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
"$SPIP_CLI" wordpress:importer "$WP6" --no-ansi > "$SAUVEGARDES/import-wp6.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp6.tsv"
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6.tsv" && echo IDENTIQUE
```

### Task 2 : échec d'un traitement et codes de sortie

**Files :**
- Modify : `spip-cli/WordpressImporter.php` (liste des traitements, `--traitements`, boucle, `appliquer_traitement()`)

**Interfaces :**
- Produces : un traitement qui retourne `false` arrête la commande avec le code `1` ; tout autre retour (dont `null`) vaut succès. `appliquer_traitement(string $traitement): bool`.

- [x] **Step 1 : constater le comportement actuel**

```bash
source "$WP2SPIP/tests/integration/environnement.sh"; cd "$SPIP_WP6"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_inconnu --no-ansi; echo "code $?"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_mots --no-ansi; echo "code $?"
```

Expected (avant correction) : `code 0` les deux fois ; le second affiche « Aucune fonction implémentée ».

- [x] **Step 2 : liste par défaut sans `importer_mots`**

Dans `execute()`, remplacer la liste :

```php
		$traitements_disponibles = array(
			'importer_metas',
			'importer_auteurs',
			'importer_rubriques',
			'importer_documents',
			'importer_articles',
			'importer_acces',
			'importer_polyhierarchie',
			'importer_commentaires',
		);
```

- [x] **Step 3 : refuser les noms inconnus et s'arrêter sur un échec**

Remplacer, de `// Peut-être qu'on veut lancer seulement certains traitements` jusqu'au `return Command::SUCCESS;` final de `execute()` :

```php
		// Peut-être qu'on veut lancer seulement certains traitements
		if ($traitements_ok = $input->getOption('traitements')) {
			$traitements_ok = array_filter(array_map('trim', explode(',', $traitements_ok)));
			if ($inconnus = array_diff($traitements_ok, $traitements_disponibles)) {
				$output->writeln('<error>Traitements inconnus : ' . join(', ', $inconnus) . '. Traitements disponibles : ' . join(', ', $traitements_disponibles) . '</error>');
				return Command::FAILURE;
			}
			// Toujours dans l'ordre de la liste : les traitements dépendent des précédents
			$traitements_ok = array_values(array_intersect($traitements_disponibles, $traitements_ok));
		}
		else {
			$traitements_ok = $traitements_disponibles;
		}
		
		// Un traitement en échec arrête l'import : les suivants dépendent de lui (les articles des rubriques…)
		foreach ($traitements_ok as $traitement) {
			if (!$this->appliquer_traitement($traitement)) {
				$output->writeln("\n<error>Import arrêté : le traitement « $traitement » a échoué.</error>");
				return Command::FAILURE;
			}
		}
		
		return Command::SUCCESS;
```

- [x] **Step 4 : `appliquer_traitement()` retourne un booléen**

Remplacer la fin de `appliquer_traitement()`, depuis `// Sinon rien, on peut pas faire cette opération` :

```php
		// Sinon rien, on peut pas faire cette opération
		else {
			$this->output->writeln("\n<error>Aucune fonction implémentée pour le traitement « $traitement » pour cette version {$this->wp_version}.</error>");
			return false;
		}
		
		// On lance le traitement trouvé : il signale un échec en retournant false
		$this->output->writeln("\n<info>Lancement du traitement « $traitement »…</info>");
		return $fonction($this) !== false;
	}
```

et ajouter le type de retour à la signature : `protected function appliquer_traitement($traitement): bool {`.

- [x] **Step 5 : vérifier**

```bash
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_inconnu --no-ansi; echo "code $?"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_mots --no-ansi; echo "code $?"
"$SPIP_CLI" wordpress:importer "$WP6" -i --no-ansi | grep "Traitements disponibles"
```

Expected : « Traitements inconnus : importer_inconnu… » et `code 1` ; idem pour `importer_mots` ; la liste disponible ne contient plus `importer_mots`. Puis contrôle NR : `code 0` et `IDENTIQUE`.

- [x] **Step 6 : commit**

```bash
git add spip-cli/WordpressImporter.php
git commit -m "Codes de sortie : traitements inconnus refusés, arrêt sur un échec"
```

### Task 2 bis : dates de modification WordPress conservées (ajoutée à l'exécution)

Constat au premier contrôle NR : `date_modif` change d'un import à l'autre. Associer un auteur ou une zone à un article (`objet_associer`) remet `date_modif` à la date du jour, après que `importer_articles` y a écrit la date WordPress ; la spec (§ 3.5) dit pourtant ces dates conservées.

- [x] `importer_articles.php` : écriture des dates WordPress (`$supplements`) déplacée après les associations (documents, URL, auteur).
- [x] `importer_acces.php` : `date_modif` rétablie (`post_modified`) après la publication et l'association à la zone.
- [x] Vérification : 82 sur 82 articles du WP 6.9 avec `date_modif` = `post_modified`, `date` et `date_redac` = `post_date`.
- [x] Exports de référence (Task 1, Step 6) refaits avec ce correctif.

### Task 3 : suppression de `--update`

**Files :**
- Modify : `spip-cli/WordpressImporter.php`, `wp2spip/importer_auteurs.php`, `wp2spip/importer_rubriques.php`, `wp2spip/importer_documents.php`, `wp2spip/importer_articles.php`, `wp2spip/importer_acces.php`, `wp2spip/importer_commentaires.php`, `readme.md`

**Interfaces :**
- Produces : plus de propriété `$command->update` ; chaque traitement ne crée que ce qui n'est pas encore importé.

- [x] **Step 1 : constater**

```bash
grep -n "update" "$WP2SPIP"/spip-cli/*.php "$WP2SPIP"/wp2spip/*.php "$WP2SPIP"/readme.md | grep -v sql_updateq
```

Expected : une vingtaine de lignes (option, propriété, branches `elseif ($command->update)`).

- [x] **Step 2 : la commande**

Dans `spip-cli/WordpressImporter.php` :
- supprimer `public $update = false;` ;
- supprimer le bloc `->addOption('update', 'u', …)` ;
- supprimer `// Est-ce qu'on doit mettre à jourles choses déjà migrées ?` et la ligne `$this->update = $input->getOption('update');` ;
- dans le message d'infos, remplacer la ligne `'* <comment>'. ($this->update ? … ) .'</comment>',` par :

```php
			'* <comment>Les contenus déjà importés ne sont pas ré-importés.</comment>',
```

- remplacer le second paragraphe de `setHelp()` par :

```
Lorsqu’un contenu est déjà importé (auteur, article, etc), une trace est gardée et il ne sera jamais réimporté : on peut relancer la commande, ou un traitement seul. L’import part d’un Wordpress figé. Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.
```

- [x] **Step 3 : les traitements auteurs, rubriques, documents, articles**

Dans chacun des quatre fichiers, remplacer le bloc d'en-tête (`// S'il n'y a pas l'option update…` jusqu'à l'accolade fermante du `if`) par, avec la table du traitement (`spip_auteurs`, `spip_rubriques`, `spip_documents`, `spip_articles`) :

```php
	// Les contenus déjà importés ne sont pas retouchés
	$ids_wordpress = array_column(sql_allfetsel('id_wordpress', 'spip_auteurs', 'id_wordpress>0'), 'id_wordpress') ?: array(0);
```

Puis supprimer dans chacun la branche `// Sinon on ne met à jour que si demandé` / `elseif ($command->update) { … }` entière et la ligne `$nb_maj = 0;`. Dans `importer_documents.php` et `importer_articles.php`, remplacer `$command->update ? $nb_maj++ : $nb_import++;` par `$nb_import++;`.

- [x] **Step 4 : `importer_acces.php`**

Remplacer :

```php
		// Déjà traité lors d'un import précédent : on ne touche plus à ce qu'en a fait le site, sauf mise à jour
		$deja = sql_countsel('spip_zones_liens', array('id_zone = ' . $zones[$cle], 'objet = "article"', 'id_objet = ' . $id_article));
		if ($deja and !$command->update) {
```

par :

```php
		// Déjà traité lors d'un import précédent : on ne touche plus à ce qu'en a fait le site
		if (sql_countsel('spip_zones_liens', array('id_zone = ' . $zones[$cle], 'objet = "article"', 'id_objet = ' . $id_article))) {
```

et supprimer le bloc `// En mise à jour, retirer de nos zones…` / `if ($command->update) { … }` entier.

- [x] **Step 5 : `importer_commentaires.php`**

Dans le docblock, remplacer les deux lignes `- chaque message garde son id_wordpress : …` / `et --update met à jour …` par :

```php
 * - chaque message garde son id_wordpress : un nouvel import ne crée pas de doublon
```

Remplacer :

```php
		if (isset($forums[$id_comment]) and !$command->update) {
```

par `if (isset($forums[$id_comment])) {`, puis le bloc d'écriture :

```php
		if (isset($forums[$id_comment])) {
			sql_updateq('spip_forum', $forum, 'id_forum = ' . intval($forums[$id_comment]));
			$nb_maj++;
		}
		elseif ($id_forum = sql_insertq('spip_forum', $forum)) {
```

par :

```php
		if ($id_forum = sql_insertq('spip_forum', $forum)) {
```

Supprimer `$nb_maj = 0;`, remplacer le commentaire `// 1. Créer ou mettre à jour les messages` par `// 1. Créer les messages`, et le bilan final par :

```php
	$command->output->writeln("$nb_import messages importés, " . count($threads) . " fils de discussion."
		. ($nb_sans_article ? " $nb_sans_article commentaires ignorés (contenu non importé)." : ''));
```

- [x] **Step 6 : readme**

Dans `readme.md`, supprimer la ligne `-u, --update …` de l'aide, remplacer le paragraphe d'aide « Lorsqu’un contenu est déjà importé… » par le texte du Step 2, et remplacer la section « Importer un site qui continue de vivre » par :

```markdown
## Refaire un import
L'import part d'un Wordpress figé : une copie du site, ou un site qui n'évolue plus pendant la migration. Relancer la commande n'importe que les contenus pas encore importés, et ne modifie pas ceux qui le sont déjà.

Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.
```

- [x] **Step 7 : vérifier**

```bash
grep -n "update" "$WP2SPIP"/spip-cli/*.php "$WP2SPIP"/wp2spip/*.php "$WP2SPIP"/readme.md | grep -v sql_updateq
cd "$SPIP_WP6"; "$SPIP_CLI" wordpress:importer "$WP6" --update --no-ansi; echo "code $?"
for f in "$WP2SPIP"/spip-cli/*.php "$WP2SPIP"/wp2spip/*.php; do php -l "$f" | grep -v "^No syntax"; done
```

Expected : aucune ligne ; l'option `--update` n'existe pas (`code 1`) ; aucune erreur de syntaxe. Puis contrôle NR : `IDENTIQUE`. Puis relancer l'import sur le même site sans remise à zéro : `code 0`, `diff` toujours `IDENTIQUE` (rien n'est recréé).

- [x] **Step 8 : commit**

```bash
git add spip-cli/WordpressImporter.php wp2spip/*.php readme.md
git commit -m "Suppression de l'option --update : l'import part d'un Wordpress figé"
```

### Task 4 : contenus lus dans l'ordre de leur identifiant WordPress

**Files :**
- Modify : `wp2spip/importer_auteurs.php`, `wp2spip/importer_rubriques.php`, `wp2spip/importer_documents.php`, `wp2spip/importer_articles.php` (requête principale et `wp2spip_index_medias()`), `wp2spip/importer_acces.php`

**Interfaces :**
- Produces : traitements reproductibles d'un import à l'autre, quel que soit le moteur.

- [x] **Step 1 : ajouter les `ORDER BY`**

L'ordre est le 5ᵉ paramètre de `sql_allfetsel($select, $from, $where, $groupby, $orderby, $limit, $having, $serveur)`. Dans les appels qui lisent la base WordPress, remplacer le 5ᵉ paramètre `''` par :

| Fichier | Requête | Ordre |
|---|---|---|
| `importer_auteurs.php` | `wp_users` | `'ID'` |
| `importer_rubriques.php` | `wp_term_taxonomy … wp_terms` | `'term.term_id'` |
| `importer_documents.php` | `wp_posts` (attachments) | `'ID'` |
| `importer_articles.php` | `wp_posts` (post, page) | `'ID'` |
| `importer_articles.php`, `wp2spip_index_medias()` | `wp_posts` (guid) | `'ID'` |
| `importer_articles.php`, `wp2spip_index_medias()` | `wp_postmeta` | `'meta_id'` |
| `importer_articles.php`, `wp2spip_importer_articles_documents()` | `wp_posts` (attachments d'un post) | `'ID'` |
| `importer_acces.php` | `wp_posts` | `'ID'` |

Exemple pour `importer_articles.php` :

```php
	if ($wp_posts = sql_allfetsel(
		'*',
		'wp_posts',
		array(
			sql_in('post_type', array('post', 'page')),
			sql_in('ID', $ids_wordpress, 'NOT'),
		),
		'',
		'ID',
		'',
		'',
		$command->base
	)) {
```

(supprimer au passage la ligne commentée `//'post_status = "publish"',`).

- [x] **Step 2 : vérifier**

Relire `git diff` : chacune des huit requêtes du tableau a son ordre, et aucune autre requête n'a changé. Puis contrôle NR. Expected : `IDENTIQUE` (MySQL renvoyait déjà cet ordre en pratique ; seule la garantie change).

- [x] **Step 3 : commit**

```bash
git add wp2spip/*.php
git commit -m "Contenus Wordpress lus dans l'ordre de leur identifiant"
```

### Task 5 : rubriques avec l'identifiant de leur catégorie

**Files :**
- Create : `inc/wp2spip.php`
- Modify : `wp2spip/importer_rubriques.php`, `wp2spip/importer_auteurs.php`

**Interfaces :**
- Produces (dans `inc/wp2spip.php`) :
  - `wp2spip_identifiants_occupes(string $objet, array $ids): array` — identifiants (int) parmi `$ids` déjà pris par un objet SPIP ;
  - `wp2spip_verifier_identifiants($command, string $objet, array $ids): bool` — `false` (et message) si un identifiant est occupé ;
  - `wp2spip_erreur_insertion($command, string $objet, int $id): bool` — message, retourne `false` ;
  - `wp2spip_erreur_modification($command, string $objet, int $id, string $erreur): bool` — message avec l'erreur de `objet_modifier()`, retourne `false`.
- Consumes : `appliquer_traitement()` arrête l'import quand un traitement retourne `false` (Task 2).

- [x] **Step 1 : test qui échoue — identifiant occupé**

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
"$SPIP_CLI" php:eval 'include_spip("action/editer_objet"); var_dump(objet_inserer("rubrique", 0, array("id_rubrique" => 1, "titre" => "Occupée")));'
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_rubriques --no-ansi; echo "code $?"
"$SPIP_CLI" php:eval 'var_dump(sql_countsel("spip_rubriques"));'
```

Expected avant correction : `int(1)` à l'insertion, puis l'import passe (`code 0`) et crée toutes les catégories à côté de la rubrique 1 (compte = catégories + 1).

- [x] **Step 2 : helpers**

`inc/wp2spip.php` :

```php
<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/objets');

/**
 * Identifiants, parmi ceux à créer, déjà pris par un objet SPIP
 *
 * L'import crée articles, rubriques et documents avec l'identifiant de leur source Wordpress.
 * Les contenus déjà importés étant écartés avant, un identifiant pris l'est par un objet
 * qui ne vient pas de ce contenu Wordpress (SPIP non vierge, import interrompu d'une version
 * antérieure de wp2spip…).
 *
 * @param string $objet article, rubrique ou document
 * @param array $ids identifiants Wordpress des contenus pas encore importés
 * @return array identifiants occupés
 */
function wp2spip_identifiants_occupes($objet, $ids) {
	if (!$ids) {
		return array();
	}
	$cle = id_table_objet($objet);
	$occupes = sql_allfetsel($cle, table_objet_sql($objet), sql_in($cle, array_map('intval', $ids)));
	return array_map('intval', array_column($occupes, $cle));
}

/**
 * Vérifie, avant de créer le moindre objet de ce type, que les identifiants à créer sont libres
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param array $ids
 * @return bool false si un identifiant est occupé : le traitement doit s'arrêter
 */
function wp2spip_verifier_identifiants($command, $objet, $ids) {
	if (!$occupes = wp2spip_identifiants_occupes($objet, $ids)) {
		return true;
	}
	$command->output->writeln(
		'<error>' . count($occupes) . " identifiants de $objet déjà pris par des objets SPIP qui ne viennent pas de ce Wordpress : "
		. join(', ', array_slice($occupes, 0, 20)) . (count($occupes) > 20 ? '…' : '') . '</error>'
	);
	$command->output->writeln('<error>L’import se fait dans un SPIP vierge : remettre le SPIP à zéro, puis relancer un import complet.</error>');
	return false;
}

/**
 * Signale un objet créé que l'API n'a pas pu renseigner
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param int $id
 * @param string $erreur message retourné par objet_modifier()
 * @return bool false, à retourner par le traitement
 */
function wp2spip_erreur_modification($command, $objet, $id, $erreur) {
	$command->output->writeln("\n<error>Impossible de renseigner l’objet $objet $id : $erreur</error>");
	return false;
}

/**
 * Signale un objet qui n'a pas pu être créé avec l'identifiant de son contenu Wordpress
 *
 * @param WordpressImporter $command
 * @param string $objet
 * @param int $id
 * @return bool false, à retourner par le traitement
 */
function wp2spip_erreur_insertion($command, $objet, $id) {
	$command->output->writeln("\n<error>Impossible de créer l’objet $objet $id avec l’identifiant de son contenu Wordpress.</error>");
	return false;
}
```

- [x] **Step 3 : `importer_rubriques.php`**

Après `$wp_categories = wp2spip_enfants_rubriques($wp_categories, 0);`, ajouter `include_spip('inc/wp2spip');` aux inclusions du début du bloc, et :

```php
		// Chaque rubrique prend l'identifiant de sa catégorie : ils doivent tous être libres
		if (!wp2spip_verifier_identifiants($command, 'rubrique', array_column($wp_categories, 'id_term'))) {
			return false;
		}
```

Dans la boucle, remplacer la composition de la rubrique et sa création (de `// On compose la rubrique SPIP` jusqu'à la fin du `if (!$rubrique_old = …) { … }`) par :

```php
			// On compose la rubrique SPIP
			$rubrique = array(
				'id_parent' => $id_parent,
				'confirme_deplace' => 'oui',
				'titre' => texte_backend(sale($wp_category['titre'])),
				'texte' => texte_backend(sale($wp_category['description'])),
			);
			
			// Créée avec l'identifiant de la catégorie et son id_wordpress, en une seule insertion :
			// aucune rubrique n'existe sans son id_wordpress
			$id_rubrique = $id_wordpress_category;
			if (objet_inserer('rubrique', $id_parent, array('id_rubrique' => $id_rubrique, 'id_wordpress' => $id_wordpress_category)) != $id_rubrique) {
				return wp2spip_erreur_insertion($command, 'rubrique', $id_rubrique);
			}
			
			// INSUP
			autoriser_exception('modifier', 'rubrique', $id_rubrique, true);
			autoriser_exception('instituer', 'rubrique', $id_rubrique, true);
			autoriser_exception('publierdans', 'rubrique', $id_parent, true);
			autoriser_exception('creerrubriquedans', 'rubrique', $id_parent, true);
			
			// objet_modifier() retourne un message d'erreur, ou une chaîne vide
			if ($erreur = objet_modifier('rubrique', $id_rubrique, $rubrique)) {
				return wp2spip_erreur_modification($command, 'rubrique', $id_rubrique, $erreur);
			}
			$nb_import++;
```

Dans `importer_auteurs.php`, ajouter `include_spip('inc/wp2spip');` aux inclusions et remplacer de même :

```php
				if ($ok = objet_modifier('auteur', $id_auteur, $auteur)) {
					$nb_import++;
				}
```

par :

```php
				if ($erreur = objet_modifier('auteur', $id_auteur, $auteur)) {
					return wp2spip_erreur_modification($command, 'auteur', $id_auteur, $erreur);
				}
				$nb_import++;
```

(la recherche du parent par `id_wordpress`, juste avant, reste inchangée.)

Ajouté à l'exécution : propager l'échec de `objet_modifier()` dans `importer_auteurs` a fait apparaître des logins refusés par SPIP, jusque-là ignorés sans bruit (login `admin` déjà pris par l'administrateur créé à l'installation ; login de moins de 4 caractères). Avant `objet_modifier()`, le login est contrôlé par `auth_spip_verifier_login()` (inclure `auth/spip`) ; s'il est refusé, l'auteur est importé sans login, comme avant, avec un avertissement qui donne la raison :

```php
			// Login refusé par SPIP (déjà pris, souvent par l'administrateur créé à l'installation, ou trop court) :
			// on importe l'auteur sans login, à compléter à la main
			if ($refus = auth_spip_verifier_login($auteur['login'])) {
				$command->output->writeln("\nLogin « {$auteur['login']} » refusé par SPIP ($refus) : l’auteur Wordpress $id_wordpress_user est importé sans login.");
				unset($auteur['login']);
			}
```

- [x] **Step 4 : vérifier**

Rejouer le Step 1. Expected : « 1 identifiants de rubrique déjà pris … : 1 », « Import arrêté », `code 1`, `int(1)` rubrique en base (rien n'a été créé).

Puis contrôle NR, et :

```bash
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | grep spip_rubriques
```

Expected : `IDENTIQUE` ; `spip_rubriques : N objets importés, 0 identifiants différents de id_wordpress`.

Puis la même vérification sur SQLite, avec le site réel (identifiants imposés en SQLite) :

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
cd "$SPIP_REEL_SQLITE"
"$SPIP_CLI" wordpress:importer "$WP_REEL" -t importer_metas,importer_auteurs,importer_rubriques --no-ansi; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | grep spip_rubriques
```

Expected : `code 0` ; 0 identifiant différent.

- [x] **Step 5 : commit**

```bash
git add inc/wp2spip.php wp2spip/importer_rubriques.php wp2spip/importer_auteurs.php
git commit -m "Rubriques créées avec l'identifiant de leur catégorie Wordpress, échecs de l'API signalés"
```

### Task 6 : titres et textes de rubriques sans entités HTML

**Files :**
- Modify : `inc/wp2spip.php` (nouvelle fonction), `wp2spip/importer_rubriques.php`

**Interfaces :**
- Produces : `wp2spip_decoder_entites(string $texte): string` — décode les entités HTML sauf celles de `<`, `>` et `&`.

- [x] **Step 1 : test qui échoue**

Après le contrôle NR de la Task 5 (base WP 6.9 importée) :

```bash
cd "$SPIP_WP6"; "$SPIP_CLI" php:eval 'var_dump(sql_getfetsel("titre", "spip_rubriques", "id_rubrique = 1"));'
```

Expected avant correction : `string(15) "Non class&#233;"` (WordPress stocke ce nom avec une entité).

- [x] **Step 2 : helper**

Ajouter à `inc/wp2spip.php` :

```php
/**
 * Décode les entités HTML d'un texte, sauf celles des caractères qui ont un sens en HTML
 *
 * Wordpress stocke souvent les noms et descriptions avec des entités (&#233;, &amp;…).
 * On garde &lt; &gt; &amp; (et leurs formes numériques) pour ne pas créer de balise.
 *
 * @param string $texte
 * @return string
 */
function wp2spip_decoder_entites($texte) {
	return preg_replace_callback('/&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', function ($entite) {
		$caractere = html_entity_decode($entite[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return in_array($caractere, array('<', '>', '&')) ? $entite[0] : $caractere;
	}, (string) $texte);
}
```

- [x] **Step 3 : l'appliquer aux rubriques**

Dans `importer_rubriques.php`, remplacer :

```php
				'titre' => texte_backend(sale($wp_category['titre'])),
				'texte' => texte_backend(sale($wp_category['description'])),
```

par :

```php
				'titre' => wp2spip_decoder_entites(sale($wp_category['titre'])),
				'texte' => wp2spip_decoder_entites(sale($wp_category['description'])),
```

- [x] **Step 4 : vérifier**

```bash
cd "$SPIP_WP6"
"$SPIP_CLI" php:eval 'include_spip("inc/wp2spip"); var_dump(wp2spip_decoder_entites("Non class&#233; &amp; &lt;b&gt; &eacute;t&#xE9; &#60;"));'
```

Expected : `string(…) "Non classé &amp; &lt;b&gt; été &#60;"`. Puis contrôle NR : seules diffèrent des lignes `rubrique` dont le titre ou le texte avait des entités (`Non class&#233;` → `Non classé`) ; vérifier avec `diff … | grep -v "^[<>] rubrique"` → seulement des lignes `---` et de numéros.

- [x] **Step 5 : commit**

```bash
git add inc/wp2spip.php wp2spip/importer_rubriques.php
git commit -m "Rubriques : titres et textes sans entités HTML"
```

### Task 7 : documents avec l'identifiant de leur média

**Files :**
- Modify : `wp2spip/importer_documents.php`

**Interfaces :**
- Consumes : `wp2spip_verifier_identifiants()`, `wp2spip_erreur_insertion()`, `wp2spip_erreur_modification()` (Task 5).
- Produces : `id_document` = ID du média WordPress pour tout média dont le fichier a été trouvé.

- [x] **Step 1 : test qui échoue — identifiant occupé**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
ID=$(mysql $MYSQL_OPTIONS -N "$BASE_WP6" -e "select min(ID) from wp_posts where post_type='attachment' and post_status='inherit'")
"$SPIP_CLI" php:eval "include_spip('action/editer_objet'); var_dump(objet_inserer('document', null, array('id_document' => $ID, 'titre' => 'Occupé')));"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_documents --no-ansi; echo "code $?"
"$SPIP_CLI" php:eval 'var_dump(sql_countsel("spip_documents"));'
```

Expected avant correction : `code 0` et tous les médias importés en plus du document occupant.

- [x] **Step 2 : création à identifiant imposé**

Dans `importer_documents.php`, ajouter `include_spip('inc/wp2spip');` aux inclusions, puis, juste avant `$nb_attachments = count($wp_attachments);` :

```php
		// Chaque document prend l'identifiant de son média : ils doivent tous être libres
		if (!wp2spip_verifier_identifiants($command, 'document', array_column($wp_attachments, 'ID'))) {
			return false;
		}
```

Remplacer `$nb_maj = 0;` par `$nb_refuses = 0;`. Dans la boucle, remplacer tout le bloc `if (is_readable($chemin)) { … }` par :

```php
			if (is_readable($chemin)) {
				$id_document = $id_wordpress;
				
				// On compose le document SPIP
				$document = array(
					'titre' => $wp_attachment['post_title'],
					'descriptif' => sale($wp_attachment['post_content'] ?: $wp_attachment['post_excerpt']),
					'date' => $wp_attachment['post_date'],
					'maj' => $wp_attachment['post_modified'],
				);
				
				// On compose le FILE
				$file = array(
					'tmp_name' => $chemin,
					'name' => basename($chemin),
					'titrer' => true,
					'mode' => 'auto',
				);
				
				// Document créé vide avec l'identifiant du média et son id_wordpress, en une seule insertion,
				// puis le fichier installé dedans : ajouter_un_document() met à jour un document existant
				if (objet_inserer('document', null, array('id_document' => $id_document, 'id_wordpress' => $id_wordpress)) != $id_document) {
					return wp2spip_erreur_insertion($command, 'document', $id_document);
				}
				$retour = $ajouter_un_document($id_document, $file, null, null, 'auto');
				
				// Fichier refusé (type non autorisé, taille…) : pas de document vide
				if (intval($retour) !== $id_document) {
					sql_delete('spip_documents', 'id_document = ' . $id_document);
					$nb_refuses++;
					if ($command->output->isVerbose()) {
						$command->output->writeln("\nMédia Wordpress $id_wordpress refusé : " . (is_string($retour) ? $retour : basename($chemin)));
					}
				}
				else {
					// INSUP
					autoriser_exception('modifier', 'document', $id_document, true);
					autoriser_exception('instituer', 'document', $id_document, true);
					
					if ($erreur = objet_modifier('document', $id_document, $document)) {
						return wp2spip_erreur_modification($command, 'document', $id_document, $erreur);
					}
					$nb_import++;
					
					// Ajouter l'URL libre
					if ($wp_attachment['post_name']) {
						sql_insertq(
							'spip_urls',
							array(
								'type' => 'document',
								'id_objet' => $id_document,
								'date' => $wp_attachment['post_date'],
								'url' => $wp_attachment['post_name'],
							)
						);
					}
				}
				
				// Si distant, on supprime la copie locale temporaire
				if ($distant) {
					supprimer_fichier($chemin);
				}
			}
```

Après la boucle, remplacer `// Une ligne vide à la fin` et son `writeln('')` par :

```php
		// Une ligne vide à la fin
		$command->output->writeln('');
		if ($nb_refuses) {
			$command->output->writeln("$nb_refuses médias refusés par SPIP (type de fichier non autorisé…), non importés (détail avec -v).");
		}
```

- [x] **Step 3 : vérifier**

Rejouer le Step 1. Expected : message d'identifiant pris, « Import arrêté », `code 1`, `int(1)` document en base.

Puis contrôle NR et :

```bash
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | grep spip_documents
ls "$WP6"/wp-content/uploads | head -3   # source intacte
```

Expected : `IDENTIQUE`, y compris les noms de fichiers dans `IMG/` (colonne 6 des lignes `document`) : les médias sont traités dans le même ordre (Task 4), les suffixes de collision sont donc les mêmes ; toute différence est un écart à analyser avant de continuer. 0 identifiant différent ; dossier WordPress inchangé.

- [x] **Step 4 : commit**

```bash
git add wp2spip/importer_documents.php
git commit -m "Documents créés avec l'identifiant de leur média Wordpress"
```

### Task 8 : articles et pages avec l'identifiant de leur contenu

**Files :**
- Modify : `wp2spip/importer_articles.php` (fonction `wp2spip_importer_articles_dist`)

**Interfaces :**
- Consumes : `wp2spip_verifier_identifiants()`, `wp2spip_erreur_insertion()`, `wp2spip_erreur_modification()` (Task 5).
- Produces : `id_article` = ID WordPress pour chaque `post` et `page` ; c'est ce sur quoi la Task 9 écrit les liens internes.

- [x] **Step 1 : test qui échoue — identifiant occupé**

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
ID=$(mysql $MYSQL_OPTIONS -N "$BASE_WP6" -e "select min(ID) from wp_posts where post_type in ('post','page')")
"$SPIP_CLI" php:eval "include_spip('action/editer_objet'); var_dump(objet_inserer('article', 0, array('id_article' => $ID, 'titre' => 'Occupé')));"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_articles --no-ansi; echo "code $?"
```

Expected avant correction : `code 0`.

- [x] **Step 2 : vérification préalable**

Ajouter `include_spip('inc/wp2spip');` aux inclusions, puis, juste avant `$nb_posts = count($wp_posts);` :

```php
		// Chaque article ou page prend l'identifiant de son contenu : ils doivent tous être libres
		if (!wp2spip_verifier_identifiants($command, 'article', array_column($wp_posts, 'ID'))) {
			return false;
		}
```

- [x] **Step 3 : création à identifiant imposé**

Supprimer `'id_wordpress' => $id_wordpress,` du tableau `$article`. Après la Task 3, le bloc de création se lit :

```php
			// Si ça n'a pas déjà été importé c'est un ajout
			$id_article = null;
			if (!$article_old = sql_fetsel('id_article, id_rubrique', 'spip_articles', 'id_wordpress = '.$id_wordpress)) {
				$id_article = objet_inserer('article', $id_rubrique_principale);
				
				// INSUP
				…cinq autoriser_exception…
			}
			
			// Si on a un id_article, c'est qu'on vient d'insérer ou qu'on doit mettre à jour
			if ($id_article) {
				…objet_modifier, documents, URL libre, auteur…
			}
```

Remplacer tout ce qui précède `…objet_modifier, documents, URL libre, auteur…` (de `// Si ça n'a pas déjà été importé` jusqu'à `if ($id_article) {` inclus) par :

```php
			// Créé avec l'identifiant du contenu Wordpress et son id_wordpress, en une seule insertion :
			// aucun article n'existe sans son id_wordpress
			$id_article = $id_wordpress;
			if (objet_inserer('article', $id_rubrique_principale, array('id_article' => $id_article, 'id_wordpress' => $id_wordpress)) != $id_article) {
				return wp2spip_erreur_insertion($command, 'article', $id_article);
			}
			
			// INSUP
			autoriser_exception('modifier', 'article', $id_article, true);
			autoriser_exception('instituer', 'article', $id_article, true);
			autoriser_exception('modifier', 'rubrique', $id_rubrique_principale, true);
			autoriser_exception('instituer', 'rubrique', $id_rubrique_principale, true);
			autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
```

puis supprimer l'accolade fermante de l'ancien `if ($id_article) {` et désindenter d'une tabulation son contenu (objet_modifier, documents, URL libre, auteur), qui passe au niveau de la boucle.

Enfin, propager l'échec de `objet_modifier()`. Remplacer :

```php
			if (!$erreur = objet_modifier('article', $id_article, $article)) {
				$nb_import++;
			}
```

par :

```php
			if ($erreur = objet_modifier('article', $id_article, $article)) {
				return wp2spip_erreur_modification($command, 'article', $id_article, $erreur);
			}
			$nb_import++;
```

et, à la fin de la boucle, `if (!$erreur) { sql_updateq('spip_articles', $supplements, …); }` par le seul `sql_updateq('spip_articles', $supplements, 'id_article = '.$id_article);` (Task 2 bis), puisqu'on n'y arrive plus en cas d'erreur.

- [x] **Step 4 : vérifier**

Rejouer le Step 1 : message d'identifiant pris, `code 1`, aucun article créé hormis l'occupant. Puis contrôle NR et :

```bash
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';"
```

Expected : `IDENTIQUE` ; les trois tables à 0 identifiant différent ; il peut rester des écarts « lien Wordpress non converti » : c'est le test de la Task 9.

- [x] **Step 5 : commit**

```bash
git add wp2spip/importer_articles.php
git commit -m "Articles et pages créés avec l'identifiant de leur contenu Wordpress"
```

### Task 9 : liens internes écrits d'après l'identifiant WordPress

**Files :**
- Modify : `wp2spip/importer_articles.php` (`wp2spip_chercher_lien()`, nouvelles `wp2spip_index_contenus()` et `wp2spip_normaliser_slug()`)

**Interfaces :**
- Consumes : `id_article` = ID WordPress (Task 8) ; `wp2spip_chercher_document()`, `wp2spip_url_du_site()` (existants).
- Produces : `wp2spip_index_contenus(string $base): array` → `array('types' => array(ID => post_type), 'slugs' => array(slug normalisé => array(ID, …)), 'chemins' => array(ID => chemin))` ; `wp2spip_chercher_slug(string $chemin, array $contenus): int` (0 si ambigu) ; `wp2spip_normaliser_slug(string $slug): string`.

- [x] **Step 1 : test qui échoue**

Un lien vers un contenu pas encore importé doit être converti. Le vérifier sur un SPIP où aucun article n'existe encore :

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_metas,importer_auteurs,importer_rubriques,importer_documents --no-ansi > /dev/null; echo "code $?"
cat > /tmp/lien.php <<'EOF'
<?php
include_spip('wp2spip/importer_articles');
$u = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', 'wordpress');
$p = sql_fetsel('ID, post_name', 'wp_posts', 'post_type="post" and post_status="publish"', '', 'ID desc', '1', '', 'wordpress');
var_dump(sql_countsel('spip_articles'), wp2spip_chercher_lien("$u/?p={$p['ID']}", $u), wp2spip_chercher_lien("$u/{$p['post_name']}/", $u));
EOF
"$SPIP_CLI" php:eval "include '/tmp/lien.php';"
```

Expected avant correction : `int(0)` articles, puis les deux URL renvoyées inchangées.

- [x] **Step 2 : index des contenus et résolution des slugs**

Ajouter dans `importer_articles.php`, après `wp2spip_chercher_lien()` :

```php
/**
 * Index des contenus Wordpress qu'un lien peut désigner
 *
 * @param string $base
 * @return array array(
 *     'types' => array(ID => post_type),
 *     'slugs' => array(slug normalisé => array(ID, …)),
 *     'chemins' => array(ID => chemin normalisé : slug, ou parent/enfant pour une page)
 * )
 */
function wp2spip_index_contenus($base = 'wordpress') {
	static $index = array();
	if (isset($index[$base])) {
		return $index[$base];
	}
	$index[$base] = array('types' => array(), 'slugs' => array(), 'chemins' => array());

	$contenus = sql_allfetsel(
		'ID, post_type, post_name, post_parent',
		'wp_posts',
		sql_in('post_type', array('post', 'page', 'attachment')),
		'',
		'ID',
		'',
		'',
		$base
	);
	$types = array_column($contenus, 'post_type', 'ID');
	$noms = array_column($contenus, 'post_name', 'ID');
	$parents = array_column($contenus, 'post_parent', 'ID');

	foreach ($contenus as $contenu) {
		$id = intval($contenu['ID']);
		$index[$base]['types'][$id] = $contenu['post_type'];
		if ($contenu['post_name'] === '') {
			continue;
		}
		$slug = wp2spip_normaliser_slug($contenu['post_name']);
		$index[$base]['slugs'][$slug][] = $id;

		// Une page enfant a pour chemin celui de ses parents : parent/enfant
		$chemin = $slug;
		$parent = intval($contenu['post_parent']);
		$vus = array();
		while (
			$contenu['post_type'] == 'page'
			and $parent
			and ($types[$parent] ?? '') == 'page'
			and !isset($vus[$parent])
		) {
			$vus[$parent] = true;
			$chemin = wp2spip_normaliser_slug($noms[$parent]) . '/' . $chemin;
			$parent = intval($parents[$parent]);
		}
		$index[$base]['chemins'][$id] = $chemin;
	}

	return $index[$base];
}

/**
 * Contenu désigné par le chemin d'une URL
 *
 * Le dernier segment est le slug. Si plusieurs contenus ont ce slug (pages de parents
 * différents, article et page…), on garde ceux dont le chemin complet termine l'URL,
 * et parmi eux le plus long (parent/enfant plutôt qu'enfant). Toujours ambigu : 0,
 * le lien reste tel quel plutôt que de viser peut-être le mauvais contenu.
 *
 * @param string $chemin chemin de l'URL, sans / au début ni à la fin
 * @param array $contenus index de wp2spip_index_contenus()
 * @return int ID Wordpress, ou 0
 */
function wp2spip_chercher_slug($chemin, $contenus) {
	$chemin = join('/', array_map('wp2spip_normaliser_slug', explode('/', $chemin)));
	$candidats = $contenus['slugs'][basename($chemin)] ?? array();
	if (count($candidats) > 1) {
		$longueurs = array();
		foreach ($candidats as $id) {
			$complet = $contenus['chemins'][$id];
			if ($chemin === $complet or substr($chemin, -strlen($complet) - 1) === "/$complet") {
				$longueurs[$id] = strlen($complet);
			}
		}
		$candidats = $longueurs ? array_keys($longueurs, max($longueurs)) : array();
	}
	return (count($candidats) == 1) ? intval($candidats[0]) : 0;
}

/**
 * Forme comparable d'un slug : Wordpress stocke les caractères non ASCII encodés en minuscules (%c3%a9),
 * les liens les écrivent encodés en majuscules ou en clair
 *
 * @param string $slug
 * @return string
 */
function wp2spip_normaliser_slug($slug) {
	return strtolower(rawurlencode(rawurldecode($slug)));
}
```

Vérifier la résolution des slugs ambigus (le contenu de test n'en a pas) sur un index construit à la main :

```bash
cd "$SPIP_WP6"; "$SPIP_CLI" php:eval '
include_spip("wp2spip/importer_articles");
$c = array(
	"slugs" => array("contact" => array(10), "equipe" => array(20, 30), "actu" => array(40, 50), "%c3%a9t%c3%a9" => array(60)),
	"chemins" => array(10 => "contact", 20 => "asso/equipe", 30 => "club/equipe", 40 => "actu", 50 => "actu", 60 => "%c3%a9t%c3%a9"),
);
foreach (array("contact", "2012/01/contact", "asso/equipe", "club/equipe", "equipe", "actu", "%C3%A9t%C3%A9", "été") as $chemin) {
	echo "$chemin => " . wp2spip_chercher_slug($chemin, $c) . "
";
}'
```

Expected : `contact => 10`, `2012/01/contact => 10`, `asso/equipe => 20`, `club/equipe => 30`, `equipe => 0` (ambigu), `actu => 0` (article et page de même slug), `%C3%A9t%C3%A9 => 60`, `été => 60`.

- [x] **Step 3 : `wp2spip_chercher_lien()`**

Remplacer la fonction entière (docblock compris) par :

```php
/**
 * Cherche si un lien peut être remplacé par un contenu interne au SPIP
 * 
 * Cela peut être un document si c'est un fichier ou la page d'un média Wordpress,
 * ou un article : les articles et pages SPIP ont l'identifiant de leur contenu Wordpress,
 * le lien s'écrit donc directement, que le contenu visé soit déjà importé ou non.
 * 
 * @param string $lien
 * @param string $url_wordpress
 * @param string $base
 * @return string Retourne le lien interne au SPIP, document123 ou article123, ou le lien inchangé
 */
function wp2spip_chercher_lien($lien, $url_wordpress, $base='wordpress') {
	// Seulement si c'est une URL relative OU qu'elle pointe sur le site d'origine
	if (!wp2spip_url_du_site($lien, $url_wordpress)) {
		return $lien;
	}
	// Un fichier de la médiathèque
	if ($id_document = wp2spip_chercher_document($lien, $url_wordpress, $base)) {
		return "document$id_document";
	}
	// Un fichier d'uploads introuvable n'est pas une page
	if (strpos($lien, '/wp-content/uploads/') !== false) {
		return $lien;
	}
	
	// Un contenu, désigné par son identifiant (?p=, ?page_id=) ou par son chemin
	$contenus = wp2spip_index_contenus($base);
	$id_wordpress = intval(parametre_url($lien, 'page_id')) ?: intval(parametre_url($lien, 'p'));
	if (
		!$id_wordpress
		and $chemin = trim((string) parse_url($lien, PHP_URL_PATH), '/')
	) {
		$id_wordpress = wp2spip_chercher_slug($chemin, $contenus);
	}
	
	switch ($contenus['types'][$id_wordpress] ?? '') {
		case 'post':
		case 'page':
			return "article$id_wordpress";
		case 'attachment':
			// La page d'un média : son document, s'il a été importé
			if ($id_document = intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . $id_wordpress))) {
				return "document$id_document";
			}
	}
	
	return $lien;
}
```

- [x] **Step 4 : vérifier**

Rejouer le Step 1 : toujours `int(0)` articles, et les deux URL deviennent `article<ID>`. Puis contrôle NR :

```bash
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6.tsv" | grep "^[<>]" | cut -f1 | sort | uniq -c
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';"
```

Expected : les seules différences sont des lignes `article` (texte : URL WordPress → `article#wpN` ou `document#wpN`) et celles de la Task 6 ; `verifier_identifiants.php` affiche **OK**. Relire les lignes changées : chaque nouveau `article#wpN` doit correspondre au contenu WordPress visé par l'URL d'origine.

- [x] **Step 5 : commit**

```bash
git add wp2spip/importer_articles.php
git commit -m "Liens internes écrits d'après l'identifiant Wordpress, contenu importé ou non"
```

### Task 10 : option pour garder l'adresse du site SPIP

**Files :**
- Modify : `spip-cli/WordpressImporter.php`, `wp2spip/importer_metas.php`

**Interfaces :**
- Produces : option `--garder-adresse` ; propriété `public $garder_adresse = false;`.

- [x] **Step 1 : test qui échoue**

```bash
cd "$SPIP_WP6"
"$SPIP_CLI" php:eval 'include_spip("inc/config"); ecrire_config("adresse_site", "http://exemple.test"); '
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_metas --garder-adresse --no-ansi; echo "code $?"
```

Expected avant correction : option inconnue, `code 1`.

- [x] **Step 2 : l'option**

Dans `WordpressImporter.php` : ajouter `public $garder_adresse = false;` après `public $base = 'wordpress';`, l'option après `info` :

```php
			->addOption(
				'garder-adresse',
				null,
				InputOption::VALUE_NONE,
				'Ne pas remplacer l’adresse du site SPIP par celle du Wordpress.'
			)
```

et, après `$this->base = $input->getOption('base');` :

```php
		// Garder l'adresse du site SPIP (import dans un site déjà à sa future adresse)
		$this->garder_adresse = $input->getOption('garder-adresse');
```

Dans `importer_metas.php`, remplacer le bloc `siteurl` par :

```php
		if (!empty($options['siteurl'])) {
			if ($command->garder_adresse) {
				$command->output->writeln('* <comment>Adresse du site</comment> : conservée (' . lire_config('adresse_site') . ')');
			}
			else {
				$command->output->writeln('* <comment>Adresse du site</comment> : ' . $options['siteurl']);
				ecrire_config('adresse_site', $options['siteurl']);
			}
		}
```

- [x] **Step 3 : vérifier**

```bash
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_metas --garder-adresse --no-ansi; echo "code $?"
"$SPIP_CLI" php:eval 'include_spip("inc/config"); var_dump(lire_config("adresse_site"));'
"$SPIP_CLI" wordpress:importer "$WP6" -t importer_metas --no-ansi >/dev/null
"$SPIP_CLI" php:eval 'include_spip("inc/config"); var_dump(lire_config("adresse_site"));'
```

Expected : « conservée (http://exemple.test) », `code 0`, `http://exemple.test` ; puis sans l'option, l'adresse du WordPress 6.9.

- [x] **Step 4 : commit**

```bash
git add spip-cli/WordpressImporter.php wp2spip/importer_metas.php
git commit -m "Option --garder-adresse : ne pas remplacer l'adresse du site SPIP"
```

### Task 11 : pipeline `wp2spip_traitements`

**Files :**
- Modify : `paquet.xml`, `spip-cli/WordpressImporter.php`

**Interfaces :**
- Produces : pipeline `wp2spip_traitements` ; `w2spip_traitements` reste appelé après lui, pour les extensions existantes.

- [x] **Step 1 : déclaration**

Dans `paquet.xml`, remplacer :

```xml
	<!-- Pipelines du plugin -->
	<pipeline nom="w2spip_traitements" action="" />
```

par :

```xml
	<!-- Pipelines du plugin -->
	<pipeline nom="wp2spip_traitements" action="" />
	<!-- Ancien nom, toujours appelé pour compatibilité -->
	<pipeline nom="w2spip_traitements" action="" />
```

Dans `WordpressImporter.php`, remplacer `$traitements_disponibles = pipeline('w2spip_traitements', $traitements_disponibles);` par :

```php
		$traitements_disponibles = pipeline('wp2spip_traitements', $traitements_disponibles);
		// Ancien nom du pipeline, gardé pour les extensions qui l'utilisent
		$traitements_disponibles = pipeline('w2spip_traitements', $traitements_disponibles);
```

- [x] **Step 2 : vérifier avec un plugin de test temporaire**

```bash
cd "$SPIP_WP6"; P=plugins/test_wp2spip_pipeline; mkdir -p $P
cat > $P/paquet.xml <<'EOF'
<paquet prefix="test_wp2spip_pipeline" categorie="divers" version="1.0.0" etat="test" compatibilite="[4.2.0;4.4.*]">
	<nom>Test des pipelines de wp2spip</nom>
	<pipeline nom="wp2spip_traitements" inclure="test_wp2spip_pipeline.php" />
	<pipeline nom="w2spip_traitements" inclure="test_wp2spip_pipeline.php" />
	<necessite nom="wp2spip" />
</paquet>
EOF
cat > $P/test_wp2spip_pipeline.php <<'EOF'
<?php
function test_wp2spip_pipeline_wp2spip_traitements($t) { $t[] = 'importer_test_nouveau'; return $t; }
function test_wp2spip_pipeline_w2spip_traitements($t) { $t[] = 'importer_test_ancien'; return $t; }
EOF
"$SPIP_CLI" plugins:activer test_wp2spip_pipeline -y
"$SPIP_CLI" wordpress:importer "$WP6" -i --no-ansi | grep "Traitements disponibles"
"$SPIP_CLI" plugins:desactiver test_wp2spip_pipeline -y; rm -rf $P
```

Expected : la liste se termine par `importer_test_nouveau, importer_test_ancien`.

- [x] **Step 3 : commit**

```bash
git add paquet.xml spip-cli/WordpressImporter.php
git commit -m "Pipeline wp2spip_traitements, l'ancien w2spip_traitements reste appelé"
```

### Task 12 : documentation et validation complète

**Files :**
- Modify : `readme.md`, `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md`

- [x] **Step 1 : readme**

Mettre l'aide de `readme.md` à jour avec la sortie réelle de `spip help wordpress:importer` (nouvelle option `--garder-adresse`, plus de `--update`), ajouter après « Refaire un import » :

```markdown
## Identifiants
Les articles, pages, rubriques et documents SPIP reprennent l'identifiant de leur contenu Wordpress (article 42 = contenu Wordpress 42). L'import doit donc se faire dans un SPIP vierge : si un identifiant est déjà pris, la commande s'arrête avant de créer quoi que ce soit, avec le code de sortie 1.
```

et, dans « Pour les devs », remplacer la mention du pipeline par `wp2spip_traitements` (l'ancien `w2spip_traitements` reste appelé).

- [x] **Step 2 : spec**

Dans la spec :
- § 1 : principe « Identifiants conservés » : retirer « (cible, § 6, sous-projet 1) » ;
- § 2.1 : ajouter la ligne `--garder-adresse` au tableau des options ; fusionner les deux paragraphes « Code de sortie » en un seul, au présent, reprenant le comportement cible ;
- § 2.2 : retirer `importer_mots` de la liste (mentionner qu'il viendra avec le sous-projet 5) ;
- § 2.3 : pipeline `wp2spip_traitements`, ancien nom toujours appelé ;
- § 3.3 : remplacer la limite `texte_backend()` par « Titres et textes sans entités HTML (sauf `&lt;`, `&gt;`, `&amp;`) » ;
- § 3.4 et § 3.5 : identifiant imposé ; supprimer la limite « liens internes vers un contenu pas encore importé » et décrire la résolution par `?p=`, `?page_id=` ou slug dans `wp_posts` ;
- § 4 : ajouter les commits des Tasks 1 à 11 ;
- § 5.3 : mentionner `tests/integration/` (remise à zéro, export comparable, vérification des identifiants) ;
- § 6 : marquer les sous-projets 1 et 2 « réalisé ».

Relire : aucune mention du site réel autrement que « site réel » ; aucune mention de `--update` hors historique.

- [x] **Step 3 : validation sur les quatre sites**

Chaque import et chaque vérification doit réussir : la boucle s'arrête au premier code non nul.

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
valider() { # <site SPIP> <dossier WordPress> <nom>
	(cd "$1" \
	&& "$SPIP_CLI" wordpress:importer "$2" --no-ansi > "$SAUVEGARDES/import-$3.log" 2>&1 \
	&& "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" \
	&& "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-$3.tsv") \
	|| { echo "ÉCHEC : $3 (voir $SAUVEGARDES/import-$3.log)"; return 1; }
	echo "$3 : OK"
}
set -e
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
valider "$SPIP_WP6" "$WP6" wp6
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP7" "$SAUVEGARDES/vierge-wp7-v2.sql.gz" "$BASE_WP7"
valider "$SPIP_WP7" "$WP7" wp7
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_SQLITE" "$SAUVEGARDES/vierge-sqlite-v2.tgz"
valider "$SPIP_REEL_SQLITE" "$WP_REEL" reel-SQLITE
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_REEL_MYSQL" "$SAUVEGARDES/vierge-mysql-v2.sql.gz" "$BASE_REEL_MYSQL"
valider "$SPIP_REEL_MYSQL" "$WP_REEL" reel-MYSQL
diff "$SAUVEGARDES/export-reel-SQLITE.tsv" "$SAUVEGARDES/export-reel-MYSQL.tsv"
echo "SQLite = MySQL"
set +e
diff <(cut -f1,2 "$SAUVEGARDES/export-wp6.tsv") <(cut -f1,2 "$SAUVEGARDES/export-wp7.tsv")
diff "$SAUVEGARDES/export-reel-sqlite-reference.tsv" "$SAUVEGARDES/export-reel-SQLITE.tsv" | grep "^[<>]" | cut -f1 | sort | uniq -c
```

Expected : `wp6 : OK`, `wp7 : OK`, `reel-SQLITE : OK`, `reel-MYSQL : OK`, `SQLite = MySQL` (la commande s'arrête avant au moindre échec) ; entre WP 6.9 et 7.1, seule diffère la ligne `zone` (le 7.1 est sans Accès restreint) ; par rapport à la référence du site réel, seules diffèrent des lignes `article` (liens convertis) et `rubrique` (entités), à relire une à une. Relancer ensuite `valider "$SPIP_WP6" "$WP6" wp6-bis` sur le site déjà importé : `OK`, et `diff` entre `export-wp6.tsv` et `export-wp6-bis.tsv` vide.

- [x] **Step 4 : commit**

```bash
git add readme.md docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md
git commit -m "Documentation : identifiants conservés, codes de sortie, --garder-adresse"
```

Pas de push sans demande explicite.

### Task 12 bis : seconde relecture de Codex (ajoutée à l'exécution)

- [x] `importer_acces` : chaque contenu est lié à sa zone **avant** d'être publié, et chaque étape est contrôlée (zone créée, lien présent, statut `publie`) ; un échec arrête le traitement, si bien qu'un contenu privé ou protégé n'est jamais public, même un instant.
- [x] `verifier_identifiants.php` : contenus privés ou protégés jamais publiés hors d'une zone, et, avec Accès restreint, publiés dans leur zone (testé : lien de zone retiré → `ECHEC`, code `1`).
- [x] `importer_auteurs` : un login refusé par SPIP est remplacé par le premier libre parmi `login-wp`, `login-wp2`… (validé par `auth_spip_verifier_login()`), signalé dans le bilan, au lieu d'un auteur sans login (remplace le choix noté à la Task 5).
- [x] readme et spec : un identifiant déjà pris arrête **le traitement concerné** avant qu'il ne crée un objet de ce type ; les traitements précédents ont pu créer des objets.
- [x] Validation complète rejouée sur les quatre sites.

---

## Lot 3 — Sous-projets suivants

Chacun suit le cycle de la spec (§ 6) : spec détaillée, relecture, puis plan de réalisation pas à pas sur le modèle du Lot 2, et validation avec `tests/integration/`. Ce plan fixe leur ordre, leur contenu et leurs critères de fin ; le détail des tâches sera écrit avec leur spec.

### Task 12 ter : sous-projet 11 — préparation d'un SPIP (avant le 3)

- [x] Spec : `docs/superpowers/specs/2026-10-08-wp2spip-preparation-spip-design.md` (script shell, options + `wp-config.php`, téléchargement, installation, plugins, base externe, import).
- [x] Plan : `docs/superpowers/plans/2026-10-08-wp2spip-preparation-spip.md` (SPIP-Cli actuel, sans nouveau correctif ; contrôles après chaque étape).
- [x] Réalisation ; validation : préparation depuis un dossier vide en MySQL et en SQLite pour WP 6.9 et 7.1, avec `--importer`, puis vérificateur à OK. Résultat : `tests/preparation/tester_preparer_spip.sh --complet` à 0 échec (cas d'erreur ; SQLite, MySQL distincte et base partagée sur une copie jetable) ; médias refusés par SPIP 4.4.28 corrigés ; vérificateur complété (documents comparés par identifiant).

### Task 13 : sous-projet 3 — balisage des blocs de l'éditeur

- [x] Spec : `docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md` (blocs convertis avant sale, structure gardée avec les seules classes `wp-block-…`, galeries → albums `<albumN>`, embarqués en URL, plugins requis téléchargés et activés par l'import puis relance).
- [x] Plan : `docs/superpowers/plans/2026-10-08-wp2spip-blocs-editeur.md` (code mis au point sur un prototype ; SVP notait le schéma des plugins sans créer leurs tables, corrigé aussi dans le script de préparation).
- [x] Réalisation ; validation sur WP 6.9 / 7.1 depuis un dossier vide et sur leurs SPIP de test, et sur le site réel : vérificateur (plus de `<!-- wp:` hors code, ni de classe `has-…`/`is-…`, albums complets, leurs images publiées) et test des conversions à OK. Résultat : WP 6.9 et 7.1 préparés et importés depuis un dossier vide (Albums, et Accès restreint pour le 7.1, installés par l'import), et sur leurs SPIP de test (`plugins/auto` et dépôt créés par l'import) : vérificateur et test des conversions (20 cas) à OK ; site réel sur son SPIP de test : export identique à celui d'avant les blocs ; site réel depuis un dossier vide : Accès restreint installé pour 98 contenus, vérificateur à OK ; tests de la préparation à 0 échec. Écart au plan : les images d'un album restaient `prop` (medias ne recalcule pas le statut d'un document à son lien), invisibles dans l'album public ; corrigé (`document_instituer()` après le lien) et contrôlé par le vérificateur.

### Task 14 : sous-projet 4 — hiérarchie des pages

- [x] Décision préalable (spec § 7) : pages uniques liées par a2a (type `sous_page`, parent → enfant, ordre WordPress), a2a installé par l'import si besoin ; pas de squelette fourni.
- [x] Spec : `docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md`.
- [x] Plan : `docs/superpowers/plans/2026-10-08-wp2spip-hierarchie-pages.md` (code mis au point sur un prototype : installation d'a2a, liens dans l'ordre WordPress, relance sans doublon, conflit et page absente en échec, boucles d'exemple vérifiées).
- [x] Réalisation ; validation : les 13 pages enfants du contenu de test et les 18 du site réel retrouvent leur parent, dans l'ordre WordPress. Résultat : WP 6.9 et 7.1 depuis un dossier vide et sur leurs SPIP de test : a2a installé par l'import, 13 liens pour 5 pages parentes, vérificateur, test « sans page enfant » et test des conversions à OK ; export WP 6.9 identique à la référence, plus 13 lignes `sous_page` ; site réel sur son SPIP de test (`plugins/auto` et dépôt créés par l'import) et depuis un dossier vide (miroir du WordPress pour les accès locaux) : 18 liens pour 5 pages parentes, rangs par titre, vérificateur à OK, export identique plus 18 lignes `sous_page` ; garde, conflit, page absente et dépôt injoignable en échec (code 1) ; tests de la préparation à 0 échec.

### Task 15 : sous-projet 5 — étiquettes (`importer_mots`)

- [x] Spec : `docs/superpowers/specs/2026-10-09-wp2spip-etiquettes-design.md` (`post_tag` → mots-clés du groupe « Étiquettes », `id_mot` = `term_id` ; `importer_mots` entre `importer_hierarchie_pages` et `importer_acces`, après la création des articles ; échec si un contenu lié manque).
- [ ] Plan, réalisation ; validation : 114 étiquettes du contenu de test, liens conformes à `wp_term_relationships`.

### Task 16 : sous-projet 6 — préfixe des tables

- [x] Spec : `docs/superpowers/specs/2026-10-09-wp2spip-prefixe-tables-design.md` (préfixe lu dans `wp-config.php`, `--prefixe` pour le remplacer, exigé s'il est introuvable ; tables contrôlées avant tout traitement ; SPIP amorcé avec un autre préfixe refusé).
- [ ] Plan, réalisation ; validation : copie du WordPress 6.9 sous le préfixe `wpx_` (base `jetable`) importée à l'identique.

### Task 17 : sous-projet 7 — tests automatisés

- [x] Spec : `docs/superpowers/specs/2026-10-09-wp2spip-tests-automatises-design.md` (PHPUnit selon le skill `spip-testing` : `tests/unit/` sans SPIP, `tests/integration/` dans un SPIP de `vendor/` avec une base WordPress SQLite de test ; `valider.sh` et référence versionnée normalisée pour l'import complet).
- [ ] Plan, réalisation.

### Task 18 : sous-projet 8 — extension `wp2spip_yoast`

- [ ] Spec : catégorie principale Yoast → rubrique principale (traitement inséré par `wp2spip_traitements` après `importer_articles`, avant `importer_polyhierarchie`) ; ensuite titre SEO et méta-description ; zones multiples si besoin (spec § 3.6).
- [ ] Plan, réalisation dans un plugin séparé ; validation sur le site réel.

### Task 19 : sous-projet 9 — extension `wp2spip_acf`

- [ ] Spec : champs ACF → Champs Extras (via Champs Extras Interface, à vérifier) d'après les définitions `acf-field` ; correspondances vers des champs natifs.
- [ ] Plan, réalisation dans un plugin séparé ; validation sur le site réel.

### Task 20 : sous-projet 10 — signalements amont

- [ ] sale : `extraire_images()` parcourt une portion de texte de trop (warning PHP 8 `sale_fonctions.php`) ; ticket avec cas reproductible.
- [ ] Polyhiérarchie configurable : pipeline `objet_compte_enfants` non déclaré, champ `date` codé en dur dans `calculer_rubriques` ; ticket.

### Task 21 : publication

- [ ] Décisions (spec § 7) : ordre des sous-projets 3 à 10, version 3.0.0. Décidé : `compat-spip-4.4` est la branche principale (branche par défaut du dépôt), sans fusion dans `master` ; pas de demande de fusion vers le dépôt d'origine, abandonné.
- [ ] `compat-spip-4.4` branche par défaut du dépôt ; étiquette `v3.0.0`, push — uniquement sur demande explicite.
