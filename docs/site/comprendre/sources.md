# Sources et méthode

## Références du moteur et des extensions

Les références de code et de specs du moteur sont des permaliens vers `epilibre-design/wordpress-to-spip-migrator` ; les specs restent sur GitHub et ne sont pas publiées comme pages de ce site. Les liens vers Yoast et ACF ouvrent leurs dépôts distincts.

- [README](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/readme.md), [manifeste SPIP](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/paquet.xml), [Composer](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json).
- [Spec d’ensemble](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md).
- [Spec de préparation](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-preparation-spip-design.md), [blocs](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md), [hiérarchie des pages](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md).
- [Spec préfixes](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-prefixe-tables-design.md), [tests automatisés](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-tests-automatises-design.md).
- [Import des étiquettes](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_mots.php), [conversion HTML5](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_html.php) et [tests de rendu](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/HtmlSpipTest.php).
- [Extensions du migrateur](extensions.md) : plugins publiés [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast) et [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf), et points d’extension du moteur.

## Références WordPress

Les pages historiques citent des fichiers du miroir officiel `WordPress/WordPress` à leur SHA exact. `schema.php` décrit les tables ; `post.php` décrit les types ; `blocks.php` et les bibliothèques de blocs renseignent la sérialisation et les références.

Les notes officielles de [WordPress 7.1 « Mary Lou »](https://wordpress.org/news/2026/08/mary-lou/) complètent les références de WP7. La [référence des bases WordPress](https://codex.wordpress.org/Database_Description) et le [manuel de l’éditeur de blocs](https://developer.wordpress.org/block-editor/) apportent un contexte général, mais ne remplacent pas la comparaison des versions exactes.

## Ne pas confondre les couches

Un changement d’interface peut conserver le même schéma SQL, tandis qu’un nouveau type de contenu ou un attribut de bloc change les données à interpréter. Le site privilégie ces effets sur la migration plutôt qu’un inventaire de nouveautés de chaque version.
