# Couverture et compatibilité

**Objectif : WordPress 4 à 7.** La spec d’ensemble fixe une cible plus précise, **4.9 à 7.x**, et rapporte des essais en 4.9, 6.9 et 7.1. Ne pas transformer ces indications en garantie pour chaque version, thème et extension.

## Matrice des formes de données

| Famille/version | Formes pertinentes | Mécanismes disponibles | Validation restante |
|---|---|---|---|
| 4.0–4.8 | HTML/shortcodes, termes hérités ; `termmeta` depuis 4.4 | Traitements génériques pour articles, catégories, médias… | En dehors de la cible minimale 4.9 de la spec ; qualifier ces versions et les termes partagés |
| 4.9 | Modèle classique et données d’extensions | Traitements génériques et conversions classiques | Essai rapporté par la spec, pas une suite automatisée couvrant tous les cas 4.x |
| 5.x | Blocs depuis 5.0, commentaires en 5.5, objets de site en 5.9 | Analyse de blocs, sélection des deux formes de commentaires | Variantes de blocs et références ; thème/navigation non importés |
| 6.x | Compositions, métadonnées et édition du site | Convertisseur de blocs et tests d’intégration | Références synchronisées, nouveaux attributs et diversité de thèmes |
| 6.9 | Jeu Theme Unit Test, avec référence versionnée | Script d’import complet comparatif ; validations rapportées dans les plans | Rejouer sur une installation identifiée ; ne pas extrapoler à tous les sites |
| 7.x / 7.1 | Continuité du socle, blocs et types de site | Lecture de version, traitements génériques ; import complet 7.1 avec référence versionnée | Qualifier fonctions et références exactes ; couverture complète non démontrée |

## Trois preuves différentes

- **Code présent** : confirme un mécanisme, pas son résultat sur toutes les sources.
- **Test présent** : fournit des cas exécutables ; préciser leur jeu de données.
- **Test exécuté** : permet un constat sur les versions, paramètres et résultats de cette exécution.

Les tests SQLite utilisent un schéma et des fixtures des tables consultées ; ils ne reproduisent pas toutes les installations historiques. Les imports complets 6.9/7.1 demandent les WordPress externes et leur configuration.

## Quelle que soit la version

Les étiquettes sont désormais importées par le cœur, et Yoast/ACF disposent d’extensions publiées avec un périmètre précis. Les types personnalisés, menus, révisions et autres données d’extensions restent soumis à [l’état du projet](../etat-projet.md). Une version prise en compte par l’architecture n’ajoute pas les traitements absents.

La recherche de variantes par version permet d’étendre le moteur. Une nouvelle adaptation doit expliquer les différences de stockage et fournir des cas de conversion et de relations vérifiables.

## Choisir un scénario de qualification

Sélectionner la version exacte et un jeu représentatif des fonctions réellement utilisées. Préparer une source figée, une destination vierge et un inventaire attendu. Exécuter les tests appropriés et la recette, puis consigner commit, version PHP/SPIP, plugins et anomalies restantes.

Sources : [cible de la spec](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md), [tests et commandes](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/readme.md), [architecture de la commande](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/spip-cli/WordpressImporter.php).
