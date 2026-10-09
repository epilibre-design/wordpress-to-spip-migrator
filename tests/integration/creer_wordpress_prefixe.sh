#!/bin/bash
# Copie du WordPress 6.9 de test sous le préfixe de tables wpx_, dans la base jetable des essais
# Usage : creer_wordpress_prefixe.sh
#   Tables wp_ de $BASE_WP6 copiées dans $BASE_PREP_MYSQL sous le nom wpx_… (la base est d'abord vidée de ses
#   tables wp_ et wpx_), clés préfixées renommées comme le fait un changement de préfixe dans WordPress
#   (méta des utilisateurs wp_…, option wp_user_roles), et miroir du dossier du WordPress dans $ESSAIS/wp6-wpx :
#   liens vers ses fichiers, wp-config.php désignant $BASE_PREP_MYSQL avec $table_prefix = 'wpx_'.
#   Les accès du WordPress 6.9 doivent avoir les droits sur $BASE_PREP_MYSQL.
# Variables : tests/integration/environnement.sh
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/environnement.sh"
miroir="$ESSAIS/wp6-wpx"

requete() { # requete <base> <requête>
	mysql $MYSQL_OPTIONS -N -B "$1" -e "$2"
}

# 1. Tables wp_ et wpx_ retirées de la base jetable, puis tables wp_ du WordPress 6.9 copiées et renommées
anciennes=$(requete "$BASE_PREP_MYSQL" "show tables" | grep -E '^wpx?_' | paste -sd, - || true)
[ -z "$anciennes" ] || requete "$BASE_PREP_MYSQL" "drop table $anciennes"
tables=$(requete "$BASE_WP6" "show tables like 'wp\\_%'")
mysqldump $MYSQL_OPTIONS --no-tablespaces "$BASE_WP6" $tables | mysql $MYSQL_OPTIONS "$BASE_PREP_MYSQL"
renommages=$(for table in $tables; do printf '%s to wpx_%s,' "$table" "${table#wp_}"; done)
requete "$BASE_PREP_MYSQL" "rename table ${renommages%,}"

# 2. Clés qui portent le préfixe
requete "$BASE_PREP_MYSQL" "update wpx_usermeta set meta_key = concat('wpx_', substring(meta_key, 4)) where meta_key like 'wp\\_%'"
requete "$BASE_PREP_MYSQL" "update wpx_options set option_name = 'wpx_user_roles' where option_name = 'wp_user_roles'"

# 3. Miroir du dossier : DB_NAME et $table_prefix remplacés chacun exactement une fois
rm -rf "$miroir" && mkdir -p "$miroir"
for fichier in "$WP6"/*; do
	[ "$(basename "$fichier")" = wp-config.php ] || ln -s "$fichier" "$miroir/"
done
BASE=$BASE_PREP_MYSQL php -r '
	$source = file_get_contents($argv[1]);
	$remplacements = array(
		"/define\\(\\s*([\x27\"])DB_NAME\\1\\s*,\\s*([\x27\"]).*?\\2\\s*\\)/" => fn($m) => "define( \x27DB_NAME\x27, " . var_export(getenv("BASE"), true) . " )",
		"/\\\$table_prefix\\s*=\\s*([\x27\"]).*?\\1\\s*;/" => fn($m) => "\$table_prefix = \x27wpx_\x27;",
	);
	foreach ($remplacements as $motif => $remplacement) {
		$source = preg_replace_callback($motif, $remplacement, $source, -1, $nombre);
		if ($nombre !== 1) {
			fwrite(STDERR, "$motif : $nombre remplacements au lieu d’un\n");
			exit(1);
		}
	}
	file_put_contents($argv[2], $source);
' "$WP6/wp-config.php" "$miroir/wp-config.php"

echo "WordPress à préfixe wpx_ : $miroir (base $BASE_PREP_MYSQL, $(echo $tables | wc -w) tables)"
