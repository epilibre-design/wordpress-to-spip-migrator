# 1. Audit and backup

The migration starts with a **frozen WordPress site** and a **blank, offline SPIP site**. The engine does not provide continuous synchronization with a WordPress site that changes during the import.

## Inventory before installing

| Inventory | Questions to resolve |
|---|---|
| Version and database | Exact WordPress version? Prefix? Single or multisite installation? Custom tables? |
| Content | How many posts, pages, drafts, private content, and custom post types? |
| Relationships | Multiple categories, sub-pages, tags, authors, nested comments? |
| Media | Files present? Links outside the media library? Remote media or external storage? |
| Editing | Classic editor, blocks, synced patterns, plugin blocks, specific shortcodes? |
| Presentation | Menus, widgets, templates, styles, navigation, and theme to be rebuilt? |
| Extensions | ACF, SEO, shop, forms, or other non-native data? |

A multisite database contains global data and per-site tables. Selecting a prefix is not equivalent to a full migration of a multisite network; prepare and qualify each selected source.

## Maintain a reference state

Back up the database and files, including uploads, themes, and configuration. Verify that a restoration is possible. Keep counts by type and status, a few representative URLs, screenshots of complex pages, and examples of relationships.

Freeze writes during the import, or work on a consistent copy of the database and files. WordPress counters, especially those for taxonomies, do not replace the inventory of actual relationships.

## Decide on adaptations

Consult the [mapping matrix](../correspondances/index.md). Tags are imported as keywords, including those without content; verify their links in the inventory. If the site uses Yoast SEO or ACF, plan for the [corresponding extensions](../comprendre/extensions.md) and examine their scope. For missing or partial functions, choose an adaptation, manual recovery, or independent preservation of the source.

## Prepare acceptance criteria

Plan for a check of identifiers, dates, statuses, relationships, texts, media, and access. Specifically, choose a child page, a multi-category post, a gallery, and private content. Define redirects and SPIP rendering before the switchover.

**Next step: [prepare SPIP](preparer.md).**
