# Migrator extensions

Yoast SEO and ACF are supported via **two separate published plugins**. Their processing is added to the wp2spip engine when they are installed and active.

## Installing migrator extensions

Retrieve [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast) or [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf), place them in the `plugins/` directory of the destination SPIP, and activate them alongside wp2spip **before the import**. Consult their README files for installation and exact versions. The published versions require wp2spip ≥ 3.0.0 and PHP 8.1 or newer.

From the root of this SPIP:

```bash
spip wordpress:importer /path/to/wordpress-fige --info
spip wordpress:importer --garder-adresse -v /path/to/wordpress-fige
```

The first command must show the processing of the active extension; the second executes them in the same chain as the engine's processes. Detection prepares the necessary target plugins based on the source data. The `outils/preparer_spip.sh` script does not retrieve migrator extensions: to add them, prepare without `--importer`, install them, then launch the import.

## Yoast SEO

**Published extension: [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast).** It adds two processes:

| Process | Position | Conversion |
|---|---|---|
| `importer_yoast_categories` | Just after `importer_articles`, before polyhierarchy | Yoast primary category → SPIP primary section; others become secondary sections |
| `importer_yoast_seo` | Added at the end of the list | Titles, meta descriptions, and indexing directives → SEO plugin, for articles/pages and categories |

A Yoast primary category that no longer belongs to the content is flagged, and the core choice is kept. Metadata for a category deleted from WordPress is ignored. The SEO plugin is required only if metadata to be imported is present; changing the primary section alone does not require it.

Known title variables are replaced. A value still containing an unknown variable is ignored and counted in the summary. SEO metadata already present in SPIP is preserved. Check the tags produced in the `<head>` of the destination site.

Keyphrases, scores, canonical URLs, social data, XML sitemaps, global title templates, and metadata for other taxonomies are not imported. See [the scope and reported tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-yoast-design.md), then the extension repository documentation for its current state.

## Advanced Custom Fields

**Published extension: [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf).** The `importer_acf` process, added at the end of the list, retrieves the database definitions of published groups applicable to articles/pages, then their values on the imported content. It creates `acf_<name>` columns in `spip_articles`, editable with Champs Extras Interface, and preserves values already present in the destination.

| Supported ACF types | Conversion |
|---|---|
| Text, email, URL, number, range | Value in a text field |
| `textarea`, `wysiwyg` | Text converted with the wp2spip converter; internal links for `wysiwyg` |
| Select, radio buttons, button group | Choices preserved; multiple selections and checkboxes stored according to Champs Extras |
| Boolean, date | Value adapted to the target format |
| Image, file | ID of the imported document, linked to the article; missing media flagged |

Champs Extras and its interface are detected based on importable fields, even when their values are still empty. Group rules allow retaining definitions applicable to content; they are not reproduced as SPIP form display conditions.

Groups outside of articles/pages, ACF 4 definitions or those declared in PHP/JSON, repeaters, sub-fields, nested groups, galleries, relations, and flexible content are not imported by this extension. Examine the exclusion summary; imported extra fields still require explicit use in templates.

The `wp2spip_acf_correspondances` pipeline specific to the extension allows associating an ACF name with an **existing** `spip_articles` column instead of creating an extra field. See [the scope and reported tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-acf-design.md), then the extension repository documentation for its current state.

## Engine extension points

| Mechanism | Usage |
|---|---|
| `wp2spip_traitements` | Insert a process into the ordered list |
| `w2spip_traitements` | Old name still called, for compatibility |
| Function variants by version | Adapt a process to a WordPress data format |
| `wp2spip_bloc` | Add/replace the conversion of a block |
| `wp2spip_plugins_requis` | Supplement dependencies detected before import |

Development must provide its own code, dependencies, and validations. Extension test suites are executed in their own repositories; their tests do not replace a trial run on your source.

[Engine architecture](traitements.md).
