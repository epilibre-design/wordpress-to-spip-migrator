
# Wordpress 2 SPIP

Ce plugin fournit des outils en ligne de commande pour importer le contenu d'un Wordpress dans un SPIP.

## Installation
Tout est basé sur une commande [SPIP-Cli](https://contrib.spip.net/SPIP-Cli) `wordpress:importer`, il faut donc l'installer au préalable.

Afin d'importer le même type de fonctionnalités que dans Wordpress, il nécessite aussi Polyhiérarchie et Pages uniques, ainsi que la librairie Sale pour transformer au mieux le HTML en syntaxe SPIP.

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
Depuis un dossier vide, `outils/preparer_spip.sh` télécharge et installe SPIP, ajoute sale, pages, polyhier et wp2spip, déclare la base du Wordpress comme base externe, et peut lancer l'import :

``` bash
$ SPIP_ADMIN_PASS='…' outils/preparer_spip.sh --spip /chemin/du/spip --wordpress /chemin/du/wordpress --importer
```

Les accès à la base sont lus dans le `wp-config.php` du Wordpress, sans l'exécuter. La base de SPIP est en SQLite par défaut (`--base-spip mysql:<base>` pour une base MySQL existante). Sans `SPIP_ADMIN_PASS`, un mot de passe est généré et affiché à la fin. Toutes les options : `outils/preparer_spip.sh --help`.

Il faut SPIP-Cli avec les correctifs de `plugins:svp:telecharger` (sélection du plugin, autorisation). `core:installer` ne recevant les mots de passe qu'en argument, ils sont brièvement visibles dans la liste des processus pendant l'installation, et il affiche celui de l'administrateur.

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

Si le plugin [Accès restreint](https://contrib.spip.net/Acces-Restreint-3-0) est actif, ils sont publiés dans une zone réservée aux visiteurs identifiés (une zone pour les contenus privés, une pour les contenus protégés). Les mots de passe Wordpress ne sont pas repris. Sinon, ils restent non publiés.

## Pour les devs
Chaque contenu possible à importer est implémenté dans des traitements `wp2spip_<traitement>` dans des fichiers `wp2spip/<traitement>.php`.

Il est possible de créer des variantes de ces fonctions pour des versions précises de Wordpress, afin que la même commande sache importer toutes les versions suivant leurs évolutions.

Pour cela, la commande cherche les traitements dans cet ordre :

- `wp2spip_<traitement>_<versionX>_<versionY>`
- `wp2spip_<traitement>_<versionX>`
- `wp2spip_<traitement>`
Une extension peut ajouter ses propres traitements avec le pipeline `wp2spip_traitements`, qui reçoit la liste ordonnée des traitements : elle y insère les siens à la position voulue, et fournit le fichier `wp2spip/<traitement>.php` correspondant. L'ancien nom du pipeline, `w2spip_traitements`, est toujours appelé.

Un traitement signale un échec en retournant `false` : la commande s'arrête et retourne le code de sortie 1.
