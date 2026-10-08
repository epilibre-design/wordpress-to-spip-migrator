# wp2spip — sous-projet 11 : préparation d'un SPIP

Date : 2026-10-08
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 11 (réalisé avant le sous-projet 3).
Statut : design validé, planifié (plan : `docs/superpowers/plans/2026-10-08-wp2spip-preparation-spip.md`).

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
| variable d'environnement `SPIP_ADMIN_PASS` | mot de passe de l'administrateur | aléatoire, affiché au bilan ; jamais `adminadmin`, la valeur par défaut de SPIP-Cli |
| `--adresse` | adresse du site SPIP | adresse du WordPress (`siteurl`) |
| `--wp2spip` | `copie` du dossier de wp2spip où se trouve le script, ou `lien` symbolique (pour développer wp2spip) | `copie` |
| `--spip-cli` | exécutable SPIP-Cli | `spip` du `PATH` |
| `--droits` | droits des dossiers d'écriture de SPIP, passés à `core:preparer` | `775` (et non le `777` par défaut de SPIP-Cli) |
| `--depot` | dépôt de plugins SVP | `https://plugins.spip.net/depots/principal.xml` |
| `--importer` | lance `wordpress:importer` à la fin | non |
| `-h`, `--help` | aide | |

## 3. Étapes

Chaque étape est annoncée. Les codes de sortie de SPIP-Cli ne couvrent pas tous les échecs (`core:installer` continue quand l'administrateur n'a pas pu être créé, `plugins:svp:telecharger` et `plugins:activer` retournent toujours `0`) : après chaque étape, le script **contrôle son résultat** dans le SPIP. Il s'arrête à la première commande en échec ou au premier contrôle raté : message explicite, code `1`.

1. **Contrôles préalables**
   - SPIP-Cli exécutable, commandes `core:installer` et `plugins:svp:telecharger` présentes (`spip list`, sans SPIP installé) ; le bon fonctionnement de `plugins:svp:telecharger` ne se voit qu'à l'étape 5, sur un SPIP installé ;
   - dossier `--spip` absent ou vide ;
   - `wp-config.php` lisible ;
   - préfixe des tables WordPress `wp_`, tant que le sous-projet 6 (`--prefixe`) n'est pas fait ; sinon arrêt avec un message ;
   - base SPIP : avec `sqlite`, extension `pdo_sqlite` présente ; avec `mysql:<base>`, base accessible, différente de celle du WordPress sauf `--base-partagee`, et **sans table au préfixe `spip_`** (arrêt sinon) ; hôte MySQL de SPIP sans port ni socket, car `core:installer` se connecte toujours sur le port par défaut (arrêt sinon, avec un message) ;
   - base du WordPress lisible avec les accès de `wp-config.php`.
2. **Lecture de `wp-config.php`** : `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` (port éventuel séparé), `$table_prefix`, lus par `outils/lire_wp_config.php`, qui analyse les jetons PHP du fichier (`token_get_all`) **sans l'exécuter** : seules les valeurs littérales sont reconnues, une définition commentée est ignorée ; `siteurl` et `admin_email` lus ensuite dans `wp_options`.
3. **Téléchargement de SPIP** : `core:telecharger spip -R <version> -d <dossier>`, puis `core:preparer --auto --droits <droits>` dans le dossier : `--auto` crée `plugins/auto`, où SVP télécharge les plugins de l'étape 5. Contrôles : `ecrire/inc_version.php` présent, `plugins/auto` présent et accessible en écriture.
4. **Installation de SPIP** : `core:installer` avec serveur, hôte, login, `--db-pass`, base, préfixe, administrateur, `--admin-pass` et adresse. Le mot de passe de l'administrateur est **toujours donné** : celui de `SPIP_ADMIN_PASS`, sinon un mot de passe aléatoire ; jamais la valeur par défaut de SPIP-Cli (`adminadmin`). Contrôles : `config/connect.php` présent ; l'administrateur existe, est webmestre, et s'authentifie avec ce mot de passe (`auth_spip_dist`, par `php:eval`).
5. **Plugins**
   - `plugins:svp:depoter <dépôt>` ;
   - `plugins:svp:telecharger <préfixe> -y`, **un appel par préfixe** (sale, pages, polyhier) : dans un même appel, SPIP-Cli réutilise le décideur SVP d'un préfixe à l'autre et retente les téléchargements déjà faits, qui échouent (« Impossible de déballer ») ; contrôle après chaque appel : `paquet.xml` du préfixe (`prefix="…"`) présent sous `plugins/auto/` ; sinon arrêt, avec un message qui indique la version de SPIP-Cli requise et précise que le SPIP est déjà installé dans le dossier (à supprimer avant de relancer) ;
   - wp2spip : copie (ou lien symbolique) du dossier du script dans `plugins/wp2spip` ;
   - `plugins:activer sale pages polyhier wp2spip -y`, puis `plugins:maj:bdd` ;
   - contrôles : `plugins:lister` montre les quatre plugins actifs ; pour chaque plugin dont le `paquet.xml` déclare un `schema`, la méta `<préfixe>_base_version` vaut ce schéma (tables et champs installés).

   Albums, Accès restreint et Forum ne sont pas installés ici : `wordpress:importer` les télécharge et les active lui-même si le contenu WordPress en a besoin (spec du sous-projet 3, § 4). Le SPIP préparé reste vierge de ce qui ne sert pas.
