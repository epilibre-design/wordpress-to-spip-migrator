# Médias, galeries et blocs

**État : implémenté avec limites.** L’import du fichier et la conversion de sa référence dans un texte sont deux opérations différentes.

## Médias → documents

Les médias de type `attachment` et statut `inherit`, même sans parent, deviennent des documents SPIP. L’identifiant est conservé, le titre, le descriptif et la date sont repris. Les fichiers sont recherchés localement selon le chemin du `guid`, puis téléchargés si nécessaire ; ils sont copiés dans la destination.

Une configuration d’uploads particulière, un chemin de site en sous-dossier ou un stockage externe doit être testé : la recherche locale n’est pas un inventaire universel des fichiers WordPress. Un fichier refusé par SPIP ne doit pas laisser de document vide ; les refus sont signalés. Le code peut aussi ne pas importer un fichier illisible : comparer les inventaires, pas seulement les compteurs du bilan.

L’index utilisé pour reconnaître les URL dans les textes tient compte du fichier attaché, des tailles dérivées, des originaux d’images réduites/retouchées et du `guid`. Reconnaître une miniature sert à retrouver le document SPIP ; cela ne promet pas de recopier chaque variante de taille comme un document indépendant.

## Conversion du contenu

| Élément enregistré | Résultat recherché |
|---|---|
| Image ou bloc image | `<imgN>` avec alignement ; légende dans le descriptif |
| Audio, vidéo, fichier | `<docN>` |
| Lien vers un média | `[texte->documentN]` |
| Galerie classique `[gallery]` ou bloc galerie | Album et `<albumN>` |
| Colonnes, groupe, boutons… | Structure gardée avec classes `wp-block-…` à styler |
| Contenu embarqué | URL seule ; lecteur à fournir notamment avec oEmbed |
| Bloc dynamique identifié | Contenu enregistré utile conservé ; retiré s’il ne contient ni texte ni média exploitable |
| Bloc inconnu | Contenu enregistré conservé, bilan à examiner |

Exemple : une galerie contenant les médias `72` et `73` devient un album lié à ses documents et à l’article. L’album a son propre identifiant SPIP ; ce n’est pas une nouvelle copie de chaque image.

## Structure enregistrée et rendu calculé

Un bloc peut stocker son HTML entre commentaires ; un autre stocke seulement des attributs ou une référence. Une liste d’articles calculée par WordPress n’est pas une liste figée dans `post_content`. Une composition synchronisée référencée par `core/block` exige une résolution supplémentaire : le moteur documenté n’importe pas les objets `wp_block` et ne garantit pas leur expansion.

Les blocs de couverture peuvent laisser des URL de fond pointant vers la source malgré l’import du document. Les bilans distinguent des médias absents de la médiathèque et des médias connus dont l’emplacement n’a pas de raccourci SPIP adapté.

## Contrôler

Vérifier fichiers, légendes, galeries, alignements, liens, lectures audio/vidéo et fonds de blocs. Examiner les blocs inconnus et dynamiques. La mise en page dépend des squelettes et du CSS SPIP. Le [statut des documents après liaison](../comprendre/extensions.md#correctif-du-statut-des-documents) fait l’objet d’un correctif prévu.

Sources : [documents](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_documents.php), [blocs](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip_blocs.php), [index et associations](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/wp2spip/importer_articles.php).
