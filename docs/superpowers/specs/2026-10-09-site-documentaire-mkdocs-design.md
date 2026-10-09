# Site documentaire MkDocs — WordPress vers SPIP

Date : 2026-10-09.
Statut : cadrage approuvé après relecture et retouches finales ; réalisation autorisée.
Référence du code inspecté : `9f08d61f86515d80975cb6fbb228cac0142437f2`, branche distante `compat-spip-4.4`.

## 1. Besoin et critères de réussite

Créer un mini-site documentaire en français pour le miroir https://github.com/tech-nova/wordpress-to-spip-migrator, avec plusieurs pages et onglets. Le lecteur doit pouvoir installer l’outil, préparer sa migration, comprendre les conversions entre fonctions natives WordPress et SPIP, et évaluer ce qui est conservé de la structure de ses données.

L’utilisateur précise que l’outil est en cours de création : une bonne partie des specs n’est pas implémentée. La documentation ne doit donc pas présenter une décision de conception comme une fonction disponible. La couverture de WordPress 4 à 7 est l’objectif demandé, pas une garantie déjà démontrée.

Lecteurs supposés : responsables de migration et développeurs connaissant au moins un des deux CMS. Deux parcours complémentaires : « réaliser une migration » et « comprendre la conversion ». Aucune modification du moteur PHP ni mise en œuvre des specs fonctionnelles dans cette tâche.

## 2. Choix technique proposé

Recommandation : MkDocs avec Material, navigation par onglets, menu latéral, table des matières, recherche en français, thème clair/sombre, blocs de commande copiables, tableaux et diagrammes Mermaid. Site entièrement statique, consultable sur mobile, sans service applicatif ni base de données. Mermaid est servi localement, avec licence et provenance, pour rendre les diagrammes sans dépendre d’un CDN. Le verrou documentaire retenu impose Python >= 3.11.

Deux alternatives examinées : thème standard MkDocs (moins de dépendances, navigation et présentation des correspondances moins confortables) ; référence générée uniquement depuis le code (utile pour l’API, insuffisante pour expliquer les enjeux éditoriaux et l’évolution de WordPress). Material avec rédaction manuelle et références au code répond le mieux au besoin.

Fichiers proposés à la racine du miroir : `mkdocs.yml`, `requirements-docs.txt` ; sources publiques dans `docs/site/`. Configurer explicitement `docs_dir: docs/site` : toutes les sources hors de ce répertoire, notamment `docs/superpowers/`, sont exclues de la sortie publique, indépendamment de la navigation. Résultat généré dans `site/`, ignoré par Git, dépendances Python dans un environnement virtuel hors du checkout. Ne pas modifier Composer, ses verrous, les tests PHP ou le moteur de migration.

Les liens vers le code et les specs exclus du site sont des permaliens GitHub, de la forme `https://github.com/tech-nova/wordpress-to-spip-migrator/blob/<commit-complet>/docs/superpowers/specs/<fichier>.md`, vers le commit documenté contenant la source. Aucun lien relatif vers `../superpowers/`, aucun lien vers une branche mobile. Une spec uniquement locale n’a pas de permalink distant : ne pas inventer un lien avant sa publication.

Configuration minimale requise (MkDocs 1.6 ou ultérieur, versions exactes verrouillées lors de la réalisation) :

```yaml
docs_dir: docs/site
site_dir: site
validation:
  links:
    not_found: warn
    unrecognized_links: warn
    anchors: warn
    absolute_links: warn
```

