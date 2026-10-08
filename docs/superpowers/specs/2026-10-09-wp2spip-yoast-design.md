# wp2spip — sous-projet 8 : extension `wp2spip_yoast`

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 8.
Statut : design validé (« SEO, et dépôt séparé »), à planifier.

## 1. Constat

Yoast SEO, extension WordPress très répandue, enregistre :

- par contenu, des métadonnées `wp_postmeta` : `_yoast_wpseo_primary_category` (identifiant de la catégorie principale, choisie parmi les catégories du contenu), `_yoast_wpseo_title` (titre pour les moteurs de recherche), `_yoast_wpseo_metadesc` (méta-description), `_yoast_wpseo_meta-robots-noindex` (`1` : ne pas indexer ; `2` : indexer) et `_yoast_wpseo_meta-robots-nofollow` (`1`) ; et des données d'analyse (`_yoast_wpseo_focuskw`, `_yoast_wpseo_linkdex`, `_yoast_wpseo_content_score`…) ;
- par terme, l'option `wpseo_taxonomy_meta` (tableau sérialisé `taxonomie => term_id => array('wpseo_title' => …, 'wpseo_desc' => …, 'wpseo_noindex' => 'noindex' | 'index' | 'default', …)`) ;
- des réglages globaux (`wpseo_titles`, `wpseo_social`…).

Les titres et descriptions peuvent contenir des variables (`%%title%%`, `%%sitename%%`, `%%sep%%`…), remplacées par WordPress à l'affichage.

wp2spip prend aujourd'hui pour rubrique principale d'un article sa première catégorie (ordre `term_order`, puis identifiant), les autres devenant des rubriques secondaires (`importer_polyhierarchie`). La catégorie principale choisie dans Yoast est ignorée.

Site réel (Yoast 5.5) : 184 contenus ont une catégorie principale notée, dont 58 vides ; parmi les autres, 83 sont déjà la rubrique principale choisie par wp2spip, 43 en diffèrent. 27 méta-descriptions, 3 titres, 6 expressions clés ; option `wpseo_taxonomy_meta` : une catégorie avec titre et description, deux catégories en `noindex`. Aucune variable dans ces valeurs. Les WordPress de test (contenu *Theme Unit Test*) n'ont pas Yoast.

Côté SPIP, le plugin **SEO** (préfixe `seo`, version 3.1.0, compatible SPIP 4) stocke des métadonnées par objet dans la table `spip_seo` (`id_objet`, `objet`, `meta_name` parmi `title`, `description`, `keywords`, `copyright`, `author`, `robots`, `meta_content` ; clé primaire `(id_objet, objet, meta_name)`), affichées dans le `<head>` si sa configuration l'active : `seo/meta_tags/activate` et `seo/meta_tags/activate_editing` à `yes`, objets éditables dans `seo/meta_tags/editable_tables` (par défaut `spip_articles` et `spip_rubriques`), insertion dans le `<head>` par `seo/insert_head/activate`.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Forme | **plugin SPIP séparé** `wp2spip_yoast`, dans son propre dépôt (`technova69/wp2spip_yoast`, local pour l'instant) ; il nécessite wp2spip (`[3.0.0;]`, version qui comporte `wp2spip_table()`) |
| Plugin cible | **SEO** pour titres, descriptions et indexation ; déclaré par le pipeline `wp2spip_plugins_requis` seulement si le WordPress a des métadonnées Yoast à importer, donc installé par l'import comme les autres plugins requis |
| Catégorie principale | traitement `importer_yoast_categories`, **juste après `importer_articles`** (avant `importer_polyhierarchie`, qui range les autres catégories en rubriques secondaires) |
| Titres, descriptions, indexation | traitement `importer_yoast_seo`, **à la fin** de la liste |
| Variables Yoast | variables connues remplacées ; valeur avec une variable inconnue non importée, comptée au bilan |
| Valeurs déjà présentes | une métadonnée SEO existante n'est jamais remplacée (relance, saisie dans SPIP) |
| Hors périmètre | expressions clés, scores, URL canonique, données sociales (Open Graph, Twitter), liens internes, réglages globaux de Yoast, plan du site XML |

## 3. Plugin `wp2spip_yoast`

### 3.1 Dépôt et paquet

