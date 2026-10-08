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
	# Sortie capturée avant le grep : grep -q quitte tôt, et pipefail ferait échouer le tube (SIGPIPE) de façon aléatoire
	local actifs
	actifs=$(spip plugins:lister --short --raw --no-dist)
	grep -qx "[[:space:]]*$1" <<<"$actifs"
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