6. **Base externe** : `config/wordpress.php` écrit par `spip php:eval` avec les fonctions d'installation de SPIP (`install_fichier_connexion(_DIR_CONNECT . 'wordpress.php', install_connexion(hôte, port, login, mot de passe, base, 'mysql', '', '', ''))`), les accès étant lus par `getenv()` dans des variables d'environnement du sous-processus plutôt qu'écrits dans le code PHP passé en argument (pas de problème de guillemets) ; contrôle : lecture de `siteurl` dans `wp_options` par ce serveur.
7. **Import** (avec `--importer`) : `wordpress:importer <dossier WordPress>` depuis le dossier SPIP ; son code de sortie devient celui du script.
8. **Bilan** : dossier, adresse, base, identifiants de l'administrateur (mot de passe affiché s'il a été généré), et la commande d'import à lancer si `--importer` n'a pas été donné.

## 4. Hors périmètre

- Configuration du serveur web (nginx, Apache) et du nom de domaine.
- Création de la base MySQL et de son utilisateur : la base doit exister et être accessible.
- Mise à jour d'un SPIP existant : le script ne travaille que dans un dossier vide.

## 5. Dépendances et sécurité

- **SPIP-Cli** : version actuelle, sans nouveau correctif. `plugins:svp:telecharger` ne fonctionne qu'avec les correctifs déjà proposés en amont (sélection du plugin, autorisation, remontée des erreurs) ; sans eux, le contrôle de l'étape 5 arrête le script avec un message qui le dit. Ses limites restantes sont contournées par le script : un appel par préfixe, et des contrôles après chaque étape plutôt que les seuls codes de sortie.
- **Mots de passe** : `core:installer` les reçoit par `--db-pass` et `--admin-pass`, seules entrées qu'il connaît. Ils sont donc **brièvement visibles dans les arguments du processus** (`ps`) pendant l'installation, et `core:installer` affiche le mot de passe de l'administrateur dans le terminal : limites acceptées. Le script lui-même les reçoit par les variables d'environnement `SPIP_DB_PASS` et `SPIP_ADMIN_PASS`, jamais en argument ; le mot de passe de la base WordPress n'est transmis à `php:eval` que par l'environnement.
- **SPIP 4.4 récent** : depuis medias 4.4.15 (SPIP 4.4.28, correctif de sécurité #4919), `ajouter_un_document()` vérifie `autoriser('joindredocument')`, refusé en ligne de commande sans auteur connecté : `importer_documents` refusait alors tous les médias. Le script téléchargeant la dernière 4.4, wp2spip pose une exception d'autorisation pour le seul appel de `ajouter_un_document()`, levée aussitôt après, même en cas d'erreur ; et `tests/integration/verifier_identifiants.php` compare, par identifiant, les documents importés aux médias que sélectionne `importer_documents` (pièces jointes au statut `inherit`).
- **Sorties** : le script ne garde aucune sortie de SPIP-Cli dans un fichier journal ; elles s'affichent seulement dans le terminal.
- **Fichiers** : le script n'écrit que dans le dossier `--spip` ; le dossier WordPress n'est jamais modifié.

## 6. Validation

- Préparation complète depuis un dossier vide, base **SQLite** (défaut), base **MySQL distincte**, et base **partagée** avec le WordPress (`--base-partagee`, comme les sites de test actuels ; sur une copie jetable des tables du WordPress, les tests n'écrivant jamais dans les bases des WordPress ni des SPIP de test), pour les WordPress 6.9 et 7.1, avec `--importer` : sale, pages et polyhier présents sous `plugins/auto/`, actifs (`plugins:lister`), schémas installés ; administrateur authentifié avec le mot de passe choisi (`SPIP_ADMIN_PASS`) ou généré, jamais avec `adminadmin` ; puis `tests/integration/verifier_identifiants.php` à **OK**.
- `--wp2spip lien` : `plugins/wp2spip` est un lien vers le dépôt ; `copie` : une copie sans `.git`, `docs` ni `tests`.
- Cas d'erreur, chacun avec code `1` et message explicite : dossier non vide, `wp-config.php` illisible, préfixe de tables autre que `wp_`, accès MySQL refusés, base MySQL identique à celle du WordPress sans `--base-partagee`, base contenant déjà des tables `spip_`, WordPress sur un port sans `--sql-hote`, SPIP-Cli introuvable (aucun fichier écrit) ; plugin non téléchargé, simulé par un préfixe inexistant ajouté à la liste (variable d'environnement `PREPARER_SPIP_PLUGINS_SVP`, réservée aux tests, qui remplace la liste `sale pages polyhier`) : SPIP installé dans le dossier, rien écrit ailleurs, message qui le dit.
- Les sites de test de `tests/integration/` peuvent ensuite être recréés avec ce script plutôt qu'à la main.
