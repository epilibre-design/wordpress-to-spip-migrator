
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
  -u, --update                     Met à jour les contenus déjà migrés avec la version du Wordpress. Peut être utile si le site Wordpress continue d’évoluer.
  -h, --help                       Display help for the given command. When no command is given display help for the list command
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi|--no-ansi             Force (or disable --no-ansi) ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Pour lancer la commande, vous devez avoir préalablement ajouté la base de données du Wordpress en tant que base externe dans votre SPIP, et fournir en argument le dossier où se trouve les fichiers du Wordpress.
  
  Lorsqu’un contenu est déjà importé (auteur, article, etc), une trace est gardé et il ne sera jamais réimporté. Vous pouvez donc lancer la commande plusieurs fois sans soucis, seul les nouveaux contenus jamais importés seront migrés. Cela permet notamment de continuer à lancer la commande si le site Wordpress continue d’évoluer.
```

## Importer un site qui continue de vivre
Par défaut quand on lance la commande plusieurs fois, les contenus déjà importés ne le sont pas plusieurs fois. Avec l'option `--update` cela permet de mettre à jour le contenu déjà importé avec le contenu plus à jour du Wordpress, si jamais il continuait d'évoluer.

## Pour les devs
Chaque contenu possible à importer est implémenté dans des traitements `wp2spip_<traitement>` dans des fichiers `wp2spip/<traitement>.php`.

Il est possible de créer des variantes de ces fonctions pour des versions précises de Wordpress, afin que la même commande sache importer toutes les versions suivant leurs évolutions.

Pour cela, la commande cherche les traitements dans cet ordre :

- `wp2spip_<traitement>_<versionX>_<versionY>`
- `wp2spip_<traitement>_<versionX>`
- `wp2spip_<traitement>`