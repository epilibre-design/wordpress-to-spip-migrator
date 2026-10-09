# Développer et tester

Les dépendances de développement sont définies dans Composer. Le plugin demande PHP **≥ 8.4** pour `Dom\HTMLDocument` ; le verrou documenté contient PHPUnit 13.4.1, qui impose **PHP ≥ 8.4.1** pour les tests.

## Depuis la racine du checkout

```bash
composer install --no-interaction --prefer-dist
composer tests-unit
composer install-spip-test
composer tests-integration
```

Utiliser `install`, pas `update`, pour respecter `composer.lock`. Les dépendances et sorties sont dans des chemins ignorés : `vendor/` et `.phpunit.cache/`.

| Commande | Ce qu’elle vérifie |
|---|---|
| `tests-unit` | Conversion HTML5, blocs et utilitaires, sans installation SPIP |
| `install-spip-test` | Prépare SPIP 4.4 SQLite, ses plugins et le patch SPIP-Cli ; vérifie les plugins et tables requis |
| `tests-integration` | Relations, étiquettes, statuts des documents, blocs, liens, hiérarchie, préfixes, rendu HTML et squelettes, dans ce SPIP avec des fixtures WordPress SQLite |
| `tests-import` | Imports complets de jeux WordPress externes configurés, comparés à une référence versionnée |

Une fixture SQLite des colonnes lues par le moteur n’est pas une installation complète de chaque version WordPress. Elle ne prouve pas à elle seule la couverture historique.

`HtmlTest` vérifie la conversion HTML et l’échappement des raccourcis présents comme texte ; `HtmlSpipTest` vérifie leur rendu avec `propre()` de SPIP. `MotsTest` contrôle les étiquettes, leurs liens et les erreurs de cohérence ; `DocumentsArticleTest` vérifie aussi le recalcul du statut des documents après association.

Les suites des extensions Yoast et ACF sont dans leurs [dépôts respectifs](../comprendre/extensions.md). La suite de wp2spip seule ne valide pas ces extensions.

## Imports complets

```bash
cp tests/integration/environnement.exemple.sh tests/integration/environnement.sh
# Renseigner les chemins et bases de test dans ce fichier ignoré.
composer tests-import
```

Le README prévoit deux jeux WordPress 6.9 et 7.1 avec le contenu Theme Unit Test. Ils doivent être installés séparément ; `tests-import` ne les crée pas. Les variables `WP6`, `WP7`, `SPIP_WP6`, `SPIP_WP7` et les accès MySQL doivent désigner des environnements de test.

!!! warning "Bases jetables"
    Les scripts de préparation et de remise à zéro peuvent supprimer les destinations et leurs tables. Utiliser des copies, des chemins dédiés et la base jetable `BASE_PREP_MYSQL`, jamais une base de production.

L’export normalise certaines valeurs, dont l’adresse du site et la date d’installation WordPress. Une modification de référence se relit comme une modification du comportement, pas comme une formalité pour faire passer les tests.

## Ce que le résultat permet d’affirmer

Rapporter la commande, la révision, les versions exactes et les cas exécutés. Distinguer réussites, échecs, cas ignorés et suites non exécutées. Un code de sortie nul sans cas exécuté ne valide pas le moteur.

Sources : [configuration PHPUnit](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/phpunit.xml), [scripts Composer](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json), [fixtures](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/data/wordpress/schema.sql), [script d’import complet](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/valider.sh).
