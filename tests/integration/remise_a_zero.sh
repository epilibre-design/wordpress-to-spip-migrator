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
# Métas en cache (tmp/meta_cache.php) : lues par SPIP avant la base, elles y réécriraient l’état précédent
rm -rf tmp/cache/* tmp/meta_cache.php
echo "Site remis à zéro : $site"
