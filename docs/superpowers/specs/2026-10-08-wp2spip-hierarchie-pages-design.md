# wp2spip — sous-projet 4 : hiérarchie des pages

Date : 2026-10-08
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 4, et § 7, question 1.
Statut : réalisé (plan : `docs/superpowers/plans/2026-10-08-wp2spip-hierarchie-pages.md`).

## 1. Constat

Dans WordPress, une page (`post_type = page`) n'a ni catégorie ni étiquette : son seul classement est sa **page parente** (`post_parent`, `0` au premier niveau, profondeur libre). Les pages d'un même parent sont ordonnées par `menu_order` (saisi à la main), puis par titre. Les thèmes s'en servent pour les menus de pages, les sous-menus, le fil d'Ariane et la liste des sous-pages ; l'adresse d'une page enfant reprend le chemin de ses parents (`/parent/enfant/`). Une page parente est une page comme une autre, avec son texte.

wp2spip importe chaque page comme **page unique** du plugin Pages : un article hors rubrique (`id_rubrique = -1`), d'identifiant celui de la page WordPress. Le plugin Pages ne connaît aucune hiérarchie (« des pages sans rubrique ») : `post_parent` ne sert aujourd'hui qu'à résoudre les liens internes `parent/enfant`, et la parenté et l'ordre des pages sont perdus.

| WordPress | Pages enfants | Parents | Profondeur | `menu_order` |
|---|---|---|---|---|
| 6.9 et 7.1, contenu *Theme Unit Test* | 13, toutes publiées | 5 pages publiées | 3 niveaux | renseigné pour certaines pages |
| site réel | 18, toutes publiées | 5 pages publiées | 3 niveaux | `0` partout : ordre par titre |

L'import reprend toutes les pages, quel que soit leur statut : le parent d'une page est toujours importé, s'il s'agit bien d'une page.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Rôle de wp2spip | créer dans SPIP une **structure de données** qui permette d'écrire un squelette rendant la hiérarchie d'origine ; wp2spip **ne fournit ni ne modifie de squelette** |
| Structure | liens du plugin **a2a** (liaison d'articles) entre pages uniques ; les pages restent des pages uniques, avec leur identifiant et leur adresse |
| Plugin | a2a **détecté, téléchargé et activé** par l'import quand le contenu a des pages enfants, comme Albums et Accès restreint (spec des blocs, § 4) |
| Type de liaison | `sous_page`, **ajouté à la configuration d'a2a** (`a2a/types_liaisons`) : il reste défini et utilisable dans l'espace privé après la désinstallation de wp2spip |
| Sens | **un lien par page enfant**, de la page parente vers l'enfant ; la page parente se retrouve en lisant le lien à l'envers (`id_article_lie`) |
| Ordre | rang du lien = ordre WordPress des pages d'un même parent : `menu_order`, puis titre, puis identifiant |

Pistes écartées : une rubrique par page parente (les pages quitteraient le plugin Pages, et une page parente deviendrait un conteneur), un lien vers la parente écrit dans le texte (rien d'exploitable par un squelette), des liens dans les deux sens (information en double, désynchronisable par une modification à la main).

## 3. Plugin a2a

a2a 4.2.1 (stable, SPIP `[4.2.0;4.*]`) est référencé par le dépôt standard `https://plugins.spip.net/depots/principal.xml`. Il ajoute la table `spip_articles_lies` :

| Champ | Rôle |
|---|---|
| `id_article` | article source : la page parente |
| `id_article_lie` | article lié : la page enfant |
| `rang` | ordre des liens d'un même article source, à partir de 1 |
| `type_liaison` | type du lien : `sous_page` |

Les types de liaison sont lus dans la configuration `a2a/types_liaisons` (tableau `type => libellé`), complétée par `$GLOBALS['a2a_types_liaisons']` (types non modifiables). La fonction `action_a2a_lier_article_dist($id_article_cible, $id_article_source, $type_liaison)` (`action/a2a.php`) crée un lien s'il n'existe pas, avec le rang suivant le plus grand rang de l'article source, et invalide le cache de l'article source.

## 4. Détection et installation

`wp2spip_plugins_requis()` (`inc/wp2spip_plugins.php`) ajoute a2a quand au moins une page a pour parent une page :

- critère partagé avec le traitement : `wp2spip_where_pages_enfants()`, conditions sur `wp_posts` (`post_type = page`, `post_parent > 0`, parent de type `page`) ;
- description : `'a2a' => array('nom' => 'A2A', 'table' => 'spip_articles_lies', 'dist' => false, 'raison' => "$nb pages enfants")`.

Le reste est le mécanisme du sous-projet 3, inchangé : téléchargement par `plugins:svp:telecharger`, méta du schéma effacée, `plugins:activer`, `plugins:maj:bdd`, relance de l'import ; en cas d'échec, code `1` avant tout traitement, avec les commandes à lancer à la main. Un WordPress sans page enfant n'installe pas a2a.

`paquet.xml` déclare `<utilise nom="a2a" compatibilite="[4.2.0;]" />`.

## 5. Traitement `importer_hierarchie_pages`

Nouveau traitement, fichier `wp2spip/importer_hierarchie_pages.php`, placé dans la liste par défaut **juste après `importer_articles`**. Il reste désactivable par `--traitements`, comme les autres.

1. Pages enfants lues avec `wp2spip_where_pages_enfants()`, triées par parent, `menu_order`, `post_title`, `ID`. Aucune : le traitement ne fait rien.
2. a2a inactif alors qu'il y a des pages enfants : message d'erreur et retour `false` (code `1`), comme `importer_acces` sans Accès restreint. Par la commande, ce cas ne se présente pas : elle vérifie et installe les plugins requis avant le premier traitement, même avec `--traitements` ; si l'installation échoue, elle s'arrête avant tout traitement (spec des blocs, § 4). Cette garde protège un appel direct du traitement.
3. Type de liaison : si `sous_page` n'est pas dans `a2a/types_liaisons`, il y est ajouté avec le libellé « Sous-page (WordPress) », sans toucher aux autres types ni aux autres réglages d'a2a.
4. Pour chaque page enfant, dans l'ordre : la page parente et la page enfant doivent exister dans `spip_articles` (identifiant = ID WordPress) ; sinon, la page enfant est notée comme **absente** (l'import est incomplet). Le lien `sous_page` est créé par `action_a2a_lier_article_dist(id_enfant, id_parent, 'sous_page')`. Le rang est attribué par a2a dans l'ordre des créations, donc de 1 à n pour chaque parent sur un SPIP vierge.
5. **Contrôle après l'appel** : a2a ne signale pas un lien non créé. Quand ses liaisons multiples sont désactivées (`a2a/types_differents`), il n'insère rien si les deux pages sont déjà liées par un lien d'un autre type. Après chaque appel, le traitement vérifie la présence du lien `sous_page` ; absent, la page enfant est notée en **conflit**. Les réglages d'a2a ne sont pas modifiés.
6. Un lien `sous_page` déjà présent n'est pas recréé (contrôle d'a2a) : relancer l'import ne crée pas de doublon.
7. **Échec** : s'il y a des pages absentes ou en conflit, le traitement crée tous les liens possibles, puis affiche les identifiants WordPress concernés et retourne `false` (code `1`). Il ne réussit jamais en laissant une page enfant sans son lien.

