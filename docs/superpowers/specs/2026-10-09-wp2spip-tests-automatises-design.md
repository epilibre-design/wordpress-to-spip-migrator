# wp2spip — sous-projet 7 : tests automatisés

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 7.
Statut : réalisé.

## 1. Constat

Les tests de wp2spip sont des scripts :

- `tests/integration/tester_blocs.php`, `tester_hierarchie_pages.php` : lancés dans un SPIP par `spip php:eval`, ils affichent `ok` / `ECHEC` ; le premier s'appuie sur les documents d'un SPIP qui a importé le WordPress 6.9 ;
- `tests/integration/verifier_identifiants.php`, `exporter_import.php` : contrôle et export d'un SPIP importé ;
- `tests/preparation/tester_preparer_spip.sh`, `tests/integration/remise_a_zero.sh` : préparation et remise à zéro.

Les validations des plans s'enchaînent à la main, sur des SPIP et des WordPress MySQL propres à la machine, avec des exports de référence locaux. Rien ne se lance depuis un clone neuf. wp2spip n'a ni `composer.json` ni configuration PHPUnit.

Le skill `spip-testing` décrit l'organisation standard des tests d'un plugin SPIP : PHPUnit, deux niveaux (unitaire sans SPIP, intégration dans un SPIP installé dans `vendor/spip/spip/` par SPIP-Cli), `spip/tests` pour les squelettes, et des scripts composer.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Cadre | **PHPUnit**, organisé comme le prévoit le skill `spip-testing` |
| Tests unitaires | `tests/unit/`, PHP seul, fonctions SPIP imitées par `tests/bootstrap.php` |
| Tests d'intégration | `tests/integration/*Test.php`, dans un SPIP SQLite installé dans `vendor/spip/spip/` |
| Données WordPress des tests d'intégration | une **base WordPress SQLite construite par les tests** à partir de jeux de données versionnés ; aucun WordPress MySQL requis |
| Import complet du contenu de test | script `tests/integration/valider.sh`, comparé à une **référence versionnée** normalisée |
| Lancement | scripts composer `tests-unit`, `install-spip-test`, `tests-integration`, `tests-import` |
| SPIP-Cli | celui de composer, avec le patch `tests/spip-cli.patch` ; le SPIP-Cli du projet n'est pas modifié |

## 3. Mise en place

- `composer.json` : `"type": "spip-plugin"`, `require-dev` : `phpunit/phpunit` (version majeure du skill), `spip/spip-cli` et `spip/tests` (`dev-master`, dépôts `git.spip.net`), dépôt composer `https://get.spip.net/composer` ; scripts composer du skill, plus `tests-import`. `composer.lock` est versionné.
- `phpunit.xml` : suites `unit` (`tests/unit`) et `integration` (`tests/integration`, fichiers `*Test.php`) ; les autres fichiers de `tests/integration/` (scripts, données) ne sont pas lus par PHPUnit.
- `.gitignore` : `vendor/`, `.phpunit.cache/`.
- `outils/preparer_spip.sh` exclut aussi `vendor`, `composer.json`, `composer.lock`, `phpunit.xml` et `scripts` de la copie de wp2spip.

## 4. Tests unitaires

`tests/bootstrap.php` définit `_ECRIRE_INC_VERSION` et imite, avec garde `function_exists()`, les seules fonctions SPIP appelées par les fonctions testées ; les valeurs imitées passent par `$GLOBALS['_test_…']`, remises à zéro dans `setUp()`.

Fonctions couvertes (une classe de test par fichier source, cas par fournisseur de données) :

- `inc/wp2spip.php` : `wp2spip_decoder_entites()` ;
- `wp2spip/importer_articles.php` : `wp2spip_normaliser_slug()`, `wp2spip_url_du_site()`, `wp2spip_chemin_upload()`, `wp2spip_chercher_slug()` (index de contenus fourni par le test), `wp2spip_liens_medias_restants()` ;
- `inc/wp2spip_blocs.php` : `wp2spip_analyser_blocs()` (blocs imbriqués, auto-fermants, HTML hors bloc, attributs JSON), `wp2spip_nettoyer_balises()`, `wp2spip_balise_nettoyee()`, `wp2spip_bloc_legende()`, `wp2spip_bloc_dynamique()`, `wp2spip_conversion_bloc()`.

Une fonction qui se révèle dépendre de la base ou de sale va dans les tests d'intégration plutôt que d'imiter ces dépendances.

## 5. Tests d'intégration

### 5.1 SPIP de test

`scripts/install-spip-test.sh`, d'après le modèle du skill :

