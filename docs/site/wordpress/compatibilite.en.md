# Coverage and compatibility

**Target: WordPress 4.9 to 7.x**, with tests conducted on 4.9, 6.9, and 7.1. Do not interpret these indications as a guarantee for every version, theme, and extension.

## Data format matrix

| Family/version | Relevant formats | Available mechanisms | Remaining validation |
|---|---|---|---|
| 4.0–4.8 | HTML/shortcodes, legacy terms; `termmeta` since 4.4 | Generic processing for articles, categories, media… | Outside the 4.9 minimum target; qualify these versions and shared terms |
| 4.9 | Classic model and extension data | Generic processing and classic conversions | Manual test, not an automated suite covering all 4.x cases |
| 5.x | Blocks since 5.0, comments in 5.5, site objects in 5.9 | Block analysis, selection of both comment forms | Block variants and references; theme/navigation not imported |
| 6.x | Patterns, metadata, and site editing | Block converter and integration tests | Synchronized references, new attributes, and theme diversity |
| 6.9 | Theme Unit Test set, with versioned reference | Full comparative import script | Re-run on an identified installation; do not extrapolate to all sites |
| 7.x / 7.1 | Core continuity, blocks, and site types | Version reading, generic processing; full 7.1 import with versioned reference | Qualify exact functions and references; full coverage not demonstrated |

## Tests

SQLite tests use a schema and fixtures of the queried tables; they do not reproduce all historical installations. Full 6.9/7.1 imports require external WordPress instances and their configuration.

## Regardless of the version

Tags are imported by the core, and Yoast/ACF have published extensions with a specific scope. Custom types, menus, revisions, and other extension data are not imported (see the [matrix](../correspondances/index.md)). A version supported by the architecture does not add missing processing.

Searching for variants by version allows for extending the engine. A new adaptation must explain storage differences and provide verifiable conversion and relationship cases.

## Choosing a qualification scenario

Select the exact version and a representative set of the functions actually used. Prepare a frozen source, a blank destination, and an expected inventory. Execute the appropriate tests and the acceptance procedure, then log the commit, PHP/SPIP version, plugins, and remaining anomalies.

Sources: [overall spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md), [tests and commands](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/readme.md), [command architecture](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/spip-cli/WordpressImporter.php).
