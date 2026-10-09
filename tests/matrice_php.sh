#!/bin/bash
# Suites de wp2spip sous chaque version de PHP de la cible
# Usage : tests/matrice_php.sh [--import] [version…]   (défaut : 8.1 8.2 8.3 8.4)
#   Pour chaque version, un dossier temporaire placé en tête du PATH fait de « php » cette version : Composer,
#   PHPUnit, SPIP-Cli (#!/usr/bin/env php) et les scripts qu'ils appellent utilisent tous le même PHP.
#   --import ajoute l'import complet des deux WordPress de test (tests/integration/valider.sh, environnement.sh), par un
#   SPIP-Cli autonome (tests/spip_cli_autonome.sh) : sous PHP 8.1 à 8.3, le plugin copié charge Masterminds depuis lib/.
set -uo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
import=
versions=()
for argument in "$@"; do
	case "$argument" in
		--import) import=1 ;;
		*) versions+=("$argument") ;;
	esac
done
[ ${#versions[@]} -gt 0 ] || versions=(8.1 8.2 8.3 8.4)

travail=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-matrice.XXXXXX")
trap 'rm -rf "$travail"' EXIT
if [ -n "$import" ]; then
	VALIDER_SPIP_CLI=$("$racine/tests/spip_cli_autonome.sh" "$travail/spip-cli") || { echo "ECHEC : SPIP-Cli autonome"; exit 1; }
	export VALIDER_SPIP_CLI
fi

echecs=()
for version in "${versions[@]}"; do
	executable=$(command -v "php$version") || { echecs+=("$version (php$version absent)"); continue; }
	bin="$travail/php$version"
	mkdir -p "$bin"
	ln -s "$executable" "$bin/php"
	(
		export PATH="$bin:$PATH"
		cd "$racine" || exit 1
		echo "=== PHP $(php -r 'echo PHP_VERSION;')"
		composer check-platform-reqs --no-interaction >/dev/null || { echo "ECHEC : composer check-platform-reqs"; exit 1; }
		vendor/bin/phpunit --testsuite unit || exit 1
		vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap_integration.php || exit 1
		if [ -n "$import" ]; then
			source tests/integration/environnement.sh
			tests/integration/valider.sh "$WP6" && tests/integration/valider.sh "$WP7" || exit 1
		fi
	) || echecs+=("$version")
done

if [ ${#echecs[@]} -gt 0 ]; then
	echo "ECHEC : PHP ${echecs[*]}"
	exit 1
fi
echo "OK : PHP ${versions[*]}"