Utiliser des liens internes relatifs vers les fichiers Markdown, avec leurs ancres lorsqu’elles sont nécessaires. `anchors: warn` rend les ancres introuvables bloquantes avec `mkdocs build --strict` ; leur niveau par défaut `info` ne suffit pas. Les liens externes HTTPS restent des références externes ; ce réglage ne vérifie pas leur disponibilité sur le réseau. Références : [configuration MkDocs](https://www.mkdocs.org/user-guide/configuration/) et [validation des liens](https://www.mkdocs.org/user-guide/configuration/#validation).

### Préparation multilingue

Demande complémentaire : utiliser `mkdocs-static-i18n`, `docs_structure: suffix`, avec le français par défaut. Conserver `docs_dir: docs/site` : les sources françaises sont `index.md`, `installer/installation.md`, etc., et les futures traductions `index.en.md`, `installer/installation.en.md`. Configurer `fr` avec `default: true`, `build: true`, et `en` avec `build: false` jusqu’à la rédaction des traductions. Les URL françaises restent à la racine ; les futures pages anglaises utilisent `/en/`. Documenter le repli vers le français et vérifier une page anglaise temporaire, sans publier de faux contenu anglais.

## 3. Organisation du site

| Onglet | Pages proposées | Résultat attendu |
|---|---|---|
| Accueil | Vue d’ensemble ; état du projet | Comprendre conversion de données, conservation de structure et développement en cours |
| Installer | Prérequis ; installation ; environnement de développement | Distinguer installation du plugin, SPIP-Cli et dépendances des tests |
| Migrer | Audit et sauvegardes ; préparation du SPIP ; lancement ; validation et bascule ; dépannage | Suivre un parcours complet, avec les contrôles et conséquences d’un échec |
| Correspondances | Matrice générale ; articles et pages ; catégories et étiquettes ; auteurs et accès ; médias et blocs ; commentaires ; liens et adresses | Identifier les données source, l’objet SPIP, le plugin requis, les relations conservées et les limites |
| WordPress 4–7 | Modèle de données ; WordPress 4 ; WordPress 5 ; WordPress 6 ; WordPress 7 ; matrice de compatibilité | Relier les changements de stockage et fonctionnalités à leurs conséquences de migration |
| Comprendre | Chaîne de traitements ; identifiants et traçabilité ; conversion des textes ; extensions et feuille de route ; sources | Expliquer les mécanismes et séparer le moteur disponible des évolutions prévues |

Environ 25 pages courtes, regroupées par sujet ; les informations communes sont liées plutôt que répétées. L’accueil propose deux entrées visibles : « Préparer ma migration » et « Comprendre les correspondances ». Les intitulés de navigation restent simples et en français.

## 4. Contrat éditorial et états

Chaque fonction comporte un état explicite :

- **Implémenté dans la révision documentée du miroir** : code présent et références précises ; état de l’implémentation et validation sont distincts.
- **Prévu par une spec** : description de la cible et lien vers la spec ; aucun exemple de commande ne laisse croire qu’elle existe déjà.
- **Hors périmètre / adaptation nécessaire** : données non importées ou rendu à reconstruire.

La validation est un axe séparé : tests présents dans le dépôt, résultats d’exécution assortis d’une version et d’une date, essais rapportés par une spec, validation manquante. Une spec qui raconte un essai n’est pas une preuve que la fonction figure dans cette version du miroir.

Ordre des sources : code et tests du commit examiné pour le comportement disponible ; specs pour intentions et décisions ; documentation/code officiel WordPress pour l’histoire des données. Quand une spec décrit un ancien comportement désormais remplacé, expliquer l’évolution et documenter la version du code inspectée.

### Politique de mise à jour des états

Afficher « Révision documentée » et le SHA complet du code dans le pied de page commun à toutes les pages, avec un lien vers le commit GitHub ; la page « État du projet » reprend la branche, la date de revue et la matrice des fonctions. La révision de code documentée est distincte du commit ultérieur publiant les sources du site.

À chaque synchronisation du miroir, le mainteneur de la documentation doit :

1. Relever le nouveau commit exact du miroir et examiner les différences avec la révision précédemment documentée.
2. Revérifier chaque ligne de la matrice contre les traitements disponibles, leur orchestration, les plugins requis et les tests ; mettre à jour également les pages métier concernées.
3. Conserver la distinction entre code présent, tests existants et validation exécutée ; une fonction ajoutée ne devient pas automatiquement « validée ».
4. Actualiser les permaliens, la révision affichée et la date de revue ensemble, puis refaire les contrôles du site avant sa publication.

Si cette revue n’est pas terminée, conserver l’ancienne révision affichée et indiquer que le site documente cette version : ne pas afficher automatiquement le HEAD du miroir sous un tableau ancien. Aucun changement d’état ne doit être déduit du statut d’une spec seule.

Cas concret : `importer_mots` est absent du miroir à `9f08d61f86515d80975cb6fbb228cac0142437f2`. La matrice publique indique « non implémenté dans la révision documentée ; prévu par la spec ». Elle ne décrit pas l’état d’une autre branche. Dès la synchronisation suivante, vérifier le traitement, sa place dans la commande et les tests, puis actualiser la correspondance des étiquettes.

## 5. Correspondances à expliquer

Chaque page métier suit le même ordre : fonction WordPress, stockage, exemple simple, équivalent SPIP, relations et valeurs conservées, pertes/transformations, état actuel, contrôle après import.

| Fonction | Correspondance et points à traiter | État observé |
|---|---|---|
| Options du site | `options` vers métas SPIP ; adresse et `--garder-adresse` | Code présent |
| Articles/pages | `posts` vers articles / Pages uniques ; dates, statuts, auteurs | Code présent |
| Catégories | `terms`, `term_taxonomy`, `term_relationships` vers rubriques et Polyhiérarchie | Code présent |
| Hiérarchie des pages | `post_parent`, `menu_order` vers liens a2a `sous_page` et rang | Code présent |
| Étiquettes | `post_tag` vers mots-clés dans un groupe dédié | Non implémenté dans la révision documentée ; prévu par la spec |
| Utilisateurs | `users`, `usermeta`, rôles vers auteurs et statuts SPIP ; mots de passe réinitialisés | Code présent |
| Médias | `attachment`, métadonnées et uploads vers documents, fichiers et liens ; miniatures et légendes | Code présent ; limites documentées |
| Éditeur classique/blocs | HTML, shortcodes, commentaires de blocs vers syntaxe SPIP, modèles et Albums | Code présent ; conversion partielle selon le bloc |
| Privé/protégé | Statuts et mots de passe de contenu vers zones Accès restreint | Code présent ; sémantique d’accès transformée |
| Commentaires | `comments` vers forums ; relations parent/enfant et modération | Code présent |
| Liens internes | Identifiants, slugs et URL média vers raccourcis ; slugs ambigus et URL résiduelles | Code présent ; pas de garantie de redirections HTTP |
| Menus, thèmes, widgets, révisions, types personnalisés | Inventaire source et rendu/structure à adapter ; absence d’équivalence automatique vérifiée | Ne pas annoncer comme importés |
| ACF/Yoast | Extensions, Champs Extras et SEO envisagés dans des specs séparées | Non natifs WordPress ; implémentation absente de ce checkout |

Les exemples utilisent des données fictives et ne contiennent aucun secret. Illustrer une même relation avant/après, par exemple deux catégories d’un article ou une page parente avec ses sous-pages ; ne pas réduire une migration à une copie de texte.

## 6. Évolution WordPress 4–7

Distinguer trois couches : schéma SQL, types/relations enregistrés, représentation du contenu (HTML, shortcodes, blocs, attributs sérialisés). Une nouvelle fonctionnalité ne crée pas nécessairement une nouvelle table.

### WordPress 4

Expliquer le modèle classique : `posts` partagé par plusieurs types, métadonnées clé/valeur, taxonomies, relations, utilisateurs, commentaires et options. Examiner les changements de termes partagés en 4.2 et les métadonnées de termes en 4.4 à partir de sources officielles ; ne pas attribuer au migrateur une importation générale de `termmeta`. La spec d’ensemble vise 4.9 et plus, ce qui laisse 4.0–4.8 à qualifier pour l’objectif WP4 complet.

### WordPress 5

Expliquer l’introduction de l’éditeur de blocs en 5.0 : stockage dans `post_content`, commentaires de sérialisation, attributs JSON, HTML enregistré et blocs dynamiques. Examiner les blocs réutilisables et, en 5.9, les données de l’édition du site (modèles, parties, styles, navigation), en indiquant ce que le convertisseur lit ou ignore.

### WordPress 6

Expliquer l’extension de l’édition du site, les compositions synchronisées et leurs références, ainsi que les différences entre contenu éditorial et données de thème. Relier chaque évolution retenue à un stockage et à une conséquence concrète pour l’import ; ne pas écrire un catalogue de nouveautés sans incidence sur les données.

### WordPress 7

Prévoir une page complète, sourcée sur les versions exactes disponibles, avec changements confirmés, stabilité du modèle, nouveaux types/attributs éventuels et impacts. Utiliser notamment les [notes officielles de WordPress 7.1 « Mary Lou »](https://wordpress.org/news/2026/08/mary-lou/), apportées pendant la relecture, et les confronter au code de cette version pour expliquer les évolutions de stockage pertinentes. Aucun changement précis de WP7 ne sera inventé à partir d’un numéro de version. La publication d’une version WordPress ne démontre pas sa prise en charge par wp2spip.

### Matrice de couverture

Colonnes : famille/version, forme de données, fonction concernée, code disponible, tests présents ou essais rapportés, validation restante. Ne pas déduire une couverture de toutes les versions intermédiaires d’un test sur une seule version.

## 7. Mécanismes et étapes de migration

Présenter le flux source figée → identification version/préfixe → vérification des tables → plugins requis → traitements ordonnés → contrôles → adaptation des squelettes et accès → bascule. Montrer que SPIP est hors ligne et vierge pendant l’import.

Décrire la conservation des identifiants des articles/pages, rubriques et documents, ainsi que `id_wordpress` pour la traçabilité ; auteurs et forums ont leur propre numérotation. Expliquer les collisions, les limites de la relance et la nécessité de recommencer sur une destination remise à zéro après interruption ou changement du moteur. Mentionner séparément les traitements qui recalculent des relations/configurations.

Inclure les prérequis réels des scripts : accès à la base externe, fichiers WordPress, préfixe, patch SPIP-Cli, plugins, PHP applicatif versus PHP >= 8.4.1 des tests verrouillés. Expliquer les codes de sortie et les contrôles éditoriaux, relationnels, médias, URL et accès ; un code 0 ne suffit pas à valider le rendu final.

## 8. Sources et autonomie de la documentation

Utiliser le code et les tests de la révision documentée, les specs accessibles par permaliens, et les sources officielles WordPress sur les versions pertinentes. Associer chaque évolution historique à une source et distinguer l’évolution du stockage de celle de l’interface. Si une affirmation ne peut pas être confirmée, préciser la limite au lieu de l’inventer. Les notes de version WordPress renseignent l’histoire du CMS, pas la couverture du migrateur.

La construction du site requiert Python et les dépendances documentaires verrouillées, indépendamment de PHP, de Composer et de l’exécution de la migration. Les prérequis PHP/Composer concernent le migrateur et sont expliqués dans son parcours d’installation. Les observations propres à une machine de rédaction n’appartiennent ni au design du produit ni aux prérequis du site.

## 9. Validation du livrable

Installer les dépendances documentaires dans un environnement virtuel, fixer les versions choisies, appliquer les réglages de validation de la section 2, puis exécuter `mkdocs build --strict`. Vérifier que toutes les pages de navigation existent et que les liens internes relatifs et ancres sont résolus. Dans une copie temporaire des sources, introduire un lien vers une page inexistante puis une ancre inexistante : chaque construction stricte doit échouer. Retirer ces liens et confirmer que la construction réelle réussit.

Vérifier l’absence des specs, plans et autres sources hors `docs/site/` dans la sortie et dans l’index de recherche. Contrôler les permaliens vers les specs et le code. Vérifier que la recherche référence les pages françaises, que les onglets et diagrammes sont rendus dans le HTML généré et que la révision documentée apparaît sur plusieurs pages. Effectuer une requête HTTP représentative sur le site servi localement.

Relire les états des fonctions contre les traitements du code. Vérifier les sources historiques retenues. Vérifier le diff final : seules la documentation, sa configuration et l’exclusion des sorties générées sont concernées. Aucun déploiement externe ou changement de l’outil dans ce périmètre.

Le résultat fournit les sources maintenables et la sortie statique vérifiée. Les commandes `mkdocs serve` et `mkdocs build --strict`, ainsi que les possibilités de publication statique, sont documentées. La destination de publication peut être choisie ultérieurement ; sa configuration ne bloque pas la création du site.
