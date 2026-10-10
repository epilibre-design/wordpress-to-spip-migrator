# 2. Preparing a blank SPIP site

The destination remains offline until access and content checks are completed. The directory requested by the script must be absent or empty; existing data is incompatible with preserving WordPress identifiers.

## Preparation from checkout

With SPIP-Cli installed and patched, and its dependencies available:

```bash
bash outils/preparer_spip.sh --help
bash outils/preparer_spip.sh \
  --spip /chemin/vers/spip-test \
  --wordpress /chemin/vers/wordpress-fige \
  --spip-cli /chemin/vers/spip \
  --wp2spip lien
```

This command prepares the destination **without launching the import**. `--wp2spip lien` links the checkout for development; `copie` is the default. The `--importer` option can chain the import, but does not exempt you from auditing or checks.

## Choosing the SPIP database

| Option | Destination | Condition |
|---|---|---|
| No option / `--base-spip sqlite` | Local SQLite | PDO SQLite extension available |
| `--base-spip mysql:base_spip` | Existing MySQL database | Valid access and no tables with the SPIP prefix already present |
| With `--base-partagee` | Database also used by WordPress | Explicit authorization for cohabitation; distinct prefixes |

The source WordPress database remains declared as an external database, even when SPIP uses SQLite. Using SQLite for the destination does not automatically convert a MySQL WordPress installation to SQLite.

The script reads connection constants and the prefix from `wp-config.php` without executing it. A calculated prefix or ambiguous configuration requires adapted preparation; the import command allows for an explicit `--prefixe`. Hosts with ports or sockets require special attention to script options, particularly `--sql-hote` for SPIP.

## Access and initial password

`SPIP_ADMIN_PASS` can provide the initial password; otherwise, the script generates one and displays it. `SPIP_DB_PASS` can replace the destination's MySQL password. Do not write these in documentation or version control them.

SPIP-Cli receives installation passwords as arguments and may display them: protect logs and machine access during this step.

## Verify before proceeding

From the SPIP root, using the same CLI executable:

```bash
spip plugins:lister --short --raw --no-dist
spip wordpress:importer /chemin/vers/wordpress-fige --info
```

Confirm that Pages uniques, Polyhiérarchie, and wp2spip are active, with PHP 8.1 or newer and the DOM extension. Sale is no longer installed or required. Other plugins required by the content will be detected before processing. The source is readable and the prefix matches the expected tables.

To import Yoast or ACF data, install and activate the [migrator extensions](../comprendre/extensions.md) in this SPIP before the import; they are not added by this script. Prepare without `--importer`, then verify the processes with `--info`.

Source: [preparation script](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/outils/preparer_spip.sh). **Next: [launch the import](importer.md).**
