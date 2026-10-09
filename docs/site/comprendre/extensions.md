# Extensions et signalements

Le moteur de wp2spip 3.0.0 importe les étiquettes, recalcule le statut des documents joints et convertit le HTML sans Sale. Yoast SEO et ACF sont pris en charge par **deux plugins séparés publiés**, pas par des traitements inclus dans ce checkout.

## Installer les extensions du migrateur

Récupérer [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast) ou [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf), les placer dans `plugins/` du SPIP de destination et les activer à côté de wp2spip **avant l’import**. Consulter leurs README pour l’installation et les versions exactes. Les versions publiées demandent wp2spip ≥ 3.0.0 et PHP 8.4.

Depuis la racine de ce SPIP :

```bash
spip wordpress:importer /chemin/vers/wordpress-fige --info
spip wordpress:importer --garder-adresse -v /chemin/vers/wordpress-fige
```

La première commande doit montrer les traitements de l’extension active ; la seconde les exécute dans la même chaîne que ceux du moteur. La détection prépare les plugins cibles nécessaires selon les données source. Le script `outils/preparer_spip.sh` ne récupère pas les extensions du migrateur : pour les ajouter, préparer sans `--importer`, les installer, puis lancer l’import.

## Yoast SEO

**Extension publiée : [wp2spip_yoast](https://git.spip.net/technova69/wp2spip_yoast).** Elle ajoute deux traitements :

| Traitement | Position | Conversion |
|---|---|---|
| `importer_yoast_categories` | Juste après `importer_articles`, avant la polyhiérarchie | Catégorie principale Yoast → rubrique principale SPIP ; les autres deviennent des rubriques secondaires |
| `importer_yoast_seo` | Ajouté en fin de liste | Titres, méta-descriptions et directives d’indexation → plugin SEO, pour les articles/pages et les catégories |

Une catégorie principale Yoast qui n’appartient plus au contenu est signalée et le choix du cœur est gardé. Les métadonnées d’une catégorie supprimée de WordPress sont ignorées. Le plugin SEO est requis seulement si des métadonnées à importer sont présentes ; changer la rubrique principale seul ne le nécessite pas.

Les variables de titre connues sont remplacées. Une valeur contenant encore une variable inconnue est ignorée et comptée au bilan. Une métadonnée SEO déjà présente dans SPIP est conservée. Contrôler les balises produites dans le `<head>` du site de destination.

Expressions clés, scores, URL canoniques, données sociales, plan du site XML, modèles globaux de titres et métadonnées des autres taxonomies ne sont pas repris. Voir [le périmètre et les essais rapportés](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-yoast-design.md), puis la documentation du dépôt de l’extension pour son état actuel.

## Advanced Custom Fields

**Extension publiée : [wp2spip_acf](https://git.spip.net/technova69/wp2spip_acf).** Le traitement `importer_acf`, ajouté en fin de liste, reprend les définitions en base des groupes publiés applicables aux articles/pages, puis leurs valeurs sur les contenus importés. Il crée des colonnes `acf_<nom>` dans `spip_articles`, modifiables avec Champs Extras Interface, et conserve les valeurs déjà présentes dans la destination.

| Types ACF pris en charge | Conversion |
|---|---|
| Texte, email, URL, nombre, plage | Valeur dans un champ texte |
| `textarea`, `wysiwyg` | Texte converti avec le convertisseur de wp2spip ; liens internes pour `wysiwyg` |
| Sélection, boutons radio, groupe de boutons | Choix conservés ; sélections multiples et cases à cocher stockées selon Champs Extras |
| Booléen, date | Valeur adaptée au format cible |
| Image, fichier | Identifiant du document importé, lié à l’article ; média absent signalé |

Champs Extras et son interface sont détectés selon les champs importables, même quand leurs valeurs sont encore vides. Les règles de groupe permettent de retenir les définitions applicables aux contenus ; elles ne sont pas reproduites comme des conditions d’affichage de formulaire SPIP.

Les groupes hors articles/pages, les définitions ACF 4 ou déclarées en PHP/JSON, les répéteurs, sous-champs, groupes imbriqués, galeries, relations et contenus flexibles ne sont pas importés par cette extension. Examiner le bilan des exclusions ; des champs extras importés demandent encore un usage explicite dans les squelettes.

Le pipeline propre à l’extension `wp2spip_acf_correspondances` permet d’associer un nom ACF à une colonne **existante** de `spip_articles` au lieu de créer un champ extra. Voir [le périmètre et les essais rapportés](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-acf-design.md), puis la documentation du dépôt de l’extension pour son état actuel.

## Correctif du statut des documents

**Implémenté dans le moteur.** Après association des pièces jointes aux articles, `importer_articles` recalcule leur statut avec `document_instituer()`. Ce recalcul évite qu’un document joint à un article publié reste proposé uniquement parce qu’il n’apparaît pas dans le texte.

Les tests d’intégration, le vérificateur et l’export comparatif prennent en compte ce comportement. La recette doit toujours contrôler publication et accès aux fichiers. [Lire le correctif et ses critères](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/specs/2026-10-09-wp2spip-statut-documents-design.md).

## Signalements aux plugins tiers

Le sous-projet de signalements a produit des **brouillons de tickets avec reproductions et correctifs proposés**, conservés dans `docs/signalements/`. Il n’ajoute pas un pipeline ou une API de signalement au migrateur et ne signifie pas que les tickets ont été envoyés ni les correctifs intégrés aux plugins tiers.

- **Sale 1.0.0** : pertes de textes entiers et avertissements PHP 8. Le [brouillon](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/signalements/sale-extraire-images.md) contient les reproductions ; son [corpus de 103 contenus](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/signalements/sale-corpus-theme-unit-test.md) permet de vérifier le correctif proposé. wp2spip utilise désormais son [propre convertisseur](conversion.md).
- **Polyhiérarchie configurable 1.2.0** : le [brouillon en deux parties indépendantes](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/signalements/polyhierarchie-configurable.md) traite le calcul des rubriques et le comptage des objets rangés. Ce plugin est distinct de Polyhiérarchie, dépendance du migrateur ; le signalement ne crée pas une dépendance supplémentaire.

## Points d’extension du moteur

| Mécanisme | Usage |
|---|---|
| `wp2spip_traitements` | Insérer un traitement dans la liste ordonnée |
| `w2spip_traitements` | Ancien nom toujours appelé, pour compatibilité |
| Variantes de fonctions par version | Adapter un traitement à une forme de données WordPress |
| `wp2spip_bloc` | Ajouter/remplacer la conversion d’un bloc |
| `wp2spip_plugins_requis` | Compléter les dépendances détectées avant import |

Un développement doit fournir son code, ses dépendances et ses validations. Les suites des extensions sont exécutées dans leurs propres dépôts ; les essais rapportés dans les specs ne remplacent pas une recette sur votre source.

[État du projet](../etat-projet.md) · [Architecture du moteur](traitements.md).
