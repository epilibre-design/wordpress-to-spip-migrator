# Identifiants et traçabilité

Les identifiants relient les objets dans les deux CMS. Préserver un numéro facilite les raccourcis et les contrôles ; conserver une trace de l’origine reste nécessaire même lorsque le numéro change.

## Deux mécanismes complémentaires

| Objet source | Identifiant SPIP | Trace source |
|---|---|---|
| Article/page `posts.ID` | Même `id_article` | `id_wordpress` |
| Catégorie `terms.term_id` | Même `id_rubrique` | `id_wordpress` |
| Média `posts.ID` | Même `id_document` | `id_wordpress` |
| Utilisateur `users.ID` | Numérotation SPIP | `id_wordpress` |
| Commentaire `comments.comment_ID` | Numérotation SPIP du forum | `id_wordpress` |
| Galerie convertie | Album créé dans SPIP | Associations et contexte de conversion, pas un ID WordPress de galerie universel |

Le plugin ajoute un champ de traçabilité aux tables d’objets éditoriaux déclarées. Une page et un article WordPress partageant la table `posts`, leurs identifiants sont déjà dans le même espace source.

## Résoudre une relation

Pour un auteur WordPress `7` devenu auteur SPIP `9`, l’article est associé à `9` après recherche de `id_wordpress = 7`. Il n’est pas associé au numéro `7` par supposition.

Pour l’article WordPress `42`, le raccourci `article42` vise le bon article SPIP après import. Le contenu cible peut être importé plus tard : sa conservation d’identifiant permet la référence anticipée.

## Pourquoi une destination vierge ?

Avant de créer une série d’articles, rubriques ou documents, le moteur vérifie les identifiants nécessaires. Une collision arrête le traitement avant la création des objets de cette série, mais les séries précédentes peuvent déjà exister.

Un autre article SPIP numéro `42` ne doit pas être confondu avec le contenu WordPress `42`. La vérification empêche cette ambiguïté ; elle ne transforme pas un SPIP existant en espace de fusion.

## Une trace n’est pas un suivi continu

Retrouver `id_wordpress` évite des doublons à la relance. Cela ne signifie pas que le moteur synchronise les modifications WordPress, reconstitue un objet partiellement écrit ou réimporte son historique. Une migration répétée se prépare sur une destination remise à zéro.

La désinstallation supprime les champs de traçabilité ajoutés par le plugin. Conserver les rapports et exports utiles avant de retirer l’outil ; ne pas traiter sa désinstallation comme une annulation de migration.

Sources : [déclaration des champs](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip_pipelines.php), [installation/désinstallation](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip_administrations.php), [vérification des identifiants](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip.php).
