# Conversion des contenus

Le texte WordPress combine souvent HTML, shortcodes, sérialisation de blocs et références à des objets. Les traiter comme une simple chaîne à copier perd les relations avec les médias et les contenus.

## Avant et après

Exemple source, enregistré dans `post_content` :

```html
<!-- wp:image {"id":72,"align":"left"} -->
<figure class="wp-block-image alignleft">
  <img src="https://exemple.test/wp-content/uploads/photo.jpg" class="wp-image-72">
  <figcaption>Une légende</figcaption>
</figure>
<!-- /wp:image -->
```

Avec le document `72` importé, la cible est un modèle de type `<img72|left>` et une légende enregistrée comme descriptif. La relation au fichier est explicite ; son affichage dépend du modèle et du squelette SPIP.

## Les étapes de conversion

1. Analyser les commentaires `<!-- wp:… -->` et les attributs JSON en arbre de blocs.
2. Convertir les blocs reconnus et les galeries, en conservant leur ordre.
3. Protéger temporairement les fragments déjà convertis avec des marqueurs `wp2spipblocN`.
4. Convertir le HTML restant avec Sale.
5. Restaurer les fragments protégés et réécrire les références reconnues aux contenus et médias.

Les marqueurs empêchent Sale de traiter une deuxième fois un raccourci déjà produit. Les structures sélectionnées gardent des classes `wp-block-…` pour être stylées dans SPIP.

## Trois cas à distinguer

| Forme enregistrée | Possibilité de conversion |
|---|---|
| HTML autonome | Peut être converti, avec vérification du résultat |
| Bloc sérialisé avec attributs et HTML | Peut recevoir un traitement spécialisé |
| Bloc calculé ou référence vers un autre objet | Demande une résolution ou une reconstruction ; aucun rendu complet n’est garanti |

Le moteur n’exécute pas WordPress pour calculer tous ses blocs. Les blocs dynamiques identifiés conservent leur texte ou média enregistré exploitable et sont retirés lorsqu’ils n’en ont pas ; le HTML enregistré des blocs inconnus est conservé et signalé. Un bloc inconnu sans contenu enregistré peut donc ne fournir aucun texte à conserver.

## Compléter une conversion

Le pipeline `wp2spip_bloc` reçoit le bloc et la sortie produite, ou `null` si aucun traitement ne l’a reconnu. Une extension peut ajouter une conversion adaptée à un type précis. Documenter sa structure source et tester médias, attributs, contenu et relations.

Cette possibilité d’extension n’implémente pas les fonctions de plugins WordPress par elle-même. Les shortcodes propres à un thème ou une extension demandent un traitement explicite.

Sources : [analyse et conversion](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip_blocs.php), [intégration dans les articles](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_articles.php), [spec des blocs](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/docs/superpowers/specs/2026-10-08-wp2spip-blocs-editeur-design.md).
