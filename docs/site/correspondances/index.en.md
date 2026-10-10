# WordPress → SPIP Matrix

The engine converts objects **and their relationships**. The SQL structures of the two CMSs are not copied identically: a taxonomy becomes a section, a page becomes a specific article, a gallery becomes an album.

| Native WordPress function | Source storage | SPIP equivalent | Notes |
|---|---|---|---|
| General options | `options` | Site metas | Selection of settings |
| Posts | `posts` (`post`) | Articles | Texts converted, dates and links retained |
| Pages | `posts` (`page`) | Articles from the Pages uniques plugin | Page template not retained |
| Parent/child pages | `post_parent`, `menu_order` | a2a `sous_page` links with rank | Rendering to be built |
| Categories | `terms`, `term_taxonomy` | Sections | Hierarchy preserved |
| Multiple categories | `term_relationships` | Main section and secondary Polyhiérarchie | — |
| Tags | `post_tag` taxonomy | Keywords in the "Tags" group | Identifiers, descriptions and links retained, even without content |
| Users and roles | `users`, `usermeta` | SPIP authors and statuses | Rights transformed, passwords not retained |
| Private/protected | `post_status`, `post_password` | Accès restreint zones | Different access rule |
| Media | `attachment`, `postmeta`, uploads | Documents and associations | Missing/rejected files possible |
| Galleries | `gallery` block or shortcode | Albums | — |
| Blocks/HTML | `post_content` | SPIP syntax, models and HTML preserved | Partial conversion depending on block type |
| Comments | `comments` | Forums and discussion threads | Spam, trash, pingbacks/trackbacks excluded |
| Internal links and slugs | Text, `post_name` | Shortcuts and URL entries | Server redirects to be prepared |
| Revisions/autosaves | `posts` (`revision`) | No history import | Out of scope |
| Classic or block menus | `nav_menu_item`, taxonomies, `wp_navigation` | Template navigation | To be rebuilt |
| Themes, widgets and templates | Files, options, `wp_template` types… | Templates, styles and configuration | To be adapted |
| Custom types and taxonomies | Site or plugin records | Specific objects/plugins | Extension required |

## Example: two categories

A WordPress article `42` belongs to "News" (`12`) and "Culture" (`18`). If `12` is selected as the main one, SPIP receives article `42` in section `12`, also linked to section `18` via Polyhiérarchie. The content title is not duplicated into two articles.

## What "preserve as much as possible" means

Preserving identifiers, hierarchies, associations, and dates when an equivalent exists; making changes in meaning visible; identifying data that is not retained. This does not promise to keep all tables, all metadata, or all capabilities of extensions.

ACF and Yoast are **WordPress extensions**, not native functions. The separate plugins [wp2spip_acf and wp2spip_yoast](../comprendre/extensions.md) add their conversions when installed and active; they are not part of the engine checkout.
