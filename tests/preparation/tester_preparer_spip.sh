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
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_'/\$table_prefix = 'wp-x_'/"
erreur_attendue "préfixe invalide" "invalide : lettres, chiffres et _ seulement" --wordpress "$ESSAIS/wp-prefixe"
faux_wordpress "$ESSAIS/wp-prefixe" "s/^\$table_prefix *= *'wp_';/if (getenv('X')) { \$table_prefix = 'a_'; } else { \$table_prefix = 'wp_'; }/"
erreur_attendue "préfixe affecté deux fois" "table_prefix introuvable" --wordpress "$ESSAIS/wp-prefixe"
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
[ -f "$spip/plugins/wp2spip/paquet.xml" ] && [ ! -L "$spip/plugins/wp2spip" ] \
	&& [ -z "$(cd "$spip/plugins/wp2spip" && ls -d tests vendor scripts composer.json composer.lock phpunit.xml .phpunit.cache 2>/dev/null)" ]
resultat "MySQL distincte : wp2spip copié, sans tests/, vendor/, scripts/ ni fichiers de Composer et PHPUnit" $?
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
