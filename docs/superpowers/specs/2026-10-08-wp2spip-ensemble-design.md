# wp2spip — spec d'ensemble

Date : 2026-10-08
Branche de référence : `compat-spip-4.4`, partie de `master` au commit `3b9fc8d` (dernier commit de `master`, septembre 2022) ; elle inclut donc tout `master`. Les anciennes branches `v0` et `v1` (plugin de première génération, `plugin.xml`) ne concernent pas la commande SPIP-Cli et sont hors du champ de cette spec.
Statut : état des lieux et feuille de route. Chaque sous-projet de la feuille de route (§ 6) fera l'objet de sa propre spec détaillée avant réalisation.

## 1. Objet et périmètre

wp2spip importe le contenu d'un site WordPress dans un site SPIP, en ligne de commande, via une commande [SPIP-Cli](https://contrib.spip.net/SPIP-Cli).

Principes :

- **WordPress figé** : l'import part d'un WordPress qui ne bouge pas pendant la migration (copie du site, ou site gelé). wp2spip ne prévoit pas de suivre un WordPress qui continue d'évoluer.
- **SPIP vierge** : le site SPIP de destination ne contient aucun contenu avant le premier import.
- **SPIP hors ligne** : le site SPIP n'est pas accessible au public pendant l'import, et le reste jusqu'au contrôle des accès (contenus privés ou protégés, zones restreintes). Un contenu peut être incomplet entre deux traitements (rubriques sans articles, articles sans leurs rubriques secondaires…) : cette condition évite de l'exposer ainsi. `importer_acces` lie toutefois chaque contenu à sa zone avant de le publier, si bien qu'un contenu privé ou protégé n'est jamais public, même un instant.
- **Traitements relançables** : un nouveau passage n'importe que ce qui ne l'a pas encore été, ce qui permet de lancer un traitement seul (`--traitements`). Les objets déjà importés (auteurs, rubriques, documents, articles, messages de forum) ne sont pas retouchés, à l'exception de trois traitements qui recalculent à chaque passage : `importer_metas` réécrit la configuration du site, `importer_polyhierarchie` réaligne les rubriques secondaires des articles et recalcule le statut des rubriques, `importer_commentaires` recalcule les fils de discussion (`id_parent`, `id_thread`, `date_thread`) des messages importés. Ces recalculs partent de WordPress, figé : ils produisent le même résultat à chaque passage.
- **Import interrompu** (coupure, erreur) : il se refait de zéro, comme après une amélioration de wp2spip (ci-dessous). wp2spip ne prévoit pas de reprise qui compléterait les contenus laissés incomplets par l'interruption.
- **Refaire un import** (par exemple après une amélioration de wp2spip) : remettre le SPIP à zéro, puis relancer un import complet. Il n'y a pas de mise à jour des contenus déjà importés.
- **Identifiants conservés** : articles, pages, documents et rubriques SPIP reprennent l'identifiant de leur source WordPress (§ 6, sous-projet 1). Un identifiant déjà pris dans SPIP arrête le traitement concerné avant qu'il ne crée le moindre objet de ce type, et l'import avec le code `1` ; les traitements précédents ont pu créer des objets, d'où la remise à zéro avant de relancer.

Cibles :

| | Versions |
|---|---|
| SPIP | 4.2 à 4.4 (`compatibilite="[4.2.0;4.4.*]"`) |
| PHP | 8.x (testé en 8.4) |
| WordPress | 4.9 à 7.x (testé en 4.9, 6.9 et 7.1) |
| Base SPIP | MySQL et SQLite |

Hors périmètre du cœur :

- le thème WordPress et sa mise en page ;
- les données des extensions WordPress (Yoast, ACF, calendriers, formulaires…) : elles relèvent d'**extensions de wp2spip** (§ 6, sous-projets 8 et 9) ;
- les menus de navigation WordPress (`nav_menu`), les liens du blogroll (`wp_links`) et les types de contenus personnalisés.

## 2. Architecture

### 2.1 Commande

```
spip wordpress:importer [options] <dir_wordpress>
```

Options :

