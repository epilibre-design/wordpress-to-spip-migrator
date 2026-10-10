# Identifiers and traceability

Identifiers link objects between the two CMSs. Preserving a number facilitates shortcuts and checks; keeping a record of the origin remains necessary even when the number changes.

## Two complementary mechanisms

| Source object | SPIP identifier | Source trace |
|---|---|---|
| Post/page `posts.ID` | Same `id_article` | `id_wordpress` |
| Category `terms.term_id` | Same `id_rubrique` | `id_wordpress` |
| Tag `terms.term_id` | Same `id_mot`, in the "Tags" group | `id_wordpress` |
| Media `posts.ID` | Same `id_document` | `id_wordpress` |
| User `users.ID` | SPIP numbering | `id_wordpress` |
| Comment `comments.comment_ID` | SPIP forum numbering | `id_wordpress` |
| Converted gallery | Album created in SPIP | Associations and conversion context, not a universal WordPress gallery ID |

The plugin adds a traceability field to the declared editorial object tables. Since a WordPress page and post share the `posts` table, their identifiers are already in the same source space.

## Resolving a relationship

For a WordPress author `7` who has become SPIP author `9`, the article is associated with `9` after searching for `id_wordpress = 7`. It is not associated with number `7` by assumption.

For WordPress article `42`, the shortcut `article42` points to the correct SPIP article after import. The target content can be imported later: its identifier preservation allows for anticipated referencing.

## Why a blank destination?

Before creating a series of articles, sections, documents, or keywords, the engine checks the necessary identifiers. A collision stops processing before the creation of objects in that series, but previous series may already exist. For tags, the keyword group may also have been created before this check.

Another SPIP article number `42` must not be confused with WordPress content `42`. The verification prevents this ambiguity; it does not transform an existing SPIP site into a merge space.

## A trace is not continuous tracking

Finding `id_wordpress` avoids duplicates upon re-running. This does not mean that the engine synchronizes WordPress changes, reconstructs a partially written object, or re-imports its history. A repeated migration should be prepared on a reset destination.

Uninstalling removes the traceability fields added by the plugin. Keep useful reports and exports before removing the tool; do not treat its uninstallation as a migration rollback.

Sources: [field declaration](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip_pipelines.php), [installation/uninstallation](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip_administrations.php), [identifier verification](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip.php).
