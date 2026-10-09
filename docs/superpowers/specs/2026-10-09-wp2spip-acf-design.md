# wp2spip — sous-projet 9 : extension `wp2spip_acf`

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 9.
Statut : réalisé (dépôt [`wp2spip_acf`](https://git.spip.net/technova69/wp2spip_acf), publié le 2026-10-09) ; décisions prises seul, à relire à la revue finale.

Écarts décidés pendant la réalisation : les plugins requis dépendent des champs importables et non des valeurs (sinon un site aux champs définis mais encore vides échouerait à la garde du § 4.2) ; les groupes non publiés (désactivés) sont ignorés sans être comptés.

## 1. Constat

Advanced Custom Fields (ACF) ajoute des champs aux contenus WordPress. Depuis ACF 5, leurs définitions sont des contenus :

- `acf-field-group` : un groupe (titre, et dans `post_content` un tableau sérialisé dont `location`, liste de règles « OU » de règles « ET » `array('param' => …, 'operator' => '==' | '!=', 'value' => …)`, par exemple `post_category == 3`, `post_type == post`, `post == 518`) ;
- `acf-field` : un champ, enfant de son groupe (`post_parent`), ou d'un autre champ pour les sous-champs (répéteur, groupe, contenu flexible) ; `post_excerpt` = nom, `post_name` = clé (`field_…`), `post_title` = libellé, `post_content` = réglages sérialisés (`type`, `choices`, `multiple`, `default_value`…), `menu_order` = ordre.

Les valeurs sont des métadonnées du contenu : `<nom>` = valeur, `_<nom>` = clé du champ. Selon le type : texte, HTML (`wysiwyg`), identifiant de média (`image`, `file`, quel que soit le format de retour réglé), choix (`select`, `radio`), tableau sérialisé (`checkbox`, `select` multiple), `1`/`0` (`true_false`), date `Ymd` (`date_picker`). Les révisions ont aussi des valeurs. ACF 4 stockait ses définitions dans des contenus `acf` (format différent) ; des champs peuvent aussi être déclarés en PHP ou en JSON, hors de la base.

Site réel (ACF 6.1) : 3 groupes, 30 champs (`select`, `text`, `wysiwyg`, `file`, `image`), règles par catégorie ou par contenu ; valeurs sur environ 190 contenus importés (et plus d'un millier de révisions) ; 3 anciens contenus `acf` d'ACF 4. Les WordPress de test n'ont pas ACF.

Côté SPIP, **Champs Extras** (`cextras`) ajoute des colonnes aux tables des objets, décrites par des saisies (plugin `saisies`) ; **Champs Extras Interface** (`iextras`, qui nécessite `cextras`, `saisies`, `verifier`, `yaml`, `select2`) les rend modifiables dans l'espace privé : les saisies d'une table sont dans la méta `champs_extras_<table>` (tableau sérialisé), les colonnes sont créées par `champs_extras_creer($table, $saisies)`. Une saisie : `array('saisie' => 'input', 'options' => array('nom' => …, 'label' => …, 'sql' => …, 'traitements' => …), 'identifiant' => …)`.

## 2. Décisions (prises seul, à relire)

| Sujet | Décision |
|---|---|
| Forme | plugin séparé `wp2spip_acf`, dépôt [`wp2spip_acf`](https://git.spip.net/technova69/wp2spip_acf), comme `wp2spip_yoast` (sous-projet 8) ; nécessite wp2spip `[3.0.0;]` |
| Plugins cibles | **Champs Extras Interface** (`iextras`, avec `cextras` et leurs dépendances) : les champs créés restent modifiables dans l'espace privé ; déclarés par `wp2spip_plugins_requis` seulement s'il y a des champs à importer |
| Objets | les champs des groupes qui s'appliquent aux articles ou aux pages WordPress → colonnes de `spip_articles` ; les autres groupes (taxonomies, utilisateurs, options, commentaires, médias, menus) sont hors périmètre, comptés au bilan |
| Nom des colonnes | `acf_<nom>` (nom ACF en minuscules) : jamais de collision avec une colonne de SPIP ou d'un plugin, et lisible dans les squelettes (`#ACF_<NOM>`) ; un nom qui ne donne pas un identifiant SQL valide (`^[a-z0-9_]+$`, 64 caractères au plus avec le préfixe) n'est pas importé, compté |
| Types | ceux du § 3.3 ; les autres (répéteur, groupe, contenu flexible, galerie, relation, lien vers un contenu, carte…) et les sous-champs ne sont pas importés, comptés au bilan |
| Champs natifs | aucune correspondance automatique (le sens d'un champ n'est pas connu) ; un site peut en déclarer par le pipeline `wp2spip_acf_correspondances` (nom ACF → colonne existante de `spip_articles`) |
| Traitement | `importer_acf`, **à la fin** de la liste des traitements |
| Valeurs | celles des contenus importés (articles et pages, pas les révisions), reconnues par leur clé (`_<nom>` = clé du champ) ; une valeur déjà présente dans SPIP n'est pas remplacée |
| Hors périmètre | définitions d'ACF 4 (contenus `acf`) et champs déclarés en PHP ou JSON (comptés : contenus `acf` présents) ; logique conditionnelle, validation, instructions ; pages d'options |

## 3. Plugin `wp2spip_acf`

### 3.1 Paquet et pipelines

`paquet.xml` : préfixe `wp2spip_acf`, version `1.0.0`, compatibilité `[4.2.0;4.4.*]`, `<necessite nom="wp2spip" compatibilite="[3.0.0;]" />`, `<utilise nom="iextras" compatibilite="[4.0.0;]" />`. Pipelines :

- `wp2spip_traitements` : ajoute `importer_acf` à la fin ;
- `wp2spip_plugins_requis` : si au moins un champ importable (§ 3.2, § 3.3) n'est pas couvert par une correspondance (§ 2), ajoute `cextras` (`'table' => ''`) puis `iextras` (`'nom' => 'Champs Extras Interface'`), raison `N champs ACF` ; SVP télécharge leurs dépendances avec eux ;
- `wp2spip_acf_correspondances` (nouveau) : `data` = tableau nom ACF → colonne de `spip_articles`, vide par défaut.

### 3.2 Champs retenus

1. Groupes : contenus `acf-field-group` publiés (`post_status = publish`), dans l'ordre `menu_order`, ID.
2. Un groupe s'applique aux articles et pages si une de ses règles « OU » ne comporte que des paramètres de contenu (`post_type`, `post`, `post_category`, `post_format`, `post_status`, `post_taxonomy`, `post_template`, `page`, `page_type`, `page_parent`, `page_template`), sans `post_type` égal à autre chose que `post` ou `page`. Les règles elles-mêmes ne filtrent pas les valeurs : une valeur présente sur un contenu importé est importée.
3. Champs : `acf-field` publiés, enfants directs d'un groupe retenu, dans l'ordre du groupe puis `menu_order`. Un nom porté par plusieurs champs donne une seule colonne (premier champ par ordre de groupe), les suivants de même type y écrivent aussi ; de type différent, ils ne sont pas importés (comptés).

### 3.3 Types

| ACF | Saisie | SQL | Valeur |
|---|---|---|---|
| `text`, `email`, `url`, `number`, `range` | `input` | `text DEFAULT '' NOT NULL` | telle quelle, entités décodées |
| `textarea` | `textarea`, 5 lignes (traitement raccourcis) | `text DEFAULT '' NOT NULL` | HTML converti par wp2spip (`wpautop()` : retours à la ligne simples en sauts de ligne SPIP `_ `) |
| `wysiwyg` | `textarea`, 10 lignes (traitement raccourcis) | `text DEFAULT '' NOT NULL` | HTML converti par wp2spip, liens vers le site convertis comme dans les textes (`wp2spip_chercher_lien()`) |
| `select` (simple), `radio`, `button_group` | `selection` / `radio`, avec les choix | `text DEFAULT '' NOT NULL` | la clé choisie |
| `select` multiple, `checkbox` | `selection_multiple` / `checkbox`, avec les choix | `text DEFAULT '' NOT NULL` | clés séparées par des virgules (stockage de Champs Extras) |
| `true_false` | `oui_non` | `varchar(3) DEFAULT '' NOT NULL` | `1` → `on`, sinon vide |
| `date_picker` | `date` | `datetime DEFAULT '0000-00-00 00:00:00' NOT NULL` | `Ymd` → `Y-m-d 00:00:00` |
| `image`, `file` | `input` | `bigint(21) DEFAULT 0 NOT NULL` | identifiant du document (= ID du média) ; document lié à l'article ; média absent de SPIP : 0, compté |

Libellé de la saisie : le libellé ACF ; groupes ACF rendus par une saisie `fieldset` par groupe (titre du groupe), pour garder la présentation.

## 4. Traitement `importer_acf`

1. Champs retenus (§ 3.2) ; aucun : le traitement ne fait rien. Contenus `acf` (ACF 4) présents : signalés au bilan.
2. **Garde** : champs à créer mais `iextras` inactif : échec (code `1`).
3. **Définitions** : saisies ajoutées à la méta `champs_extras_spip_articles` (identifiants donnés par `saisies_identifier()`), sans retoucher les saisies existantes ; une saisie dont le nom existe déjà dans la méta est gardée telle quelle ; puis `champs_extras_creer('spip_articles', $nouvelles)`. Les champs déclarés par `wp2spip_acf_correspondances` ne sont pas créés ; leur colonne doit exister, sinon échec.
4. **Contrôle** : chaque colonne attendue existe (`sql_showtable`) ; sinon échec.
5. **Valeurs** : pour chaque contenu importé (article SPIP de même identifiant), chaque champ retenu dont la métadonnée a la bonne clé et une valeur non vide : conversion (§ 3.3) puis `sql_updateq()` de la colonne si elle est vide (ni l'API ni les dates de l'article ne sont touchées) ; non vide : gardée, comptée. Un contenu WordPress avec des valeurs mais sans article SPIP : échec.
6. Bilan : `C champs ACF créés en champs extras (articles), V valeurs importées, K déjà présentes ; non importés : T champs (type non pris en charge, nom invalide ou en double), S sous-champs, G groupes hors articles, M médias absents.` (détail des noms en mode verbeux). Contenus `acf` d'ACF 4 présents : une ligne avant le bilan.

## 5. Tests

Comme `wp2spip_yoast` (sous-projet 8, § 6) : dépôt avec Composer et PHPUnit, SPIP de test avec wp2spip, `iextras` et ses dépendances ; base WordPress de test de wp2spip complétée de groupes, champs et valeurs ACF couvrant chaque type du § 3.3, un sous-champ de répéteur, un type non pris en charge, un groupe de taxonomie, un nom en double de type différent, un contenu `acf` d'ACF 4, des valeurs sur une révision.

- Unitaires : groupe applicable (règles), conversion de chaque type, nom de colonne.
- Intégration : plugins requis, saisies et colonnes créées, valeurs (dont révision ignorée, média lié, valeur présente gardée, relance sans changement), correspondance native, garde sans `iextras`, échecs.

## 6. Validation

- Tests de l'extension depuis un clone neuf, relancés : OK.
- **Site réel**, SPIP de test remis à zéro, extension active : import à code `0`, Champs Extras Interface installé par l'import ; les champs des groupes du site créés et visibles dans l'interface de Champs Extras ; nombre de valeurs importées égal au nombre de valeurs non vides des contenus importés (requête de contrôle sur la base WordPress) ; médias liés ; relance sans changement ; vérificateur de wp2spip à OK, export de wp2spip identique à celui d'avant l'extension (les colonnes extras n'y figurent pas).
- **WordPress 6.9 et 7.1**, extension active : aucun plugin requis de plus, traitement sans effet, export identique à la référence.

## 7. Hors périmètre

- ACF 4 et champs hors base (PHP, JSON).
- Répéteurs, contenus flexibles, groupes de champs imbriqués, galeries, relations : à traiter par un objet éditorial dédié si un site en a besoin.
- Champs des catégories, des utilisateurs, des pages d'options.
- Publication du dépôt de l'extension.
