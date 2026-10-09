# Couverture et compatibilité

**Objectif : WordPress 4 à 7.** La spec d’ensemble fixe une cible plus précise, **4.9 à 7.x**, et rapporte des essais en 4.9, 6.9 et 7.1. Ne pas transformer ces indications en garantie pour chaque version, thème et extension.

## Matrice des formes de données

| Famille/version | Formes pertinentes | Mécanismes disponibles | Validation restante |
|---|---|---|---|
| 4.0–4.8 | HTML/shortcodes, termes hérités ; `termmeta` depuis 4.4 | Traitements génériques pour articles, catégories, médias… | En dehors de la cible minimale 4.9 de la spec ; qualifier ces versions et les termes partagés |
| 4.9 | Modèle classique et données d’extensions | Traitements génériques et conversions classiques | Essai rapporté par la spec, pas une suite automatisée couvrant tous les cas 4.x |
| 5.x | Blocs depuis 5.0, commentaires en 5.5, objets de site en 5.9 | Analyse de blocs, sélection des deux formes de commentaires | Variantes de blocs et références ; thème/navigation non importés |
| 6.x | Compositions, métadonnées et édition du site | Convertisseur de blocs et tests d’intégration | Références synchronisées, nouveaux attributs et diversité de thèmes |
| 6.9 | Jeu Theme Unit Test prévu | Script d’import complet comparatif | Exécuter sur une installation identifiée ; ne pas extrapoler à tous les sites |
| 7.x / 7.1 | Continuité du socle, blocs et types de site | Lecture de version, traitements génériques ; jeu 7.1 prévu | Qualifier fonctions et références exactes ; couverture complète non démontrée |

## Trois preuves différentes

- **Code présent** : confirme un mécanisme, pas son résultat sur toutes les sources.
- **Test présent** : fournit des cas exécutables ; préciser leur jeu de données.
- **Test exécuté** : permet un constat sur les versions, paramètres et résultats de cette exécution.

Les tests SQLite utilisent un schéma et des fixtures des tables consultées ; ils ne reproduisent pas toutes les installations historiques. Les imports complets 6.9/7.1 demandent les WordPress externes et leur configuration.

## Quelle que soit la version

Étiquettes, types personnalisés, menus, révisions et données d’extensions restent soumis à [l’état du projet](../etat-projet.md). Une version prise en compte par l’architecture n’ajoute pas les traitements absents.

La recherche de variantes par version permet d’étendre le moteur. Une nouvelle adaptation doit expliquer les différences de stockage et fournir des cas de conversion et de relations vérifiables.

## Choisir un scénario de qualification

Sélectionner la version exacte et un jeu représentatif des fonctions réellement utilisées. Préparer une source figée, une destination vierge et un inventaire attendu. Exécuter les tests appropriés et la recette, puis consigner commit, version PHP/SPIP, plugins et anomalies restantes.

Sources : [cible de la spec](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md), [tests et commandes](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/readme.md), [architecture de la commande](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/spip-cli/WordpressImporter.php).
