# État du projet

**Révision documentée : `9f08d61f86515d80975cb6fbb228cac0142437f2`**, branche du miroir `compat-spip-4.4`. Revue éditoriale : 9 octobre 2026. Le paquet déclare la version **3.0.0**, en état **test** ; ce numéro ne signifie pas que toute la feuille de route est réalisée.

## Lire les états

| État | Signification |
|---|---|
| Implémenté | Un traitement ou un mécanisme est présent dans cette révision ; ses limites restent applicables |
| Prévu par une spec | La conversion cible est décrite, mais son code n’est pas dans la révision documentée |
| Hors périmètre / à adapter | Aucune conversion automatique correspondante n’est identifiée dans le moteur |

**Validation et implémentation sont deux informations distinctes.** Un test présent dans le dépôt indique ce qui peut être contrôlé. Un essai rapporté par une spec est un résultat contextualisé, pas une validation de toutes les versions WordPress. Ce site n’est pas un rapport d’exécution des tests du migrateur.

## Fonctions dans le miroir

| Fonction | État à la révision documentée | Référence |
|---|---|---|
| Options du site, utilisateurs, articles et pages | Implémenté | [Traitements](comprendre/traitements.md) |
| Catégories multiples et parenté des pages | Implémenté | [Taxonomies](correspondances/taxonomies.md), [pages](correspondances/articles-pages.md) |
| Documents, galeries et conversion des blocs | Implémenté avec limites | [Médias et blocs](correspondances/medias-blocs.md) |
| Contenus privés/protégés et commentaires | Implémenté avec transformation des accès | [Accès](correspondances/auteurs-acces.md), [commentaires](correspondances/commentaires.md) |
| Préfixe de tables personnalisé | Implémenté | [Préparation](migrer/preparer.md) |
| Étiquettes → mots-clés | Non implémenté dans la révision documentée ; prévu par la spec | [Spec étiquettes](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-etiquettes-design.md) |
| Yoast → SEO et catégorie principale | Non implémenté dans la révision documentée ; prévu par la spec | [Spec Yoast](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-yoast-design.md) |
| ACF → Champs Extras | Non implémenté dans la révision documentée ; prévu par la spec | [Spec ACF](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-acf-design.md) |
| Ajustement du statut des documents après liaison | Correctif prévu par une spec | [Spec documents](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-statut-documents-design.md) |
| Signalements structurés aux plugins tiers | Évolution prévue par une spec | [Spec signalements](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-09-wp2spip-signalements-design.md) |
| Menus, thème, révisions, types personnalisés | Hors périmètre du moteur documenté | [Matrice générale](correspondances/index.md) |

## Mise à jour à chaque synchronisation

Le mainteneur du site doit examiner le nouveau commit du miroir, revérifier **chaque ligne** de cette matrice contre le code et les tests, puis actualiser les pages métier, les permaliens et la date de revue. Le SHA affiché et la matrice sont mis à jour ensemble.

Tant que la revue n’est pas achevée, le site conserve son ancien SHA : il décrit cette révision, même si le miroir a avancé. Il n’affiche pas automatiquement un nouveau HEAD sous un tableau ancien. L’état d’une autre branche ne change pas la matrice publique du miroir.

[Procédure de maintenance](comprendre/maintenir.md) · [Manifeste du paquet](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/paquet.xml)
