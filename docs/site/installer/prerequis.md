# Prérequis

## Choisir le bon environnement

| Usage | Prérequis |
|---|---|
| Utiliser le plugin | SPIP 4.2 à 4.4 d’après le manifeste ; PHP ≥ 8.1 d’après Composer, avec les exigences de la version SPIP retenue |
| Exécuter les tests verrouillés | PHP **≥ 8.4.1**, Composer, extensions demandées par PHPUnit et SQLite pour l’intégration |
| Préparer un SPIP automatiquement | Bash, PHP CLI, SPIP-Cli corrigé, client MySQL pour lire le WordPress, accès aux bases et téléchargements |
| Construire ce site documentaire | Python 3.11 ou ultérieur et `requirements-docs.txt` ; aucun SPIP ni WordPress requis |

La plage WordPress souhaitée est **4–7**. La spec d’ensemble cible **4.9 à 7.x** : les versions 4.0–4.8 demandent une qualification supplémentaire. Voir la [matrice de couverture](../wordpress/compatibilite.md).

## Accès aux données source

Il faut le dossier WordPress contenant `wp-includes/version.php`, `wp-config.php` et les fichiers de `wp-content/uploads/`, ainsi qu’une base lisible déclarée comme base externe dans SPIP. Un export WXR/XML seul ne remplace pas ces entrées : le moteur lit les tables SQL et les fichiers.

Le préfixe est lu dans `wp-config.php` sans l’exécuter ; `--prefixe` permet de fournir une valeur explicite. Les tables consultées sont `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`, `options`, `users`, `usermeta` et `comments`, précédées du préfixe choisi.

## Plugins SPIP

| Plugin | Rôle | Dépendance |
|---|---|---|
| Sale ≥ 1.0 | HTML → syntaxe SPIP | Obligatoire |
| Pages uniques ≥ 2.0 | Pages hors rubriques | Obligatoire |
| Polyhiérarchie ≥ 4.0 | Rubriques secondaires | Obligatoire |
| Albums ≥ 4.0 | Galeries | Selon le contenu |
| a2a ≥ 4.2 | Parenté des pages | Selon le contenu |
| Accès restreint | Contenus privés/protégés | Selon le contenu |
| Forum | Commentaires | Selon le contenu |
| oEmbed | Lecteurs des URL embarquées | Utilisé pour le rendu, à prévoir si nécessaire |

La commande télécharge et active les plugins qu’elle détecte comme requis par le contenu. Le rendu final et l’ensemble des dépendances de votre site restent à contrôler.

## Téléchargements et droits

Composer utilise Packagist et les dépôts SPIP ; SPIP-Cli utilise les serveurs de téléchargement et le dépôt SVP, par défaut `https://plugins.spip.net/depots/principal.xml`. Prévoir leurs destinations et les hébergeurs d’archives associés. Conserver la vérification TLS et celle des paquets.

SPIP doit pouvoir écrire dans ses dossiers de configuration, caches, fichiers et plugins. Garder les accès réels aux bases et les mots de passe dans une configuration locale protégée, hors des sources publiques.

Sources : [paquet](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/paquet.xml), [Composer](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/composer.json), [verrou](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/composer.lock), [tables lues](https://github.com/tech-nova/wordpress-to-spip-migrator/blob/9f08d61f86515d80975cb6fbb228cac0142437f2/inc/wp2spip.php).
