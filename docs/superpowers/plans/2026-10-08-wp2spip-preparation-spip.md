# Sous-projet 11 — préparation d'un SPIP : plan de réalisation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal :** fournir `outils/preparer_spip.sh`, qui prépare un SPIP depuis un dossier vide (téléchargement, installation, plugins, base externe du WordPress) avec les commandes actuelles de SPIP-Cli, puis peut lancer l'import.

**Architecture :** un script bash enchaîne les commandes de SPIP-Cli et **contrôle le résultat de chaque étape** dans le SPIP (fichiers, administrateur, plugins, schémas, base externe), car les codes de sortie de SPIP-Cli ne couvrent pas tous les échecs. `outils/lire_wp_config.php` lit les accès de `wp-config.php` par ses jetons PHP, sans l'exécuter. Les tests sont des scripts bash dans `tests/preparation/`, sur le modèle de `tests/integration/`.

**Tech Stack :** bash 5, PHP 8 (CLI, tokenizer), SPIP 4.4, SPIP-Cli (version actuelle, sans nouveau correctif), client `mysql`.

**Spec :** `docs/superpowers/specs/2026-10-08-wp2spip-preparation-spip-design.md`. Plan général : `2026-10-08-wp2spip-developpement.md`, Task 12 ter.

## Global Constraints

- SPIP-Cli n'est **pas modifié** : version actuelle (dépôt de travail `/src/spip-cli`, branche `fix/pr-spip-cli-plugins-telecharger`, qui porte les correctifs déjà proposés de `plugins:svp:telecharger`).
- `core:installer` reçoit `--db-pass` et `--admin-pass` en argument (visibles brièvement dans `ps`, mot de passe administrateur affiché dans le terminal : limites acceptées). Le mot de passe de l'administrateur est toujours donné : `SPIP_ADMIN_PASS`, sinon aléatoire ; **jamais `adminadmin`**.
- `core:preparer --auto` (crée `plugins/auto`).
- Après chaque étape, contrôle du résultat ; à la première commande en échec ou au premier contrôle raté : message `ERREUR : …` explicite sur la sortie d'erreur, code `1`.
- Le script n'écrit que dans le dossier `--spip` ; le WordPress n'est jamais modifié ; le script ne garde aucune sortie de SPIP-Cli dans un journal.
- Ne jamais nommer le site réel de test dans les fichiers versionnés ou les messages de commit ; messages de commit sans trailer.
- `tests/integration/environnement.sh` (accès locaux) n'est jamais versionné, ni copié dans un SPIP.

## Ce que le prototype a établi

Le script et ses tests ont été mis au point dans un prototype avant ce plan : cas d'erreur, plugin introuvable et préparation complète SQLite avec import du WordPress 6.9 passent avec le SPIP-Cli actuel. Trois constats en sont sortis, intégrés ci-dessous :