1. SPIP 4.4 téléchargé dans `vendor/spip/spip/` par `core:telecharger`, préparé (`core:preparer`, puis `plugins/auto` créé), installé en SQLite (`core:installer`, mot de passe d'administrateur aléatoire) ; chaque étape sautée si déjà faite.
2. Patch `tests/spip-cli.patch` appliqué au SPIP-Cli de `vendor/` s'il ne l'est pas (contrôle par `grep` d'une ligne du correctif) ; le patch reprend notre correctif de `plugins:svp:telecharger` (sélection du plugin, autorisation, remontée des erreurs, liste des plugins à installer remise à zéro à chaque préfixe).
3. Dépôt standard déclaré ; un appel de `plugins:svp:telecharger` par plugin, puis effacement de sa méta `<préfixe>_base_version` (défaut constaté au sous-projet 3) : `sale`, `pages`, `polyhier` (dépendances de wp2spip), `albums`, `a2a`, `accesrestreint` (requis par les tests).
4. wp2spip rendu disponible par un lien `plugins/wp2spip` vers la racine du dépôt, puis activé ; `plugins:maj:bdd`.
5. Contrôles : chaque plugin actif (`plugins:lister`), aucune table ou champ manquant (`wp2spip_tables_manquantes()`) ; sinon arrêt avec un message.

`tests/bootstrap_integration.php` charge ce SPIP comme le prévoit le skill.

### 5.2 Base WordPress de test

Les tests ne se connectent à aucun WordPress MySQL. Une base SQLite `wp2spip_tests`, déclarée comme base externe du SPIP de test, est construite à partir de jeux de données versionnés (`tests/integration/data/wordpress/*.php` : lignes des tables `wp_posts`, `wp_postmeta`, `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships`, `wp_options`), avec quelques fichiers images dans `tests/integration/data/uploads/`. Elle reproduit, à petite échelle, les cas utiles : contenus et pages (dont des pages enfants), médias et leurs fichiers, galeries, blocs de l'éditeur, contenus privés, étiquettes.

Une classe de base `tests/integration/WordpressTestCase.php` construit la base et importe dans le SPIP de test les documents dont les tests ont besoin (identifiant = ID WordPress), et retire à la fin ce que les tests ont créé (albums, légendes, liens), pour que les tests se relancent sur le même état.

### 5.3 Tests

- **Conversions des blocs** : les 20 cas de `tester_blocs.php`, sur la base de test au lieu du WordPress 6.9 ; le script est retiré.
- **Hiérarchie des pages** : le cas « sans page enfant » de `tester_hierarchie_pages.php`, et la création des liens `sous_page` dans l'ordre WordPress ; le script est retiré.
- **Plugins requis** : `wp2spip_plugins_requis()` sur la base de test (albums, accesrestreint, a2a, forum selon les données), `wp2spip_tables_manquantes()`.
- **Liens et documents** : `wp2spip_chercher_lien()`, `wp2spip_chercher_document()` sur la base de test.
- **Squelettes** (`Spip\Test\SquelettesTestCase`) : les deux boucles d'exemple du readme (sous-pages d'une page, page parente) sur des liens `sous_page` créés par le test.

Un test d'intégration vérifie aussi que wp2spip et les plugins requis sont actifs.

Les plans des sous-projets suivants utilisent `vendor/bin/phpunit --filter …` à la place des scripts retirés.

## 6. Import complet du contenu de test

`tests/integration/valider.sh <dossier WordPress>` : prépare un SPIP depuis un dossier vide par `outils/preparer_spip.sh --importer` (copie de wp2spip), lance le vérificateur, puis compare l'export **normalisé** à `tests/integration/references/theme-unit-test.tsv` :

- l'adresse du site WordPress (`siteurl`) est remplacée par `@URL_SITE@` ;
- les dates des contenus créés par l'installation de WordPress (article 1, pages 2 et 3, commentaire 1) sont remplacées par `@INSTALLATION@`.

Ainsi normalisés, les exports des WordPress 6.9 et 7.1 sont identiques : une seule référence sert aux deux. `--mettre-a-jour` réécrit la référence, dont l'évolution se lit dans le diff du commit qui change l'import. Prérequis : un WordPress installé en français avec le contenu *Theme Unit Test* (spec d'ensemble, § 5), ses accès lisibles dans son `wp-config.php`. Le script composer `tests-import` lance `valider.sh` sur `$WP6` et `$WP7` (variables de `tests/integration/environnement.sh`).

Le site réel reste comparé à des références locales ; ses exports ne sont jamais versionnés.

## 7. Documentation

Section « Tests » du readme : installation (`composer install`, `composer install-spip-test`), lancement des trois suites, prérequis de `tests-import`, mise à jour de la référence.

## 8. Validation

- Depuis un clone neuf : `composer install`, `composer install-spip-test`, `composer tests-unit`, `composer tests-integration` : tout passe.
- Chaque suite échoue sur un sabotage volontaire (une conversion de bloc modifiée ; un rang de sous-page faussé), avec un message qui désigne le cas.
- Les tests d'intégration se relancent avec le même résultat (état du SPIP de test rétabli).
- `composer tests-import` : WordPress 6.9 et 7.1 identiques à la référence ; un écart introduit exprès est signalé.
- `outils/preparer_spip.sh` : la copie de wp2spip ne contient ni `vendor`, ni `tests`, ni fichiers composer ; tests de la préparation à 0 échec.

## 9. Hors périmètre

- Intégration continue sur git.spip.net.
- Couverture de code chiffrée.
- Tests du site réel versionnés.
- Tests des traitements d'import un par un sur la base de test (l'import complet est couvert par `tests-import`).
