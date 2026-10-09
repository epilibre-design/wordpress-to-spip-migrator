# 4. Valider puis basculer

Un import terminé constitue le début de la recette. Contrôler la conversion des données et le rendu du site séparément.

## Contrôles de structure

| Contrôle | Résultat recherché |
|---|---|
| Articles/pages | Objets attendus, identifiants, dates, auteur et statuts cohérents |
| Catégories | Hiérarchie conservée et ensemble des rubriques principales/secondaires correct |
| Étiquettes | Mots du groupe « Étiquettes », identifiants et liens corrects, y compris termes sans contenu et contenus non publiés |
| Pages enfants | Liens a2a `sous_page`, parenté et ordre vérifiés |
| Médias/galeries | Fichiers lisibles, liens aux contenus et albums corrects ; refus recensés |
| Commentaires | Publication/modération et fils conformes ; exclusions comprises |
| Accès | Contenus privés/protégés inaccessibles aux visiteurs non autorisés |
| URL | Liens internes résolus et redirections externes préparées |

Avec les extensions actives, vérifier aussi les rubriques principales et métadonnées SEO issues de Yoast, ainsi que les champs extras et valeurs issus d’ACF. Examiner les données exclues ou signalées par leurs bilans.

## Vérificateur et export du dépôt

Le dépôt fournit [verifier_identifiants.php](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/verifier_identifiants.php) et [exporter_import.php](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/exporter_import.php). Ils s’utilisent dans le contexte SPIP de test configuré ; lire leurs hypothèses avant de les appliquer à une autre installation.

Depuis un SPIP de recette correctement configuré :

```bash
spip php:eval "include '/chemin/vers/le-checkout/tests/integration/verifier_identifiants.php';"
```

Vérifier la sortie attendue **`OK`** et les erreurs éventuelles : un code 0 de `php:eval` seul peut masquer une page d’erreur. L’export comparatif aide à repérer une variation, mais ne couvre pas l’ensemble des adaptations de thème et d’extensions.

## Recette éditoriale et visuelle

Ouvrir les exemples complexes définis à l’audit : bloc de couverture, galerie, liens vers un média, page imbriquée, brouillon, article programmé et contenu privé. Contrôler accents, tableaux, légendes, alignements, navigation et comportement sur petit écran.

Les classes de blocs conservées demandent des styles adaptés dans le squelette. Les menus et modèles WordPress demandent une reconstruction. Le statut des documents joints est désormais recalculé après leur liaison aux articles ; contrôler aussi les médias absents du texte et ceux des contenus non publiés. Vérifier que les caractères des raccourcis SPIP présents comme texte dans WordPress restent affichés comme texte après [conversion](../comprendre/conversion.md).

## Préparer la bascule

Reconstituer les accès, mettre au point les squelettes, préparer les redirections HTTP et vérifier le domaine final. Sauvegarder la destination validée et prévoir un retour à la source. Décider explicitement des données non converties avant d’ouvrir le site.

Après la bascule, contrôler les anciennes URL représentatives, les formulaires de connexion et les médias depuis l’adresse publique. Le migrateur ne configure ni DNS ni serveur de redirections.
