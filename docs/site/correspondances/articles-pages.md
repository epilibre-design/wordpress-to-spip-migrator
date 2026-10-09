# Articles et pages

Le moteur importe les lignes `posts` de types `post` et `page`. Les révisions et types personnalisés ne font pas partie de cette sélection.

## Objets et champs

| WordPress | SPIP | Transformation |
|---|---|---|
| `ID` | `id_article` et `id_wordpress` | Identifiant conservé |
| `post_title` | `titre` | Texte du titre repris ; titre vide à contrôler |
| `post_excerpt` | `descriptif` | HTML converti ; vide si l’extrait n’a pas été saisi |
| `post_content` | `texte` | Blocs, HTML et références convertis |
| `post_date` | `date`, `date_redac` | Dates conservées |
| `post_modified` | `maj`, `date_modif` | Rétablies après les associations |
| `post_author` | Association avec un auteur | Recherche par son identifiant WordPress |
| `post_name` | Entrée `spip_urls` | Slug repris ; routage final à vérifier |
| `comment_status` | `accepter_forum` | `open` → forum autorisé, sinon fermé |

L’extrait saisi dans WordPress (`post_excerpt`) devient le **descriptif** de l’article ou de la page, et non son chapo : comme l’extrait dans WordPress, `#INTRODUCTION` reprend le descriptif quand il est rempli, et le texte sinon. Si au moins un extrait est importé, le moteur active le descriptif des articles dans la configuration SPIP (`articles_descriptif`) pour qu’il apparaisse dans l’espace privé. Un extrait généré automatiquement par WordPress n’est pas stocké et n’est donc pas repris : `#INTRODUCTION` en produit l’équivalent. L’extrait d’un **média** alimente le descriptif du document.

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

Le squelette doit exploiter ces liens pour les sous-pages ou le fil d’Ariane. Le modèle WordPress `_wp_page_template` n’est pas repris. La [spec de parenté](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-08-wp2spip-hierarchie-pages-design.md) donne des exemples de boucles.

## Contrôler après import

Vérifier un article publié, un brouillon, un programmé, une page enfant, un article sans titre et un article avec extrait. Comparer dates, auteur et parenté ; tester le rendu des pages uniques et les URL hiérarchiques.

Sources : [articles](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [hiérarchie](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_hierarchie_pages.php).
