# wp2spip — sous-projet 11 : convertisseur HTML → SPIP sans sale — plan de réalisation

**Goal:** Remplacer sale par un convertisseur de wp2spip fondé sur l'arbre HTML5 de PHP 8.4, sans perte de texte, et qui garde tel quel ce que WordPress affiche tel quel.

**Architecture:** `inc/wp2spip_html.php` : `wp2spip_html_spip($html, $options)` analyse le HTML par `Dom\HTMLDocument` et parcourt l'arbre ; une « sortie » accumule les paragraphes (texte en cours, sauts de ligne en attente, blocs déjà formés). Chaque appel à `sale()` de wp2spip (10) et de `wp2spip_acf` (1) est remplacé ; les traitements de wp2spip avant (blocs) et après (images, légendes, lecteurs, liens internes) sont inchangés. Réalisé directement en une session (prototype, tests et comparaisons sur corpus au fil de l'eau) : le code est la référence, ce plan n'en recopie pas.

**Tech Stack:** PHP 8.4 (`Dom\HTMLDocument`), SPIP 4.4 (`propre()` pour les tests de rendu), PHPUnit 13.

Spec : `docs/superpowers/specs/2026-10-09-wp2spip-convertisseur-html-design.md`.

## Global Constraints

- Messages de commit sans trailer ; ne jamais nommer le site WordPress réel dans les fichiers versionnés ni les commits.
- Un texte affiché tel quel par WordPress est affiché tel quel par SPIP (demande du mainteneur, 2026-10-09).
- Aucune perte de texte : toute différence d'export avec l'import par sale est revue et classée.

---

### Task 1 : convertisseur et tests

- [x] `inc/wp2spip_html.php` ; `tests/unit/HtmlTest.php` (62 cas : chaque correspondance de la spec, imbrications, liste non fermée, wpautop, entités et raccourcis SPIP en texte, marqueurs de blocs, HTML mal formé, textes qui faisaient échouer sale) ; `tests/integration/HtmlSpipTest.php` (rendu par `propre()` : page qui documente les raccourcis SPIP affichée avec le même texte ; structures rendues).
- [x] Sabotage : sans l'échappement des accolades, 1 test unitaire et 6 tests de rendu en échec.
- [x] Revue du site réel : deux défauts corrigés, chacun couvert par un test (sabotage : 1 échec chacun) : liste non fermée dont la suite du texte était perdue ; souligné d’un saut de ligne dans une marque (`{{a<br>b}}`) écrit en entité.

### Task 2 : remplacement de sale

- [x] Appels remplacés (`importer_articles` : `autop` selon la présence de blocs ; légendes de blocs sans `autop`) ; légende `[caption]` plus convertie deux fois ; `wp2spip_decoder_entites()` retiré de la description des catégories (il défaisait l'échappement).
- [x] Sans sale : `paquet.xml` (nécessite PHP 8.4), `composer.json`, `outils/preparer_spip.sh`, `scripts/install-spip-test.sh`, `tests/preparation/tester_preparer_spip.sh`, `EnvironnementTest`, `BlocsTest` ; extensions `wp2spip_yoast` et `wp2spip_acf` (PHP 8.4, sans sale ; `wp2spip_acf` utilise le convertisseur).

### Task 3 : validation

- [x] Comparaison des exports avec ceux de l'import par sale, WordPress 6.9, 7.1 et site réel : différences classées, aucun mot perdu (comparaison mot à mot de chaque objet).
- [x] Référence de `composer tests-import` mise à jour après revue ; tests de wp2spip et des extensions au vert, depuis des clones neufs ; préparation complète à 0 échec.
