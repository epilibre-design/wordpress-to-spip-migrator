# 1. Auditer et sauvegarder

La migration part d’un **WordPress figé** et d’un **SPIP vierge et hors ligne**. Le moteur n’assure pas une synchronisation continue avec un site WordPress qui change pendant l’import.

## Inventorier avant d’installer

| Inventaire | Questions à résoudre |
|---|---|
| Version et base | Version WordPress exacte ? Préfixe ? Installation simple ou multisite ? Tables personnalisées ? |
| Contenus | Combien d’articles, pages, brouillons, contenus privés et types personnalisés ? |
| Relations | Catégories multiples, sous-pages, étiquettes, auteurs, commentaires imbriqués ? |
| Médias | Fichiers présents ? Liens hors médiathèque ? Médias distants ou stockage externe ? |
| Édition | Éditeur classique, blocs, compositions synchronisées, blocs de plugins, shortcodes spécifiques ? |
| Présentation | Menus, widgets, modèles, styles, navigation et thème à reconstruire ? |
| Extensions | ACF, SEO, boutique, formulaires ou autres données non natives ? |

Une base multisite contient des données globales et des tables par site. La sélection d’un préfixe n’équivaut pas à la migration complète d’un réseau multisite ; préparer et qualifier chaque source retenue.

## Conserver un état de référence

Sauvegarder la base et les fichiers, y compris uploads, thèmes et configuration. Vérifier qu’une restauration est possible. Conserver des nombres par type et statut, quelques URL représentatives, des captures de pages complexes et des exemples de relations.

Geler les écritures pendant l’import, ou travailler sur une copie cohérente de la base et des fichiers. Les compteurs WordPress, notamment ceux des taxonomies, ne remplacent pas l’inventaire des relations réelles.

## Décider des adaptations

Consulter la [matrice des correspondances](../correspondances/index.md). Pour chaque fonction absente ou partielle, choisir une extension, une reprise manuelle ou une conservation indépendante de la source. Les étiquettes sont **prévues par une spec**, mais ne sont pas importées dans la révision documentée.

## Préparer les critères d’acceptation

Prévoir un contrôle des identifiants, dates, statuts, relations, textes, médias et accès. Choisir notamment une page enfant, un article multicatégorie, une galerie et un contenu privé. Définir les redirections et le rendu SPIP avant la bascule.

**Étape suivante : [préparer SPIP](preparer.md).**
