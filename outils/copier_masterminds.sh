#!/bin/bash
# Recopie Masterminds HTML5-PHP de vendor/ dans lib/masterminds-html5/, livré avec le plugin
# (analyseur HTML5 sous PHP 8.1 à 8.3). À relancer après chaque mise à jour de masterminds/html5 dans composer.lock :
# tests/unit/MastermindsCopieTest.php échoue tant que la copie diffère du verrou.
set -euo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
source="$racine/vendor/masterminds/html5"
copie="$racine/lib/masterminds-html5"

[ -f "$source/src/HTML5.php" ] || { echo "Masterminds absent de vendor/ : lancer composer install" >&2; exit 1; }
version=$(php -r '
	$verrou = json_decode(file_get_contents($argv[1]), true);
	foreach ($verrou["packages"] as $paquet) {
		if ($paquet["name"] === "masterminds/html5") {
			echo ltrim($paquet["version"], "v");
		}
	}' "$racine/composer.lock")
[ -n "$version" ] || { echo "masterminds/html5 absent de composer.lock" >&2; exit 1; }

rm -rf "$copie"
mkdir -p "$copie"
cp -R "$source/src" "$copie/src"
cp "$source/LICENSE.txt" "$copie/LICENSE.txt"
echo "$version" >"$copie/VERSION"
echo "Masterminds $version copié dans $copie"
