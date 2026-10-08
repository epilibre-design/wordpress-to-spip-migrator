# wp2spip — spec d'ensemble

Date : 2026-10-08
Branche de référence : `compat-spip-4.4`
Statut : état des lieux et feuille de route. Chaque sous-projet de la feuille de route (§ 6) fera l'objet de sa propre spec détaillée avant réalisation.

## 1. Objet et périmètre

wp2spip importe le contenu d'un site WordPress dans un site SPIP **vierge**, en ligne de commande, via une commande [SPIP-Cli](https://contrib.spip.net/SPIP-Cli). L'import est **ré-exécutable** : un nouveau passage n'importe que ce qui ne l'a pas encore été, et l'option `--update` met à jour ce qui l'a déjà été (utile si le WordPress continue de vivre pendant la migration).

Cibles :

| | Versions |
|---|---|
| SPIP | 4.2 à 4.4 (`compatibilite="[4.2.0;4.4.*]"`) |
| PHP | 8.x (testé en 8.4) |
| WordPress | 4.9 à 7.x (testé en 4.9, 6.9 et 7.1) |
| Base SPIP | MySQL et SQLite |

Hors périmètre du cœur :

- le thème WordPress et sa mise en page ;
- les données des extensions WordPress (Yoast, ACF, calendriers, formulaires…) : elles relèvent d'**extensions de wp2spip** (§ 6, sous-projets 7 et 8) ;
- les menus de navigation WordPress (`nav_menu`), les liens du blogroll (`wp_links`) et les types de contenus personnalisés.

## 2. Architecture

### 2.1 Commande

```
spip wordpress:importer [options] <dir_wordpress>
```

| Argument / option | Rôle |
|---|---|
| `dir_wordpress` | dossier d'installation du WordPress (lecture de `wp-includes/version.php` et des fichiers de `wp-content/uploads/`) |
| `-b, --base` | identifiant de la base WordPress déclarée dans SPIP comme base externe (défaut : `wordpress`) |
| `-t, --traitements` | liste de traitements à lancer, séparés par des virgules (défaut : tous) |
| `-i, --info` | affiche la version de WordPress et les traitements disponibles, sans rien importer |
| `-u, --update` | met à jour les contenus déjà importés |
| `-v` | détail des anomalies par contenu (liens vers des médias non convertis…) |

La commande retourne `0` en cas de succès, `1` si elle n'est pas lancée depuis un site SPIP ou si `wp-includes/version.php` est introuvable.

### 2.2 Traitements

L'import est une suite de **traitements**, exécutés dans cet ordre :

1. `importer_metas`
2. `importer_auteurs`
3. `importer_rubriques`
4. `importer_mots` (non implémenté, § 6)
5. `importer_documents`
6. `importer_articles`
7. `importer_acces`
8. `importer_polyhierarchie`
9. `importer_commentaires`

Chaque traitement est une fonction chargée par `charger_fonction()` depuis `wp2spip/<traitement>.php`. La commande cherche, dans l'ordre, une variante propre à la version de WordPress :

- `wp2spip_<traitement>_<X>_<Y>` (fichier `wp2spip/<traitement>/<X>_<Y>.php`)
- `wp2spip_<traitement>_<X>`
- `wp2spip_<traitement>` (générique)

Un plugin peut donc surcharger un traitement, ou en fournir une variante pour une version précise de WordPress.

### 2.3 Points d'extension

- **Pipeline `w2spip_traitements`** : reçoit la liste ordonnée des traitements ; une extension y insère les siens à la position voulue et fournit le fichier `wp2spip/<traitement>.php` correspondant. Le nom comporte une coquille historique (§ 6, sous-projet 1).
- **Surcharge par `charger_fonction()`** : voir § 2.2.

### 2.4 Traçabilité et ré-exécution

