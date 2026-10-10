# WordPress 6: patterns and site structures

The 6.x family expands the use of site editing and patterns. For migration, the difficulty often lies in types, references, and metadata rather than the appearance of a new table.

## Content and presentation side-by-side

The template, template part, global style, and navigation types already present in 5.9 continue to use `posts`. Editorial articles and presentation structure therefore share storage, with different meanings.

The engine must not arbitrarily transform a `wp_template` into a published article. The final rendering depends on SPIP templates and a recovery of menus and styles.

## 6.3: synchronized or non-synchronized patterns

The `wp_block` type is used for patterns, and the `wp_pattern_sync_status` meta, added in 6.3, distinguishes, among other things, a non-synchronized pattern. A synchronized pattern can be referenced; a non-synchronized insertion can provide a copy of blocks within the content.

| Source form | Consequence |
|---|---|
| Blocks copied into the article | The converter can process the blocks actually saved |
| Reference to a synchronized pattern | Resolution of the referenced content is necessary; not guaranteed in the documented engine |
| Definition in a theme/plugin | WordPress files and context may be required |

This distinction matters more for import than a simple "pattern" label. Sharing content between several articles is a relationship that must be preserved or explicitly transformed.

## Typographic data and metadata

In the 6.9 code, `wp_font_family` and `wp_font_face` are also registered types. They concern presentation and do not become SPIP editorial content. The fonts and styles of the future site require a separate theme decision.

Attributes and metadata added by the editor or extensions can modify the behavior of a block without changing `posts`. Auditing the actual content and its references remains essential.

## Coverage status

The repository provides a complete import scenario for a WordPress 6.9 set and a versioned reference; the plans report its validation, followed by the review of differences after replacing Sale. The block and integration tests provide controllable cases, but do not demonstrate that all blocks, minor versions, and themes of the 6 family are converted. Replaying the scenario requires an external configured WordPress installation.

Sources: [6.0 types](https://github.com/WordPress/WordPress/blob/cc101b64012b16d087780657a2b828ccd7794a63/wp-includes/post.php), [6.3 patterns and meta](https://github.com/WordPress/WordPress/blob/ac3899153a790a6f060dc816ff94812e0fd99875/wp-includes/post.php), [6.9 types](https://github.com/WordPress/WordPress/blob/ec24ee6087dad52052c7d8a11d50c24c9ba89a3b/wp-includes/post.php), [article selection](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [block tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/BlocsTest.php).
