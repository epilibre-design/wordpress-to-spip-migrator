# wp2spip — sous-projet 6 : préfixe des tables

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 6.
Statut : design validé, à planifier.

## 1. Constat

Le préfixe des tables WordPress se choisit à l'installation (`$table_prefix` dans `wp-config.php`, `wp_` par défaut) ; beaucoup de sites en changent. wp2spip écrit en dur les noms `wp_posts`, `wp_comments`… : une soixantaine d'occurrences dans les traitements, `inc/wp2spip_plugins.php`, `inc/wp2spip_blocs.php`, la commande et les scripts de test, y compris dans des jointures et une sous-requête. La méta des rôles d'un utilisateur porte aussi le préfixe (`<préfixe>capabilities`). Le script de préparation refuse explicitement tout autre préfixe que `wp_`.

Le préfixe de connexion de SPIP (`spip_connect_db`, 7ᵉ argument) ne s'applique qu'aux tables écrites `spip_…` dans les requêtes : il ne peut pas servir pour les tables WordPress.

Les trois WordPress de test utilisent `wp_`.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Source du préfixe | `$table_prefix` lu dans le `wp-config.php` du dossier WordPress fourni (sans l'exécuter) ; l'option `--prefixe` le remplace |
| Préfixe introuvable | `wp-config.php` illisible, sans affectation de `$table_prefix`, ou avec plusieurs affectations : **`--prefixe` exigé** (code `1`) ; jamais de `wp_` supposé, qui pourrait désigner un autre WordPress de la même base |
| Contrôles | format du préfixe, puis présence de **toutes** les tables lues par wp2spip, avant tout traitement et avant la vérification des plugins requis |
| Mémoire | préfixe gardé dans la méta SPIP `wp2spip_prefixe_tables` ; un SPIP déjà amorcé avec un autre préfixe est refusé (remise à zéro demandée) |
| Accès aux noms | une fonction `wp2spip_table($nom)` donne le nom complet de chaque table, partout |
| Test | copie de `wp_6` dans la base `jetable` sous le préfixe `wpx_`, refaite à chaque validation |

## 3. Préfixe de l'import

### 3.1 Détermination

Au début de `WordpressImporter::execute()`, après la lecture de la version de WordPress :

1. Option `--prefixe <préfixe>` donnée : elle est utilisée. Si `wp-config.php` annonce un autre préfixe, la commande le signale (`Préfixe des tables : wpx_ (option --prefixe ; wp-config.php annonce wp_).`) et continue avec l'option.
2. Sinon : `wp-config.php` est lu comme texte ; l'affectation `$table_prefix = '…';` (guillemets simples ou doubles) doit y figurer **exactement une fois**. Fichier illisible, affectation absente ou multiple (par exemple conditionnelle) : code `1`, `Préfixe des tables WordPress introuvable dans wp-config.php : indiquer --prefixe.`
3. Le préfixe retenu est affiché : `Préfixe des tables : wp_ (wp-config.php).`

### 3.2 Contrôles

Avant `verifier_plugins()` (qui lit déjà les contenus et les commentaires) et avant tout traitement :

1. **Format** : `^[A-Za-z0-9_]+$`, la règle de WordPress (`wp-admin/setup-config.php`). Sinon code `1`. Le préfixe ainsi contrôlé peut être placé dans le texte des requêtes.
2. **Tables** : les neuf tables lues par wp2spip doivent exister dans la base WordPress (`sql_showtable(…, true, $base)`) : `posts`, `postmeta`, `terms`, `term_taxonomy`, `term_relationships`, `options`, `users`, `usermeta`, `comments`. Une table manquante : code `1`, avec la liste des tables absentes et le rappel de `--prefixe`.
3. **SPIP déjà amorcé** : si la méta `wp2spip_prefixe_tables` existe et diffère du préfixe retenu : code `1`, `Ce SPIP a été importé depuis les tables wp_ ; pour importer depuis wpx_, remettre le SPIP à zéro.` La méta n'est pas modifiée.
4. Contrôles passés : la méta `wp2spip_prefixe_tables` est écrite (ou confirmée).

### 3.3 Noms des tables

`inc/wp2spip.php`, inclus (`include_spip('inc/wp2spip')`) par chaque fichier qui lit les tables WordPress, fournit :

- `wp2spip_prefixe_tables(): string` : la méta `wp2spip_prefixe_tables`, ou `wp_` si elle n'existe pas (SPIP sans import : scripts de test, fonctions appelées hors de la commande) ;
- `wp2spip_table(string $nom): string` : `wp2spip_prefixe_tables() . $nom`.

Toutes les occurrences de `wp_<table>` dans le code et les scripts de test passent par `wp2spip_table()` : appels `sql_*`, jointures écrites dans le nom de table (`wp_posts AS P JOIN wp_postmeta …`), sous-requêtes (`post_parent IN (SELECT ID FROM wp_posts …)`). La méta des rôles est lue sous la clé `wp2spip_prefixe_tables() . 'capabilities'`. Les noms de variables PHP (`$wp_posts`…) ne sont pas concernés.

Les fonctions partagées (critères de `inc/wp2spip_plugins.php`, `wp2spip_chercher_lien()`, `wp2spip_chercher_document()`, vérificateur, export) gardent leur signature : elles obtiennent le préfixe par la méta.

### 3.4 Script de préparation

`outils/preparer_spip.sh` ne refuse plus les préfixes autres que `wp_` : il contrôle le format du préfixe lu dans `wp-config.php` (même règle), l'utilise dans ses propres requêtes (`<préfixe>options`), et le transmet à l'import par `--prefixe` (un préfixe qu'il a pu lire n'a pas à être relu autrement par la commande).

## 4. WordPress de test à préfixe `wpx_`

Script versionné `tests/integration/creer_wordpress_prefixe.sh` (sans accès en clair : il lit les variables de `tests/integration/environnement.sh`) :

1. Vide les tables `wpx_%` de la base `jetable`, puis y copie chaque table `wp_%` de la base du WordPress 6.9 sous le nom `wpx_…` (structure et contenu).
2. Renomme les clés qui portent le préfixe, comme le fait un changement de préfixe dans WordPress : dans `wpx_usermeta`, les `meta_key` commençant par `wp_` (`wp_capabilities`, `wp_user_level`…) ; dans `wpx_options`, `wp_user_roles`.
3. Monte un miroir du dossier du WordPress 6.9 (liens vers ses fichiers) dont le `wp-config.php` désigne la base `jetable` et `$table_prefix = 'wpx_'`, avec des accès qui ont les droits sur `jetable`.

La base `jetable` est vidée par les tests de la préparation : la copie est refaite à chaque validation.

## 5. Validation

- **Préfixe `wpx_`** : le miroir préparé et importé depuis un dossier vide (`outils/preparer_spip.sh --importer`) : code `0`, vérificateur à OK, export **identique** à la référence du WordPress 6.9 (`export-wp6-reference.tsv`).
- **Option** : sur un SPIP vierge, import du miroir avec un `wp-config.php` modifié pour annoncer `wp_` (tables `wp_…` absentes de `jetable`) : code `1`, tables absentes listées ; même import avec `--prefixe wpx_` : code `0`, message signalant l'écart avec `wp-config.php`.
- **Préfixe introuvable** : `wp-config.php` sans `$table_prefix` : code `1` ; avec `--prefixe wpx_` : code `0`.
- **Format** : `--prefixe "wp_;x"` : code `1`, aucun traitement.
- **SPIP amorcé** : après un import en `wpx_`, une relance avec `--prefixe wp_` : code `1`, méta inchangée.
- **Non-régression** : WordPress 6.9 et 7.1 (sur leurs SPIP de test et depuis un dossier vide) et site réel (sur son SPIP de test) : exports identiques à ceux d'avant le sous-projet, vérificateur à OK.
- Tests de la préparation (`tests/preparation/tester_preparer_spip.sh --complet`) : 0 échec.

## 6. Hors périmètre

- Multisite : tables des sites secondaires (`<préfixe><N>_posts`…), table `<préfixe>blogs`.
- Tables non lues par wp2spip (`termmeta`, `commentmeta`, `links`…).
