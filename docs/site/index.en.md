<div class="hero" markdown>

# Change CMS. Keep the relationships.

**WordPress → SPIP** converts editorial data to recover linked articles, pages, authors, and media. The migration aims to preserve the site structure and then provide it with a new rendering in SPIP.

Version 3.0.0 (test status) · PHP 8.4 · SPIP 4.2–4.4 · Target: WordPress 4.9–7.x

</div>

<div class="grid cards" markdown>

- **Prepare my migration**

    Inventory the site, install dependencies, prepare a blank destination, import, and verify the results.

    [Start with the audit](migrer/audit.md)

- **Understand the mappings**

    See how a parent page, multiple categories, or a gallery become SPIP objects and links.

    [Explore the matrix](correspondances/index.md)

- **Locate my WordPress version**

    Understand the classic model, blocks, and site editing data, from WordPress 4 to 7.

    [Explore the structures](wordpress/modele.md)

- **Contribute to the tool**

    Understand the processing order, extension points, and published complementary plugins.

    [Read the mechanisms](comprendre/traitements.md)

</div>

## Data conversion

An article belonging to two categories becomes a SPIP article linked to a primary section and a secondary section. A child page remains linked to its parent page. Media retains an identifier allowing its references to be rewritten within texts.

These mappings have editorial consequences: a WordPress role and a SPIP status do not necessarily grant the same rights; a saved block and a calculated block are not converted in the same way.

!!! info "A version to be qualified on your site"
    The engine imports tags and converts HTML with its own parser; separate extensions add Yoast SEO and ACF support. The package is in **test** status: consult the [coverage](wordpress/compatibilite.md) and validate a representative import before switching.

## What remains to be rebuilt

The WordPress theme does not become a SPIP template. Menus, widgets, theme templates, custom types, and extensions require a separate inventory and strategy. Preserving data relationships helps rebuild the rendering; it does not guarantee an identical appearance.

[See the mappings](correspondances/index.md) · [Install the migrator](installer/installation.md) · [Read the sources](comprendre/sources.md)
