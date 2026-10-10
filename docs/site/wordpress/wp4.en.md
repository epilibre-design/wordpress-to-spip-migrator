# WordPress 4: Classic Editor and Taxonomies

The 4 family illustrates an already rich model: typed content, metadata, taxonomies, media, comments, and configuration. The classic editor text combines HTML and shortcodes; plugins could store other structures as early as this era.

## Editorial Foundation

Posts (`post`), pages (`page`), media (`attachment`), and revisions (`revision`) all share the `posts` table. Roles are found in user metadata; categories and tags use term tables and their relationships.

For migration, this requires **selecting types and statuses**: copying all rows from `posts` as SPIP articles would also import revisions, menus, or plugin data.

## 4.2: Separation of shared terms

Historically, a single `term_id` could be shared between taxonomies. WordPress 4.2 introduced term splitting during modifications, using `_split_shared_term`. Taxonomy migrations must distinguish between `term_id`, `term_taxonomy_id`, and relationships.

An old or upgraded copy may still present legacy situations. Preserving SPIP section identifiers requires qualifying these cases; the "WP4" objective does not allow the assumption that all old structures have already been normalized.

## 4.4: Term metadata

The `termmeta` table and term metadata APIs allow for associating additional values with categories and other taxonomies. The base model retains its term and relationship tables; this new layer can carry data important to the site.

wp2spip imports categories, tags, their descriptions, and their relationships to content, but does not perform a general transfer of `termmeta`. Values specific to the theme or plugins require a dedicated strategy.

## Media and shortcodes

Images, captions, and galleries use HTML and shortcodes such as `[caption]` and `[gallery]`. The migrator has conversions for several native forms. Plugin shortcodes are not covered simply because they are found in the same text.

## Coverage and control

The engine targets **4.9 to 7.x**, with a test in 4.9; the 4.0–4.8 range is not demonstrated. Qualifying these versions, shared terms, shortcodes, and relationships is part of the work required for the complete WordPress 4 objective.

Sources: [term splitting, 4.2](https://github.com/WordPress/WordPress/blob/87bf150016e042bc3e21f2f1cb9de44042b8cdb1/wp-includes/taxonomy.php), [4.4 schema](https://github.com/WordPress/WordPress/blob/f6a29831c76d2dbe82e9ae673539f910654c58a4/wp-admin/includes/schema.php), [4.4 term API](https://github.com/WordPress/WordPress/blob/f6a29831c76d2dbe82e9ae673539f910654c58a4/wp-includes/taxonomy.php), [types in 4.9](https://github.com/WordPress/WordPress/blob/29ffbff370968ae48a1b7a34e35c8b8e75cf0f91/wp-includes/post.php), [overall spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md).
