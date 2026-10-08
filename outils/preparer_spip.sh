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
# SVP installe chaque plugin dans un processus qui ne connaît pas encore ses tables : la version de son schéma
# est notée sans que ses tables soient créées. Effacée, elle fait installer le plugin par plugins:maj:bdd, dans un processus neuf.
(
	export PREPARER_PLUGINS="${plugins_svp[*]}"
	spip_cli php:eval 'include_spip("inc/meta"); foreach (explode(" ", getenv("PREPARER_PLUGINS")) as $prefixe) { effacer_meta($prefixe . "_base_version"); }'
) || erreur "effacement des versions de schéma notées par plugins:svp:telecharger"
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

# Tables et champs déclarés par les plugins actifs, mais absents de la base : la version d'un schéma peut être notée
# sans que ses tables soient créées (plugins installés par SVP)
tables_manquantes=$(spip_cli php:eval 'include_spip("inc/wp2spip_plugins"); echo join(", ", wp2spip_tables_manquantes());') \
	|| erreur "contrôle des tables de la base impossible"
[ -z "$tables_manquantes" ] || erreur "tables ou champs absents de la base après plugins:maj:bdd : $tables_manquantes"

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
