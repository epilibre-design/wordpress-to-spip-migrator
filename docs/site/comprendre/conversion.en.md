# Content conversion

WordPress text often combines HTML, shortcodes, block serialization, and object references. Treating them as a simple string to copy loses the relationships with media and content.

## Before and after

Source example, saved in `post_content`:

```html
<!-- wp:image {"id":72,"align":"left"} -->
<figure class="wp-block-image alignleft">
  <img src="https://exemple.test/wp-content/uploads/photo.jpg" class="wp-image-72">
  <figcaption>A caption</figcaption>
</figure>
<!-- /wp:image -->
```

With document `72` imported, the target is a model of type `<img72|left>` and a caption saved as a description. The relationship to the file is explicit; its display depends on the SPIP model and template.

## Conversion steps

1. Parse `<!-- wp:… -->` comments and JSON attributes into a block tree.
2. Convert recognized blocks and galleries, preserving their order.
3. Temporarily protect already converted fragments with `wp2spipblocN` markers.
4. Convert the remaining HTML with `wp2spip_html_spip()`, based on the HTML5 tree built by `Dom\HTMLDocument` from PHP 8.4 onwards, and Masterminds HTML5-PHP previously. On malformed HTML, Masterminds may not reopen an interrupted bold or italic tag: the text is kept, but its formatting should be verified.
5. Restore protected fragments and rewrite recognized references to content and media.

The markers prevent the HTML converter from processing a shortcut already produced a second time. Selected structures retain `wp-block-…` classes to be styled in SPIP. Sale is no longer involved in this chain.

## HTML5 → SPIP shortcuts

The converter is included in wp2spip (`inc/wp2spip_html.php`). It traverses the HTML5 tree nodes, notably to handle nested structures and malformed HTML. Articles, comments, category and tag descriptions, captions, and text metas use this same converter.

| Saved HTML | Result |
|---|---|
| Paragraphs and `<br>` | Separate paragraphs and SPIP line breaks `_ ` |
| `<strong>`, `<b>` ; `<em>`, `<i>` | Bold `{{…}}` ; italic `{…}` |
| `<h1>` to `<h3>` ; `<h4>` to `<h6>` | Headings `{{{…}}}` ; bold paragraphs |
| Links and anchors | SPIP shortcuts, then rewriting of recognized internal references |
| Simple or nested lists | SPIP bulleted or numbered lists |
| Simple tables | SPIP tables; nested tables, multi-line cells, or `rowspan` kept in HTML |
| Quotes | `<quote>…</quote>` |
| Inline code ; `<pre><code>` ; other `<pre>` | `<code>…</code>` ; `<cadre>…</cadre>` ; `<poesie>…</poesie>` |
| Images, audio, video, and embedded content | Tags kept for subsequent media conversions |
| Other structures and tags | HTML kept with converted content; scripts, styles, and comments removed |

### Line breaks

The `autop` mode follows the two WordPress representations. For the classic editor, comments, and descriptions, an empty line becomes a paragraph and a single return becomes a SPIP line break. For an article containing blocks, HTML whitespace is reduced as in a browser; its serialization line breaks do not become visible breaks. Block captions are also converted without `autop`.

### Text showing SPIP shortcuts

Characters displayed as text by WordPress remain text in SPIP. The converter escapes shortcut characters (`{`, `}`, `[`, `]`, `|`, `~`, as well as `-` or `_` at the start of a line) and HTML characters using entities. For example, the literal text `{{gras}}` does not become bold, whereas `<strong>gras</strong>` becomes the shortcut `{{gras}}`.

Code and preformatted text keep their raw content, according to the produced SPIP format. Recognized WordPress shortcodes (`[caption]`, `[gallery]`, `[audio]`, `[video]`, `[embed]`, `[playlist]`) remain available for conversion by dedicated steps.

`HtmlTest` unit tests check the conversion; `HtmlSpipTest` checks the visible text and structures after rendering by SPIP's `propre()`. The replacement of Sale specifically corrects text loss on long sequences of spaces; import references were updated after comparing differences, according to the [implementation plan](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/plans/2026-10-09-wp2spip-convertisseur-html.md).

## Three cases to distinguish

| Saved form | Conversion possibility |
|---|---|
| Standalone HTML | Can be converted, with result verification |
| Serialized block with attributes and HTML | Can receive specialized processing |
| Calculated block or reference to another object | Requires resolution or reconstruction; no full rendering is guaranteed |

The engine does not execute WordPress to calculate all its blocks. Identified dynamic blocks retain their saved, usable text or media and are removed when they have none; the saved HTML of unknown blocks is kept and flagged. An unknown block without saved content may therefore provide no text to keep.

## Completing a conversion

The `wp2spip_bloc` pipeline receives the block and the produced output, or `null` if no processing recognized it. An extension can add a conversion adapted to a specific type. Document its source structure and test media, attributes, content, and relationships.

This extension capability does not implement WordPress plugin functions by itself. Shortcodes specific to a theme or extension require explicit processing.

Sources: [HTML5 converter](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_html.php), [block analysis](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php), [integration into articles](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [unit tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/unit/HtmlTest.php), [rendering tests](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/HtmlSpipTest.php).
