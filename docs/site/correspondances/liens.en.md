# Links, slugs, and site address

Distinguish between converting a link within text, saving a slug, and redirecting an old public URL.

## Links in content

| Source | Conversion |
|---|---|
| `?p=42` or `?page_id=42` | Shortcut to article `42` |
| Content URL recognized by its slug | Shortcut to the identified object |
| Media URL recognized in uploads | Shortcut to the corresponding document |
| Ambiguous URL or unrecognized media | May remain pointing to the source; manual check required |

Retaining identifiers allows for referencing an article imported later. Resolution by slug takes parent page paths into account; if multiple candidates remain possible, the link is not forced toward an arbitrary object.

File path variants, protocols, `www.`, and thumbnails are normalized to identify media. This recognition has a limited scope: it does not replace a crawl of all links across the entire site.

## Slug and public URL

Slugs for articles/pages and documents are saved in `spip_urls`. The processing of SPIP sections reads the source slug but does not save it as a URL: category URLs must be rebuilt and their redirects prepared. Their presence does not mean that WordPress permalink rules, full page paths, or category prefixes will automatically be identical in the SPIP site.

Example: `[Read->article42]` remains linked to the SPIP object, whereas an old public URL `/2020/03/a-title/` may require an HTTP redirect. These are two different mechanisms.

## Destination address

Metadata uses `siteurl` as the default `adresse_site`. Use `--garder-adresse` to keep the staging SPIP URL. Other selected metas are `blogname` → site name, `admin_email` → webmaster email, and `blogdescription` → converted slogan.

## Verify addresses before the switch

Inventory important old URLs; compare final SPIP URLs and prepare redirects on the server. Look for residual links to WordPress, particularly background images, media outside the media library, and ambiguous slugs. Test these addresses after the switch.

Sources: [links and index](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [metas](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_metas.php), [SPIP sections](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_rubriques.php).