| Argument / option | Rôle |
|---|---|
| `dir_wordpress` | dossier d'installation du WordPress (lecture de `wp-includes/version.php` et des fichiers de `wp-content/uploads/`) |
| `-b, --base` | identifiant de la base WordPress déclarée dans SPIP comme base externe (défaut : `wordpress`) |
| `-t, --traitements` | liste de traitements à lancer, séparés par des virgules (défaut : tous) |
| `-i, --info` | affiche la version de WordPress et les traitements disponibles, sans rien importer |
| `--garder-adresse` | ne remplace pas l'adresse du site SPIP par celle du WordPress |
| `-v` | détail des anomalies par contenu (liens vers des médias non convertis…) |

**Code de sortie** :

- `1` si la commande n'est pas lancée depuis un site SPIP, ou si `wp-includes/version.php` est introuvable ;
- un nom inconnu passé à `--traitements` est une erreur : message, aucun traitement lancé, code `1` ;
- un traitement introuvable est une erreur : message, arrêt, code `1` ;
- un traitement signale un échec en retournant `false` (identifiant déjà pris, erreur de l'API d'édition…) : la commande s'arrête, les traitements suivants dépendant des précédents (les articles des rubriques…), et retourne `1` ;
- sinon, `0`.

### 2.2 Traitements

L'import est une suite de **traitements**, exécutés dans cet ordre :

1. `importer_metas`
2. `importer_auteurs`
3. `importer_rubriques`
4. `importer_documents`
5. `importer_articles`
6. `importer_hierarchie_pages`
7. `importer_mots`
8. `importer_acces`
9. `importer_polyhierarchie`
10. `importer_commentaires`

`importer_mots` (étiquettes, sous-projet 5) lie les mots aux articles, qui doivent donc exister (spec `2026-10-09-wp2spip-etiquettes-design.md`).

Les contenus WordPress sont lus dans l'ordre de leur identifiant : deux imports d'un même WordPress produisent le même résultat.

Chaque traitement est une fonction chargée par `charger_fonction()` depuis `wp2spip/<traitement>.php`. La commande cherche, dans l'ordre, une variante propre à la version de WordPress :

- `wp2spip_<traitement>_<X>_<Y>` (fichier `wp2spip/<traitement>/<X>_<Y>.php`)
- `wp2spip_<traitement>_<X>`
- `wp2spip_<traitement>` (générique)

Un plugin peut donc surcharger un traitement, ou en fournir une variante pour une version précise de WordPress.

### 2.3 Points d'extension

- **Pipeline `wp2spip_traitements`** : reçoit la liste ordonnée des traitements ; une extension y insère les siens à la position voulue et fournit le fichier `wp2spip/<traitement>.php` correspondant. L'ancien nom, `w2spip_traitements` (coquille historique), est toujours appelé après lui, pour les extensions existantes.
- **Surcharge par `charger_fonction()`** : voir § 2.2.

### 2.4 Traçabilité et ré-exécution

