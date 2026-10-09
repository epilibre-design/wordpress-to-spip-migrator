# Commentaires et fils de discussion

**État : implémenté**, avec le plugin Forum SPIP. La conversion préserve l’association à l’article et les réponses entre messages ; les numéros de forum sont ceux de SPIP.

## Données reprises

| WordPress | Forum SPIP |
|---|---|
| `comment_post_ID` | Article d’accueil retrouvé par son identifiant source |
| `comment_ID` | `id_wordpress`, distinct de `id_forum` |
| `comment_content` | Texte passé par Sale |
| Auteur, email, URL, IP | Champs correspondants du message |
| `user_id` | Auteur SPIP retrouvé, sinon pas d’auteur connecté associé |
| `comment_date` | Date du message |
| `comment_parent` | Parent et racine du fil SPIP |

L’IP et l’email peuvent être des données personnelles ; vérifier leur utilité, leurs accès et leur exposition dans le site de destination.

## Modération et exclusions

Commentaires approuvés (`1`) → publiés ; en attente (`0`) → proposés. Spam, corbeille, trackbacks et pingbacks ne sont pas importés. Les commentaires dont le contenu parent n’est pas importé sont ignorés et comptés.

Le moteur accepte `comment_type` vide, utilisé par les anciens WordPress, ou `comment`. Cette différence de stockage est particulièrement pertinente autour de [WordPress 5.5](../wordpress/wp5.md#commentaires-a-partir-de-55).

## Reconstruire le fil en deux temps

Les messages sont créés puis reliés. Une réponse peut donc retrouver un parent créé après elle. `id_parent` désigne le message auquel on répond ; `id_thread` désigne la racine. La date du fil est recalculée d’après ses messages publiés.

Si un parent est exclu (spam, par exemple), la réponse ne peut pas garder ce parent et peut former un nouveau fil. Le texte des messages déjà importés n’est pas réécrit lors d’une relance ; les relations de fil sont recalculées.

## Contrôler

Comparer nombre de commentaires admissibles, statut, article d’accueil et parenté. Vérifier une réponse imbriquée, un message en attente et une réponse à un parent exclu. Le compteur affiché par WordPress n’est pas forcément celui des commentaires admissibles à cette conversion.

Sources : [traitement](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_commentaires.php), [sélection des commentaires](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip_plugins.php).