1. `plugins:svp:telecharger sale pages polyhier` en un seul appel retente, pour chaque préfixe, les téléchargements des préfixes précédents (« Impossible de déballer ») : **un appel par préfixe**.
2. Depuis medias 4.4.15 (SPIP 4.4.28, correctif de sécurité #4919), `ajouter_un_document()` vérifie `autoriser('joindredocument')`, refusé en ligne de commande : **tous les médias sont refusés** (« Impossible d'enregistrer le document … en base de données »). Les sites de test, en SPIP 4.4.21, ne le montrent pas. `autoriser()` reçoit un identifiant `null`, cherché sous la clé `''` : l'exception doit être posée sur `'*'`.
3. Le vérificateur ne contrôle pas les documents : il était à OK avec 0 document importé sur 37. Et `php:eval` peut retourner `0` en affichant une page d'erreur de SPIP : les tests exigent la dernière ligne `OK`.

## Environnement

Variables de `tests/integration/environnement.sh` (plan général, « Environnement de test »), plus deux nouvelles, ajoutées à la Task 2 :

| Variable | Contenu |
|---|---|
| `ESSAIS` | dossier de travail des tests de préparation (SPIP créés puis laissés pour inspection) ; il reçoit des copies de `wp-config.php` : `chmod 700` |
| `BASE_PREP_MYSQL` | base MySQL **jetable**, réservée aux tests, accessible au login MySQL des `wp-config.php` des WordPress de test : SPIP du scénario « MySQL distincte », puis copie des tables du WordPress 6.9 pour le scénario « base partagée ». Les tests la vident ; ils n'écrivent jamais dans les bases des WordPress ni des SPIP de test |

Exporter d'abord `WP2SPIP`, puis `source "$WP2SPIP/tests/integration/environnement.sh"`.

---

### Task 1 : le vérificateur contrôle les documents

**Files :**
- Modify : `tests/integration/verifier_identifiants.php` (après la comparaison des rubriques)

**Interfaces :**
- Produces : les médias que `importer_documents` sélectionne (`wp_posts` de type `attachment` au statut `inherit`) comparés, par identifiant, aux `spip_documents` importés (`id_wordpress`) : échec `documents : N médias Wordpress non importés (…)` ou `documents : N documents sans média Wordpress correspondant (…)`, avec les dix premiers identifiants.

- [ ] **Step 1 : relever l'état actuel**

Sur les quatre sites de test, tous les médias sont aujourd'hui importés (relevé du prototype : 1612 / 1612 pour le site réel, 37 / 37 pour les WordPress 6.9 et 7.1 ; toutes leurs pièces jointes sont au statut `inherit`) :

```bash
source "$WP2SPIP/tests/integration/environnement.sh"
for s in "$SPIP_REEL_SQLITE" "$SPIP_REEL_MYSQL" "$SPIP_WP6" "$SPIP_WP7"; do
	(cd "$s" && "$SPIP_CLI" php:eval 'echo sql_countsel("spip_documents", "id_wordpress > 0"), " / ", sql_countsel("wp_posts", "post_type = \"attachment\" and post_status = \"inherit\"", "", "", "wordpress"), "\n";')
done
```

Expected : quatre lignes `N / N`. Un site remis à zéro sans être réimporté affiche `0 / N` : le réimporter d'abord (plan général, Task 12 Step 3).

- [ ] **Step 2 : ajouter la comparaison**

Dans `tests/integration/verifier_identifiants.php`, juste après le bloc qui se termine par `$echecs[] = "rubriques : $nb_spip importées pour $nb_wp catégories Wordpress";` et son `}` :

```php
// Médias : ceux que importer_documents sélectionne (pièces jointes au statut inherit), comparés par identifiant
$ids_wp = array_map('intval', array_column(sql_allfetsel('ID', 'wp_posts', array('post_type = "attachment"', 'post_status = "inherit"'), '', '', '', '', $base), 'ID'));
$ids_spip = array_map('intval', array_column(sql_allfetsel('id_wordpress', 'spip_documents', 'id_wordpress > 0'), 'id_wordpress'));
if ($manquants = array_diff($ids_wp, $ids_spip)) {
	$echecs[] = 'documents : ' . count($manquants) . ' médias Wordpress non importés (' . join(', ', array_slice($manquants, 0, 10)) . (count($manquants) > 10 ? '…' : '') . ')';
}
if ($en_trop = array_diff($ids_spip, $ids_wp)) {
	$echecs[] = 'documents : ' . count($en_trop) . ' documents sans média Wordpress correspondant (' . join(', ', array_slice($en_trop, 0, 10)) . (count($en_trop) > 10 ? '…' : '') . ')';
}
```

- [ ] **Step 3 : vérifier sur les quatre sites**

```bash
for s in "$SPIP_REEL_SQLITE" "$SPIP_REEL_MYSQL" "$SPIP_WP6" "$SPIP_WP7"; do
	(cd "$s" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1)
done
```

Expected : `OK` quatre fois.

- [ ] **Step 4 : commit**

```bash
git add tests/integration/verifier_identifiants.php
git commit -m "Vérificateur : comparer, par identifiant, les documents importés aux médias Wordpress"
```

---

### Task 2 : script de préparation et cas d'erreur

**Files :**
- Create : `outils/lire_wp_config.php`
- Create : `outils/preparer_spip.sh` (exécutable)
- Create : `tests/preparation/tester_preparer_spip.sh` (exécutable ; cas d'erreur)
- Modify : `tests/integration/environnement.exemple.sh` (fin du fichier)

**Interfaces :**
- Produces : `outils/lire_wp_config.php <wp-config.php>` écrit `NOM=valeur` suivi d'un caractère nul pour `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`, `table_prefix` (code `1` si le fichier est illisible ou qu'une valeur manque) ; `outils/preparer_spip.sh` (options de la spec § 2, variables `SPIP_DB_PASS`, `SPIP_ADMIN_PASS`, et `PREPARER_SPIP_PLUGINS_SVP` réservée aux tests), qui écrit au bilan `Mot de passe : <mot de passe> (généré)` quand il le génère ; `tests/preparation/tester_preparer_spip.sh [--complet]`, qui affiche `ok    …` ou `ECHEC …` par cas, puis `N échec(s)`, code `0` si aucun échec.

- [ ] **Step 1 : variables d'environnement des tests**

À la fin de `tests/integration/environnement.exemple.sh` :

```bash

# Tests de outils/preparer_spip.sh (tests/preparation/) : dossier de travail, et base MySQL jetable,
# vidée par les tests, accessible au login MySQL des WordPress de test
export ESSAIS=/chemin/vers/essais-preparation
export BASE_PREP_MYSQL=base_jetable
```

Reporter ces deux lignes, avec les valeurs de la machine, dans `tests/integration/environnement.sh` (non versionné) ; `ESSAIS` hors de tout dépôt, par exemple à côté de `$SAUVEGARDES`.

- [ ] **Step 2 : écrire les tests des cas d'erreur**

`tests/preparation/tester_preparer_spip.sh` :

```bash
#!/bin/bash
# Tests de outils/preparer_spip.sh
# Usage : tester_preparer_spip.sh [--complet]
#   sans option : cas d'erreur (rapides, sans réseau) ;
#   --complet : en plus, préparations complètes avec import (réseau, plusieurs minutes)
# Aucune écriture dans les bases des WordPress de test : seulement dans $BASE_PREP_MYSQL, base jetable dédiée
# Variables : tests/integration/environnement.sh (dont ESSAIS, dossier de travail, et BASE_PREP_MYSQL, base vide réservée à ces essais)
set -uo pipefail
source "$(dirname "$0")/../integration/environnement.sh"
preparer="$WP2SPIP/outils/preparer_spip.sh"
echecs=0

resultat() { # resultat <libellé> <code 0 si réussi>
	if [ "$2" -eq 0 ]; then
		echo "ok    $1"
	else
		echo "ECHEC $1"
		echecs=$((echecs + 1))
	fi
}

# Un cas d'erreur : code 1, message attendu sur la sortie d'erreur, dossier SPIP non créé
erreur_attendue() { # erreur_attendue <libellé> <message> <arguments de preparer_spip.sh…>
	local libelle=$1 message=$2 code
	shift 2
	rm -rf "$ESSAIS/spip-erreur"
	"$preparer" --spip "$ESSAIS/spip-erreur" --spip-cli "$SPIP_CLI" "$@" >/dev/null 2>"$ESSAIS/erreur.txt"
	code=$?
	[ "$code" -eq 1 ] && grep -qF -- "$message" "$ESSAIS/erreur.txt" && [ ! -e "$ESSAIS/spip-erreur" ]
	# Code lu avant la substitution $(…) du libellé, qui le remettrait à 0
	local reussi=$?
	resultat "$libelle (code $code : $(head -c 150 "$ESSAIS/erreur.txt"))" "$reussi"
}

# Copie de wp-config.php du WordPress 6.9, modifiée par sed, dans un faux dossier WordPress
faux_wordpress() { # faux_wordpress <dossier> <expression sed>
	rm -rf "$1" && mkdir -p "$1"
	sed "$2" "$WP6/wp-config.php" >"$1/wp-config.php"
}

mkdir -p "$ESSAIS"
chmod 700 "$ESSAIS"

# Cas d'erreur
mkdir -p "$ESSAIS/non-vide" && touch "$ESSAIS/non-vide/fichier"
"$preparer" --spip "$ESSAIS/non-vide" --wordpress "$WP6" --spip-cli "$SPIP_CLI" >/dev/null 2>"$ESSAIS/erreur.txt"
code=$?
[ "$code" -eq 1 ] && grep -qF "n'est pas vide" "$ESSAIS/erreur.txt" && [ "$(ls -A "$ESSAIS/non-vide")" = fichier ]
resultat "dossier non vide (code $code)" $?

erreur_attendue "option inconnue" "option inconnue" --wordpress "$WP6" --inconnue x
erreur_attendue "wp-config.php illisible" "wp-config.php illisible" --wordpress "$ESSAIS/sans-wordpress"
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_'/\$table_prefix = 'autre_'/"
erreur_attendue "préfixe autre que wp_" "seul wp_ est géré" --wordpress "$ESSAIS/wp-prefixe"
faux_wordpress "$ESSAIS/wp-refuse" "s/define( *'DB_PASSWORD', *'[^']*' *)/define( 'DB_PASSWORD', 'mauvais-mot-de-passe' )/"
erreur_attendue "accès MySQL refusés" "illisible avec les accès de wp-config.php" --wordpress "$ESSAIS/wp-refuse"
erreur_attendue "base du WordPress sans --base-partagee" "ajouter --base-partagee" --wordpress "$WP6" --base-spip "mysql:$BASE_WP6"
mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL" -e "create table if not exists spip_essai_preparation (id int)"
erreur_attendue "base contenant des tables spip_" "contient déjà des tables spip_" --wordpress "$WP6" --base-spip "mysql:$BASE_PREP_MYSQL"
mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL" -e "drop table spip_essai_preparation"
faux_wordpress "$ESSAIS/wp-port" "s/define( *'DB_HOST', *'\([^']*\)' *)/define( 'DB_HOST', '\1:3306' )/"
erreur_attendue "WordPress sur un port, sans --sql-hote" "donner --sql-hote" --wordpress "$ESSAIS/wp-port" --base-spip "mysql:$BASE_PREP_MYSQL"
erreur_attendue "SPIP-Cli introuvable" "SPIP-Cli introuvable" --wordpress "$WP6" --spip-cli "$ESSAIS/spip-cli-inexistant"
rm -rf "$ESSAIS/wp-prefixe" "$ESSAIS/wp-refuse" "$ESSAIS/wp-port" "$ESSAIS/non-vide" "$ESSAIS/erreur.txt"

if [ "${1:-}" != --complet ]; then
	echo "$echecs échec(s)"
	[ "$echecs" -eq 0 ]
	exit
fi
```

Puis `chmod +x tests/preparation/tester_preparer_spip.sh`.

- [ ] **Step 3 : lancer les tests, qui échouent**

Run : `tests/preparation/tester_preparer_spip.sh`
Expected : `ECHEC` sur chaque cas (`outils/preparer_spip.sh` n'existe pas : code 127), puis `9 échec(s)`, code `1`.

- [ ] **Step 4 : lecteur de `wp-config.php`**

`outils/lire_wp_config.php` :

```php
<?php
$fichier = $argv[1] ?? '';
if (!is_readable($fichier) or ($source = file_get_contents($fichier)) === false) {
	fwrite(STDERR, "Impossible de lire $fichier\n");
	exit(1);
}
function wp_config_chaine($jeton) {
	$contenu = substr($jeton, 1, -1);
	if ($jeton[0] === "'") {
		return strtr($contenu, array('\\\\' => '\\', "\\'" => "'"));
	}
	return stripcslashes($contenu);
}
$jetons = array();
foreach (token_get_all($source) as $jeton) {
	if (is_array($jeton) and in_array($jeton[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
		continue;
	}
	$jetons[] = is_array($jeton) ? array($jeton[0], $jeton[1]) : array(null, $jeton);
}
$valeurs = array();
$attendus = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST');
foreach ($jetons as $i => $jeton) {
	if (
		$jeton[0] === T_STRING and strtolower($jeton[1]) === 'define'
		and ($jetons[$i + 1][1] ?? '') === '('
		and ($jetons[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING
		and ($jetons[$i + 3][1] ?? '') === ','
		and ($jetons[$i + 4][0] ?? null) === T_CONSTANT_ENCAPSED_STRING
		and ($jetons[$i + 5][1] ?? '') === ')'
		and in_array($nom = wp_config_chaine($jetons[$i + 2][1]), $attendus)
		and !isset($valeurs[$nom])
	) {
		$valeurs[$nom] = wp_config_chaine($jetons[$i + 4][1]);
	}
	if (
		$jeton[0] === T_VARIABLE and $jeton[1] === '$table_prefix'
		and ($jetons[$i + 1][1] ?? '') === '='
		and ($jetons[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING
		and !isset($valeurs['table_prefix'])
	) {
		$valeurs['table_prefix'] = wp_config_chaine($jetons[$i + 2][1]);
	}
}
foreach (array_merge($attendus, array('table_prefix')) as $nom) {
	if (!isset($valeurs[$nom])) {
		fwrite(STDERR, "$nom introuvable dans $fichier (valeur littérale attendue)\n");
		exit(1);
	}
	echo "$nom=$valeurs[$nom]\0";
}
```

- [ ] **Step 5 : vérifier le lecteur**

```bash
cat > "$ESSAIS/wp-config-essai.php" <<'EOF'
<?php
// define('DB_NAME', 'commentee');
define( 'DB_NAME', 'ba\'se' );
define('DB_USER', "u\"x");
define('DB_PASSWORD', '');
define('DB_HOST', 'h:3307');
$table_prefix  = 'abc_';
EOF
php outils/lire_wp_config.php "$ESSAIS/wp-config-essai.php" | tr '\0' '\n'
php outils/lire_wp_config.php "$ESSAIS/inexistant.php"; echo "code $?"
rm "$ESSAIS/wp-config-essai.php"
```

Expected : `DB_NAME=ba'se`, `DB_USER=u"x`, `DB_PASSWORD=`, `DB_HOST=h:3307`, `table_prefix=abc_` (la définition commentée est ignorée) ; puis `Impossible de lire …` et `code 1`.

- [ ] **Step 6 : script de préparation**

`outils/preparer_spip.sh` :

```bash
#!/bin/bash
# Prépare un SPIP depuis un dossier vide pour importer un WordPress avec wp2spip,
# en enchaînant les commandes de SPIP-Cli (voir --help)
set -euo pipefail

WP2SPIP_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

aide() {
	cat <<'AIDE'
Usage : preparer_spip.sh --spip <dossier> --wordpress <dossier WordPress> [options]

Prépare un SPIP depuis un dossier vide : téléchargement, installation, plugins sale, pages,
polyhier et wp2spip, base du WordPress déclarée comme base externe ; puis peut lancer l'import.

Options :
  --spip <dossier>          dossier du SPIP à créer, vide ou absent (requis)
  --wordpress <dossier>     dossier du WordPress (requis)
  --version-spip <X.Y[.Z]>  version de SPIP (défaut : 4.4, dernière publiée)
  --base-spip <base>        sqlite (défaut) ou mysql:<base>, base existante
  --base-partagee           autorise mysql:<base> à être la base même du WordPress
  --sql-hote <hôte>         hôte MySQL de SPIP, sans port (défaut : celui de wp-config.php)
  --sql-login <login>       login MySQL de SPIP (défaut : celui de wp-config.php)
  --admin-login <login>     premier administrateur (défaut : admin)
  --admin-email <email>     son email (défaut : admin_email du WordPress)
  --adresse <url>           adresse du site SPIP (défaut : siteurl du WordPress)
  --wp2spip copie|lien      copie de wp2spip, ou lien symbolique pour le développer (défaut : copie)
  --spip-cli <exécutable>   SPIP-Cli (défaut : spip du PATH)
  --droits <droits>         droits des dossiers d'écriture de SPIP (défaut : 775)
  --depot <url>             dépôt de plugins SVP (défaut : https://plugins.spip.net/depots/principal.xml)
  --importer                lance wordpress:importer à la fin
  -h, --help                cette aide

Variables d'environnement :
  SPIP_DB_PASS     mot de passe MySQL de SPIP (défaut : celui de wp-config.php)
  SPIP_ADMIN_PASS  mot de passe de l'administrateur (défaut : aléatoire, affiché au bilan)

core:installer ne reçoit les mots de passe qu'en argument : ils sont brièvement visibles
dans la liste des processus pendant l'installation, et il affiche celui de l'administrateur.
AIDE
}

erreur() {
	echo "ERREUR : $*" >&2
	exit 1
}

etape() {
	echo
	echo "== $* =="
}

# Lance une commande ; en cas d'échec, arrêt avec la commande en cause
lancer() {
	"$@" || erreur "échec (code $?) de : $*"
}

# SPIP-Cli dans le dossier du SPIP
spip_cli() {
	(cd "$spip" && "$spip_cli_exe" --no-ansi "$@")
}

# Téléchargement depuis le dossier parent, pour que SPIP-Cli ne charge pas un SPIP voisin
telecharger_spip() {
	(cd "$(dirname "$spip")" && "$spip_cli_exe" --no-ansi core:telecharger spip -R "$version_spip" -d "$spip")
}

# Requête MySQL : mysql_requete <hôte> <port> <login> <mot de passe> <base> <requête>
# Le mot de passe passe par MYSQL_PWD, jamais en argument
mysql_requete() {
	MYSQL_PWD=$4 mysql -h "$1" ${2:+-P "$2"} -u "$3" -N -B "$5" -e "$6"
}

erreur_plugins() {
	erreur "$* ; SPIP-Cli doit comporter les correctifs de plugins:svp:telecharger (sélection du plugin, autorisation). Le SPIP est déjà installé dans $spip : supprimer ce dossier avant de relancer."
}

# Options
spip=
wordpress=
version_spip=4.4
base_spip=sqlite
base_partagee=
sql_hote=
sql_login=
admin_login=admin
admin_email=
adresse=
mode_wp2spip=copie
spip_cli_exe=spip
droits=775
depot=https://plugins.spip.net/depots/principal.xml
importer=
while [ $# -gt 0 ]; do
	option=$1
	case "$option" in
		--base-partagee) base_partagee=1; shift; continue ;;
		--importer) importer=1; shift; continue ;;
		-h|--help) aide; exit 0 ;;
		--*) [ $# -ge 2 ] || erreur "valeur attendue après $option" ;;
		*) erreur "argument inconnu : $option (voir --help)" ;;
	esac
	case "$option" in
		--spip) spip=$2 ;;
		--wordpress) wordpress=$2 ;;
		--version-spip) version_spip=$2 ;;
		--base-spip) base_spip=$2 ;;
		--sql-hote) sql_hote=$2 ;;
		--sql-login) sql_login=$2 ;;
		--admin-login) admin_login=$2 ;;
		--admin-email) admin_email=$2 ;;
		--adresse) adresse=$2 ;;
		--wp2spip) mode_wp2spip=$2 ;;
		--spip-cli) spip_cli_exe=$2 ;;
		--droits) droits=$2 ;;
		--depot) depot=$2 ;;
		*) erreur "option inconnue : $option (voir --help)" ;;
	esac
	shift 2
done
[ -n "$spip" ] || erreur "--spip est requis (voir --help)"
[ -n "$wordpress" ] || erreur "--wordpress est requis (voir --help)"
[[ $version_spip =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]] || erreur "--version-spip : X.Y ou X.Y.Z attendu"
[[ $droits =~ ^[0-7]{3}$ ]] || erreur "--droits : trois chiffres octaux attendus (775 par exemple)"
case "$mode_wp2spip" in copie|lien) ;; *) erreur "--wp2spip : copie ou lien" ;; esac
case "$base_spip" in sqlite|mysql:?*) ;; *) erreur "--base-spip : sqlite ou mysql:<base>" ;; esac
spip=$(realpath -m "$spip")
wordpress=$(realpath -m "$wordpress")
# Liste réservée aux tests (simuler un plugin introuvable)
read -r -a plugins_svp <<<"${PREPARER_SPIP_PLUGINS_SVP:-sale pages polyhier}"

etape "Contrôles préalables"
spip_cli_trouve=$(command -v "$spip_cli_exe") || erreur "SPIP-Cli introuvable : $spip_cli_exe"
spip_cli_exe=$(realpath "$spip_cli_trouve")
commandes=$("$spip_cli_exe" --no-ansi list --raw 2>&1) || erreur "SPIP-Cli ne répond pas : $spip_cli_exe list"
for commande in core:telecharger core:preparer core:installer plugins:svp:depoter plugins:svp:telecharger php:eval; do
	grep -q "^$commande " <<<"$commandes" || erreur "SPIP-Cli sans la commande $commande : $spip_cli_exe"
done
if [ -e "$spip" ]; then
	[ -d "$spip" ] || erreur "$spip existe et n'est pas un dossier"
	[ -z "$(ls -A "$spip")" ] || erreur "le dossier $spip n'est pas vide"
fi
[ -r "$wordpress/wp-config.php" ] || erreur "wp-config.php illisible dans $wordpress"

etape "Lecture de wp-config.php"
declare -A wp
mapfile -d '' lignes < <(php "$WP2SPIP_DIR/outils/lire_wp_config.php" "$wordpress/wp-config.php")
wait $! || erreur "lecture de $wordpress/wp-config.php impossible"
for ligne in "${lignes[@]}"; do
	wp[${ligne%%=*}]=${ligne#*=}
done
[ "${wp[table_prefix]}" = wp_ ] || erreur "préfixe des tables WordPress « ${wp[table_prefix]} » : seul wp_ est géré pour l'instant"
wp_hote=${wp[DB_HOST]}
wp_port=
if [[ $wp_hote == *:* ]]; then
	wp_port=${wp_hote##*:}
	wp_hote=${wp_hote%:*}
	[[ $wp_port =~ ^[0-9]+$ ]] || erreur "DB_HOST « ${wp[DB_HOST]} » : socket non géré, seul un port numérique l'est"
fi
wp_requete() {
	mysql_requete "$wp_hote" "$wp_port" "${wp[DB_USER]}" "${wp[DB_PASSWORD]}" "${wp[DB_NAME]}" "$1"
}
wp_siteurl=$(wp_requete "select option_value from wp_options where option_name = 'siteurl'") \
	|| erreur "base du WordPress ${wp[DB_NAME]} illisible avec les accès de wp-config.php"
wp_admin_email=$(wp_requete "select option_value from wp_options where option_name = 'admin_email'") \
	|| erreur "base du WordPress ${wp[DB_NAME]} illisible avec les accès de wp-config.php"
admin_email=${admin_email:-$wp_admin_email}
adresse=${adresse:-$wp_siteurl}

etape "Contrôle de la base de SPIP"
sql_pass=
base_mysql=
if [ "$base_spip" = sqlite ]; then
	php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);' || erreur "extension PHP pdo_sqlite absente"
else
	base_mysql=${base_spip#mysql:}
	if [ -z "$sql_hote" ]; then
		[ -z "$wp_port" ] || erreur "le WordPress utilise le port $wp_port, or core:installer ne se connecte que sur le port par défaut : donner --sql-hote"
		sql_hote=$wp_hote
	fi
	[[ $sql_hote != *:* ]] || erreur "--sql-hote « $sql_hote » : ni port ni socket, core:installer se connecte sur le port par défaut"
	sql_login=${sql_login:-${wp[DB_USER]}}
	sql_pass=${SPIP_DB_PASS-${wp[DB_PASSWORD]}}
	[ "$base_mysql" != "${wp[DB_NAME]}" ] || [ -n "$base_partagee" ] \
		|| erreur "la base $base_mysql est celle du WordPress : ajouter --base-partagee pour y créer les tables de SPIP"
	tables=$(mysql_requete "$sql_hote" "" "$sql_login" "$sql_pass" "$base_mysql" "show tables like 'spip\\_%'") \
		|| erreur "base MySQL $base_mysql inaccessible avec le login $sql_login"
	[ -z "$tables" ] || erreur "la base $base_mysql contient déjà des tables spip_ : un SPIP y est déjà installé"
fi
admin_pass=${SPIP_ADMIN_PASS:-}
admin_pass_genere=
if [ -z "$admin_pass" ]; then
	admin_pass=$(php -r 'echo bin2hex(random_bytes(12));')
	admin_pass_genere=1
fi

etape "Téléchargement de SPIP $version_spip"
mkdir -p "$(dirname "$spip")"
lancer telecharger_spip
[ -f "$spip/ecrire/inc_version.php" ] || erreur "SPIP absent de $spip après core:telecharger"
lancer spip_cli core:preparer --auto --droits "$droits"
[ -d "$spip/plugins/auto" ] && [ -w "$spip/plugins/auto" ] || erreur "plugins/auto absent ou non accessible en écriture après core:preparer --auto"

etape "Installation de SPIP"
if [ "$base_spip" = sqlite ]; then
	options_base=(--db-server sqlite3 --db-database spip)
else
	options_base=(--db-server mysql --db-host "$sql_hote" --db-login "$sql_login" "--db-pass=$sql_pass" --db-database "$base_mysql")
fi
# Mot de passe toujours donné : jamais le adminadmin par défaut de SPIP-Cli
lancer spip_cli core:installer "${options_base[@]}" --db-prefix spip \
	--admin-login "$admin_login" --admin-email "$admin_email" "--admin-pass=$admin_pass" --adresse-site "$adresse"
[ -f "$spip/config/connect.php" ] || erreur "config/connect.php absent après core:installer"
# core:installer continue (code 0) quand l'administrateur n'a pas pu être créé
admin_ok=$(
	export PREPARER_ADMIN_LOGIN=$admin_login PREPARER_ADMIN_PASS=$admin_pass
	spip_cli php:eval 'include_spip("auth/spip"); $auteur = auth_spip_dist(getenv("PREPARER_ADMIN_LOGIN"), getenv("PREPARER_ADMIN_PASS")); echo ($auteur and $auteur["statut"] === "0minirezo" and $auteur["webmestre"] === "oui") ? "oui" : "non";'
) || erreur "contrôle de l'administrateur impossible"
[ "$admin_ok" = oui ] || erreur "administrateur $admin_login absent, non webmestre, ou sans le mot de passe attendu après core:installer"

etape "Plugins"
lancer spip_cli plugins:svp:depoter "$depot"
# Un appel par préfixe : dans un même appel, SPIP-Cli retente les téléchargements des préfixes précédents.
# Son code de sortie ne dit pas si le téléchargement a réussi : le plugin est cherché sur le disque.
for prefixe in "${plugins_svp[@]}"; do
	spip_cli plugins:svp:telecharger "$prefixe" -y || erreur_plugins "plugins:svp:telecharger $prefixe a échoué"
	grep -rqs --include=paquet.xml "prefix=\"$prefixe\"" "$spip/plugins/auto" \
		|| erreur_plugins "plugin $prefixe absent de plugins/auto après plugins:svp:telecharger"
done
if [ "$mode_wp2spip" = lien ]; then
	lancer ln -s "$WP2SPIP_DIR" "$spip/plugins/wp2spip"
else
	# Ni .git, ni docs, ni tests (tests/integration/environnement.sh contient des accès locaux)
	mkdir "$spip/plugins/wp2spip"
	tar -C "$WP2SPIP_DIR" --exclude=./.git --exclude=./docs --exclude=./tests -cf - . \
		| tar -C "$spip/plugins/wp2spip" -xf - || erreur "copie de wp2spip dans plugins/wp2spip"
fi
lancer spip_cli plugins:activer "${plugins_svp[@]}" wp2spip -y
lancer spip_cli plugins:maj:bdd
actifs=$(spip_cli plugins:lister --short --raw --no-dist) || erreur "échec de plugins:lister"
for prefixe in "${plugins_svp[@]}" wp2spip; do
	grep -qx "[[:space:]]*$prefixe" <<<"$actifs" || erreur "plugin $prefixe inactif après plugins:activer"
done
# Schéma installé : la méta <préfixe>_base_version vaut le schema du paquet.xml (s'il en déclare un)
for prefixe in "${plugins_svp[@]}" wp2spip; do
	paquet=$(grep -Rls --include=paquet.xml "prefix=\"$prefixe\"" "$spip/plugins") || erreur "paquet.xml du plugin $prefixe introuvable"
	paquet=${paquet%%$'\n'*}
	schema=$(sed -n 's/.*[[:space:]]schema="\([^"]*\)".*/\1/p' "$paquet")
	schema=${schema%%$'\n'*}
	[ -n "$schema" ] || continue
	version=$(export PREPARER_META="${prefixe}_base_version"; spip_cli php:eval 'echo $GLOBALS["meta"][getenv("PREPARER_META")] ?? "";') \
		|| erreur "lecture de la méta ${prefixe}_base_version impossible"
	[ "$version" = "$schema" ] || erreur "schéma du plugin $prefixe non installé (méta ${prefixe}_base_version : « $version », attendu : $schema)"
done

etape "Base externe du WordPress"
(
	export WP2SPIP_WP_HOTE=$wp_hote WP2SPIP_WP_PORT=$wp_port WP2SPIP_WP_LOGIN=${wp[DB_USER]} \
		WP2SPIP_WP_PASS=${wp[DB_PASSWORD]} WP2SPIP_WP_BASE=${wp[DB_NAME]}
	spip_cli php:eval 'include_spip("inc/install"); install_fichier_connexion(_DIR_CONNECT . "wordpress.php", install_connexion(getenv("WP2SPIP_WP_HOTE"), getenv("WP2SPIP_WP_PORT"), getenv("WP2SPIP_WP_LOGIN"), getenv("WP2SPIP_WP_PASS"), getenv("WP2SPIP_WP_BASE"), "mysql", "", "", ""));'
) || erreur "écriture de config/wordpress.php"
siteurl_lue=$(spip_cli php:eval 'echo sql_getfetsel("option_value", "wp_options", "option_name = " . sql_quote("siteurl"), "", "", "", "", "wordpress");') \
	|| erreur "lecture de la base externe wordpress"
[ "$siteurl_lue" = "$wp_siteurl" ] || erreur "la base externe wordpress ne répond pas comme attendu (siteurl lu : « $siteurl_lue »)"

code=0
if [ -n "$importer" ]; then
	etape "Import du WordPress"
	spip_cli wordpress:importer "$wordpress" || code=$?
fi

etape "Bilan"
echo "SPIP : $spip"
echo "Adresse : $adresse"
echo "Base : $base_spip"
echo "Administrateur : $admin_login"
if [ -n "$admin_pass_genere" ]; then
	echo "Mot de passe : $admin_pass (généré)"
else
	echo "Mot de passe : celui de SPIP_ADMIN_PASS"
fi
if [ -n "$importer" ]; then
	echo "Import : code $code"
else
	printf 'Import : cd %q && %q wordpress:importer %q\n' "$spip" "$spip_cli_exe" "$wordpress"
fi
exit "$code"
```

Puis `chmod +x outils/preparer_spip.sh`.

- [ ] **Step 7 : lancer les tests**

Run : `tests/preparation/tester_preparer_spip.sh`
Expected : neuf lignes `ok    …` (dossier non vide, option inconnue, `wp-config.php` illisible, préfixe autre que `wp_`, accès MySQL refusés, base du WordPress sans `--base-partagee`, base contenant des tables `spip_`, WordPress sur un port sans `--sql-hote`, SPIP-Cli introuvable), puis `0 échec(s)`, code `0`, en quelques secondes. Le cas « accès MySQL refusés » montre aussi le message `ERROR 1045` du client `mysql` : c'est attendu.

Vérifier aussi l'aide : `outils/preparer_spip.sh --help` affiche l'usage, les options et les deux variables d'environnement, code `0`.

- [ ] **Step 8 : commit**

```bash
git add outils/lire_wp_config.php outils/preparer_spip.sh tests/preparation/tester_preparer_spip.sh tests/integration/environnement.exemple.sh
git commit -m "Script de préparation d'un SPIP depuis un dossier vide, avec ses contrôles et les tests des cas d'erreur"
```

---

### Task 3 : préparations complètes, et médias refusés par SPIP 4.4 récent

**Files :**
- Modify : `tests/preparation/tester_preparer_spip.sh` (ajout à la fin)
- Modify : `wp2spip/importer_documents.php` (appel de `$ajouter_un_document`)

**Interfaces :**
- Consumes : `outils/preparer_spip.sh` (Task 2), comparaison des documents du vérificateur (Task 1).

- [ ] **Step 1 : base MySQL jetable des essais**

Elle est créée par un administrateur MySQL (fait sur la machine de test : base `jetable`), accessible au login MySQL des `wp-config.php` des WordPress de test, par exemple :

```bash
sudo mysql -e "CREATE DATABASE <base> CHARACTER SET utf8mb4; GRANT ALL PRIVILEGES ON <base>.* TO '<login>'@'localhost';"
```

Vérifier : `mysql $MYSQL_OPTIONS -N "$BASE_PREP_MYSQL" -e "select 1"` affiche `1`.

- [ ] **Step 2 : écrire les tests des préparations complètes**

Ajouter à la fin de `tests/preparation/tester_preparer_spip.sh`, après le bloc `if [ "${1:-}" != --complet ]; then … fi` :

```bash
# Préparations complètes

# sale, pages et polyhier sous plugins/auto ; ces trois plugins et wp2spip actifs
plugins_prets() { # plugins_prets <dossier SPIP>
	local prefixe actifs
	actifs=$(cd "$1" && "$SPIP_CLI" --no-ansi plugins:lister --short --raw --no-dist) || return 1
	for prefixe in sale pages polyhier; do
		grep -rqs --include=paquet.xml "prefix=\"$prefixe\"" "$1/plugins/auto" || return 1
	done
	for prefixe in sale pages polyhier wp2spip; do
		grep -qx "[[:space:]]*$prefixe" <<<"$actifs" || return 1
	done
}

# L'administrateur s'authentifie-t-il avec ce mot de passe ?
admin_authentifie() { # admin_authentifie <dossier SPIP> <mot de passe>
	local reponse
	reponse=$(cd "$1" && ESSAI_PASS=$2 "$SPIP_CLI" php:eval 'include_spip("auth/spip"); echo auth_spip_dist("admin", getenv("ESSAI_PASS")) ? "oui" : "non";')
	[ "$reponse" = oui ]
}

# Vérificateur de l'import à OK
import_verifie() { # import_verifie <dossier SPIP>
	# php:eval peut retourner 0 sur une page d'erreur de SPIP : la dernière ligne doit être OK
	(cd "$1" && "$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';") >"$ESSAIS/verification.txt" 2>&1 \
		&& [ "$(tail -n 1 "$ESSAIS/verification.txt")" = OK ]
}

# Vide la base jetable des essais : toutes ses tables
vider_base_essais() {
	local tables
	tables=$(mysql $MYSQL_OPTIONS -N "$BASE_PREP_MYSQL" -e "show tables" | paste -sd, -)
	[ -z "$tables" ] || mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL" -e "drop table $tables"
}

# Plugin introuvable sur le dépôt : arrêt après l'installation de SPIP, avec un message qui le dit
spip="$ESSAIS/spip-plugin-introuvable"
rm -rf "$spip"
PREPARER_SPIP_PLUGINS_SVP="sale prefixe_inexistant_wp2spip" "$preparer" --spip "$spip" --wordpress "$WP6" --spip-cli "$SPIP_CLI" >"$ESSAIS/plugin-introuvable.log" 2>&1
code=$?
[ "$code" -eq 1 ] && grep -qF "Le SPIP est déjà installé dans $spip" "$ESSAIS/plugin-introuvable.log" && [ -f "$spip/config/connect.php" ]
resultat "plugin introuvable : code 1, SPIP installé et signalé (code $code)" $?
rm -rf "$spip"

# SQLite, WordPress 6.9, dossier vide existant, wp2spip en lien, mot de passe généré
spip="$ESSAIS/spip-sqlite"
rm -rf "$spip" && mkdir -p "$spip"
"$preparer" --spip "$spip" --wordpress "$WP6" --spip-cli "$SPIP_CLI" --wp2spip lien --importer >"$ESSAIS/sqlite.log" 2>&1
code=$?
[ "$code" -eq 0 ]
resultat "SQLite, WordPress 6.9 : code 0 (code $code, journal $ESSAIS/sqlite.log)" $?
plugins_prets "$spip"
resultat "SQLite : plugins sous plugins/auto et actifs" $?
[ "$(readlink "$spip/plugins/wp2spip")" = "$WP2SPIP" ]
resultat "SQLite : plugins/wp2spip est un lien vers le dépôt" $?
pass_genere=$(sed -n 's/^Mot de passe : \(.*\) (généré)$/\1/p' "$ESSAIS/sqlite.log")
[ -n "$pass_genere" ] && admin_authentifie "$spip" "$pass_genere" && ! admin_authentifie "$spip" adminadmin
resultat "SQLite : administrateur authentifié avec le mot de passe généré, affiché au bilan, pas avec adminadmin" $?
import_verifie "$spip"
reussi=$?
resultat "SQLite : vérificateur à OK ($(tail -1 "$ESSAIS/verification.txt"))" "$reussi"

# MySQL distincte, WordPress 7.1, wp2spip copié, mot de passe choisi par SPIP_ADMIN_PASS
spip="$ESSAIS/spip-mysql"
rm -rf "$spip"
vider_base_essais
pass_admin="Admin-$RANDOM-$RANDOM"
SPIP_ADMIN_PASS=$pass_admin "$preparer" --spip "$spip" --wordpress "$WP7" --spip-cli "$SPIP_CLI" --base-spip "mysql:$BASE_PREP_MYSQL" --importer >"$ESSAIS/mysql.log" 2>&1
code=$?
[ "$code" -eq 0 ]
resultat "MySQL distincte, WordPress 7.1 : code 0 (code $code, journal $ESSAIS/mysql.log)" $?
plugins_prets "$spip"
resultat "MySQL distincte : plugins sous plugins/auto et actifs" $?
[ -f "$spip/plugins/wp2spip/paquet.xml" ] && [ ! -L "$spip/plugins/wp2spip" ] && [ ! -e "$spip/plugins/wp2spip/tests" ]
resultat "MySQL distincte : wp2spip copié, sans tests/" $?
admin_authentifie "$spip" "$pass_admin" && ! admin_authentifie "$spip" adminadmin
resultat "MySQL distincte : administrateur authentifié avec le mot de passe de SPIP_ADMIN_PASS, pas avec adminadmin" $?
import_verifie "$spip"
reussi=$?
resultat "MySQL distincte : vérificateur à OK ($(tail -1 "$ESSAIS/verification.txt"))" "$reussi"

# Base partagée (comme les sites de test), sur une copie jetable du WordPress 6.9 : ses tables wp_ copiées
# dans $BASE_PREP_MYSQL, et un dossier WordPress dont wp-config.php désigne cette base (fichiers en liens)
spip="$ESSAIS/spip-partagee"
wordpress="$ESSAIS/wp6-jetable"
rm -rf "$spip"
vider_base_essais
tables_wp=$(mysql $MYSQL_OPTIONS -N "$BASE_WP6" -e "show tables like 'wp\\_%'")
mysqldump $MYSQL_OPTIONS --no-tablespaces "$BASE_WP6" $tables_wp | mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL"
resultat "base partagée : tables du WordPress 6.9 copiées dans $BASE_PREP_MYSQL" $?
faux_wordpress "$wordpress" "s/define( *'DB_NAME', *'[^']*' *)/define( 'DB_NAME', '$BASE_PREP_MYSQL' )/"
ln -s "$WP6/wp-includes" "$wordpress/wp-includes"
ln -s "$WP6/wp-content" "$wordpress/wp-content"
"$preparer" --spip "$spip" --wordpress "$wordpress" --spip-cli "$SPIP_CLI" --base-spip "mysql:$BASE_PREP_MYSQL" --base-partagee --importer >"$ESSAIS/partagee.log" 2>&1
code=$?
[ "$code" -eq 0 ]
resultat "base partagée, WordPress 6.9 : code 0 (code $code, journal $ESSAIS/partagee.log)" $?
plugins_prets "$spip"
resultat "base partagée : plugins sous plugins/auto et actifs" $?
import_verifie "$spip"
reussi=$?
resultat "base partagée : vérificateur à OK ($(tail -1 "$ESSAIS/verification.txt"))" "$reussi"

echo "$echecs échec(s)"
[ "$echecs" -eq 0 ]
```

- [ ] **Step 3 : lancer les tests complets, qui échouent sur les médias**

Run : `tests/preparation/tester_preparer_spip.sh --complet` (réseau, une dizaine de minutes)
Expected : les cas d'erreur, `plugin introuvable` et la copie des tables du WordPress 6.9 à `ok` ; les trois préparations à `code 0`, plugins et administrateur à `ok` ; mais les trois `vérificateur à OK` en `ECHEC`, avec `documents : 37 médias Wordpress non importés (…)` dans `$ESSAIS/verification.txt` (SPIP 4.4.28 téléchargé : medias 4.4.15 refuse les fichiers). Le journal `$ESSAIS/sqlite.log` montre `37 médias refusés par SPIP`.

- [ ] **Step 4 : exception d'autorisation pour les documents**

L'exception ne vaut que pour l'appel de `ajouter_un_document()`, et elle est levée aussitôt après, même en cas d'erreur. Dans `wp2spip/importer_documents.php`, remplacer la ligne :

```php
				$retour = $ajouter_un_document($id_document, $file, null, null, 'auto');
```

par :

```php
				// ajouter_un_document() de SPIP 4.4 récent vérifie autoriser('joindredocument') : en ligne de commande, sans
				// auteur connecté, tous les fichiers seraient refusés. Document joint à aucun objet : type vide, identifiant
				// null (que autoriser() cherche sous la clé '', d'où l'exception générique '*'), levée aussitôt après
				autoriser_exception('joindredocument', '', '*', true);
				try {
					$retour = $ajouter_un_document($id_document, $file, null, null, 'auto');
				} finally {
					autoriser_exception('joindredocument', '', '*', false);
				}
```

- [ ] **Step 5 : non-régression sur SPIP 4.4.21**

L'exception ne change rien là où le contrôle n'existe pas :

```bash
"$WP2SPIP/tests/integration/remise_a_zero.sh" "$SPIP_WP6" "$SAUVEGARDES/vierge-wp6-v2.sql.gz" "$BASE_WP6"
cd "$SPIP_WP6"
"$SPIP_CLI" wordpress:importer "$WP6" --no-ansi > "$SAUVEGARDES/import-wp6.log" 2>&1; echo "code $?"
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/exporter_import.php';" > "$SAUVEGARDES/export-wp6.tsv"
diff "$SAUVEGARDES/export-wp6-reference.tsv" "$SAUVEGARDES/export-wp6.tsv" && echo IDENTIQUE
"$SPIP_CLI" php:eval "include '$WP2SPIP/tests/integration/verifier_identifiants.php';" | tail -n 1
cd "$WP2SPIP"
```

Expected : `code 0`, `IDENTIQUE`, `OK`.

- [ ] **Step 6 : relancer les tests complets**

Run : `tests/preparation/tester_preparer_spip.sh --complet`
Expected : toutes les lignes à `ok`, dont les trois `vérificateur à OK (OK)`, puis `0 échec(s)`, code `0`. Contrôler aussi qu'aucun appel de `plugins:svp:telecharger` n'a échoué : `grep -c "en échec\|Impossible de déballer" "$ESSAIS"/sqlite.log "$ESSAIS"/mysql.log "$ESSAIS"/partagee.log` affiche `0` pour chaque journal.

- [ ] **Step 7 : commit**

```bash
git add tests/preparation/tester_preparer_spip.sh wp2spip/importer_documents.php
git commit -m "Préparations complètes testées (SQLite, MySQL distincte, base partagée) ; médias acceptés par SPIP 4.4 récent"
```

---

### Task 4 : documentation

**Files :**
- Modify : `readme.md` (nouvelle section avant « Refaire un import »)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-preparation-spip-design.md` (ligne `Statut`)
- Modify : `docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md` (§ 6, ligne du sous-projet 11)
- Modify : `docs/superpowers/plans/2026-10-08-wp2spip-developpement.md` (Task 12 ter)

- [ ] **Step 1 : readme**

Dans `readme.md`, juste avant `## Refaire un import` :

````markdown
## Préparer un SPIP
Depuis un dossier vide, `outils/preparer_spip.sh` télécharge et installe SPIP, ajoute sale, pages, polyhier et wp2spip, déclare la base du Wordpress comme base externe, et peut lancer l'import :

``` bash
$ SPIP_ADMIN_PASS='…' outils/preparer_spip.sh --spip /chemin/du/spip --wordpress /chemin/du/wordpress --importer
```

Les accès à la base sont lus dans le `wp-config.php` du Wordpress, sans l'exécuter. La base de SPIP est en SQLite par défaut (`--base-spip mysql:<base>` pour une base MySQL existante). Sans `SPIP_ADMIN_PASS`, un mot de passe est généré et affiché à la fin. Toutes les options : `outils/preparer_spip.sh --help`.

Il faut SPIP-Cli avec les correctifs de `plugins:svp:telecharger` (sélection du plugin, autorisation). `core:installer` ne recevant les mots de passe qu'en argument, ils sont brièvement visibles dans la liste des processus pendant l'installation, et il affiche celui de l'administrateur.
````

- [ ] **Step 2 : statuts**

- spec du sous-projet 11 : `Statut : design validé, à planifier.` devient `Statut : réalisé (plan : docs/superpowers/plans/2026-10-08-wp2spip-preparation-spip.md).` ;
- spec d'ensemble, § 6 : marquer la ligne du sous-projet 11 comme réalisée, comme celles des sous-projets 1 et 2 ;
- plan général, Task 12 ter : cocher `Réalisation ; validation …`, avec le résultat (`tests/preparation/tester_preparer_spip.sh --complet` : 0 échec ; médias refusés par SPIP 4.4.28 corrigés).

- [ ] **Step 3 : commit**

```bash
git add readme.md docs/superpowers
git commit -m "Sous-projet 11 réalisé : documentation de la préparation d'un SPIP"
```
