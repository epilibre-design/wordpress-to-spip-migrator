# WordPress 5: blocks and site editing

WordPress 5 marks an evolution in content **representation**. The SQL foundation remains, but reading `post_content` as standalone HTML is no longer sufficient for all content.

## 5.0: block serialization

The block editor saves `<!-- wp:name {attributes} -->` comments, HTML, and child blocks. `parse_blocks()` reconstructs this tree. Some blocks save HTML; dynamic blocks may only store their attributes.

```html
<!-- wp:paragraph -->
<p>A saved paragraph.</p>
<!-- /wp:paragraph -->
```

The migrator parses this representation before its HTML5 converter and offers specialized conversions. This preserves media relationships and some layout structures, but not the full functionality of the WordPress editor.

## Reusable blocks

The `wp_block` type stores reusable content, referenced from other content. Copying only the post containing the reference may result in the loss of the reused block's text. wp2spip does not import `wp_block` as a standalone object and does not guarantee the expansion of its references.

Plan for an inventory of references and an expansion process or an adapted reconstruction.

## Comments from 5.5 onwards

The WordPress 5.5 schema defines `comment_type` with the default value `comment`, whereas older databases use an empty string for ordinary comments.

The engine selects both forms. This limited value change shows why an apparently simple SQL condition might exclude comments from another version.

## 5.9: site editing data

Templates (`wp_template`), template parts (`wp_template_part`), global styles (`wp_global_styles`), and navigation (`wp_navigation`) are types registered in `posts`. Some templates also originate from theme files: the database alone does not explain the entire rendering.

The documented migrator selects posts, pages, and media, not these theme/navigation objects. Finding them in `posts` does not mean they are imported. SPIP templates and menus must be built from the source inventory.

## Control based on functions used

Test classic HTML, nested blocks, galleries, reusable blocks, embedded content, and dynamic blocks. Verify comments and identify ignored theme data. A WP5 site using exclusively the classic editor can be very different from a WP5.9 site with a block theme.

Sources: [5.0 blocks](https://github.com/WordPress/WordPress/blob/491c67be12ca8a9fe37ae38307ba7e298c976ec3/wp-includes/blocks.php), [5.0 types](https://github.com/WordPress/WordPress/blob/491c67be12ca8a9fe37ae38307ba7e298c976ec3/wp-includes/post.php), [5.5 schema](https://github.com/WordPress/WordPress/blob/537fd931bc02e6e934a2d774422b897871aa87ad/wp-admin/includes/schema.php), [5.9 types](https://github.com/WordPress/WordPress/blob/73157386d069425c5e6ea7c4fc0122e8a9b58a7b/wp-includes/post.php), [migrator conversion](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php).
