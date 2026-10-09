#!/bin/bash
# SPIP-Cli indépendant de l'autoload Composer de wp2spip, comme celui d'un utilisateur
# Usage : tests/spip_cli_autonome.sh <dossier>   (affiche le chemin de l'exécutable)
#   Le SPIP-Cli de vendor/bin charge l'autoload du dépôt, qui fournit déjà Masterminds : le plugin n'y charge pas sa
#   copie de lib/. Celui-ci est une copie de vendor/spip/spip-cli munie de ses seules dépendances, résolues pour
#   PHP 8.1 : sous PHP 8.1 à 8.3, l'import passe par lib/masterminds-html5/. Le correctif de plugins:svp:telecharger
#   (tests/spip-cli.patch, merge request 91 de SPIP-Cli) y est appliqué s'il manque.
set -euo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
dossier=${1:?Usage : spip_cli_autonome.sh <dossier>}
source="$racine/vendor/spip/spip-cli"

[ -f "$source/bin/spip" ] || { echo "SPIP-Cli absent de vendor/ : lancer composer install" >&2; exit 1; }
rm -rf "$dossier"
cp -R "$source" "$dossier"
rm -rf "$dossier/vendor"
grep -q 'UPPER(pl.prefixe) = UPPER' "$dossier/src/Command/PluginsSvpTelecharger.php" \
	|| patch -s -d "$dossier" -p1 <"$racine/tests/spip-cli.patch" \
	|| { echo "Correctif de SPIP-Cli non appliqué" >&2; exit 1; }
(
	cd "$dossier"
	export COMPOSER_ROOT_VERSION=dev-master
	composer config platform.php 8.1.0
	composer update --no-dev --no-interaction --quiet
) >&2
[ ! -e "$dossier/vendor/masterminds" ] || { echo "Masterminds présent dans les dépendances de SPIP-Cli" >&2; exit 1; }
echo "$dossier/bin/spip"