Dépôt git `wp2spip_yoast` (à côté de `wp2spip`), branche `main`. `paquet.xml` : préfixe `wp2spip_yoast`, version `1.0.0`, compatibilité `[4.2.0;4.4.*]` (celle de wp2spip), `<necessite nom="wp2spip" compatibilite="[3.0.0;]" />`, `<utilise nom="seo" compatibilite="[3.1.0;]" />`, pipelines `wp2spip_traitements` et `wp2spip_plugins_requis`. Fichiers : `wp2spip_yoast_pipelines.php`, `inc/wp2spip_yoast.php` (fonctions communes), `wp2spip/importer_yoast_categories.php`, `wp2spip/importer_yoast_seo.php`, `readme.md`, tests (§ 6).

### 3.2 Pipelines

- `wp2spip_traitements` : insère `importer_yoast_categories` juste après `importer_articles` et ajoute `importer_yoast_seo` à la fin. Si `importer_articles` est absent de la liste (extension qui l'aurait retiré), `importer_yoast_categories` n'est pas ajouté.
- `wp2spip_plugins_requis` : ajoute `seo` (`'nom' => 'SEO'`, `'table' => 'spip_seo'`, `'dist' => false`) si au moins une métadonnée à importer existe : `_yoast_wpseo_title`, `_yoast_wpseo_metadesc` non vide, `_yoast_wpseo_meta-robots-noindex` = `1` ou `_yoast_wpseo_meta-robots-nofollow` = `1` sur un contenu importé (article ou page), ou une entrée `wpseo_title`, `wpseo_desc` non vide ou `wpseo_noindex` = `noindex` pour une catégorie dans `wpseo_taxonomy_meta`. La raison donnée : `N métadonnées Yoast (titres, descriptions, indexation)`. La catégorie principale ne demande aucun plugin.

Toutes les tables WordPress sont nommées par `wp2spip_table()`.

## 4. Traitement `importer_yoast_categories`

1. Contenus lus : articles WordPress (`post_type = post`, tous statuts) ayant `_yoast_wpseo_primary_category` non vide et non nul, dans l'ordre de leur identifiant.
2. Pour chacun, l'article SPIP de même identifiant ; absent : échec (code `1`), comme les autres traitements qui suivent `importer_articles`.
3. **Catégorie obsolète** : si la catégorie principale n'est plus une catégorie du contenu (Yoast garde la valeur quand on décoche la catégorie), la rubrique principale choisie par wp2spip est gardée ; le contenu est compté au bilan (« catégorie principale Yoast obsolète »).
4. **Rubrique absente** : si la catégorie est une catégorie du contenu mais que la rubrique de même identifiant n'existe pas dans SPIP : échec (code `1`), identifiants affichés.
5. Sinon, si la rubrique principale de l'article diffère : `objet_modifier('article', $id, array('id_parent' => $id_rubrique))`, avec les exceptions d'autorisation de `importer_articles` (`modifier`, `instituer` de l'article ; `publierdans` de la rubrique) ; les dates WordPress de l'article (`date`, `date_redac`, `date_modif`, `maj`) sont rétablies après le changement (la modification les mettrait à la date du jour). Déjà la bonne : rien n'est fait (compté).
6. Idempotent : à une relance, chaque article est déjà dans sa rubrique.
7. Bilan : `N rubriques principales changées d'après Yoast, K déjà conformes, O catégories principales Yoast obsolètes (rubrique de wp2spip gardée).`

`importer_polyhierarchie`, qui suit, range les autres catégories en rubriques secondaires d'après la rubrique principale en place.

## 5. Traitement `importer_yoast_seo`

1. **Garde** : métadonnées à importer (§ 3.2) mais SEO inactif : échec (code `1`). Aucune : le traitement ne fait rien.
2. **Contenus** (articles et pages importés) : `_yoast_wpseo_title` → `title`, `_yoast_wpseo_metadesc` → `description` ; `_yoast_wpseo_meta-robots-noindex` = `1` et/ou `_yoast_wpseo_meta-robots-nofollow` = `1` → `robots` = `noindex`, `nofollow` ou `noindex, nofollow`. Objet `article`, identifiant = ID WordPress ; article absent de SPIP : échec.
3. **Catégories** (`wpseo_taxonomy_meta['category']`) : `wpseo_title` → `title`, `wpseo_desc` → `description`, `wpseo_noindex` = `noindex` → `robots` = `noindex`. Objet `rubrique`, identifiant = `term_id` ; rubrique absente de SPIP : échec. Les autres taxonomies (étiquettes…) sont hors périmètre.
4. **Variables** : `%%title%%` (titre du contenu ou de la catégorie), `%%term_title%%` (titre de la catégorie), `%%category%%` et `%%primary_category%%` (titre de la rubrique principale de l'article), `%%sitename%%` (`blogname`), `%%sitedesc%%` (`blogdescription`), `%%sep%%` (séparateur de `wpseo_titles['separator']`, `-` par défaut ; les codes `sc-dash`, `sc-ndash`, `sc-mdash`, `sc-middot`, `sc-bull`, `sc-star`, `sc-pipe`, `sc-tilde`, `sc-laquo`, `sc-raquo`, `sc-lt`, `sc-gt` donnent `-`, `–`, `—`, `·`, `•`, `*`, `|`, `~`, `«`, `»`, `<`, `>`), `%%page%%` (vide). Puis espaces multiples réduits et texte rogné. Une valeur qui garde une autre variable `%%…%%` n'est pas importée ; elle est comptée au bilan, avec son objet en mode verbeux.
5. Valeurs décodées de leurs entités HTML (`wp2spip_decoder_entites()`), sans balise ; vides après cela : ignorées.
6. **Écriture** : `sql_insertq('spip_seo', …)` si le couple objet–`meta_name` n'existe pas ; s'il existe, il est gardé et compté (« déjà présentes »).
7. **Configuration de SEO**, s'il y a au moins une métadonnée importée ou présente : `seo/meta_tags/activate` et `seo/meta_tags/activate_editing` à `yes`, `spip_articles` et `spip_rubriques` ajoutés à `seo/meta_tags/editable_tables` s'ils n'y sont pas, `seo/insert_head/activate` à `yes` ; les autres réglages ne sont pas touchés.
8. Bilan : `N métadonnées SEO importées (titres T, descriptions D, indexation R), K déjà présentes, V avec une variable Yoast inconnue.`

## 6. Tests

Dans le dépôt de l'extension, selon le skill `spip-testing` et comme wp2spip (sous-projet 7) :

- `composer.json` : `require-dev` PHPUnit, SPIP-Cli, `spip/tests`, et **wp2spip** (`technova69/wp2spip`, branche `compat-spip-4.4`, depuis son dépôt git, installé en source pour avoir ses tests) ; l'autoload de développement reprend `Wp2spip\Tests\Integration\` de wp2spip (`WordpressTestCase`).
- `scripts/install-spip-test.sh` : celui de wp2spip, avec en plus `seo`, wp2spip lié depuis `vendor/technova69/wp2spip` et l'extension depuis la racine du dépôt ; même correctif de SPIP-Cli (`tests/spip-cli.patch`, copie de celui de wp2spip).
- Unitaires : remplacement des variables, composition de `robots`, lecture de `wpseo_taxonomy_meta`.
- Intégration, sur la base WordPress de test de wp2spip complétée de métadonnées Yoast (données ajoutées par l'extension) : plugins requis (`seo` requis seulement avec des métadonnées), catégorie principale (changée, déjà conforme, obsolète, rubrique absente en échec, relance sans effet, dates gardées), métadonnées SEO (contenus, catégories, `robots`, variables connues et inconnue, valeur présente gardée, garde sans SEO actif, configuration), ordre des traitements (`importer_yoast_categories` juste après `importer_articles`, `importer_yoast_seo` en dernier).

## 7. Validation

- Tests unitaires et d'intégration de l'extension, depuis un clone neuf, et relancés : OK.
- **Site réel**, sur son SPIP de test remis à zéro, avec l'extension active : import à code `0`, SEO installé par l'import ; 43 rubriques principales changées (les catégories obsolètes, s'il y en a, comptées et laissées) ; 27 descriptions et 3 titres d'articles ; titre, description de la catégorie concernée et `noindex` des deux catégories ; relance de `-t importer_yoast_categories,importer_yoast_seo` sans changement. L'export de wp2spip ne diffère de celui d'avant l'extension que par la rubrique principale des articles concernés et les rubriques secondaires qui en découlent ; vérificateur de wp2spip à OK.
- **WordPress 6.9 et 7.1**, avec l'extension active : aucun plugin requis de plus, traitements sans effet, export identique à la référence de wp2spip.

## 8. Hors périmètre

- Données d'analyse de Yoast (expressions clés, scores), URL canonique, Open Graph et Twitter, liens internes, plan du site XML, réglages globaux (modèles de titres par type de contenu).
- Métadonnées SEO des étiquettes et des autres taxonomies.
- Publication du dépôt de l'extension (dépôt distant à créer par le mainteneur).