## 6. Bilan

Le traitement affiche :

- `N liens de sous-pages créés (a2a, type sous_page), pour P pages parentes.` ;
- à une relance, `K liens de sous-pages déjà présents.` ;
- en cas d'échec (code `1`) :
  - `Pages enfants ou parentes absentes de SPIP, pas de lien : <ID enfant> (parent <ID parent>), …` ;
  - `Liens sous_page non créés par a2a, les pages étant déjà liées par un autre type (liaisons multiples désactivées dans la configuration d'a2a) : <ID enfant> (parent <ID parent>), …`.

## 7. Données pour le squelette

Le readme décrit la structure et donne deux boucles d'exemple, sans livrer de squelette :

```html
<!-- Sous-pages d'une page, dans l'ordre WordPress -->
<BOUCLE_sous_pages(ARTICLES_LIES){id_article}{type_liaison=sous_page}{par rang}>
	<BOUCLE_sous_page(ARTICLES){id_article=#ID_ARTICLE_LIE}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_sous_page>
</BOUCLE_sous_pages>

<!-- Page parente d'une page -->
<BOUCLE_parente(ARTICLES_LIES){id_article_lie=#ID_ARTICLE}{type_liaison=sous_page}>
	<BOUCLE_page_parente(ARTICLES){id_article}><a href="#URL_ARTICLE">#TITRE</a></BOUCLE_page_parente>
</BOUCLE_parente>
```

Les boucles d'exemple sont vérifiées sur un SPIP de test (calcul d'un squelette d'essai qui n'est pas versionné) avant d'être publiées dans le readme.

## 8. Validation

- **Vérificateur** (`tests/integration/verifier_identifiants.php`) : pour chaque page WordPress dont le parent est une page, exactement un lien `sous_page` depuis l'article de son parent ; aucun autre lien `sous_page` ; rangs des liens d'un même parent conformes à l'ordre WordPress (`menu_order`, titre, ID). Contrôle actif quand a2a est actif ou quand le WordPress a des pages enfants.
- **Export** (`tests/integration/exporter_import.php`) : une ligne `sous_page` par lien (parent, enfant, rang, en identifiants WordPress).
- **WordPress 6.9 et 7.1** : depuis un dossier vide (`outils/preparer_spip.sh --importer`) et sur leurs SPIP de test : a2a installé par l'import, 13 liens pour chacun, vérificateur à OK.
- **Site réel** : sur son SPIP de test et depuis un dossier vide : 18 liens, rangs par titre, vérificateur à OK ; le reste de l'export identique à celui d'avant le sous-projet.
- **Sans page enfant** : un WordPress sans page enfant n'installe pas a2a, et le traitement ne fait rien.
- **Échecs** :
  - installation impossible (dépôt injoignable par `_WP2SPIP_DEPOT_SVP`, sur un SPIP sans dépôt) : code `1`, a2a dans la liste des plugins non installés, aucun traitement lancé ;
  - garde du traitement : a2a désactivé, traitement appelé directement (sans la commande) : retour `false` et message ;
  - conflit : sur un SPIP importé, un lien `sous_page` remplacé par un lien d'un autre type entre les mêmes pages, liaisons multiples désactivées, puis `-t importer_hierarchie_pages` : code `1`, page signalée en conflit ;
  - page absente : sur un SPIP importé, l'article d'une page parente retiré, puis `-t importer_hierarchie_pages` : code `1`, page enfant signalée.
- Tests de la préparation (`tests/preparation/tester_preparer_spip.sh --complet`) : 0 échec.

## 9. Hors périmètre

- Squelettes : aucun n'est fourni ni modifié (menus, fil d'Ariane et liste des sous-pages sont à écrire pour le site).
- Adresses imbriquées `parent/enfant` : non reproduites ; les liens internes vers une page sont déjà convertis en raccourcis vers son article.
- Hiérarchie d'autres types de contenus (types personnalisés hiérarchiques) : hors du périmètre de wp2spip, qui n'importe que `post` et `page`.
- Modèle de page WordPress (`_wp_page_template`) : non repris.