- À l'installation, wp2spip ajoute un champ `id_wordpress` à **toutes** les tables d'objets éditoriaux (déclaration générique `$tables[]` dans `declarer_tables_objets_sql`). Il contient l'identifiant WordPress d'origine et sert à :
  - ne pas importer deux fois le même contenu ;
  - retrouver les correspondances WordPress → SPIP entre traitements (auteur d'un article, document d'une image, parent d'un commentaire…).
- À la désinstallation, ces champs sont supprimés.
- Configuration propre au plugin : `wp2spip/zones` (identifiants des zones d'Accès restreint créées par l'import).

### 2.5 Accès à WordPress

- **Base** : la base WordPress est déclarée dans SPIP comme base externe (Maintenance technique → « Déclarer une base externe »). Toutes les requêtes WordPress passent par l'API `sql_*` avec ce serveur. Elle peut être la même base que celle du SPIP : les tables SPIP (`spip_*`) et WordPress (`wp_*`) cohabitent sans collision.
- **Préfixe** : les tables WordPress sont aujourd'hui supposées préfixées par `wp_` (§ 6, sous-projet 6).
- **Fichiers** : les médias sont lus dans `wp-content/uploads/` du dossier fourni ; à défaut, téléchargés depuis leur URL d'origine.

### 2.6 Dépendances

| Plugin | Lien | Rôle |
|---|---|---|
| sale (≥ 1.0.0) | `necessite` | conversion du HTML WordPress en raccourcis SPIP |
| pages (≥ 2.0.0) | `necessite` | pages WordPress → pages uniques |
| polyhier (≥ 4.0.0) | `necessite` | catégories multiples → rubriques secondaires |
| forum | `utilise` ; activé par wp2spip si le WordPress a des commentaires à importer (sous-projet 3, spec des blocs § 4) | commentaires → messages de forum |
| accesrestreint | `utilise` ; téléchargé et activé par wp2spip si le WordPress a des contenus privés ou protégés (sous-projet 3, spec des blocs § 4) | contenus privés ou protégés → zones restreintes |
| a2a | `utilise` ; téléchargé et activé par wp2spip si le WordPress a des pages enfants (sous-projet 4) | hiérarchie des pages → liens `sous_page` entre pages uniques |

## 3. Fonctionnalités, traitement par traitement

### 3.1 `importer_metas`

| WordPress (`wp_options`) | SPIP |
|---|---|
| `siteurl` | `adresse_site` |
| `blogname` | `nom_site` |
| `admin_email` | `email_webmaster` |
| `blogdescription` (passé par sale) | `slogan_site` |

Limite : l'adresse du site SPIP est écrasée par celle du WordPress, ce qui est gênant pour un site de test local (§ 6, sous-projet 2).

### 3.2 `importer_auteurs`

- Utilisateurs de `wp_users`, sauf les comptes sans email dont l'identifiant commence par `_` (faux comptes créés en masse par certaines extensions).
- Nom : prénom + nom (métas `first_name`, `last_name`), sinon nom affiché, sinon `user_nicename`, sinon identifiant.
- Identifiant, email, site web repris. Les auteurs gardent la numérotation de SPIP (l'administrateur créé à l'installation porte le n° 1).
- Un identifiant refusé par SPIP (déjà pris, souvent par l'administrateur créé à l'installation, ou trop court) est remplacé par le premier libre parmi `login-wp`, `login-wp2`… (validé par SPIP, déterministe pour un même WordPress), ce qui garde l'accès au compte et à « mot de passe oublié » ; le login attribué est signalé dans le bilan de l'import.
- Statut d'après le rôle WordPress (méta `wp_capabilities`) :

| Rôle WordPress | Statut SPIP |
|---|---|
| administrator | administrateur et webmestre |
| editor | administrateur |
| author, contributor | rédacteur |
| subscriber, autre | visiteur |

- Mot de passe : les mots de passe WordPress ne sont pas repris. À la création, l'auteur reçoit un mot de passe aléatoire et inconnu, non vide, ce qui lui permet d'utiliser « mot de passe oublié ».

### 3.3 `importer_rubriques`

- Taxonomies `category` et `link_category` → rubriques, en conservant la hiérarchie (les parents sont créés avant leurs enfants), avec `id_rubrique` = identifiant de la catégorie.
- Titre et description passés par sale, sans entités HTML (`é` et non `&#233;`), sauf `&lt;`, `&gt;` et `&amp;`, gardées pour ne pas créer de balise.

### 3.4 `importer_documents`

- Médias (`post_type = attachment`, `post_status = inherit`), y compris ceux rattachés à aucun contenu, avec `id_document` = identifiant du média : le document est créé vide avec cet identifiant, puis le fichier y est installé (`ajouter_un_document($id_document, …)`).
- Un fichier refusé par SPIP (type non autorisé…) ne laisse pas de document vide : il est compté, et détaillé avec `-v`.
- Fichier lu dans `wp-content/uploads/` ; à défaut, téléchargé depuis son URL (`guid`), puis la copie temporaire est supprimée.
- Le fichier est **copié** dans `IMG/` : le dossier WordPress n'est jamais modifié.
- Titre, descriptif (contenu ou extrait du média), date ; URL propre d'après le slug WordPress.

### 3.5 `importer_articles`

**Contenus importés** : articles (`post`) et pages (`page`), avec `id_article` = identifiant WordPress.

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
| lien vers un contenu (`?p=`, `?page_id=` ou slug) | `[texte->articleN]`, N étant l'identifiant WordPress, que le contenu soit déjà importé ou non |
| raccourci `[caption]` | `<docN\|alignement\|largeur=…>`, la légende devenant le descriptif du document |
| `<img>` | `<imgN\|alignement>` |
| lecteur `<audio>` / `<video>`, raccourcis `[audio]` / `[video]` | `<docN>` |

La recherche du document à partir d'une URL (`wp2spip_chercher_document()`) :
- ne considère que les URL du site d'origine ou relatives, indépendamment du protocole et du `www.` ;
- compare le chemin sous `wp-content/uploads/`, décodé, à un index de **tous les noms** de chaque média : fichier réel (`_wp_attached_file`), tailles dérivées, original d'une image réduite (`-scaled`) ou retouchée (`_wp_attachment_backup_sizes`), `guid` ;
- pour une image, cherche d'abord d'après le fichier (`src`), puis d'après la classe `wp-image-N`.

**Bilan** : la commande signale les liens vers des fichiers du site restés tels quels, en distinguant les fichiers **absents de la médiathèque** (souvent des liens déjà cassés sur le site WordPress) des médias **importés mais placés là où SPIP n'a pas de raccourci** (image de fond d'un bloc « Couverture »…). Le détail par contenu s'affiche avec `-v`.

