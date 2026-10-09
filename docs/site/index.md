<div class="hero" markdown>

# Changer de CMS. Garder les relations.

**WordPress → SPIP** convertit les données éditoriales pour retrouver des articles, des pages, des auteurs et des médias reliés entre eux. La migration vise à préserver la structure du site, puis à lui donner un nouveau rendu dans SPIP.

Outil en développement · SPIP 4.2–4.4 · Objectif : WordPress 4–7

</div>

<div class="grid cards" markdown>

- **Préparer ma migration**

    Inventorier le site, installer les dépendances, préparer une destination vierge, importer et contrôler les résultats.

    [Commencer par l’audit](migrer/audit.md)

- **Comprendre les correspondances**

    Voir comment une page parente, plusieurs catégories ou une galerie deviennent des objets et des liens SPIP.

    [Explorer la matrice](correspondances/index.md)

- **Situer ma version WordPress**

    Comprendre le modèle classique, les blocs et les données de l’édition du site, de WordPress 4 à 7.

    [Explorer les structures](wordpress/modele.md)

- **Contribuer à l’outil**

    Comprendre l’ordre des traitements et les points d’extension, puis lire les évolutions prévues.

    [Lire les mécanismes](comprendre/traitements.md)

</div>

## Une conversion de données

Un article appartenant à deux catégories devient un article SPIP lié à une rubrique principale et à une rubrique secondaire. Une page enfant reste liée à sa page parente. Un média conserve un identifiant permettant de réécrire ses références dans les textes.

Ces correspondances ont des conséquences éditoriales : un rôle WordPress et un statut SPIP ne donnent pas nécessairement les mêmes droits ; un bloc enregistré et un bloc calculé ne se convertissent pas de la même manière.

!!! warning "L’outil se construit encore"
    Cette documentation décrit le miroir au commit affiché en bas de chaque page. Les fonctions **implémentées**, **prévues par une spec** et **hors périmètre** sont distinguées. La prise en charge de toute la plage WordPress 4–7 reste un objectif : consulter la [couverture](wordpress/compatibilite.md) avant de préparer un site réel.

## Ce qui reste à reconstruire

Le thème WordPress ne devient pas un squelette SPIP. Les menus, widgets, modèles de thème, types personnalisés et extensions demandent un inventaire et une stratégie distincts. Préserver les relations des données aide à reconstruire le rendu ; cela ne garantit pas une apparence identique.

[Voir l’état du projet](etat-projet.md) · [Installer le migrateur](installer/installation.md) · [Lire les sources](comprendre/sources.md)
