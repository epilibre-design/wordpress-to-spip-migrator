#!/bin/bash
# Import complet d'un WordPress de test, comparé à la référence versionnée
# Usage : valider.sh <dossier WordPress> [--mettre-a-jour]
#   Prépare un SPIP SQLite depuis un dossier vide (outils/preparer_spip.sh --importer),
#   lance le vérificateur, puis compare l'export normalisé à tests/integration/references/theme-unit-test.tsv :
#   adresse du WordPress remplacée par @URL_SITE@, date de son installation (contenus créés par l'installation)
#   par @INSTALLATION@. --mettre-a-jour réécrit la référence.
#   Prérequis : WordPress installé en français avec le contenu Theme Unit Test, accès lisibles dans son wp-config.php.
#   Le SPIP préparé est supprimé si tout est conforme, gardé sinon.
#   VERSION_SPIP=X.Y : version de SPIP préparée (défaut de outils/preparer_spip.sh : 4.4).
#   VALIDER_SPIP_CLI=<exécutable> : SPIP-Cli utilisé (défaut : vendor/bin/spip, qui charge l'autoload du dépôt ; tests/spip_cli_autonome.sh en donne un sans).
set -uo pipefail

racine=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
reference="$racine/tests/integration/references/theme-unit-test.tsv"
spip_cli=${VALIDER_SPIP_CLI:-$racine/vendor/bin/spip}
sources_cli="$racine/vendor/spip/spip-cli"
[ "$spip_cli" = "$racine/vendor/bin/spip" ] || sources_cli=$(cd "$(dirname "$spip_cli")/.." && pwd)
wordpress=${1:?Usage : valider.sh <dossier WordPress> [--mettre-a-jour]}
mettre_a_jour=${2:-}

[ -x "$spip_cli" ] || { echo "SPIP-Cli absent : $spip_cli (composer install, ou VALIDER_SPIP_CLI)" >&2; exit 1; }
grep -q 'UPPER(pl.prefixe) = UPPER' "$sources_cli/src/Command/PluginsSvpTelecharger.php" \
	|| { echo "SPIP-Cli sans correctif ($sources_cli) : lancer composer install-spip-test" >&2; exit 1; }

travail=$(mktemp -d "${TMPDIR:-/tmp}/wp2spip-valider.XXXXXX")
spip="$travail/spip"
echo "Préparation et import de $wordpress dans $spip"
"$racine/outils/preparer_spip.sh" --spip "$spip" --wordpress "$wordpress" --spip-cli "$spip_cli" ${VERSION_SPIP:+--version-spip "$VERSION_SPIP"} --importer >"$travail/import.log" 2>&1
code=$?
if [ "$code" -ne 0 ]; then
	echo "ECHEC : préparation ou import en code $code (journal $travail/import.log)"
	exit 1
fi

(cd "$spip" && "$spip_cli" --no-ansi php:eval "include '$racine/tests/integration/verifier_identifiants.php';") >"$travail/verification.txt" 2>&1
code=$?
if [ "$code" -ne 0 ] || [ "$(tail -n 1 "$travail/verification.txt")" != OK ]; then
	echo "ECHEC : vérificateur (code $code, sortie $travail/verification.txt)"
	exit 1
fi

(cd "$spip" && "$spip_cli" --no-ansi php:eval "include '$racine/tests/integration/exporter_import.php';") >"$travail/export.tsv" 2>&1 \
	|| { echo "ECHEC : export ($travail/export.tsv)"; exit 1; }
# Adresse du WordPress, et date de son installation : celle de son premier article
url_site=$(cd "$spip" && "$spip_cli" --no-ansi php:eval 'include_spip("inc/wp2spip"); echo sql_getfetsel("option_value", wp2spip_table("options"), "option_name = \"siteurl\"", "", "", "", "", "wordpress");')
installation=$(awk -F'\t' '$1 == "article" && $2 == "1" { print $5 }' "$travail/export.tsv")
[ -n "$url_site" ] && [ -n "$installation" ] || { echo "ECHEC : adresse ou date d'installation introuvable"; exit 1; }
URL_SITE=$url_site INSTALLATION=$installation php -r '
	echo str_replace(array(getenv("URL_SITE"), getenv("INSTALLATION")), array("@URL_SITE@", "@INSTALLATION@"), stream_get_contents(STDIN));
' <"$travail/export.tsv" >"$travail/export-normalise.tsv"

if [ "$mettre_a_jour" = --mettre-a-jour ]; then
	cp "$travail/export-normalise.tsv" "$reference"
	echo "Référence mise à jour : $reference ($(wc -l <"$reference") lignes)"
elif ! diff "$reference" "$travail/export-normalise.tsv" >"$travail/ecarts.diff"; then
	echo "ECHEC : export différent de la référence ($(grep -c '^[<>]' "$travail/ecarts.diff") lignes, détail $travail/ecarts.diff)"
	exit 1
fi
rm -rf "$travail"
echo "OK : $wordpress conforme à la référence"