**Limites connues** :
- **slug ambigu** : un lien désigné par son slug est résolu dans `wp_posts`. Si plusieurs contenus ont ce slug (pages de parents différents, article et page), le chemin complet de la page doit terminer l'URL ; s'il reste plusieurs candidats, le lien est laissé tel quel plutôt que de viser peut-être le mauvais contenu ;
- un contenu sans titre reçoit le titre par défaut de SPIP (« Nouvel article N° … ») ;

### 3.6 `importer_acces`

- Accès restreint est téléchargé et activé par la commande dès que le WordPress a des contenus privés ou protégés (sous-projet 3). S'il n'est pas actif malgré tout : arrêt en échec (code `1`).
- S'il est actif : chaque contenu **privé**, ou **protégé par mot de passe** et publié/programmé, est lié à une zone, puis publié (date WordPress conservée) ; si l'association ou la publication échoue, le traitement s'arrête en échec :
  - « WordPress : contenus privés » ;
  - « WordPress : contenus protégés par mot de passe ».
- Les deux zones s'appliquent au site public et sont accessibles à **tout visiteur identifié** (`autoriser_si_connexion`). Les mots de passe WordPress ne sont pas repris.
- **C'est un choix de migration, plus large que WordPress** : dans WordPress, un contenu privé n'est visible que de son auteur et des comptes ayant la capacité `read_private_posts` (administrateurs et éditeurs) ; un contenu protégé, de quiconque connaît son mot de passe. L'ouverture à tout compte connecté a été retenue parce que les sites migrés se servent souvent des contenus privés comme d'un espace réservé à leurs membres. Une restriction plus forte reste possible à la main, par exemple désactiver l'option « toute personne connectée » de la zone et n'y lier que les administrateurs et éditeurs ; ce n'est pas l'équivalent de WordPress, une zone étant partagée par tous ses contenus : elle ne reproduit ni l'accès de chaque auteur à ses propres contenus privés, ni le mot de passe propre à chaque contenu protégé.
- Les zones sont créées une fois, leurs identifiants gardés en configuration (`wp2spip/zones`) et réutilisés, même si elles sont renommées.
- Un contenu déjà lié à sa zone n'est plus modifié.

**Plusieurs zones et publics différents.** L'accès aux zones repose uniquement sur Accès restreint, qui ouvre une zone soit à tout visiteur connecté (`autoriser_si_connexion`), soit aux comptes qui lui sont liés un par un. Le cœur de wp2spip se limite aux deux zones ci-dessus, ouvertes à tout visiteur connecté : WordPress ne connaît que ses rôles (un contenu privé est réservé aux administrateurs et éditeurs), pas de publics distincts. Pour distinguer des publics :

