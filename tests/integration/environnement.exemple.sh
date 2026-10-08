# Copier en environnement.sh (non versionné) et renseigner les valeurs de la machine de test
export WP2SPIP=/chemin/vers/wp2spip
export SPIP_CLI=/chemin/vers/spip-cli/bin/spip
export SAUVEGARDES=/chemin/vers/sauvegardes

# WordPress 6.9 et 7.1 de test (contenu Theme Unit Test), et les SPIP qui les importent
export SPIP_WP6=/chemin/vers/spip-wp6
export WP6=/chemin/vers/wordpress-6.9
export BASE_WP6=base_wp6
export SPIP_WP7=/chemin/vers/spip-wp7
export WP7=/chemin/vers/wordpress-7.1
export BASE_WP7=base_wp7

# Site réel, importé dans un SPIP SQLite et dans un SPIP MySQL
export WP_REEL=/chemin/vers/wordpress-reel
export SPIP_REEL_SQLITE=/chemin/vers/spip-reel-sqlite
export SPIP_REEL_MYSQL=/chemin/vers/spip-reel-mysql
export BASE_REEL_MYSQL=base_reel

# Accès MySQL
export MYSQL_OPTIONS=-uutilisateur
export MYSQL_PWD=motdepasse
