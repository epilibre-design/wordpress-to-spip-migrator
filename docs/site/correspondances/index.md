# Matrice WordPress → SPIP

Le moteur convertit des objets **et leurs relations**. Les structures SQL des deux CMS ne sont pas copiées à l’identique : une taxonomie devient une rubrique, une page devient un article particulier, une galerie devient un album.

Lire [l’état du projet](../etat-projet.md) pour les règles de validation et de mise à jour.

| Fonction native WordPress | Stockage source | Équivalent SPIP | Remarques |
|---|---|---|---|
| Options générales | `options` | Métas du site | Sélection de réglages |
| Articles | `posts` (`post`) | Articles | Textes convertis, dates et liens repris |
| Pages | `posts` (`page`) | Articles du plugin Pages uniques | Modèle de page non repris |
| Pages parentes/enfants | `post_parent`, `menu_order` | Liens a2a `sous_page` avec rang | Rendu à construire |
| Catégories | `terms`, `term_taxonomy` | Rubriques | Hiérarchie conservée |
| Catégories multiples | `term_relationships` | Rubrique principale et secondaires Polyhiérarchie | — |
| Étiquettes | Taxonomie `post_tag` | Mots-clés du groupe « Étiquettes » | Identifiants, descriptions et liens repris, même sans contenu |
| Utilisateurs et rôles | `users`, `usermeta` | Auteurs et statuts SPIP | Droits transformés, mots de passe non repris |
| Privé/protégé | `post_status`, `post_password` | Zones Accès restreint | Règle d’accès différente |
| Médias | `attachment`, `postmeta`, uploads | Documents et associations | Fichiers manquants/refusés possibles |
| Galeries | Bloc `gallery` ou shortcode | Albums | — |
| Blocs/HTML | `post_content` | Syntaxe SPIP, modèles et HTML conservé | Conversion partielle selon le type de bloc |
| Commentaires | `comments` | Forums et fils de discussion | Spam, corbeille, pingbacks/trackbacks exclus |
| Liens internes et slugs | Texte, `post_name` | Raccourcis et entrées d’URL | Redirections serveur à préparer |
| Révisions/autosaves | `posts` (`revision`) | Pas d’import de l’historique | Hors périmètre |
| Menus classiques ou à blocs | `nav_menu_item`, taxonomies, `wp_navigation` | Navigation des squelettes | À reconstruire |
| Thèmes, widgets et modèles | Fichiers, options, types `wp_template`… | Squelettes, styles et configuration | À adapter |
| Types et taxonomies personnalisés | Enregistrements du site ou de plugins | Objets/plugins spécifiques | Extension nécessaire |

## Exemple : deux catégories

Un article WordPress `42` appartient à « Actualités » (`12`) et « Culture » (`18`). Si `12` est retenue comme principale, SPIP reçoit l’article `42` dans la rubrique `12`, lié aussi à la rubrique `18` par Polyhiérarchie. Le titre du contenu n’est pas dupliqué dans deux articles.

## Ce que « préserver au maximum » signifie

Préserver identifiants, hiérarchies, associations et dates quand un équivalent existe ; rendre visibles les changements de sens ; identifier les données non reprises. Cela ne promet pas de conserver toutes les tables, toutes les métadonnées ou toutes les capacités des extensions.

ACF et Yoast sont des **extensions WordPress**, pas des fonctions natives. Les plugins séparés [wp2spip_acf et wp2spip_yoast](../comprendre/extensions.md) ajoutent leurs conversions lorsqu’ils sont installés et actifs ; ils ne font pas partie du checkout du moteur.