- À l'installation, wp2spip ajoute un champ `id_wordpress` à **toutes** les tables d'objets éditoriaux (déclaration générique `$tables[]` dans `declarer_tables_objets_sql`). Il contient l'identifiant WordPress d'origine et sert à :
  - ne pas importer deux fois le même contenu ;
  - retrouver les correspondances WordPress → SPIP entre traitements (auteur d'un article, document d'une image, parent d'un commentaire…).
- À la désinstallation, ces champs sont supprimés.
- Configuration propre au plugin : `wp2spip/zones` (identifiants des zones d'Accès restreint créées par l'import).

### 2.5 Accès à WordPress

- **Base** : la base WordPress est déclarée dans SPIP comme base externe (Maintenance technique → « Déclarer une base externe »). Toutes les requêtes WordPress passent par l'API `sql_*` avec ce serveur. Elle peut être la même base que celle du SPIP : les tables SPIP (`spip_*`) et WordPress (`wp_*`) cohabitent sans collision.
- **Préfixe** : les tables WordPress sont aujourd'hui supposées préfixées par `wp_` (§ 6, sous-projet 5).
- **Fichiers** : les médias sont lus dans `wp-content/uploads/` du dossier fourni ; à défaut, téléchargés depuis leur URL d'origine.

### 2.6 Dépendances

| Plugin | Lien | Rôle |
|---|---|---|
| sale (≥ 1.0.0) | `necessite` | conversion du HTML WordPress en raccourcis SPIP |
| pages (≥ 2.0.0) | `necessite` | pages WordPress → pages uniques |
| polyhier (≥ 4.0.0) | `necessite` | catégories multiples → rubriques secondaires |
| forum | `utilise` | commentaires → messages de forum |
| accesrestreint | `utilise` | contenus privés ou protégés → zones restreintes |

## 3. Fonctionnalités, traitement par traitement

### 3.1 `importer_metas`

| WordPress (`wp_options`) | SPIP |
|---|---|
| `siteurl` | `adresse_site` |
| `blogname` | `nom_site` |
| `admin_email` | `email_webmaster` |
| `blogdescription` (passé par sale) | `slogan_site` |

Limite : l'adresse du site SPIP est écrasée par celle du WordPress, ce qui est gênant pour un site de test local (§ 6, sous-projet 1).

### 3.2 `importer_auteurs`

- Utilisateurs de `wp_users`, sauf les comptes sans email dont l'identifiant commence par `_` (faux comptes créés en masse par certaines extensions).
- Nom : prénom + nom (métas `first_name`, `last_name`), sinon nom affiché, sinon `user_nicename`, sinon identifiant.
- Identifiant, email, site web repris.
- Statut d'après le rôle WordPress (méta `wp_capabilities`) :

| Rôle WordPress | Statut SPIP |
|---|---|
| administrator | administrateur et webmestre |
| editor | administrateur |
| author, contributor | rédacteur |
| subscriber, autre | visiteur |

- Mot de passe : les mots de passe WordPress ne sont pas repris. À la création, l'auteur reçoit un mot de passe aléatoire et inconnu, non vide, ce qui lui permet d'utiliser « mot de passe oublié ». Il n'est pas réécrit par `--update`.

### 3.3 `importer_rubriques`

- Taxonomies `category` et `link_category` → rubriques, en conservant la hiérarchie (les parents sont créés avant leurs enfants).
- Titre et description passés par sale.
- Limite : `texte_backend()` est appliqué au titre et au texte et les stocke avec des entités HTML (`&#233;` au lieu de `é`) (§ 6, sous-projet 1).

### 3.4 `importer_documents`

- Médias (`post_type = attachment`, `post_status = inherit`), y compris ceux rattachés à aucun contenu.
- Fichier lu dans `wp-content/uploads/` ; à défaut, téléchargé depuis son URL (`guid`), puis la copie temporaire est supprimée.
- Le fichier est **copié** dans `IMG/` : le dossier WordPress n'est jamais modifié.
- Titre, descriptif (contenu ou extrait du média), date ; URL propre d'après le slug WordPress.
- `--update` met à jour les métadonnées, pas le fichier.

### 3.5 `importer_articles`

**Contenus importés** : articles (`post`) et pages (`page`).

**Rubrique principale** :
- page WordPress → page unique SPIP (`id_rubrique = -1`, champ `page` = `wordpress_page_<ID>`) ;
- article → première catégorie, dans l'ordre WordPress, qui existe dans SPIP.

**Statut** :

| WordPress | SPIP |
|---|---|
| publish | publié |
| future | publié (daté dans le futur : SPIP ne l'affiche qu'à cette date) |
| draft | en cours de rédaction |
| pending | proposé |
| private | en cours de rédaction (puis publié en zone restreinte, § 3.6) |
| trash | poubelle |
| protégé par mot de passe | en cours de rédaction (puis publié en zone restreinte, § 3.6) — **jamais publié en clair** |

**Autres champs** : dates de création, de rédaction et de modification WordPress conservées ; forum ouvert selon `comment_status` ; URL propre d'après le slug ; auteur principal rattaché ; médias dont le contenu est le parent WordPress liés à l'article.

**Conversion du texte** (après passage par sale) :

| Élément WordPress | Résultat SPIP |
|---|---|
| lien vers un média du site | `[texte->documentN]` |
| lien vers un contenu (`?p=`, `?page_id=` ou slug) | `[texte->articleN]` |
| raccourci `[caption]` | `<docN\|alignement\|largeur=…>`, la légende devenant le descriptif du document |
| `<img>` | `<imgN\|alignement>` |
| lecteur `<audio>` / `<video>`, raccourcis `[audio]` / `[video]` | `<docN>` |

La recherche du document à partir d'une URL (`wp2spip_chercher_document()`) :
- ne considère que les URL du site d'origine ou relatives, indépendamment du protocole et du `www.` ;
- compare le chemin sous `wp-content/uploads/`, décodé, à un index de **tous les noms** de chaque média : fichier réel (`_wp_attached_file`), tailles dérivées, original d'une image réduite (`-scaled`) ou retouchée (`_wp_attachment_backup_sizes`), `guid` ;
- pour une image, cherche d'abord d'après le fichier (`src`), puis d'après la classe `wp-image-N`.

**Bilan** : la commande signale les liens vers des fichiers du site restés tels quels, en distinguant les fichiers **absents de la médiathèque** (souvent des liens déjà cassés sur le site WordPress) des médias **importés mais placés là où SPIP n'a pas de raccourci** (image de fond d'un bloc « Couverture »…). Le détail par contenu s'affiche avec `-v`.

