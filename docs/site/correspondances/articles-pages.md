# Articles et pages

**État : implémenté**, pour les lignes `posts` de types `post` et `page`. Les révisions et types personnalisés ne font pas partie de cette sélection.

## Objets et champs

| WordPress | SPIP | Transformation |
|---|---|---|
| `ID` | `id_article` et `id_wordpress` | Identifiant conservé |
| `post_title` | `titre` | Texte du titre repris ; titre vide à contrôler |
| `post_content` | `texte` | Blocs, HTML et références convertis |
| `post_date` | `date`, `date_redac` | Dates conservées |
| `post_modified` | `maj`, `date_modif` | Rétablies après les associations |
| `post_author` | Association avec un auteur | Recherche par son identifiant WordPress |
| `post_name` | Entrée `spip_urls` | Slug repris ; routage final à vérifier |
| `comment_status` | `accepter_forum` | `open` → forum autorisé, sinon fermé |

Le champ `post_excerpt` d’un article n’a pas de correspondance dédiée dans la composition de l’article de cette révision ; ne pas annoncer l’import d’un chapo WordPress. L’extrait d’un **média** peut en revanche alimenter son descriptif.

## Statuts

| WordPress | SPIP à la création |
|---|---|
| `publish` | `publie` |
| `future` | `publie`, avec date future ; vérifier les réglages d’affichage SPIP |
| `draft` | `prepa` |
| `pending` | `prop` |
| `private` | `prepa`, puis publication dans une zone restreinte |
| `trash` | `poubelle` |
| Publié/programmé avec mot de passe | Maintenu en préparation jusqu’au traitement des accès |

La traduction des accès est détaillée dans [Auteurs et accès](auteurs-acces.md). Un statut inconnu retombe sur la préparation ; les statuts personnalisés demandent une qualification.

## Une page reste un contenu

Une page WordPress devient une **page unique**, article sans rubrique (`id_rubrique = -1`), avec un champ `page` tel que `wordpress_page_42`. Sa parenté ne devient pas une arborescence de rubriques.

Pour la page enfant `51` de la page `42`, le moteur crée un lien a2a `sous_page` du parent vers l’enfant. Les enfants sont ordonnés par `menu_order`, titre puis identifiant ; leur position devient le rang du lien.

Le squelette doit exploiter ces liens pour les sous-pages ou le fil d’Ariane. Le modèle WordPress `_wp_page_template` n’est pas repris. La [spec de parenté](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md) donne des exemples de boucles.

## Contrôler après import

Vérifier un article publié, un brouillon, un programmé, une page enfant et un article sans titre. Comparer dates, auteur et parenté ; tester le rendu des pages uniques et les URL hiérarchiques.

Sources : [articles](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_articles.php), [hiérarchie](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_hierarchie_pages.php).
