# wp2spip — sous-projet 8 : extension `wp2spip_yoast` — plan de réalisation

**Goal:** Importer la catégorie principale, les titres SEO, les méta-descriptions et l'indexation de Yoast SEO, par une extension de wp2spip dans un dépôt séparé.

**Architecture:** Plugin SPIP `wp2spip_yoast` (dépôt git local `wp2spip_yoast`, branche `main`), branché sur les pipelines `wp2spip_traitements` et `wp2spip_plugins_requis` de wp2spip. Deux traitements : `importer_yoast_categories` (après `importer_articles`) et `importer_yoast_seo` (en dernier), qui écrit dans la table `spip_seo` du plugin SEO. Le code a été mis au point sur un prototype validé sur le site réel ; il est versionné tel quel dans le dépôt de l'extension, qui est la référence du code (ce plan ne le recopie pas).

**Tech Stack:** PHP (SPIP 4.4, plugin SEO 3.1), PHPUnit 13 (classes de test de wp2spip installées en source par Composer), bash.

Spec : `docs/superpowers/specs/2026-10-09-wp2spip-yoast-design.md`. Prérequis : sous-projets 5, 6 et 7 réalisés et poussés (le dépôt de l'extension installe wp2spip depuis la branche `compat-spip-4.4`).

## Global Constraints

- Messages de commit sans trailer ; ne jamais nommer le site WordPress réel dans les fichiers versionnés ni les commits.
- Tables WordPress nommées par `wp2spip_table()`.
- Dépôt de l'extension local seulement : aucun dépôt distant créé ni poussé.

---

### Task 1 : dépôt de l'extension

**Files (dépôt `wp2spip_yoast`) :** `paquet.xml`, `wp2spip_yoast_pipelines.php`, `inc/wp2spip_yoast.php`, `wp2spip/importer_yoast_categories.php`, `wp2spip/importer_yoast_seo.php`, `readme.md`, `composer.json`, `phpunit.xml`, `scripts/install-spip-test.sh`, `tests/spip-cli.patch`, `tests/bootstrap.php`, `tests/bootstrap_integration.php`, `tests/unit/YoastTest.php`, `tests/integration/YoastTest.php`, `tests/integration/data/yoast.php`, `.gitignore`, `.gitattributes`.

- [x] **Step 1 :** dépôt créé depuis le prototype, commit initial.
- [x] **Step 2 :** depuis un clone neuf : `composer install`, `composer tests-unit` (16 tests), `composer install-spip-test`, `composer tests-integration` (7 tests) deux fois de suite → OK.

### Task 2 : validation

- [x] **Step 1 — site réel**, SPIP de test remis à zéro, extension liée dans `plugins/` et activée : import à code 0, SEO installé par l'import ; bilans de `importer_yoast_categories` et `importer_yoast_seo` ; relance des deux traitements sans changement ; export comparé à celui du sous-projet 5 (seules les lignes `article` et `rubrique_secondaire` des articles déplacés diffèrent) ; vérificateur de wp2spip à OK.
- [x] **Step 2 — WordPress 6.9 et 7.1**, sur leurs SPIP de test remis à zéro, extension liée et activée : import à code 0, SEO non requis, les deux traitements lancés à leur place et sans effet (aucune donnée Yoast : aucun message), export identique à celui du même import sans l'extension (cas aussi couvert par `YoastTest::testSansYoast`).
- [x] **Step 3 — documentation** : statut de la spec, § 6 de la spec d'ensemble, Task 18 du plan principal.
