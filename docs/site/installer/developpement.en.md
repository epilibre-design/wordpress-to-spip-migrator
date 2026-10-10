# Developing and testing

Development dependencies are defined in Composer. The plugin requires PHP **≥ 8.1**; the lock file is resolved for PHP 8.1 and contains PHPUnit 10.5. `composer tests-matrice` replays the suites and the full import with every PHP version from 8.1 to 8.4 installed.

## From the checkout root

```bash
composer install --no-interaction --prefer-dist
composer tests-unit
composer install-spip-test
composer tests-integration
```

Use `install`, not `update`, to respect `composer.lock`. Dependencies and outputs are in ignored paths: `vendor/` and `.phpunit.cache/`.

| Command | What it verifies |
|---|---|
| `tests-unit` | HTML5 conversion, blocks and utilities, without a SPIP installation |
| `install-spip-test` | Prepares SPIP 4.4 SQLite, its plugins and the SPIP-Cli patch; verifies required plugins and tables |
| `tests-integration` | Relations, tags, document statuses, blocks, links, hierarchy, prefixes, HTML rendering and templates, in this SPIP with SQLite WordPress fixtures |
| `tests-import` | Full imports of configured external WordPress sets, compared to a versioned reference |

A SQLite fixture of the columns read by the engine is not a complete installation of every WordPress version. It does not, by itself, prove historical coverage.

`HtmlTest` verifies HTML conversion and the escaping of shortcuts present as text; `HtmlSpipTest` verifies their rendering with SPIP's `propre()`. `MotsTest` checks tags, their links, and consistency errors; `DocumentsArticleTest` also verifies the recalculation of document status after association.

The suites for the Yoast and ACF extensions are in their [respective repositories](../comprendre/extensions.md). The wp2spip suite alone does not validate these extensions.

## Full imports

```bash
cp tests/integration/environnement.exemple.sh tests/integration/environnement.sh
# Fill in the paths and test databases in this ignored file.
composer tests-import
```

The README provides two WordPress 6.9 and 7.1 sets with the Theme Unit Test content. They must be installed separately; `tests-import` does not create them. The variables `WP6`, `WP7`, `SPIP_WP6`, `SPIP_WP7` and the MySQL access credentials must point to test environments.

!!! warning "Disposable databases"
    Preparation and reset scripts may delete destinations and their tables. Use copies, dedicated paths, and the disposable `BASE_PREP_MYSQL` database, never a production database.

The export normalizes certain values, including the WordPress site address and installation date. A reference change should be read as a change in behavior, not as a formality to pass tests.

## What the result allows to claim

Report the command, the revision, the exact versions, and the cases executed. Distinguish between successes, failures, skipped cases, and unexecuted suites. A zero exit code without executed cases does not validate the engine.

Sources: [PHPUnit configuration](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/phpunit.xml), [Composer scripts](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json), [fixtures](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/data/wordpress/schema.sql), [full import script](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/valider.sh).
