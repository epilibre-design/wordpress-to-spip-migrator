# WordPress 5 : blocs et édition du site

WordPress 5 marque une évolution de la **représentation** du contenu. Le socle SQL demeure, mais lire `post_content` comme un HTML autonome ne suffit plus pour tous les contenus.

## 5.0 : sérialisation des blocs

L’éditeur de blocs enregistre des commentaires `<!-- wp:nom {attributs} -->`, du HTML et des blocs enfants. `parse_blocks()` reconstruit cet arbre. Certains blocs enregistrent du HTML ; les blocs dynamiques peuvent ne stocker que leurs attributs.

```html
<!-- wp:paragraph -->
<p>Un paragraphe enregistré.</p>
<!-- /wp:paragraph -->
```

Le migrateur analyse cette représentation avant Sale et propose des conversions spécialisées. Cela conserve les relations aux médias et une partie des structures de mise en page, pas le fonctionnement complet de l’éditeur WordPress.

## Blocs réutilisables

Le type `wp_block` conserve un contenu réutilisable, référencé depuis un autre contenu. Copier seulement l’article porteur de la référence peut perdre le texte du bloc réutilisé. La révision documentée n’importe pas `wp_block` comme un objet autonome et ne garantit pas l’expansion de ses références.

Prévoir un inventaire des références et un traitement d’expansion ou une reconstruction adaptée.

## Commentaires à partir de 5.5

Le schéma de WordPress 5.5 définit `comment_type` avec la valeur par défaut `comment`, tandis que des bases anciennes utilisent une chaîne vide pour les commentaires ordinaires.

Le moteur sélectionne les deux formes. Ce changement limité de valeur montre pourquoi une condition SQL apparemment simple peut exclure des commentaires sur une autre version.

## 5.9 : données de l’édition du site

Modèles (`wp_template`), parties de modèles (`wp_template_part`), styles globaux (`wp_global_styles`) et navigation (`wp_navigation`) sont des types enregistrés dans `posts`. Certains modèles proviennent aussi des fichiers du thème : la base seule n’explique pas tout le rendu.

Le migrateur documenté sélectionne les articles, pages et médias, pas ces objets de thème/navigation. Les retrouver dans `posts` ne les rend pas importés. Les squelettes et menus SPIP sont à construire à partir de l’inventaire source.

## Contrôler selon les fonctions utilisées

Tester HTML classique, blocs imbriqués, galerie, bloc réutilisable, contenu embarqué et bloc dynamique. Vérifier les commentaires et identifier les données de thème ignorées. Un site WP5 utilisant exclusivement l’éditeur classique peut être très différent d’un site WP5.9 à thème blocs.

Sources : [blocs 5.0](https://github.com/WordPress/WordPress/blob/491c67be12ca8a9fe37ae38307ba7e298c976ec3/wp-includes/blocks.php), [types 5.0](https://github.com/WordPress/WordPress/blob/491c67be12ca8a9fe37ae38307ba7e298c976ec3/wp-includes/post.php), [schéma 5.5](https://github.com/WordPress/WordPress/blob/537fd931bc02e6e934a2d774422b897871aa87ad/wp-admin/includes/schema.php), [types 5.9](https://github.com/WordPress/WordPress/blob/73157386d069425c5e6ea7c4fc0122e8a9b58a7b/wp-includes/post.php), [conversion du migrateur](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip_blocs.php).
