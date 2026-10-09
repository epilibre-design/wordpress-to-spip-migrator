# wp2spip — correctif : statut des documents joints aux articles

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6 (défaut constaté pendant le prototype du sous-projet 8).
Statut : réalisé (sans plan séparé : correctif d'un bloc). Validation : `DocumentsArticleTest` en échec sans le correctif ; vérificateur en échec sur un import d'avant le correctif (388 documents), à OK après ; référence : 1 document `prop` → `publie` ; site réel : 388 documents `prop` → `publie`, aucune autre différence.

## 1. Constat

`importer_articles` crée l'article, le renseigne et le publie (`objet_modifier()`, statut compris), **puis** lui joint ses médias (`wp2spip_importer_articles_documents()`, par `objet_associer()`). Le plugin medias de SPIP ne recalcule le statut d'un document qu'à son ajout ou quand un objet auquel il est lié change de statut (`medias_post_edition()`), jamais à la création d'un lien. Un média joint au contenu WordPress mais absent de son texte reste donc `prop`, alors que son article est publié : il n'apparaît pas sur le site public (boucles `DOCUMENTS`, portfolio). Les médias insérés dans le texte (`<imgN>`, `<docN>`) sont liés par SPIP pendant la modification du texte, avant la publication, et sont publiés.

Constaté sur le site réel importé : 382 documents liés à des articles publiés restent `prop` (sur 1 052) ; `document_instituer()` les publie. Les galeries (albums) avaient le même défaut, corrigé au sous-projet 3.

## 2. Décision

Après les liens de documents et d'albums d'un article, `importer_articles` appelle `document_instituer()` pour chaque document qu'il vient de lier, comme pour les images des albums : le statut suit alors celui de l'article (publié, ou publication différée selon sa date).

## 3. Validation

- Test d'intégration : sur le WordPress de test, un article publié (1177) reçoit ses médias (967, 968) par `wp2spip_importer_articles_documents()` ; ils sont `publie` ; pour un article en cours de rédaction, ils restent non publiés.
- Vérificateur (`verifier_identifiants.php`) : aucun document lié à un article publié ne reste `prop` (hors logos).
- `composer tests-import` : la référence ne change que par le statut de documents joints à des articles publiés (`prop` → `publie`) ; mise à jour dans le même commit.
- Site réel sur son SPIP de test : vérificateur à OK ; l'export ne diffère de celui d'avant que par les statuts de documents `prop` → `publie`.

## 4. Hors périmètre

- Documents liés à d'autres objets que les articles (rubriques : publiées dès qu'elles contiennent un document, règle de SPIP).
