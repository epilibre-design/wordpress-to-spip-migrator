# WordPress 4 : éditeur classique et taxonomies

La famille 4 illustre un modèle déjà riche : contenus typés, métadonnées, taxonomies, médias, commentaires et configuration. Le texte de l’éditeur classique combine HTML et shortcodes ; les extensions peuvent stocker d’autres structures dès cette époque.

## Socle éditorial

Articles (`post`), pages (`page`), médias (`attachment`) et révisions (`revision`) partagent `posts`. Les rôles se trouvent dans les métadonnées utilisateur ; les catégories et étiquettes utilisent les tables de termes et leurs relations.

Pour la migration, cela impose de **sélectionner les types et les statuts** : copier toutes les lignes de `posts` comme articles SPIP importerait aussi des révisions, menus ou données de plugins.

## 4.2 : séparation des termes partagés

Historiquement, un même `term_id` pouvait être partagé entre taxonomies. WordPress 4.2 introduit la séparation de termes lors de modifications, avec `_split_shared_term`. Les migrations de taxonomies doivent distinguer `term_id`, `term_taxonomy_id` et les relations.

Une copie ancienne ou mise à niveau peut encore présenter des situations héritées. La conservation d’identifiants des rubriques exige de qualifier ces cas ; l’objectif « WP4 » ne permet pas de supposer que toutes les structures anciennes ont déjà été normalisées.

## 4.4 : métadonnées des termes

La table `termmeta` et les API de métadonnées de termes permettent d’associer des valeurs supplémentaires aux catégories et autres taxonomies. Le modèle de base conserve ses tables de termes et de relations ; cette nouvelle couche peut porter des données importantes pour le site.

La révision documentée importe les catégories, les étiquettes, leurs descriptions et leurs relations aux contenus, mais n’effectue pas un transfert général de `termmeta`. Les valeurs propres au thème ou aux extensions demandent une stratégie dédiée.

## Médias et shortcodes

Images, légendes et galeries utilisent HTML et des shortcodes tels que `[caption]` et `[gallery]`. Le migrateur dispose de conversions pour plusieurs formes natives. Les shortcodes des extensions ne sont pas couverts simplement parce qu’ils se trouvent dans le même texte.

## Couverture et contrôle

La spec d’ensemble vise **4.9 à 7.x** et rapporte un essai en 4.9. Elle ne démontre pas toute la plage 4.0–4.8. Qualifier ces versions, les termes partagés, les shortcodes et les relations fait partie du travail nécessaire pour l’objectif WordPress 4 complet.

Sources : [séparation des termes, 4.2](https://github.com/WordPress/WordPress/blob/87bf150016e042bc3e21f2f1cb9de44042b8cdb1/wp-includes/taxonomy.php), [schéma 4.4](https://github.com/WordPress/WordPress/blob/f6a29831c76d2dbe82e9ae673539f910654c58a4/wp-admin/includes/schema.php), [API des termes 4.4](https://github.com/WordPress/WordPress/blob/f6a29831c76d2dbe82e9ae673539f910654c58a4/wp-includes/taxonomy.php), [types en 4.9](https://github.com/WordPress/WordPress/blob/29ffbff370968ae48a1b7a34e35c8b8e75cf0f91/wp-includes/post.php), [spec d’ensemble](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-ensemble-design.md).