**Limites connues** :
- hiérarchie des pages perdue (§ 6, sous-projet 3) ;
- balisage des blocs de l'éditeur WordPress (`<figure class="wp-block-…">`, `<figcaption>`…) laissé dans le texte (§ 6, sous-projet 2) ;
- un contenu sans titre reçoit le titre par défaut de SPIP (« Nouvel article N° … ») ;
- avec `--update`, la réinsertion des URL propres déjà présentes échoue sans conséquence (doublon de clé, journalisé par SPIP).

### 3.6 `importer_acces`

- Si Accès restreint n'est pas actif : message, et les contenus privés ou protégés restent non publiés.
- S'il est actif : chaque contenu **privé**, ou **protégé par mot de passe** et publié/programmé, est publié (date WordPress conservée) et lié à une zone :
  - « WordPress : contenus privés » ;
  - « WordPress : contenus protégés par mot de passe ».
- Les deux zones s'appliquent au site public et sont accessibles à **tout visiteur identifié** (`autoriser_si_connexion`). Les mots de passe WordPress ne sont pas repris.
- Les zones sont créées une fois, leurs identifiants gardés en configuration (`wp2spip/zones`) et réutilisés, même si elles sont renommées.
- Un contenu déjà lié à sa zone n'est plus modifié, sauf avec `--update`, qui retire aussi des zones les contenus redevenus publics dans WordPress.

### 3.7 `importer_polyhierarchie`

- Pour chaque article importé, les catégories WordPress autres que la rubrique principale deviennent des **rubriques secondaires** (`polyhier_set_parents()`).
- Repart à chaque passage de la rubrique principale réelle de l'article, et aligne les rubriques secondaires sur WordPress (ajout et retrait). Traite donc aussi les articles importés avant son existence.
- Termine par `calculer_rubriques()` : une rubrique qui ne contient que des articles secondaires est publiée.

