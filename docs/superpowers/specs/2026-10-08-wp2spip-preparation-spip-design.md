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
| `--base-spip` | `sqlite`, ou `mysql:<base>` (base existante) | `sqlite` : aucune écriture dans la base du WordPress, qui reste une source figée |
| `--base-partagee` | autorise `mysql:<base>` à être la base même du WordPress (tables SPIP à côté des tables `wp_`) | non : sans cette option, une base MySQL identique à celle du WordPress est refusée |
| `--sql-hote`, `--sql-login` | accès MySQL de SPIP (avec `mysql:`) | ceux de `wp-config.php` |
| variable d'environnement `WP2SPIP_SQL_PASS` | mot de passe MySQL de SPIP (avec `mysql:`) | celui de `wp-config.php` |
| `--admin-login`, `--admin-email` | premier administrateur SPIP | `admin` ; email `admin_email` du WordPress |
| variable d'environnement `WP2SPIP_ADMIN_PASS` | mot de passe de l'administrateur | aléatoire, affiché au bilan |
| `--adresse` | adresse du site SPIP | adresse du WordPress (`siteurl`) |
| `--wp2spip` | `copie` du dossier de wp2spip où se trouve le script, ou `lien` symbolique (pour développer wp2spip) | `copie` |
| `--spip-cli` | exécutable SPIP-Cli | `spip` du `PATH` |
| `--droits` | droits des dossiers d'écriture de SPIP, passés à `core:preparer` | `775` (et non le `777` par défaut de SPIP-Cli) |
| `--depot` | dépôt de plugins SVP | `https://plugins.spip.net/depots/principal.xml` |
| `--importer` | lance `wordpress:importer` à la fin | non |
| `-h`, `--help` | aide | |

## 3. Étapes

Chaque étape est annoncée. Le script s'arrête à la première erreur : message, commande en cause, code `1`.

1. **Contrôles préalables**
   - SPIP-Cli exécutable, et commande `plugins:svp:telecharger` présente (sa version est contrôlée plus tard, § 3, étape 5, car elle ne fonctionne que dans un SPIP installé) ;
   - dossier `--spip` absent ou vide ;
   - `wp-config.php` lisible ;
   - préfixe des tables WordPress `wp_`, tant que le sous-projet 6 (`--prefixe`) n'est pas fait ; sinon arrêt avec un message ;
   - base SPIP : avec `sqlite`, extension `pdo_sqlite` présente ; avec `mysql:<base>`, base accessible, différente de celle du WordPress sauf `--base-partagee`, et **sans table au préfixe `spip_`** (arrêt sinon) ;
   - base du WordPress lisible avec les accès de `wp-config.php`.
2. **Lecture de `wp-config.php`** : `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` (port éventuel séparé), `$table_prefix`, lus par expressions régulières (`php -r`), **sans exécuter le fichier** ; `siteurl` et `admin_email` lus ensuite dans `wp_options`.
3. **Téléchargement de SPIP** : `core:telecharger spip -R <version> -d <dossier>`, puis `core:preparer --droits <droits>` dans le dossier.
4. **Installation de SPIP** : `core:installer` avec serveur, hôte, accès, base, préfixe, administrateur et adresse ; l'administrateur y reçoit un **mot de passe jetable** aléatoire, puis son vrai mot de passe est posé par `spip php:eval`, qui le lit dans une variable d'environnement (`getenv()`) et non dans le code passé en argument.
5. **Plugins**
   - contrôle de la version corrigée de `plugins:svp:telecharger` : téléchargement d'un préfixe inexistant, qui doit produire l'erreur « n'est pas référencé » ; sinon arrêt, avec un message qui indique la version requise et précise que le SPIP est déjà installé dans le dossier (à supprimer avant de relancer) ;
   - `plugins:svp:depoter <dépôt>` ;
   - `plugins:svp:telecharger sale pages polyhier -y` ;
   - wp2spip : copie (ou lien symbolique) du dossier du script dans `plugins/wp2spip` ;
   - `plugins:activer sale pages polyhier wp2spip -y`, puis `plugins:maj:bdd` ;
   - contrôle : `plugins:lister` montre les quatre plugins actifs.

   Albums, Accès restreint et Forum ne sont pas installés ici : `wordpress:importer` les télécharge et les active lui-même si le contenu WordPress en a besoin (spec du sous-projet 3, § 4). Le SPIP préparé reste vierge de ce qui ne sert pas.
