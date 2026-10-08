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
| variable d'environnement `SPIP_DB_PASS` | mot de passe MySQL de SPIP (avec `mysql:`) | celui de `wp-config.php` |
| `--admin-login`, `--admin-email` | premier administrateur SPIP | `admin` ; email `admin_email` du WordPress |
| variable d'environnement `SPIP_ADMIN_PASS` | mot de passe de l'administrateur | aléatoire, affiché au bilan |
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
   - SPIP-Cli exécutable ; `core:installer` corrigé (§ 5), ce que montre son aide (`spip help core:installer`, sans SPIP installé) ; commande `plugins:svp:telecharger` présente (son résultat est contrôlé à l'étape 5, car elle ne fonctionne que dans un SPIP installé) ;
   - dossier `--spip` absent ou vide ;
   - `wp-config.php` lisible ;
   - préfixe des tables WordPress `wp_`, tant que le sous-projet 6 (`--prefixe`) n'est pas fait ; sinon arrêt avec un message ;
   - base SPIP : avec `sqlite`, extension `pdo_sqlite` présente ; avec `mysql:<base>`, base accessible, différente de celle du WordPress sauf `--base-partagee`, et **sans table au préfixe `spip_`** (arrêt sinon) ;
   - base du WordPress lisible avec les accès de `wp-config.php`.
2. **Lecture de `wp-config.php`** : `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` (port éventuel séparé), `$table_prefix`, lus par expressions régulières (`php -r`), **sans exécuter le fichier** ; `siteurl` et `admin_email` lus ensuite dans `wp_options`.
3. **Téléchargement de SPIP** : `core:telecharger spip -R <version> -d <dossier>`, puis `core:preparer --droits <droits>` dans le dossier.
4. **Installation de SPIP** : `core:installer` avec serveur, hôte, login, base, préfixe, administrateur et adresse ; les deux mots de passe (MySQL de SPIP, administrateur) lui sont transmis par les variables d'environnement `SPIP_DB_PASS` et `SPIP_ADMIN_PASS`, que lit SPIP-Cli corrigé (§ 5), et jamais en argument.
5. **Plugins**
   - `plugins:svp:depoter <dépôt>` ;
   - `plugins:svp:telecharger sale pages polyhier -y` ;
   - **contrôle du téléchargement**, qui ne se fie pas au seul code de sortie : code non nul (SPIP-Cli corrigé, § 5), ou l'un des préfixes sans `paquet.xml` correspondant (`prefix="…"`) sous `plugins/` → arrêt, avec un message qui indique la version de SPIP-Cli requise et précise que le SPIP est déjà installé dans le dossier (à supprimer avant de relancer) ;
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

- **SPIP-Cli corrigé** : `plugins:svp:telecharger` ne fonctionne qu'avec les correctifs déjà proposés en amont (sélection du plugin, autorisation, remontée des erreurs). Son résultat est contrôlé après coup, sur le disque (§ 3, étape 5), plutôt que par un test préalable : la commande ne démarre que dans un SPIP installé, et seul un téléchargement réel prouve qu'elle fonctionne.
- **Mots de passe** : aucun ne passe en argument d'un processus, ni dans le code transmis à `php:eval` ; ils n'apparaissent donc pas dans la liste des processus (`ps`) :
  - mot de passe MySQL de SPIP et mot de passe de l'administrateur : variables d'environnement `SPIP_DB_PASS` et `SPIP_ADMIN_PASS` de `core:installer` (SPIP-Cli corrigé, ci-dessous) ;
  - mot de passe de la base WordPress (pour `config/wordpress.php`) : variable d'environnement `WP2SPIP_WP_PASS`, lue par `getenv()` dans le code de `php:eval` ;
  - le mot de passe de l'administrateur, quand il est généré, est affiché au bilan, et seulement là.
- **Correctifs de SPIP-Cli**, proposés en amont comme ceux déjà faits pour `plugins:svp:telecharger`, sur le même dépôt de travail :
  - `core:installer` lit `SPIP_DB_PASS` quand `--db-pass` n'est pas donné, et `SPIP_ADMIN_PASS` quand `--admin-pass` ne l'est pas ; l'aide des deux options mentionne la variable (c'est ce que vérifie le contrôle préalable du script) ;
  - `core:installer` n'affiche plus le mot de passe de l'administrateur quand il vient de l'environnement (aujourd'hui : « … admin du site (ID N) avec le mot de passe « … » ») ;
  - `core:installer` retourne un échec (code `1`) si l'administrateur n'a pas pu être créé (aujourd'hui : message d'erreur, puis la commande continue et retourne `0`) ;
  - `plugins:svp:telecharger` retourne un échec (code `1`) si un plugin demandé n'est pas référencé ou n'a pas pu être téléchargé (aujourd'hui : messages d'erreur, code `0`).
- **Sorties** : le script ne garde aucune sortie de SPIP-Cli dans un fichier journal ; elles s'affichent seulement dans le terminal.
- **Fichiers** : le script n'écrit que dans le dossier `--spip` ; le dossier WordPress n'est jamais modifié.

## 6. Validation

- Préparation complète depuis un dossier vide, base **SQLite** (défaut), base **MySQL distincte**, et base **partagée** avec le WordPress (`--base-partagee`, comme les sites de test actuels), pour les WordPress 6.9 et 7.1, avec `--importer` ; puis `tests/integration/verifier_identifiants.php` à **OK**.
- `--wp2spip lien` : `plugins/wp2spip` est un lien vers le dépôt.
- Cas d'erreur, chacun avec code `1` et message explicite : dossier non vide, `wp-config.php` illisible, préfixe de tables autre que `wp_`, accès MySQL refusés, base MySQL identique à celle du WordPress sans `--base-partagee`, base contenant déjà des tables `spip_` (aucun fichier écrit) ; `core:installer` sans le correctif des mots de passe (aucun fichier écrit) ; plugin non téléchargé, simulé par un préfixe inexistant ajouté à la liste (SPIP installé dans le dossier, rien écrit ailleurs, message qui le dit).
- `ps` pendant la préparation, en MySQL : aucun des trois mots de passe (WordPress, MySQL de SPIP, administrateur) n'apparaît dans les arguments d'un processus.
- Correctifs de SPIP-Cli : `core:installer` sans `--db-pass` ni `--admin-pass`, variables posées, installe avec ces mots de passe sans afficher celui de l'administrateur ; option donnée, l'option l'emporte ; création de l'administrateur impossible (login trop court) → code `1` ; `plugins:svp:telecharger` d'un préfixe inexistant → code `1`.
- Les sites de test de `tests/integration/` peuvent ensuite être recréés avec ce script plutôt qu'à la main.