### 3.8 `importer_commentaires`

- Si Forum n'est pas actif : message, rien n'est importé.
- Commentaires de type vide (WordPress < 5.5) ou `comment` (≥ 5.5) ; trackbacks, pingbacks, spam et corbeille exclus.
- Approuvé → publié ; en attente → proposé.
- Texte passé par sale ; auteur, email, site, adresse IP, date repris ; un commentateur ayant un compte WordPress est relié à son auteur SPIP.
- **Fils de discussion** : `id_parent` = message auquel on répond, `id_thread` = premier message du fil, y compris quand une réponse est traitée avant son parent ; un parent non importé fait commencer un nouveau fil. `date_thread` = date du dernier message publié du fil.
- Un commentaire dont le contenu n'a pas été importé est ignoré.
- Chaque message garde son `id_wordpress` : pas de doublon à la ré-exécution ; `--update` met à jour les messages existants.

## 4. Réalisé sur la branche `compat-spip-4.4`

Point de départ : version 2.0.3, compatible SPIP 3.2 uniquement.

| Commit | Contenu |
|---|---|
| `aee0782` | **Compatibilité SPIP 4.4 et PHP 8** : `paquet.xml` (`[4.2.0;4.4.*]`, version 3.0.0, dépendances SPIP 4, forum en `utilise`) ; commande conforme à Symfony Console 5.4 (`execute(): int`, plus d'`exit`), erreur claire si le dossier WordPress est invalide ; warnings PHP 8 (`array_column` au lieu de `array_map('reset')`, variables initialisées) ; mot de passe aléatoire des auteurs (SPIP 4.4 refusait `' '`) ; rubrique principale = première catégorie dans l'ordre WordPress ; nouveau traitement `importer_polyhierarchie`. |
| `b2cf90b` | **Rubriques** : des catégories n'étaient pas importées (`array_merge()` renumérotait les clés et une affectation écrasait une catégorie déjà rangée). |
| `897ac83` | **Contenus privés et protégés** : un contenu protégé par mot de passe n'est plus publié en clair ; nouveau traitement `importer_acces` (Accès restreint). |
| `0538b49` | **Commentaires** : fils de discussion, plus de doublons, commentaires des WordPress < 5.5 (jusque-là tous ignorés), commentaires en attente, commentateurs identifiés. |
| `55f4c7f` | **Liens vers les médias** : index de tous les noms de fichiers, URL encodées, http/https/www, images retouchées et miniatures, lecteurs audio et vidéo, bilan des liens non convertis. |

## 5. Validation

### 5.1 Jeux de test

| Source WordPress | Contenu | SPIP |
|---|---|---|
| site réel, WordPress 4.9 | 342 contenus (dont 98 privés), 44 catégories dont 117 articles en plusieurs, 1 612 médias (≈ 1 Go), éditeur classique, extensions Yoast et ACF | importé deux fois : base SQLite et base MySQL |
| WordPress 6.9 standard | contenu de test officiel *Theme Unit Test* : 82 contenus (dont 1 protégé, 1 programmé, 13 pages enfants), 69 catégories, 114 étiquettes, 37 médias, 34 commentaires dont 10 réponses | base MySQL partagée avec le WordPress |
| WordPress 7.1 standard | idem | idem |

Les deux WordPress standard ont été installés avec WP-CLI, et le contenu de test officiel importé avec l'extension WordPress Importer :

```bash
wp config create --dbname=<base> --dbuser=<utilisateur> --dbpass=<mot de passe> --dbprefix=wp_
wp core install --url=<adresse> --title=<titre> --admin_user=<admin> --admin_password=<mot de passe> --admin_email=<email> --skip-email
wp plugin install wordpress-importer --activate
curl -L -o themeunittestdata.wordpress.xml https://raw.githubusercontent.com/WordPress/theme-test-data/master/themeunittestdata.wordpress.xml
wp import themeunittestdata.wordpress.xml --authors=create
```

L'import WordPress télécharge les médias du contenu de test dans `wp-content/uploads/`. Il renumérote les médias sans corriger les classes `wp-image-N` des contenus : c'est pourquoi la conversion des images cherche d'abord d'après le fichier (§ 3.5).

### 5.2 Résultats

- Site réel : résultats **identiques en SQLite et en MySQL**, contenu par contenu (texte, titre, rubrique, statut, date) ; les 126 rubriques secondaires attendues créées ; les 98 contenus privés publiés en zone restreinte, invisibles d'un visiteur anonyme, visibles d'un visiteur identifié.
- WordPress 6.9 et 7.1 : résultats **identiques entre les deux versions**, aux différences d'installation près (adresse du site, date d'installation) ; 118 rubriques secondaires attendues ; fils de commentaires conformes à WordPress, sans écart de parent ; contenu protégé publié en zone avec Accès restreint, non publié sans.
- Simulation WordPress < 5.5 (types de commentaires vides) : 30 messages, aucun écart.
- Idempotence vérifiée pour les commentaires et les zones : un second passage ne crée rien ; `--update` met à jour sans dupliquer.

