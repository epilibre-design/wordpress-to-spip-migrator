# Extensions et feuille de route

L’outil est en cours de création. Les specs expliquent les conversions envisagées ; la présence de leur fichier dans le dépôt ne constitue pas une implémentation.

## Étiquettes

**Non implémenté dans la révision documentée ; prévu par la spec.** Cible : toutes les étiquettes `post_tag` deviennent des mots-clés SPIP dans un groupe dédié, avec conservation de leurs identifiants et de leurs liens, y compris les termes sans contenu.

Le traitement prévu `importer_mots` se place après les articles et la hiérarchie des pages, car les contenus auxquels lier les mots doivent déjà exister. [Lire la spec](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-etiquettes-design.md).

## Yoast SEO

**Extension prévue, absente de ce checkout.** Yoast n’est pas une fonction native WordPress. La spec prévoit un plugin séparé, la catégorie principale Yoast et des métadonnées converties vers le plugin SEO : titres, descriptions et directives d’indexation.

Les variables de titres demandent une substitution explicite. Scores, analyses, données sociales, URL canoniques et réglages globaux ne sont pas tous repris par la cible décrite. [Lire la spec Yoast](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-yoast-design.md).

## Advanced Custom Fields

**Extension prévue, absente de ce checkout.** ACF stocke des définitions de champs et des valeurs, pas seulement des textes. La spec envisage Champs Extras/Champs Extras Interface, avec correspondances de types et conversion des références aux médias.

Définitions en PHP/JSON, anciens formats, répéteurs ou champs complexes demandent une analyse de périmètre ; ne pas annoncer une migration générale de tout ACF. [Lire la spec ACF](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-acf-design.md).

## Correctif du statut des documents

**Prévu par une spec.** Le diagnostic décrit des documents qui peuvent rester proposés après leur liaison à un article publié. La cible est un recalcul après association. Dans la révision documentée, vérifier le statut des documents durant la recette au lieu de supposer ce correctif appliqué.

[Lire la spec du correctif](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-statut-documents-design.md).

## Signalements aux plugins tiers

**Évolution prévue par une spec.** L’objectif est de rendre exploitables par des extensions les informations de conversion et les données non prises en charge, plutôt que de les limiter à des messages de terminal.

[Lire la spec des signalements](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-signalements-design.md).

## Points d’extension déjà disponibles

| Mécanisme | Usage |
|---|---|
| `wp2spip_traitements` | Insérer un traitement dans la liste ordonnée |
| `w2spip_traitements` | Ancien nom toujours appelé, pour compatibilité |
| Variantes de fonctions par version | Adapter un traitement à une forme de données WordPress |
| `wp2spip_bloc` | Ajouter/remplacer la conversion d’un bloc |
| `wp2spip_plugins_requis` | Compléter les dépendances détectées avant import |

Un développement doit fournir son code, ses dépendances et ses validations. Le site ne change l’état public de la fonction qu’après synchronisation du miroir et revue du nouveau commit.

[État du projet](../etat-projet.md) · [Architecture du moteur](traitements.md).
