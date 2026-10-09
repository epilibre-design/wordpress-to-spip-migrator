# WordPress 7 : vérifier la continuité et les références

La prise en charge de WordPress 7 fait partie de l’objectif du migrateur. Les [notes officielles de WordPress 7.1 « Mary Lou »](https://wordpress.org/news/2026/08/mary-lou/) donnent le contexte de cette version ; pour la conversion, il faut aussi examiner les données enregistrées.

## Ce que montre le code de 7.1

| Structure observée | Incidence pour la migration |
|---|---|
| `posts`, `postmeta`, taxonomies, utilisateurs, commentaires et options | Le socle relationnel reste présent |
| `wp_block` et `wp_pattern_sync_status` | Les compositions peuvent nécessiter une résolution de référence |
| `wp_template`, `wp_template_part`, `wp_global_styles` | Les données de présentation restent distinctes des articles |
| `wp_navigation` | La navigation n’est pas automatiquement une rubrique SPIP |
| `wp_font_family`, `wp_font_face` | La typographie appartient à la reconstruction du thème |

Ces structures sont **observées en 7.1** ; elles ne sont pas présentées comme des nouveautés introduites par WP7. Elles existaient déjà dans le code de 6.9 examiné.

## Comparer les schémas

La comparaison des définitions SQL dans `wp-admin/includes/schema.php` de **6.9 et 7.1** ne montre pas de changement des instructions de création des tables du cœur dans ce fichier. Cette observation est limitée à ces fichiers et versions : elle ne prouve pas l’identité de toutes les métadonnées, de tous les attributs de blocs ou de l’état d’une base mise à niveau.

Le code d’enregistrement des types évolue, notamment autour des écrans d’édition, sans que ces changements d’interface impliquent nécessairement une nouvelle table.

## Référence d’un bloc : un cas concret

```html
<!-- wp:block {"ref":123} /-->
```

Le contenu est celui d’un autre objet, pas du HTML contenu dans ce commentaire. WordPress résout cette référence lors du rendu. Dans le moteur documenté, les objets `wp_block` ne sont pas importés comme articles et cette référence n’a pas de conversion spécialisée garantissant son expansion.

Conséquence : inventorier les références et prévoir leur résolution ou reconstruction. Une base SQL lisible et un numéro de version reconnu ne suffisent pas à conserver cette structure.

## Validation attendue pour WP7

Le dépôt fournit un scénario d’import complet WordPress 7.1 et une référence versionnée ; les plans rapportent sa validation et la revue des différences après remplacement de Sale. Cela documente un jeu de test, pas une garantie pour toutes les fonctions de WP7. Rejouer l’import sur une installation externe identifiée et vérifier au minimum articles/pages, médias, blocs imbriqués, compositions référencées, navigation, accès et commentaires, en indiquant les conversions manquantes.

La publication de WP7.1 ne démontre pas la compatibilité du migrateur. [Voir la matrice de couverture](compatibilite.md).

Sources : [version officielle 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/version.php), [schéma 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-admin/includes/schema.php), [schéma 6.9](https://github.com/WordPress/WordPress/blob/ec24ee6087dad52052c7d8a11d50c24c9ba89a3b/wp-admin/includes/schema.php), [types et métadonnées 7.1](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/post.php), [résolution des références](https://github.com/WordPress/WordPress/blob/b998fef9238af183f9523b3df71618e6e57498b6/wp-includes/blocks/block.php), [conversion wp2spip](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php).