| Situation du site WordPress | Zones | Accès |
|---|---|---|
| seulement des contenus privés ou protégés | les 2 zones par défaut | tout visiteur connecté |
| une extension de gestion de membres (niveaux d'accès) | une zone par niveau, créée par une extension `wp2spip_<extension>` qui relit ces niveaux | comptes liés automatiquement d'après WordPress |
| des espaces réservés à des publics distincts, sans extension de membres | zones créées à la main après l'import (par exemple une par rubrique réservée) | comptes liés à la main à chaque zone |

Une zone dont les accès sont gérés compte par compte doit avoir l'option « toute personne connectée » désactivée : avec l'option active, la zone est ouverte à tout visiteur connecté, comptes liés ou non. Les extensions liées au référencement, comme `wp2spip_yoast`, ne gèrent pas d'accès.

### 3.7 `importer_polyhierarchie`

- Pour chaque article importé, les catégories WordPress autres que la rubrique principale deviennent des **rubriques secondaires** (`polyhier_set_parents()`).
- Repart à chaque passage de la rubrique principale réelle de l'article, et aligne les rubriques secondaires sur WordPress (ajout et retrait). Traite donc aussi les articles importés avant son existence.
- Termine par `calculer_rubriques()` : une rubrique qui ne contient que des articles secondaires est publiée.

### 3.8 `importer_commentaires`

- Forum est activé par la commande dès qu'il y a des commentaires à importer (sous-projet 3). S'il n'est pas actif malgré tout : arrêt en échec (code `1`).
- Commentaires de type vide (WordPress < 5.5) ou `comment` (≥ 5.5) ; trackbacks, pingbacks, spam et corbeille exclus.
- Approuvé → publié ; en attente → proposé.
- Texte passé par sale ; auteur, email, site, adresse IP, date repris ; un commentateur ayant un compte WordPress est relié à son auteur SPIP.
- **Fils de discussion** : `id_parent` = message auquel on répond, `id_thread` = premier message du fil, y compris quand une réponse est traitée avant son parent ; un parent non importé fait commencer un nouveau fil. `date_thread` = date du dernier message publié du fil.
- Un commentaire dont le contenu n'a pas été importé est ignoré.
- Chaque message garde son `id_wordpress` : pas de doublon à la ré-exécution.

### 3.9 `importer_hierarchie_pages`

- Exécuté juste après `importer_articles`.
- Un lien a2a de type `sous_page` de chaque page parente vers chacune de ses pages enfants (pages dont le parent est une page), rang = ordre WordPress (`menu_order`, titre, ID) ; la page parente se retrouve en lisant le lien à l'envers. Aucun squelette n'est fourni.
- Type `sous_page` ajouté à la configuration d'a2a (`a2a/types_liaisons`), sans toucher aux autres réglages.
- Un lien déjà présent n'est pas recréé. Échec (code `1`) si une page enfant ou parente manque dans SPIP, ou si a2a ne crée pas un lien (pages déjà liées par un autre type, liaisons multiples désactivées) ; tous les liens possibles sont créés avant.
- Détail : spec `2026-10-08-wp2spip-hierarchie-pages-design.md`.

## 4. Réalisé sur la branche `compat-spip-4.4`

Point de départ : version 2.0.3, compatible SPIP 3.2 uniquement.

| Commit | Contenu |
|---|---|
| `aee0782` | **Compatibilité SPIP 4.4 et PHP 8** : `paquet.xml` (`[4.2.0;4.4.*]`, version 3.0.0, dépendances SPIP 4, forum en `utilise`) ; commande conforme à Symfony Console 5.4 (`execute(): int`, plus d'`exit`), erreur claire si le dossier WordPress est invalide ; warnings PHP 8 (`array_column` au lieu de `array_map('reset')`, variables initialisées) ; mot de passe aléatoire des auteurs (SPIP 4.4 refusait `' '`) ; rubrique principale = première catégorie dans l'ordre WordPress ; nouveau traitement `importer_polyhierarchie`. |
| `b2cf90b` | **Rubriques** : des catégories n'étaient pas importées (`array_merge()` renumérotait les clés et une affectation écrasait une catégorie déjà rangée). |
| `897ac83` | **Contenus privés et protégés** : un contenu protégé par mot de passe n'est plus publié en clair ; nouveau traitement `importer_acces` (Accès restreint). |
| `0538b49` | **Commentaires** : fils de discussion, plus de doublons, commentaires des WordPress < 5.5 (jusque-là tous ignorés), commentaires en attente, commentateurs identifiés. |
| `55f4c7f` | **Liens vers les médias** : index de tous les noms de fichiers, URL encodées, http/https/www, images retouchées et miniatures, lecteurs audio et vidéo, bilan des liens non convertis. |
| `af92d93` | **Tests d'intégration** (`tests/integration/`) : remise à zéro des sites de test, export comparable d'un import à l'autre, vérification des identifiants. |
| `63d62c8` | **Codes de sortie** : traitements inconnus refusés, arrêt sur un échec, `importer_mots` retiré de la liste. |
| `6750f1f` | **Dates de modification** WordPress conservées (l'association d'un auteur ou d'une zone les écrasait). |
| `ea51e02` | **Suppression de `--update`**. |
| `77531d1` | Contenus WordPress lus dans l'ordre de leur identifiant. |
| `706a83b`, `d011e8f`, `afe5b12` | **Identifiants WordPress conservés** pour les rubriques, documents, articles et pages ; échecs de l'API d'édition signalés ; logins refusés par SPIP signalés. |
| `5a35a8c` | Rubriques sans entités HTML. |
| `a77fd91` | **Liens internes** écrits d'après l'identifiant WordPress, slugs ambigus laissés tels quels. |
| `a11f766` | Option `--garder-adresse`. |
| `ff267df` | Pipeline `wp2spip_traitements`. |

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
- Idempotence vérifiée pour les commentaires et les zones : un second passage ne crée rien.
- Après les sous-projets 1 et 2 (validation avec `tests/integration/`) : sur les quatre sites, import complet en code `0` et `verifier_identifiants.php` à **OK** (identifiant SPIP = identifiant WordPress pour tous les articles, pages, rubriques et documents ; aucun lien `?p=` ou `?page_id=` vers le site d'origine laissé tel quel ; tout `[->articleN]` désigne un article existant). Site réel : SQLite = MySQL ligne à ligne. Par rapport à l'import d'avant ces sous-projets, seuls changent les titres de rubriques sans entités, le titre par défaut d'un contenu sans titre (« Nouvel article N° » + identifiant WordPress) et, pour deux contenus de même slug, celui qui reçoit l'URL (désormais toujours le plus ancien). Un identifiant déjà pris arrête l'import (code `1`) sans créer aucun objet du type concerné, pour chaque type d'objet.

### 5.3 Méthode

- Comparaison des contenus importés **champ par champ** entre bases ou entre versions, et des structures (rubriques secondaires, fils de commentaires) **lien par lien** avec la source WordPress.
- Chaque site SPIP de test a une sauvegarde de son état vierge (dump des tables `spip_*` ou archive de la base SQLite) : la remise à zéro consiste à la restaurer et à vider `IMG/` et `local/`.
- Piège rencontré : `spip plugins:activer` n'installe pas les tables des plugins ; lancer ensuite `spip plugins:maj:bdd`.
- Outils dans `tests/integration/` (exclus des archives du plugin) : `remise_a_zero.sh` (état vierge d'un site de test), `exporter_import.php` (export trié, indépendant des identifiants SPIP, pour comparer deux imports ligne à ligne), `verifier_identifiants.php` (identifiants conservés, liens internes ; code de sortie `1` en cas d'écart). Les chemins et accès de la machine de test restent dans `environnement.sh`, non versionné.

Ces vérifications sont lancées à la main ; leur automatisation relève du sous-projet 7.

## 6. Feuille de route

Chaque sous-projet aura sa propre spec, puis son plan de réalisation.

| # | Sous-projet | Nature | Contenu |
|---|---|---|---|
| 1 | Identifiants WordPress conservés — **réalisé** | cœur | articles et pages créés avec `id_article` = ID WordPress, rubriques avec `id_rubrique` = ID de la catégorie, documents avec `id_document` = ID du média ; les auteurs gardent la numérotation automatique (l'administrateur créé à l'installation de SPIP porte le n° 1). **Création** : l'identifiant imposé et `id_wordpress` sont passés ensemble dans le paramètre `$set` de `objet_inserer()`, en une seule insertion (vérifié en MySQL et en SQLite), si bien qu'aucun objet n'existe sans son `id_wordpress`. **Documents** : document créé vide de cette façon, puis fichier installé par `ajouter_un_document($id_document, …)`, qui met à jour un document existant au lieu d'en créer un (vérifié : fichier copié, source intacte, titre conservé, aucun document en double). **Identifiant déjà occupé** : par le même contenu WordPress, celui-ci est déjà importé et n'est pas retouché ; par un autre contenu, erreur et arrêt, code `1`. Un import interrompu se refait de zéro (§ 1) : pas de logique de reprise. **Liens internes** écrits directement d'après l'ID WordPress (`?p=`, `?page_id=`, et slug résolu dans `wp_posts`), ce qui supprime la limite décrite au § 3.5 |
| 2 | Corrections et fiabilité — **réalisé** | cœur | codes de sortie conformes au comportement cible (§ 2.1) : `importer_mots` retiré de la liste par défaut, noms inconnus de `--traitements` refusés, échec d'un traitement signalé ; contenus lus dans l'ordre de leur identifiant WordPress (`ORDER BY`), pour un traitement reproductible ; titres et textes de rubriques sans entités HTML ; option pour ne pas écraser l'adresse du site ; pipeline renommé `wp2spip_traitements`, l'ancien nom restant appelé pour compatibilité ; suppression de l'option `--update` et des branches de mise à jour dans les traitements ; readme mis à jour (WordPress figé, refaire un import = remise à zéro puis import complet) |
| 11 | Préparation d'un SPIP (réalisé **avant** le 3) — **réalisé** | outil | script `outils/preparer_spip.sh` qui télécharge et installe SPIP, ses plugins et wp2spip, déclare la base WordPress, puis peut lancer l'import, en s'appuyant sur les commandes actuelles de SPIP-Cli et en contrôlant le résultat de chaque étape (spec `2026-10-08-wp2spip-preparation-spip-design.md`) ; médias de nouveau importés sur SPIP 4.4.28 (medias 4.4.15 refusait tous les fichiers en ligne de commande) |
| 3 | Balisage des blocs de l'éditeur — **réalisé** | cœur | nettoyer `<figure>`, `<figcaption>`, classes `wp-block-*` et commentaires de blocs, en gardant les légendes ; galeries vers des documents |
| 4 | Hiérarchie des pages — **réalisé** | cœur | conserver la structure des pages parentes et enfants : liens a2a `sous_page` entre pages uniques, a2a installé par l'import (spec `2026-10-08-wp2spip-hierarchie-pages-design.md`) |
| 5 | Étiquettes — **réalisé** | cœur | `importer_mots` : `post_tag` → mots-clés d'un groupe dédié, liés aux articles (spec `2026-10-09-wp2spip-etiquettes-design.md`) |
| 6 | Préfixe des tables — **réalisé** | cœur | préfixe lu dans `wp-config.php`, option `--prefixe` pour le remplacer (exigée si `wp-config.php` ne permet pas de le lire), y compris pour la méta des rôles (`<prefixe>capabilities`) ; tables contrôlées avant tout traitement (spec `2026-10-09-wp2spip-prefixe-tables-design.md`) |
| 7 | Tests automatisés — **réalisé** | cœur | PHPUnit organisé selon le skill `spip-testing` : tests unitaires sans SPIP, tests d'intégration dans un SPIP installé dans `vendor/` sur une base WordPress SQLite construite par les tests ; import complet du contenu *Theme Unit Test* comparé à une référence versionnée (spec `2026-10-09-wp2spip-tests-automatises-design.md`) |
| 8 | `wp2spip_yoast` — **réalisé** (dépôt local) | extension | catégorie principale Yoast comme rubrique principale (traitement inséré après `importer_articles`, avant `importer_polyhierarchie`) ; ensuite, titre SEO et méta-description |
| 9 | `wp2spip_acf` | extension | champs ACF → Champs Extras (vraisemblablement via Champs Extras Interface, à vérifier), d'après leurs définitions (`acf-field`) ; correspondances vers des champs natifs quand elles existent (lien hypertexte de l'article, mot-clé technique) |
| 10 | Signalements aux plugins tiers | amont | sale : `extraire_images()` parcourt une portion de texte de trop (warning PHP 8, sans effet sur le résultat) ; Polyhiérarchie configurable : pipeline `objet_compte_enfants` non déclaré, champ `date` codé en dur dans `calculer_rubriques` |

## 7. Questions ouvertes

1. **Hiérarchie des pages** (sous-projet 4) : décidé — les pages restent des pages uniques, liées par des liens a2a de type `sous_page` (page parente → page enfant, dans l'ordre WordPress) ; a2a est installé par l'import quand il y a des pages enfants ; wp2spip ne fournit pas de squelette (spec `2026-10-08-wp2spip-hierarchie-pages-design.md`).
2. **Ordre de priorité** de la feuille de route.
3. **Branche principale** : décidé — `compat-spip-4.4` devient la branche principale du dépôt (branche par défaut), sans fusion dans `master` ; le dépôt d'origine, abandonné, ne reçoit pas de demande de fusion. Reste ouverte : la publication d'une version 3.0.0.
