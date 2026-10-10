# Media, galleries, and blocks

Importing a file and converting its reference within a text are two distinct operations.

## Media → documents

Media of type `attachment` and status `inherit`, even without a parent, become SPIP documents. The identifier is preserved, and the title, description, and date are carried over. Files are searched for locally according to the `guid` path, then downloaded if necessary; they are copied to the destination.

A specific upload configuration, a site path in a subfolder, or external storage must be tested: local search is not a universal inventory of WordPress files. A file rejected by SPIP must not leave an empty document; rejections are reported. The code may also fail to import an unreadable file: compare inventories, not just the summary counters.

The index used to recognize URLs in texts takes into account the attached file, derived sizes, originals of resized/retouched images, and the `guid`. Recognizing a thumbnail serves to find the SPIP document; this does not promise to copy every size variant as an independent document.

## Content conversion

| Saved element | Desired result |
|---|---|
| Image or image block | `<imgN>` with alignment; caption in the description |
| Audio, video, file | `<docN>` |
| Link to media | `[text->documentN]` |
| Classic gallery `[gallery]` or gallery block | Album and `<albumN>` |
| Columns, group, buttons… | Structure kept with `wp-block-…` classes to be styled |
| Embedded content | URL only; player to be provided, notably with oEmbed |
| Identified dynamic block | Useful saved content kept; removed if it contains neither text nor usable media |
| Unknown block | Saved content kept, summary to be examined |

Example: a gallery containing media `72` and `73` becomes an album linked to its documents and the article. The album has its own SPIP identifier; it is not a new copy of each image.

## Saved structure and calculated rendering

A block may store its HTML between comments; another stores only attributes or a reference. A list of articles calculated by WordPress is not a fixed list in `post_content`. A synchronized composition referenced by `core/block` requires additional resolution: the documented engine does not import `wp_block` objects and does not guarantee their expansion.

Cover blocks may leave background URLs pointing to the source despite the document import. The summaries distinguish between media absent from the media library and known media whose location does not have a suitable SPIP shortcut.

## How to verify imported media

After associating attachments with an article, `importer_articles` calls `document_instituer()` to recalculate their status, including for media absent from the text. A document attached to a published article can thus be published without manual modification of that article. Documents for content in preparation remain to be checked according to SPIP rules and access processing.

Verify files, captions, galleries, alignments, links, audio/video playback, and block backgrounds. Examine unknown and dynamic blocks. Check the status of documents and their public access, as well as attachments absent from the text. Layout depends on SPIP templates and CSS.

Sources: [documents](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_documents.php), [blocks](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php), [index and associations](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php).
