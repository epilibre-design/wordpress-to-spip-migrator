# 3. Running the import

Execute SPIP-Cli from the **root of the destination SPIP site**, not from the plugin root. Use a frozen source, a blank destination, and ensure mandatory plugins are active.

## Inspection and full import

```bash
spip help wordpress:importer
spip wordpress:importer /path/to/frozen-wordpress --info
spip wordpress:importer --garder-adresse -v /path/to/frozen-wordpress
```

`--garder-adresse` preserves the destination address, which is useful for an offline staging environment. Without this option, the SPIP configuration will adopt the WordPress address. `-v` provides more details on anomalies and incomplete conversions.

## Business options

| Option | Usage |
|---|---|
| `--base=wordpress` | Name of the external SPIP connection, default `wordpress` |
| `--prefixe=site_` | Explicit prefix, overrides the one read in `wp-config.php` |
| `--traitements=name1,name2` | Subset of existing processes; dependency order remains that of the engine |
| `--info` | Version and list of processes, without importing |
| `--garder-adresse` | Preserves the SPIP address |

The command first prepares the required additional plugins; it can be re-run after they are activated. Read the messages until the final result.

## Available processes

Metas → authors → SPIP sections → documents → articles → page hierarchy → **tags (`importer_mots`)** → access → polyhierarchy → comments. See [the detailed flow](../comprendre/traitements.md).

The [Yoast and ACF extensions](../comprendre/extensions.md), if active, add their processes to the same command. Yoast adjusts the primary category after the articles and adds SEO metadata at the end of the list; ACF adds `importer_acf` at the end of the list. Use `--info` to see the effective list for your installation.

## Understanding the output

An exit code of **1** typically indicates an invalid process option, a missing prerequisite, or a failed process; subsequent processes are not executed. An exit code of **0** means the command finished according to its contract, not that all links, files, and renderings have a complete equivalent.

The reports specifically indicate rejected media, removed dynamic blocks, unknown blocks, or residual links. Keep and examine this information before [validation](valider.md).

## Re-running or restarting

A re-run generally avoids recreating tracked content, but does not update its text. Some processes recalculate configuration or relationships. This mechanism is not a guaranteed resume after interruption: to redo an import after failure, interruption, or engine evolution, start over from a reset destination with verified backups.

Source: [command](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/spip-cli/WordpressImporter.php).
