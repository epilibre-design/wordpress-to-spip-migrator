# 4. Valider puis basculer

Un import terminé constitue le début de la recette. Contrôler la conversion des données et le rendu du site séparément.

## Contrôles de structure

| Contrôle | Résultat recherché |
|---|---|
| Articles/pages | Objets attendus, identifiants, dates, auteur et statuts cohérents |
| Catégories | Hiérarchie conservée et ensemble des rubriques principales/secondaires correct |
| Pages enfants | Liens a2a `sous_page`, parenté et ordre vérifiés |
| Médias/galeries | Fichiers lisibles, liens aux contenus et albums corrects ; refus recensés |
| Commentaires | Publication/modération et fils conformes ; exclusions comprises |
| Accès | Contenus privés/protégés inaccessibles aux visiteurs non autorisés |
| URL | Liens internes résolus et redirections externes préparées |

La révision documentée ne migre pas les étiquettes. Leur absence n’est pas un succès de conversion : la traiter comme une limitation connue dans la recette.

## Vérificateur et export du dépôt

Le dépôt fournit [verifier_identifiants.php](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/tests/integration/verifier_identifiants.php) et [exporter_import.php](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/tests/integration/exporter_import.php). Ils s’utilisent dans le contexte SPIP de test configuré ; lire leurs hypothèses avant de les appliquer à une autre installation.

Depuis un SPIP de recette correctement configuré :

```bash
spip php:eval "include '/chemin/vers/le-checkout/tests/integration/verifier_identifiants.php';"
```

Vérifier la sortie attendue **`OK`** et les erreurs éventuelles : un code 0 de `php:eval` seul peut masquer une page d’erreur. L’export comparatif aide à repérer une variation, mais ne couvre pas l’ensemble des adaptations de thème et d’extensions.

## Recette éditoriale et visuelle

Ouvrir les exemples complexes définis à l’audit : bloc de couverture, galerie, liens vers un média, page imbriquée, brouillon, article programmé et contenu privé. Contrôler accents, tableaux, légendes, alignements, navigation et comportement sur petit écran.

Les classes de blocs conservées demandent des styles adaptés dans le squelette. Les menus et modèles WordPress demandent une reconstruction. Un document bien copié ne signifie pas qu’il est visible ou correctement publié : le [correctif de statut des documents](../comprendre/extensions.md#correctif-du-statut-des-documents) est prévu par une spec.

## Préparer la bascule

Reconstituer les accès, mettre au point les squelettes, préparer les redirections HTTP et vérifier le domaine final. Sauvegarder la destination validée et prévoir un retour à la source. Décider explicitement des données non converties avant d’ouvrir le site.

Après la bascule, contrôler les anciennes URL représentatives, les formulaires de connexion et les médias depuis l’adresse publique. Le migrateur ne configure ni DNS ni serveur de redirections.
