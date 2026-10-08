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
