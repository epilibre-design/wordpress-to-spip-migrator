# 4. Validate and switch

A completed import marks the beginning of the acceptance testing phase. Check data conversion and site rendering separately.

## Structural checks

| Check | Expected result |
|---|---|
| Articles/pages | Expected objects, IDs, dates, author, and statuses are consistent |
| Categories | Hierarchy preserved and all main/sub-sections are correct |
| Tags | Terms in the "Tags" group, IDs and links are correct, including terms without content and unpublished content |
| Child pages | `sous_page` a2a links, parentage, and order verified |
| Media/galleries | Files readable, links to content and albums correct; rejected items identified |
| Comments | Publication/moderation and threads compliant; exclusions included |
| Access | Private/protected content inaccessible to unauthorized visitors |
| URLs | Internal links resolved and external redirects prepared |

With extensions active, also check the main sections and SEO metadata from Yoast, as well as extra fields and values from ACF. Examine data that was excluded or flagged in their respective reports.

## Verifier and repository export

The repository provides [verifier_identifiants.php](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/verifier_identifiants.php) and [exporter_import.php](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/exporter_import.php). They are used within the configured test SPIP context; read their assumptions before applying them to another installation.

From a correctly configured test SPIP:

```bash
spip php:eval "include '/path/to/the-checkout/tests/integration/verifier_identifiants.php';"
```

Check for the expected **`OK`** output and any potential errors: a 0 exit code from `php:eval` alone may mask an error page. The comparative export helps identify variations, but does not cover all theme and extension adaptations.

## Editorial and visual acceptance

Open the complex examples defined during the audit: cover block, gallery, links to media, nested page, draft, scheduled article, and private content. Check accents, tables, captions, alignments, navigation, and behavior on small screens.

Retained block classes require adapted styles in the template. WordPress menus and templates require reconstruction. The status of attached documents is now recalculated after they are linked to articles; also check media absent from the text and those from unpublished content. Verify that SPIP shortcut characters present as text in WordPress remain displayed as text after [conversion](../comprendre/conversion.md).

## Preparing the switch

Reconstitute access, finalize templates, prepare HTTP redirects, and verify the final domain. Back up the validated destination and plan for a rollback to the source. Explicitly decide on non-converted data before opening the site.

After the switch, check representative old URLs, login forms, and media from the public address. The migrator does not configure DNS or redirect servers.
