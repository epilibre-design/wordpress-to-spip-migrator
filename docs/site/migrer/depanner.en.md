# Troubleshooting a migration

Read the first failure before restarting. Processes depend on each other; a partially written destination does not constitute a fresh, blank database.

| Symptom | Diagnosis and action |
|---|---|
| `wordpress:importer` command missing | Check SPIP root, CLI executable, and activation of wp2spip and its dependencies |
| `wp-includes/version.php` not found | Provide the WordPress installation folder, not just uploads or an XML export |
| Prefix not found | Check `wp-config.php`, then provide `--prefixe` if its value is calculated or ambiguous |
| WordPress tables missing | Check external connection, database, and prefix; do not arbitrarily try `wp_` |
| Prefix different from a started import | Start over from a blank destination; the engine refuses to mix sources |
| Plugin download/activation impossible | Read the displayed commands, check network access, SVP patch, and folder permissions |
| Identifier already taken | Destination not blank or conflict; back up then create a clean test destination |
| Unknown process | Compare with `--info`; for `importer_yoast_categories`, `importer_yoast_seo`, or `importer_acf`, check the installation and activation of the corresponding plugin |
| Tags not linked or outside the group | Examine the `importer_mots` report: missing content, deleted group, or moved imported keyword; start over from a clean destination after correction |
| HTML5 parser unavailable | Check the DOM extension of the PHP CLI actually in use (PHP ≥ 8.1) and, before PHP 8.4, the presence of `lib/masterminds-html5/` in the plugin; `-v` displays the parser used |
| Media missing/refused | Check source file, URL, permissions, and file type; use `-v` |
| Link to WordPress preserved | Examine media outside the media library, background URL, or ambiguous slug; prepare a targeted correction |
| Block conversion incomplete | Identify the block and its storage; an empty dynamic block does not provide HTML to import |

## After interruption or failure

Keep the logs without disclosing login credentials. Identify the executed processes and created objects. Reset the **destination** to zero or prepare a new disposable destination, then restart the full import from the same frozen source.

Do not use a reset script on a live installation without checking its paths and database. The WordPress source and its backups remain independent of the destination.

## Environment error or engine defect?

A missing PHP extension, a refused connection, or an inactive plugin is a prerequisite to be corrected. An erroneous relationship despite correct prerequisites may be an engine defect: log the exact version, commit, minimal scenario, and output, without modifying test references to hide the problem.

[Tests](../installer/developpement.md) · [Command and errors](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/spip-cli/WordpressImporter.php).
