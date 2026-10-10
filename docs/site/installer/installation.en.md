# Installing the migrator

## Retrieve the repository

On a development machine:

```bash
git clone --branch main https://github.com/epilibre-design/wordpress-to-spip-migrator.git
cd wordpress-to-spip-migrator
```

## Installation in an existing SPIP site

1. Prepare a blank, offline SPIP destination.
2. Install SPIP-Cli and verify that its `plugins:svp:telecharger` patch is present.
3. Verify PHP 8.1 or newer and its DOM extension, then install and activate Pages uniques and Polyhiérarchie.
4. Place this plugin under `plugins/wp2spip` — either as a copy or via a link to the checkout for development purposes — then activate it.
5. Declare the WordPress database as an external database in the SPIP administration. The default recognized name is `wordpress`.

From the **SPIP site root**, and with the correct CLI executable:

```bash
spip plugins:lister --short --raw --no-dist
spip help wordpress:importer
spip wordpress:importer /path/to/wordpress --info
```

Place the path before `--info`: this option accepts an optional value and can otherwise absorb the WordPress argument.

`--info` checks the folder, version, prefix, and tables before displaying the processes. Command and data recognition does not replace a validation import.

## Automated preparation

The repository provides `outils/preparer_spip.sh`, which installs SPIP 4.4 by default, the mandatory dependencies, the migrator, and the external connection. The [preparation guide](../migrer/preparer.md) describes its usage and the distinction between SQLite and MySQL.

## Adding Yoast SEO or ACF

The [wp2spip_yoast and wp2spip_acf](../comprendre/extensions.md) plugins are published in separate repositories. Place them in the `plugins/` folder of the destination SPIP and activate them alongside wp2spip **before** launching the import. The `--info` command should then display their additional processes. The preparation script does not install these migrator extensions.

Content detection handles the necessary target plugins: SEO for Yoast metadata, Champs Extras and its interface for importable ACF fields. Verify their configuration and rendering after import.

## SPIP-Cli and SVP patch

The test environment installs SPIP-Cli with Composer and then applies [the provided patch](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/spip-cli.patch). This patch fixes plugin selection, permissions, and download error handling. An independent installation must have the same fixes; do not assume that another `spip` executable in the PATH is patched.

The [install-spip-test.sh](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/scripts/install-spip-test.sh) script applies this patch in `vendor/`, not in a system-wide SPIP-Cli.

## Before the first import

Verify access to the source database, media files, plugins, and the prefix. Prepare a plan for [unconverted data](../correspondances/index.md), then follow the [audit](../migrer/audit.md).
