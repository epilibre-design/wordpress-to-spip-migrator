
# Wordpress 2 SPIP

Ce plugin fournit des outils en ligne de commande pour importer le contenu d'un Wordpress dans un SPIP.

## Installation
Tout est basé sur une commande [SPIP-Cli](https://contrib.spip.net/SPIP-Cli) `wordpress:importer`, il faut donc l'installer au préalable.

Afin d'importer le même type de fonctionnalités que dans Wordpress, il nécessite aussi Polyhiérarchie et Pages uniques. Il demande PHP 8.4 : le HTML de Wordpress est analysé par l'analyseur HTML5 de PHP (`Dom\HTMLDocument`) pour être converti en raccourcis SPIP (voir « Conversion du HTML »).

Il faut ensuite déclarer la base de données SQL du Wordpress en tant que base externe dans l'admin de SPIP (le nom "wordpress" étant reconnu par défaut, sinon il faudra le préciser dans les options).

Enfin la commande est auto-documentée avec `help` ou `-h` : 
``` bash
$ spip help wordpress:importer
Description:
  Importe un site Wordpress dans un site SPIP

Usage:
  wordpress:importer [options] [--] <dir_wordpress>

Arguments:
  dir_wordpress                    Chemin vers le dossier d’installation du Wordpress

Options:
  -b, --base[=BASE]                Identifiant de la base Wordpress déclarée dans SPIP [default: "wordpress"]
  -t, --traitements[=TRAITEMENTS]  Liste de traitements séparés par des virgules, si on veut n’en lancer que certains.
  -i, --info[=INFO]                Affiche la version du Wordpress et les traitements disponibles.
      --prefixe=PREFIXE            Préfixe des tables Wordpress, s’il diffère de celui de wp-config.php ou n’y est pas lisible.
      --garder-adresse             Ne pas remplacer l’adresse du site SPIP par celle du Wordpress.
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Pour lancer la commande, vous devez avoir préalablement ajouté la base de données du Wordpress en tant que base externe dans votre SPIP, et fournir en argument le dossier où se trouve les fichiers du Wordpress.
  
  Lorsqu’un contenu est déjà importé (auteur, article, etc), une trace est gardée et il ne sera jamais réimporté : on peut relancer la commande, ou un traitement seul. L’import part d’un Wordpress figé. Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.
```

## Préparer un SPIP
Depuis un dossier vide, `outils/preparer_spip.sh` télécharge et installe SPIP, ajoute pages, polyhier et wp2spip, déclare la base du Wordpress comme base externe, et peut lancer l'import :

``` bash
$ SPIP_ADMIN_PASS='…' outils/preparer_spip.sh --spip /chemin/du/spip --wordpress /chemin/du/wordpress --importer
```

Les accès à la base sont lus dans le `wp-config.php` du Wordpress, sans l'exécuter. La base de SPIP est en SQLite par défaut (`--base-spip mysql:<base>` pour une base MySQL existante). Sans `SPIP_ADMIN_PASS`, un mot de passe est généré et affiché à la fin. Toutes les options : `outils/preparer_spip.sh --help`.

Il faut SPIP-Cli avec les correctifs de `plugins:svp:telecharger` (sélection du plugin, autorisation). `core:installer` ne recevant les mots de passe qu'en argument, ils sont brièvement visibles dans la liste des processus pendant l'installation, et il affiche celui de l'administrateur.

## Préfixe des tables
L'import lit le préfixe des tables Wordpress (`$table_prefix`) dans le `wp-config.php` du dossier fourni, sans l'exécuter. S'il y est absent, calculé ou affecté plusieurs fois, l'option `--prefixe` est exigée ; elle remplace aussi un préfixe lu. Avant tout traitement, l'import vérifie le format du préfixe et la présence des tables qu'il lit, puis le note dans le SPIP : un SPIP importé depuis un préfixe refuse d'importer depuis un autre (le remettre à zéro). `outils/preparer_spip.sh` transmet le préfixe lu à l'import.

## Conversion du HTML
Le HTML de Wordpress (contenus, commentaires, descriptions, légendes) est converti en raccourcis SPIP par wp2spip lui-même (`inc/wp2spip_html.php`), à partir de l'arbre que construit l'analyseur HTML5 de PHP : paragraphes, sauts de ligne, gras et italique, intertitres, liens, listes imbriquées, tableaux, citations, code (`<cadre>`, `<code>`), texte préformaté (`<poesie>`), filets ; le reste du HTML (images, lecteurs, blocs de mise en page, `<sup>`, `<span>`…) est gardé tel quel. Les retours à la ligne suivent Wordpress : pour l'éditeur classique, les commentaires et les descriptions, une ligne vide sépare deux paragraphes et un retour simple devient un saut de ligne ; pour les contenus à blocs, ils sont sans effet.

Un texte que Wordpress affiche tel quel l'est aussi par SPIP : les caractères des raccourcis SPIP (`{`, `}`, `[`, `]`, `|`, `~`, un `-` ou un `_` en début de ligne) et du HTML (`<`, `>`, `&`) y sont écrits en entités. Une page Wordpress qui documente les raccourcis de SPIP les montre donc toujours, sans qu'ils soient interprétés.

## Blocs de l'éditeur et galeries
Les blocs de l'éditeur Wordpress sont convertis : images en `<imgN>` avec leur alignement (la légende devient le descriptif du document), médias en `<docN>`, mise en page (colonnes, groupes, couvertures, boutons…) gardée avec ses seules classes `wp-block-…`, que le squelette peut styler, contenus embarqués en URL seule sur sa ligne (le plugin oEmbed en fait un lecteur). Chaque galerie (bloc ou raccourci `[gallery]`) devient un album du plugin Albums, inséré par `<albumN>`. Les blocs dynamiques (derniers articles, recherche…), qui n'enregistrent rien dans le contenu, sont retirés. Le bilan de `importer_articles` détaille ces conversions.

## Plugins requis
Avant le premier traitement, l'import télécharge et active les plugins dont le contenu a besoin : Albums s'il y a une galerie, Accès restreint s'il y a des contenus privés ou protégés, Forum s'il y a des commentaires, a2a s'il y a des pages enfants ; puis il se relance. S'ils manquent, il crée le dossier `plugins/auto` (avec les droits de `plugins/`) et déclare le dépôt standard `https://plugins.spip.net/depots/principal.xml` (constante `_WP2SPIP_DEPOT_SVP`, modifiable dans `mes_options.php`). Il faut SPIP-Cli avec les correctifs de `plugins:svp:telecharger`. En cas d'échec, rien n'est importé et les commandes à lancer à la main sont affichées.

## Refaire un import
L'import part d'un Wordpress figé : une copie du site, ou un site qui n'évolue plus pendant la migration. Relancer la commande n'importe que les contenus pas encore importés, et ne modifie pas ceux qui le sont déjà.

Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.

La commande retourne le code de sortie 1 si un traitement échoue (l'import s'arrête alors, les traitements suivants dépendant des précédents) ou si `--traitements` contient un nom inconnu, 0 sinon.

## Identifiants
Les articles, pages, rubriques et documents SPIP reprennent l'identifiant de leur contenu Wordpress (article 42 = contenu Wordpress 42). Les liens internes (`?p=`, `?page_id=`, slug) deviennent donc des raccourcis SPIP même vers un contenu importé plus tard. L'import doit se faire dans un SPIP vierge : si un identifiant est déjà pris, le traitement concerné s'arrête avant de créer le moindre objet de ce type, et la commande retourne le code de sortie 1. Les traitements précédents ont pu créer des objets : remettre le SPIP à zéro avant de relancer.

Les auteurs gardent la numérotation de SPIP. Un login Wordpress refusé par SPIP (déjà pris, par exemple par l'administrateur créé à l'installation, ou trop court) est remplacé par le premier login libre parmi `login-wp`, `login-wp2`… ; le login attribué est signalé dans le bilan de l'import.

## Adresse du site
Par défaut, l'adresse du site SPIP prend celle du Wordpress. L'option `--garder-adresse` conserve celle du SPIP.

## Contenus privés et protégés par mot de passe
Les contenus privés ou protégés par mot de passe dans Wordpress ne sont jamais publiés tels quels.

Ils sont publiés dans une zone du plugin [Accès restreint](https://contrib.spip.net/Acces-Restreint-3-0), que l'import installe (voir « Plugins requis »), réservée aux visiteurs identifiés (une zone pour les contenus privés, une pour les contenus protégés). Les mots de passe Wordpress ne sont pas repris.

## Étiquettes
Les étiquettes Wordpress deviennent des mots-clés du groupe « Étiquettes » (créé par l'import), avec l'identifiant de l'étiquette, y compris celles qui n'étiquettent aucun contenu. Ils sont liés aux articles et pages étiquetés, quel que soit leur statut, et les mots-clés sont activés sur les articles. Si un contenu étiqueté manque dans SPIP, ou si un mot venant d'une étiquette est hors du groupe, le traitement `importer_mots` échoue et la commande retourne le code de sortie 1.

## Hiérarchie des pages
Les pages Wordpress deviennent des pages uniques (plugin Pages), sans rubrique. Leur hiérarchie est gardée par des liens du plugin [a2a](https://contrib.spip.net/Le-plugin-a2a-pour-lier-des-articles), que l'import installe s'il y a des pages enfants : un lien de type `sous_page` de chaque page parente vers chacune de ses pages enfants, dont le rang suit l'ordre Wordpress (ordre de la page, puis titre). wp2spip ne fournit pas de squelette : à chaque site d'exploiter ces liens, par exemple ainsi :

```html
<!-- Sous-pages d'une page, dans l'ordre Wordpress -->
<BOUCLE_sous_pages(ARTICLES_LIES){id_article}{type_liaison=sous_page}{par rang}>
	<BOUCLE_sous_page(ARTICLES){id_article=#ID_ARTICLE_LIE}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_sous_page>
</BOUCLE_sous_pages>

<!-- Page parente d'une page -->
<BOUCLE_parente(ARTICLES_LIES){id_article_lie=#ID_ARTICLE}{type_liaison=sous_page}>
	<BOUCLE_page_parente(ARTICLES){id_article}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_page_parente>
</BOUCLE_parente>
```

Le type `sous_page` est ajouté à la configuration d'a2a ; il y reste après la désinstallation de wp2spip. Si une page manque dans SPIP, ou si a2a ne crée pas un lien (les deux pages déjà liées par un autre type, liaisons multiples désactivées), le traitement `importer_hierarchie_pages` échoue et la commande retourne le code de sortie 1.

## Tests
Les tests demandent PHP 8.4 et Composer. Depuis la racine du dépôt :

``` bash
$ composer install
$ composer tests-unit           # tests unitaires, sans SPIP
$ composer install-spip-test    # SPIP 4.4 SQLite dans vendor/spip/spip, plugins requis, wp2spip lié (réseau, une fois)
$ composer tests-integration    # tests dans ce SPIP, sur une base WordPress SQLite de test
```

`composer install-spip-test` applique à SPIP-Cli de `vendor/` les correctifs de `plugins:svp:telecharger` (`tests/spip-cli.patch`). Les tests d'intégration lisent une base WordPress construite à partir de `tests/integration/data/` et retirent ce qu'ils créent : ils se relancent sur le même état.

`composer tests-import` importe complètement, depuis un dossier vide, deux WordPress installés en français avec le contenu *Theme Unit Test* (6.9 et 7.1, dossiers `$WP6` et `$WP7` de `tests/integration/environnement.sh`, à créer d'après `environnement.exemple.sh`), et compare chaque import à `tests/integration/references/theme-unit-test.tsv`, où l'adresse du site et la date d'installation de WordPress sont normalisées. Une évolution de l'import met la référence à jour avec `tests/integration/valider.sh <dossier WordPress> --mettre-a-jour` ; le diff de la référence se relit dans le même commit.

## Pour les devs
Chaque contenu possible à importer est implémenté dans des traitements `wp2spip_<traitement>` dans des fichiers `wp2spip/<traitement>.php`.

Il est possible de créer des variantes de ces fonctions pour des versions précises de Wordpress, afin que la même commande sache importer toutes les versions suivant leurs évolutions.

Pour cela, la commande cherche les traitements dans cet ordre :

- `wp2spip_<traitement>_<versionX>_<versionY>`
- `wp2spip_<traitement>_<versionX>`
- `wp2spip_<traitement>`
Une extension peut ajouter ses propres traitements avec le pipeline `wp2spip_traitements`, qui reçoit la liste ordonnée des traitements : elle y insère les siens à la position voulue, et fournit le fichier `wp2spip/<traitement>.php` correspondant. L'ancien nom du pipeline, `w2spip_traitements`, est toujours appelé.

Un traitement signale un échec en retournant `false` : la commande s'arrête et retourne le code de sortie 1.

Les pipelines `wp2spip_bloc` (conversion d'un type de bloc : `args` le bloc, `data` le texte produit ou `null`) et `wp2spip_plugins_requis` (plugins requis par le contenu) permettent à une extension de compléter ces deux étapes.
