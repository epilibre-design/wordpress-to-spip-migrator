# État du projet

**Révision documentée : `dc1963eb54b317e7e9e7b445bfee81199a7804bb`**, branche de référence `main` du dépôt `epilibre-design/wordpress-to-spip-migrator`. Revue éditoriale : 9 octobre 2026. Le paquet déclare la version **3.0.0**, en état **test**. Les sous-projets de la feuille de route sont marqués réalisés dans la spec d’ensemble ; cela ne garantit pas l’équivalence de tous les sites WordPress.

## Lire les états

| État | Signification |
|---|---|
| Implémenté | Un traitement ou un mécanisme est présent dans cette révision ; ses limites restent applicables |
| Extension publiée | La conversion est fournie par un plugin séparé, à installer et activer avec wp2spip |
| Signalement préparé | Un diagnostic et une proposition de correctif sont documentés ; aucun envoi aux mainteneurs tiers n’est établi |
| Hors périmètre / à adapter | Aucune conversion automatique correspondante n’est identifiée dans le moteur |

**Validation et implémentation sont deux informations distinctes.** Un test présent dans le dépôt indique ce qui peut être contrôlé. Un essai rapporté par une spec est un résultat contextualisé, pas une validation de toutes les versions WordPress. Ce site n’est pas un rapport d’exécution des tests du migrateur.

## Fonctions disponibles

| Fonction | État à la révision documentée | Référence |
|---|---|---|
| Options du site, utilisateurs, articles et pages | Implémenté | [Traitements](comprendre/traitements.md) |
| Catégories multiples et parenté des pages | Implémenté | [Taxonomies](correspondances/taxonomies.md), [pages](correspondances/articles-pages.md) |
| Documents, galeries et conversion des blocs | Implémenté avec limites | [Médias et blocs](correspondances/medias-blocs.md) |
| Contenus privés/protégés et commentaires | Implémenté avec transformation des accès | [Accès](correspondances/auteurs-acces.md), [commentaires](correspondances/commentaires.md) |
| Préfixe de tables personnalisé | Implémenté | [Préparation](migrer/preparer.md) |
| Étiquettes → mots-clés | Implémenté : `importer_mots`, groupe « Étiquettes », identifiants et liens conservés | [Taxonomies](correspondances/taxonomies.md) |
| HTML → raccourcis SPIP | Implémenté dans wp2spip, avec l’arbre HTML5 de PHP 8.4 ; Sale retiré des dépendances | [Conversion](comprendre/conversion.md) |
| Yoast → SEO et catégorie principale | Extension publiée, séparée du moteur | [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast) |
| ACF → Champs Extras | Extension publiée, pour les types et groupes pris en charge | [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf) |
| Ajustement du statut des documents après liaison | Implémenté : recalcul par `document_instituer()` | [Médias et blocs](correspondances/medias-blocs.md) |
| Signalements à Sale et Polyhiérarchie configurable | Brouillons et reproductions préparés, non envoyés ; aucune API de signalement ajoutée au moteur | [Signalements](comprendre/extensions.md#signalements-aux-plugins-tiers) |
| Menus, thème, révisions, types personnalisés | Hors périmètre du moteur documenté | [Matrice générale](correspondances/index.md) |

## Mise à jour à chaque synchronisation

Le mainteneur du site doit examiner le nouveau commit du dépôt, revérifier **chaque ligne** de cette matrice contre le code et les tests, puis actualiser les pages métier, les permaliens et la date de revue. Le SHA affiché et la matrice sont mis à jour ensemble. Les extensions ont leur propre dépôt et leur propre historique : leurs évolutions doivent aussi être examinées.

Tant que la revue n’est pas achevée, le site conserve son ancien SHA : il décrit cette révision, même si le dépôt a avancé. Il n’affiche pas automatiquement un nouveau HEAD sous un tableau ancien. L’état d’une autre branche ne change pas la matrice publique.

[Procédure de maintenance](comprendre/maintenir.md) · [Manifeste du paquet](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/paquet.xml)
