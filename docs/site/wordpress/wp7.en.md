# WordPress 7: verifying continuity and references

Support for WordPress 7 is part of the migrator's objective. The [official WordPress 7.1 "Mary Lou" release notes](https://wordpress.org/news/2026/08/mary-lou/) provide the context for this version; for conversion purposes, the stored data must also be examined.

## What the 7.1 code shows

| Observed structure | Impact on migration |
|---|---|
| `posts`, `postmeta`, taxonomies, users, comments, and options | The relational foundation remains present |
| `wp_block` and `wp_pattern_sync_status` | Compositions may require reference resolution |
| `wp_template`, `wp_template_part`, `wp_global_styles` | Presentation data remains distinct from articles |
| `wp_navigation` | Navigation is not automatically a SPIP section |
| `wp_font_family`, `wp_font_face` | Typography belongs to the theme reconstruction |

These structures are **observed in 7.1**; they are not presented as new features introduced by WP7. They already existed in the 6.9 code examined.

## Comparing schemas

Comparing the SQL definitions in `wp-admin/includes/schema.php` between **6.9 and 7.1** shows no changes to the core table creation instructions in this file. This observation is limited to these files and versions: it does not prove the identity of all metadata, all block attributes, or the state of an upgraded database.

The code for registering types is evolving, particularly around editing screens, without these interface changes necessarily implying a new table.

## Block reference: a concrete case

```html
<!-- wp:block {"ref":123} /-->
```

The content is that of another object, not the HTML contained within this comment. WordPress resolves this reference during rendering. In the documented engine, `wp_block` objects are not imported as articles, and this reference has no specialized conversion guaranteeing its expansion.

Consequence: inventory the references and plan for their resolution or reconstruction. A readable SQL database and a recognized version number are not sufficient to preserve this structure.

## Expected validation for WP7

The repository provides a complete WordPress 7.1 import scenario and a versioned reference; the plans report its validation and the review of differences after replacing Sale. This documents a test suite, not a guarantee for all WP7 functions. Re-run the import on an identified external installation and verify at a minimum: articles/pages, media, nested blocks, referenced compositions, navigation, access, and comments, while noting any missing conversions.

The release of WP7.1 does not demonstrate the migrator's compatibility. [See the coverage matrix](compatibilite.md).

Sources: [official version 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/version.php), [schema 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-admin/includes/schema.php), [schema 6.9](https://github.com/WordPress/WordPress/blob/ec24ee6087dad52052c7d8a11d50c24c9ba89a3b/wp-admin/includes/schema.php), [types and metadata 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/post.php), [reference resolution](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/blocks/block.php), [wp2spip conversion](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php).
