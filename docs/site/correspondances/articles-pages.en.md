# Articles and pages

The engine imports `posts` rows of types `post` and `page`. Revisions and custom post types are not included in this selection.

## Objects and fields

| WordPress | SPIP | Transformation |
|---|---|---|
| `ID` | `id_article` and `id_wordpress` | Identifier preserved |
| `post_title` | `titre` | Title text carried over; empty titles should be checked |
| `post_excerpt` | `descriptif` | HTML converted; empty if no excerpt was entered |
| `post_content` | `texte` | Blocks, HTML, and references converted |
| `post_date` | `date`, `date_redac` | Dates preserved |
| `post_modified` | `maj`, `date_modif` | Restored after associations |
| `post_author` | Association with an author | Search by WordPress identifier |
| `post_name` | `spip_urls` entry | Slug carried over; final routing to be verified |
| `comment_status` | `accepter_forum` | `open` → forum allowed, otherwise closed |

The excerpt entered in WordPress (`post_excerpt`) becomes the **descriptif** (description) of the article or page, not its "chapo" (lead): like the excerpt in WordPress, `#INTRODUCTION` uses the description when filled, and the text otherwise. If at least one excerpt is imported, the engine enables the article description in the SPIP configuration (`articles_descriptif`) so that it appears in the private area. An excerpt generated automatically by WordPress is not stored and is therefore not carried over: `#INTRODUCTION` produces the equivalent. The excerpt of a **media** item populates the document description.

## Statuses

| WordPress | SPIP upon creation |
|---|---|
| `publish` | `publie` |
| `future` | `publie`, with future date; check SPIP display settings |
| `draft` | `prepa` |
| `pending` | `prop` |
| `private` | `prepa`, then publication in a restricted area |
| `trash` | `poubelle` |
| Published/scheduled with password | Kept in preparation until access is processed |

The translation of access rights is detailed in [Authors and access](auteurs-acces.md). An unknown status defaults to preparation; custom statuses require qualification.

## A page remains content

A WordPress page becomes a **single page**, an article without a section (`id_rubrique = -1`), with a `page` field such as `wordpress_page_42`. Its parentage does not become a section hierarchy.

For child page `51` of page `42`, the engine creates an `a2a` link named `sous_page` from the parent to the child. Children are ordered by `menu_order`, title, then identifier; their position becomes the link rank.

The template must utilize these links for sub-pages or breadcrumbs. The WordPress template `_wp_page_template` is not carried over. The [parentage spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md) provides loop examples.

## What to check after the import

Verify a published article, a draft, a scheduled post, a child page, an article without a title, and an article with an excerpt. Compare dates, author, and parentage; test the rendering of single pages and hierarchical URLs.

Sources: [articles](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [hierarchy](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_hierarchie_pages.php).
