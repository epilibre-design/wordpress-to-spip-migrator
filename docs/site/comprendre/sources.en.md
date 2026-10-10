# Sources and methodology

## Engine and extension references

The code and engine spec references are permalinks to `epilibre-design/wordpress-to-spip-migrator`; the specs remain on GitHub and are not published as pages on this site. Links to Yoast and ACF open their respective separate repositories.

- [README](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/readme.md), [SPIP manifest](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/paquet.xml), [Composer](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json).
- [Overall spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md).
- [Preparation spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-preparation-spip-design.md), [blocks](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md), [page hierarchy](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md).
- [Prefixes spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-prefixe-tables-design.md), [automated tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-tests-automatises-design.md).
- [Tag import](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_mots.php), [HTML5 conversion](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_html.php) and [rendering tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/HtmlSpipTest.php).
- [Migrator extensions](extensions.md): published plugins [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast) and [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf), and engine extension points.

## WordPress references

Historical pages cite files from the official `WordPress/WordPress` mirror at their exact SHA. `schema.php` describes the tables; `post.php` describes the types; `blocks.php` and the block libraries provide information on serialization and references.

The official [WordPress 7.1 "Mary Lou"](https://wordpress.org/news/2026/08/mary-lou/) release notes supplement the WP7 references. The [WordPress Database Description](https://codex.wordpress.org/Database_Description) and the [Block Editor Handbook](https://developer.wordpress.org/block-editor/) provide general context, but do not replace the comparison of exact versions.

## Do not confuse the layers

An interface change may retain the same SQL schema, while a new content type or block attribute changes the data to be interpreted. This site prioritizes these effects on migration rather than an inventory of new features in each version.
