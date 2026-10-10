# Prerequisites

## Choosing the right environment

| Usage | Prerequisites |
|---|---|
| Using the plugin | SPIP 4.2 to 4.4 according to the manifest; **PHP ≥ 8.1**, with the DOM extension; the HTML5 tree comes from `Dom\HTMLDocument` starting from PHP 8.4, and from the Masterminds HTML5-PHP library bundled with the plugin beforehand. SPIP 4.2 and 4.3 support PHP up to 8.3 |
| Running locked tests | PHP **≥ 8.1** (PHPUnit 10.5), Composer, extensions required by PHPUnit and SQLite for integration |
| Preparing a SPIP site automatically | Bash, PHP CLI, patched SPIP-Cli, MySQL client to read the WordPress site, access to databases and downloads |
| Building this documentation site | Python 3.11 or later and `requirements-docs.txt`; no SPIP or WordPress required |

The targeted WordPress range is **4.9 to 7.x**: versions 4.0–4.8 require additional qualification. See the [coverage matrix](../wordpress/compatibilite.md).

## Access to source data

You need the WordPress folder containing `wp-includes/version.php`, `wp-config.php`, and the files from `wp-content/uploads/`, as well as a readable database declared as an external database in SPIP. A WXR/XML export alone does not replace these entries: the engine reads the SQL tables and the files.

The prefix is read from `wp-config.php` without executing it; `--prefixe` allows you to provide an explicit value. The tables consulted are `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`, `options`, `users`, `usermeta`, and `comments`, preceded by the chosen prefix.

## SPIP Plugins

| Plugin | Role | Dependency |
|---|---|---|
| Pages uniques ≥ 2.0 | Pages outside of sections | Mandatory |
| Polyhiérarchie ≥ 4.0 | Secondary sections | Mandatory |
| Albums ≥ 4.0 | Galleries | Depending on content |
| a2a ≥ 4.2 | Page relationships | Depending on content |
| Accès restreint | Private/protected content | Depending on content |
| Forum | Comments | Depending on content |
| oEmbed | Embedded URL players | Used for rendering, to be planned if necessary |

Sale is no longer a dependency: HTML is converted by `inc/wp2spip_html.php`, included in wp2spip.

The command downloads and activates the plugins it detects as required by the content, as well as their dependencies. The [wp2spip_yoast and wp2spip_acf](../comprendre/extensions.md) extensions add SEO and Champs Extras Interface respectively, depending on the data present. These migrator extensions are installed separately and must be active before the import. The final rendering and all dependencies of your site remain to be checked.

## Downloads and permissions

Composer uses Packagist and SPIP repositories; SPIP-Cli uses download servers and the SVP repository, by default `https://plugins.spip.net/depots/principal.xml`. Plan for their destinations and associated archive hosts. Maintain TLS and package verification.

SPIP must be able to write to its configuration, cache, file, and plugin folders. Keep actual database access and passwords in a protected local configuration, outside of public sources.

Sources: [paquet](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/paquet.xml), [Composer](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json), [lock](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.lock), [tables read](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip.php).
