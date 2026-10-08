# wp2spip — sous-projet 11 : préparation d'un SPIP

Date : 2026-10-08
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 11 (réalisé avant le sous-projet 3).
Statut : design validé, à planifier.

## 1. Objet

Importer un WordPress suppose aujourd'hui un SPIP déjà installé, avec sale, pages, polyhier et wp2spip actifs, et la base WordPress déclarée comme base externe. wp2spip fournit un **script shell** qui prépare ce SPIP depuis un dossier vide, en enchaînant les commandes existantes de SPIP-Cli, puis peut lancer l'import.

La forme de script s'impose : la commande `wordpress:importer` est déclarée par le plugin wp2spip, que SPIP-Cli ne charge que depuis un SPIP qui le contient déjà. Une préparation qui part de rien ne peut donc pas être une commande de wp2spip.

## 2. Usage

```
outils/preparer_spip.sh --spip <dossier> --wordpress <dossier WordPress> [options]
```

| Option | Rôle | Défaut |
|---|---|---|
| `--spip` | dossier du SPIP à créer, vide ou absent | requis |
| `--wordpress` | dossier du WordPress (lecture de `wp-config.php`, fichiers des médias) | requis |
| `--version-spip` | version de SPIP à télécharger (`X.Y.Z` ou `X.Y`) | `4.4` (dernière publiée) |
| `--base-spip` | `sqlite`, ou `mysql:<base>` | `mysql:` + la base du WordPress, préfixe de tables `spip` |
| `--sql-hote`, `--sql-login`, `--sql-pass` | accès MySQL de SPIP | ceux de `wp-config.php` |
| `--admin-login`, `--admin-email`, `--admin-pass` | premier administrateur SPIP | `admin` ; email `admin_email` du WordPress ; mot de passe aléatoire, affiché au bilan |
| `--adresse` | adresse du site SPIP | adresse du WordPress (`siteurl`) |
| `--wp2spip` | `copie` du dossier de wp2spip où se trouve le script, ou `lien` symbolique (pour développer wp2spip) | `copie` |
| `--spip-cli` | exécutable SPIP-Cli | `spip` du `PATH` |
| `--depot` | dépôt de plugins SVP | `https://plugins.spip.net/depots/principal.xml` |
| `--importer` | lance `wordpress:importer` à la fin | non |
| `-h`, `--help` | aide | |

## 3. Étapes

Chaque étape est annoncée. Le script s'arrête à la première erreur : message, commande en cause, code `1`.

1. **Contrôles préalables**
   - SPIP-Cli exécutable ; `plugins:svp:telecharger` présent, dans sa version corrigée (voir § 5) ;
   - dossier `--spip` absent ou vide ;
   - `wp-config.php` lisible ;
   - préfixe des tables WordPress `wp_`, tant que le sous-projet 6 (`--prefixe`) n'est pas fait ; sinon arrêt avec un message ;
   - connexion MySQL possible avec les accès retenus (et, pour SQLite, extension `pdo_sqlite` présente).
2. **Lecture de `wp-config.php`** : `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` (port éventuel séparé), `$table_prefix`, lus par expressions régulières (`php -r`), **sans exécuter le fichier** ; `siteurl` et `admin_email` lus ensuite dans `wp_options`.
3. **Téléchargement de SPIP** : `core:telecharger spip -R <version> -d <dossier>`, puis `core:preparer` dans le dossier.
4. **Installation de SPIP** : `core:installer` avec serveur, hôte, accès, base, préfixe, administrateur, adresse.
5. **Plugins**
   - `plugins:svp:depoter <dépôt>` ;
   - `plugins:svp:telecharger sale pages polyhier -y` ;
   - wp2spip : copie (ou lien symbolique) du dossier du script dans `plugins/wp2spip` ;
   - `plugins:activer sale pages polyhier wp2spip -y`, puis `plugins:maj:bdd` ;
   - contrôle : `plugins:lister` montre les quatre plugins actifs.

   Albums, Accès restreint et Forum ne sont pas installés ici : `wordpress:importer` les télécharge et les active lui-même si le contenu WordPress en a besoin (spec du sous-projet 3, § 4). Le SPIP préparé reste vierge de ce qui ne sert pas.
6. **Base externe** : `config/wordpress.php` écrit par `spip php:eval` avec les fonctions d'installation de SPIP (`install_fichier_connexion(_DIR_CONNECT . 'wordpress.php', install_connexion(hôte, port, login, mot de passe, base, 'mysql', '', '', ''))`) ; contrôle : lecture de `siteurl` dans `wp_options` par ce serveur.
7. **Import** (avec `--importer`) : `wordpress:importer <dossier WordPress>` depuis le dossier SPIP ; son code de sortie devient celui du script.
8. **Bilan** : dossier, adresse, base, identifiants de l'administrateur, et la commande d'import à lancer si `--importer` n'a pas été donné.

## 4. Hors périmètre

- Configuration du serveur web (nginx, Apache) et du nom de domaine.
- Création de la base MySQL et de son utilisateur : la base doit exister et être accessible.
- Mise à jour d'un SPIP existant : le script ne travaille que dans un dossier vide.

## 5. Dépendances et sécurité

- **SPIP-Cli corrigé** : `plugins:svp:telecharger` ne fonctionne qu'avec les correctifs proposés en amont (sélection du plugin, autorisation, remontée des erreurs). Le contrôle préalable vérifie la commande par un téléchargement à blanc impossible à confondre (préfixe inexistant, qui doit produire l'erreur « n'est pas référencé ») ; sinon arrêt avec un message qui indique la version requise.
- **Mots de passe** : jamais passés sur la ligne de commande d'un sous-processus visible (`ps`) quand c'est évitable : le mot de passe MySQL est transmis à `core:installer` par son option (pas d'alternative dans SPIP-Cli), ce que la documentation du script signale ; le mot de passe administrateur généré n'est affiché qu'au bilan.
- **Fichiers** : le script n'écrit que dans le dossier `--spip` ; le dossier WordPress n'est jamais modifié.

## 6. Validation

- Préparation complète depuis un dossier vide, base **MySQL** (base partagée avec le WordPress, préfixe `spip`) et base **SQLite**, pour les WordPress 6.9 et 7.1, avec `--importer` ; puis `tests/integration/verifier_identifiants.php` à **OK**.
- `--wp2spip lien` : `plugins/wp2spip` est un lien vers le dépôt.
- Cas d'erreur, chacun avec code `1`, message explicite et aucun fichier écrit hors du dossier `--spip` : dossier non vide ; `wp-config.php` illisible ; préfixe de tables autre que `wp_` ; accès MySQL refusés ; SPIP-Cli sans les correctifs.
- Les sites de test de `tests/integration/` peuvent ensuite être recréés avec ce script plutôt qu'à la main.
