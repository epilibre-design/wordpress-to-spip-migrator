# Extensions du migrateur

Yoast SEO et ACF sont pris en charge par **deux plugins séparés publiés**. Leurs traitements s’ajoutent à ceux du moteur wp2spip lorsqu’ils sont installés et actifs.

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

## Points d’extension du moteur

| Mécanisme | Usage |
|---|---|
| `wp2spip_traitements` | Insérer un traitement dans la liste ordonnée |
| `w2spip_traitements` | Ancien nom toujours appelé, pour compatibilité |
| Variantes de fonctions par version | Adapter un traitement à une forme de données WordPress |
| `wp2spip_bloc` | Ajouter/remplacer la conversion d’un bloc |
| `wp2spip_plugins_requis` | Compléter les dépendances détectées avant import |

Un développement doit fournir son code, ses dépendances et ses validations. Les suites des extensions sont exécutées dans leurs propres dépôts ; leurs essais ne remplacent pas une recette sur votre source.

[Architecture du moteur](traitements.md).