### 5.3 Méthode

- Comparaison des contenus importés **champ par champ** entre bases ou entre versions, et des structures (rubriques secondaires, fils de commentaires) **lien par lien** avec la source WordPress.
- Chaque site SPIP de test a une sauvegarde de son état vierge (dump des tables `spip_*` ou archive de la base SQLite) : la remise à zéro consiste à la restaurer et à vider `IMG/` et `local/`.
- Piège rencontré : `spip plugins:activer` n'installe pas les tables des plugins ; lancer ensuite `spip plugins:maj:bdd`.

Ces vérifications sont aujourd'hui manuelles (§ 6, sous-projet 6).

## 6. Feuille de route

Chaque sous-projet aura sa propre spec, puis son plan de réalisation.

| # | Sous-projet | Nature | Contenu |
|---|---|---|---|
| 1 | Petites corrections | cœur | titres et textes de rubriques sans entités HTML ; option pour ne pas écraser l'adresse du site ; pipeline renommé `wp2spip_traitements`, l'ancien nom restant appelé pour compatibilité |
| 2 | Balisage des blocs de l'éditeur | cœur | nettoyer `<figure>`, `<figcaption>`, classes `wp-block-*` et commentaires de blocs, en gardant les légendes ; galeries vers des documents |
| 3 | Hiérarchie des pages | cœur | conserver la structure des pages parentes et enfants (décision ouverte, § 7) |
| 4 | Étiquettes | cœur | `importer_mots` : `post_tag` → mots-clés d'un groupe dédié, liés aux articles |
| 5 | Préfixe des tables | cœur | option `--prefixe` (défaut `wp_`), y compris pour la méta des rôles (`<prefixe>capabilities`) |
| 6 | Tests automatisés | cœur | tests PHPUnit des fonctions de conversion, et tests d'intégration sur le contenu *Theme Unit Test* |
| 7 | `wp2spip_yoast` | extension | catégorie principale Yoast comme rubrique principale (traitement inséré après `importer_articles`, avant `importer_polyhierarchie`) ; ensuite, titre SEO et méta-description |
| 8 | `wp2spip_acf` | extension | champs ACF → Champs Extras (vraisemblablement via Champs Extras Interface, à vérifier), d'après leurs définitions (`acf-field`) ; correspondances vers des champs natifs quand elles existent (lien hypertexte de l'article, mot-clé technique) |
| 9 | Signalements aux plugins tiers | amont | sale : `extraire_images()` parcourt une portion de texte de trop (warning PHP 8, sans effet sur le résultat) ; Polyhiérarchie configurable : pipeline `objet_compte_enfants` non déclaré, champ `date` codé en dur dans `calculer_rubriques` |

## 7. Questions ouvertes

1. **Hiérarchie des pages** (sous-projet 3) : rattacher chaque page à la rubrique équivalente (quand l'arbre des catégories reproduit celui des pages), ou créer une rubrique par page parente, ou garder des pages uniques avec un lien vers leur parent ?
2. **Ordre de priorité** de la feuille de route.
3. **Fusion** de la branche `compat-spip-4.4` dans `master`, et publication d'une version 3.0.0.