6. **Base externe** : `config/wordpress.php` écrit par `spip php:eval` avec les fonctions d'installation de SPIP (`install_fichier_connexion(_DIR_CONNECT . 'wordpress.php', install_connexion(hôte, port, login, getenv('WP2SPIP_WP_PASS'), base, 'mysql', '', '', ''))`) : le mot de passe WordPress est transmis par la variable d'environnement du sous-processus, jamais dans le code passé en argument ; contrôle : lecture de `siteurl` dans `wp_options` par ce serveur.
7. **Import** (avec `--importer`) : `wordpress:importer <dossier WordPress>` depuis le dossier SPIP ; son code de sortie devient celui du script.
8. **Bilan** : dossier, adresse, base, identifiants de l'administrateur, et la commande d'import à lancer si `--importer` n'a pas été donné.

## 4. Hors périmètre

- Configuration du serveur web (nginx, Apache) et du nom de domaine.
- Création de la base MySQL et de son utilisateur : la base doit exister et être accessible.
- Mise à jour d'un SPIP existant : le script ne travaille que dans un dossier vide.

## 5. Dépendances et sécurité

- **SPIP-Cli corrigé** : `plugins:svp:telecharger` ne fonctionne qu'avec les correctifs proposés en amont (sélection du plugin, autorisation, remontée des erreurs). Le contrôle préalable vérifie la commande par un téléchargement à blanc impossible à confondre (préfixe inexistant, qui doit produire l'erreur « n'est pas référencé ») ; sinon arrêt avec un message qui indique la version requise.
- **Mots de passe**, exposition réelle :
  - mot de passe de la base WordPress (pour `config/wordpress.php`) et mot de passe de l'administrateur : transmis aux sous-processus par variable d'environnement, jamais en argument ; ils n'apparaissent donc pas dans la liste des processus (`ps`) ;
  - le mot de passe de l'administrateur passé à `core:installer` est un mot de passe **jetable**, remplacé aussitôt : son exposition (argument visible par `ps` pendant l'installation) est sans conséquence ;
  - le **mot de passe MySQL de SPIP** (avec `mysql:` seulement) n'a pas d'autre voie que l'option de `core:installer` : il est visible dans la liste des processus pendant l'installation. La documentation du script le signale ; avec `sqlite` (défaut), il n'y en a pas ;
  - le mot de passe de l'administrateur, quand il est généré, est affiché au bilan, et seulement là.
- **Fichiers** : le script n'écrit que dans le dossier `--spip` ; le dossier WordPress n'est jamais modifié.

## 6. Validation

- Préparation complète depuis un dossier vide, base **SQLite** (défaut), base **MySQL distincte**, et base **partagée** avec le WordPress (`--base-partagee`, comme les sites de test actuels), pour les WordPress 6.9 et 7.1, avec `--importer` ; puis `tests/integration/verifier_identifiants.php` à **OK**.
- `--wp2spip lien` : `plugins/wp2spip` est un lien vers le dépôt.
- Cas d'erreur, chacun avec code `1` et message explicite : dossier non vide, `wp-config.php` illisible, préfixe de tables autre que `wp_`, accès MySQL refusés, base MySQL identique à celle du WordPress sans `--base-partagee`, base contenant déjà des tables `spip_` (aucun fichier écrit) ; SPIP-Cli sans les correctifs (SPIP installé dans le dossier, rien écrit ailleurs, message qui le dit).
- `ps` pendant la préparation : ni le mot de passe WordPress ni le vrai mot de passe de l'administrateur n'apparaissent dans les arguments d'un processus.
- Les sites de test de `tests/integration/` peuvent ensuite être recréés avec ce script plutôt qu'à la main.
