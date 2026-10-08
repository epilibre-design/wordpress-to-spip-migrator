
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

## Refaire un import
L'import part d'un Wordpress figé : une copie du site, ou un site qui n'évolue plus pendant la migration. Relancer la commande n'importe que les contenus pas encore importés, et ne modifie pas ceux qui le sont déjà.

Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.

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