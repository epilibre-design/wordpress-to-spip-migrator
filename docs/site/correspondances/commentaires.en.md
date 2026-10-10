# Comments and discussion threads

Comments are imported using the SPIP Forum plugin. The conversion preserves the association with the article and the replies between messages; forum IDs are those of SPIP.

## Data imported

| WordPress | SPIP Forum |
|---|---|
| `comment_post_ID` | Host article found by its source identifier |
| `comment_ID` | `id_wordpress`, distinct from `id_forum` |
| `comment_content` | Text converted by the wp2spip HTML5 converter, with line breaks from the classic editor |
| Author, email, URL, IP | Corresponding message fields |
| `user_id` | SPIP author found, otherwise no associated logged-in author |
| `comment_date` | Message date |
| `comment_parent` | Parent and root of the SPIP thread |

IP and email addresses may be personal data; verify their utility, access, and exposure on the destination site.

## Moderation and exclusions

Approved comments (`1`) → published; pending (`0`) → proposed. Spam, trash, trackbacks, and pingbacks are not imported. Comments whose parent content is not imported are ignored and counted.

The engine accepts an empty `comment_type`, used by older WordPress versions, or `comment`. This storage difference is particularly relevant around [WordPress 5.5](../wordpress/wp5.md#comments-from-55-onwards).

## Rebuilding the thread in two stages

Messages are created and then linked. A reply can therefore find a parent created after it. `id_parent` designates the message being replied to; `id_thread` designates the root. The thread date is recalculated based on its published messages.

If a parent is excluded (spam, for example), the reply cannot keep that parent and may form a new thread. The text of already imported messages is not rewritten during a re-run; thread relationships are recalculated.

## Checking discussions after import

Compare the number of eligible comments, status, host article, and parentage. Verify a nested reply, a pending message, and a reply to an excluded parent. The counter displayed by WordPress is not necessarily the same as the number of comments eligible for this conversion.

Sources: [processing](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_commentaires.php), [comment selection](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_plugins.php).
