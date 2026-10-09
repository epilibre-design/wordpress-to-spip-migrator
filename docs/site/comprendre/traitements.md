# La chaîne de migration

La commande SPIP-Cli orchestre une suite de traitements dépendants. Elle lit la version WordPress, détermine le préfixe et vérifie la présence des tables avant de commencer à écrire les objets SPIP.

```mermaid
flowchart TD
  S[Source WordPress figée] --> V[Version, préfixe et tables]
  V --> P[Plugins requis et schémas]
  P --> M[Métas et auteurs]
  M --> R[Rubriques et documents]
  R --> A[Articles et pages]
  A --> H[Parenté des pages et accès]
  H --> L[Polyhiérarchie et commentaires]
  L --> C[Contrôles et adaptation des squelettes]
  C --> B[Bascule après recette]
```

## Ordre disponible

| Rang | Traitement | Pourquoi à cette place ? |
|---|---|---|
| 1 | `importer_metas` | Identité et configuration du site |
| 2 | `importer_auteurs` | Auteurs connus avant d’associer les articles |
| 3 | `importer_rubriques` | Catégories converties avant le classement des articles |
| 4 | `importer_documents` | Médias disponibles pour convertir leurs références |
| 5 | `importer_articles` | Objets centraux, textes, auteur et documents associés |
| 6 | `importer_hierarchie_pages` | Les pages parentes et enfants existent désormais |
| 7 | `importer_acces` | Association aux zones avant publication des contenus protégés |
| 8 | `importer_polyhierarchie` | Relations secondaires entre articles et rubriques existants |
| 9 | `importer_commentaires` | Messages rattachés aux articles et auteurs connus |

Les noms donnés à `--traitements` sélectionnent des étapes sans changer cet ordre. L’outil ne crée pas à la demande les prérequis d’un traitement isolé ; les objets nécessaires doivent déjà exister.

## Plugins avant les contenus

La détection examine le contenu et ajoute Albums, Accès restreint, Forum ou a2a si nécessaire. Les plugins sont téléchargés, activés et leurs schémas préparés avant les traitements. La commande peut se relancer pour travailler dans le nouvel environnement de plugins.

Une extension peut compléter cette détection par `wp2spip_plugins_requis`. Activer un plugin ne signifie pas que ses données WordPress sont importées : il faut un traitement correspondant.

## Variantes de version

La commande tente une fonction spécialisée pour la version majeure/mineure, puis majeure, puis générique : `wp2spip_<traitement>_<X>_<Y>`, `wp2spip_<traitement>_<X>`, `wp2spip_<traitement>`. Cette architecture permet d’ajouter des adaptations ; elle ne garantit pas que chaque version dispose d’une variante testée.

## Arrêt et réexécution

Un traitement retournant `false` arrête la commande avec code 1. Les étapes précédentes peuvent déjà avoir écrit des objets ; l’import n’est pas une transaction globale avec retour automatique à l’état initial.

Les métas sont réécrites, la polyhiérarchie est réalignée et les fils de commentaires sont recalculés à la relance. Les contenus déjà tracés ne sont généralement pas modifiés. Après interruption ou évolution de l’outil, refaire la migration depuis une destination vierge.

Sources : [orchestration](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/spip-cli/WordpressImporter.php), [plugins requis](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip_plugins.php), [spec d’ensemble](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md).
