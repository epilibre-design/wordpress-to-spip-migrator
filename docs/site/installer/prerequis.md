# Prérequis

## Choisir le bon environnement

| Usage | Prérequis |
|---|---|
| Utiliser le plugin | SPIP 4.2 à 4.4 d’après le manifeste ; **PHP ≥ 8.4**, avec l’extension DOM et `Dom\HTMLDocument` pour la conversion HTML5 |
| Exécuter les tests verrouillés | PHP **≥ 8.4.1**, Composer, extensions demandées par PHPUnit et SQLite pour l’intégration |
| Préparer un SPIP automatiquement | Bash, PHP CLI, SPIP-Cli corrigé, client MySQL pour lire le WordPress, accès aux bases et téléchargements |
| Construire ce site documentaire | Python 3.11 ou ultérieur et `requirements-docs.txt` ; aucun SPIP ni WordPress requis |

La plage WordPress visée est **4.9 à 7.x** : les versions 4.0–4.8 demandent une qualification supplémentaire. Voir la [matrice de couverture](../wordpress/compatibilite.md).

## Accès aux données source

Il faut le dossier WordPress contenant `wp-includes/version.php`, `wp-config.php` et les fichiers de `wp-content/uploads/`, ainsi qu’une base lisible déclarée comme base externe dans SPIP. Un export WXR/XML seul ne remplace pas ces entrées : le moteur lit les tables SQL et les fichiers.

Le préfixe est lu dans `wp-config.php` sans l’exécuter ; `--prefixe` permet de fournir une valeur explicite. Les tables consultées sont `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`, `options`, `users`, `usermeta` et `comments`, précédées du préfixe choisi.

## Plugins SPIP

| Plugin | Rôle | Dépendance |
|---|---|---|
| Pages uniques ≥ 2.0 | Pages hors rubriques | Obligatoire |
| Polyhiérarchie ≥ 4.0 | Rubriques secondaires | Obligatoire |
| Albums ≥ 4.0 | Galeries | Selon le contenu |
| a2a ≥ 4.2 | Parenté des pages | Selon le contenu |
| Accès restreint | Contenus privés/protégés | Selon le contenu |
| Forum | Commentaires | Selon le contenu |
| oEmbed | Lecteurs des URL embarquées | Utilisé pour le rendu, à prévoir si nécessaire |

Sale n’est plus une dépendance : le HTML est converti par `inc/wp2spip_html.php`, inclus dans wp2spip.

La commande télécharge et active les plugins qu’elle détecte comme requis par le contenu, ainsi que leurs dépendances. Les extensions [wp2spip_yoast et wp2spip_acf](../comprendre/extensions.md) ajoutent respectivement SEO et Champs Extras Interface selon les données présentes. Ces extensions du migrateur s’installent séparément et doivent être actives avant l’import. Le rendu final et l’ensemble des dépendances de votre site restent à contrôler.

## Téléchargements et droits

Composer utilise Packagist et les dépôts SPIP ; SPIP-Cli utilise les serveurs de téléchargement et le dépôt SVP, par défaut `https://plugins.spip.net/depots/principal.xml`. Prévoir leurs destinations et les hébergeurs d’archives associés. Conserver la vérification TLS et celle des paquets.

SPIP doit pouvoir écrire dans ses dossiers de configuration, caches, fichiers et plugins. Garder les accès réels aux bases et les mots de passe dans une configuration locale protégée, hors des sources publiques.

Sources : [paquet](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/paquet.xml), [Composer](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.json), [verrou](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/composer.lock), [tables lues](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip.php).
