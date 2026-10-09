# wp2spip — sous-projet 5 : étiquettes

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 5.
Statut : réalisé.

## 1. Constat

Les étiquettes WordPress (taxonomie `post_tag`) ne sont pas importées. Le nom `importer_mots` figurait dans la liste des traitements sans fichier correspondant ; il en a été retiré par le sous-projet 2.

Dans WordPress, une étiquette est un terme (`wp_terms` : `term_id`, `name`, `slug`) rattaché à la taxonomie `post_tag` par `wp_term_taxonomy` (`term_taxonomy_id`, `description`, `count`). Les liens avec les contenus sont dans `wp_term_relationships` (`object_id`, `term_taxonomy_id`). Le compteur `count` ne tient pas compte de tous les contenus (les contenus privés n'y sont pas comptés) : il n'est pas fiable pour savoir si une étiquette est utilisée.

| WordPress | Étiquettes | Sans contenu | Avec description | Liens | Remarques |
|---|---|---|---|---|---|
| 6.9 et 7.1, contenu *Theme Unit Test* | 114 | 50 | 14 | 188 (articles publiés, un programmé, un brouillon) | deux étiquettes de même nom, de slugs différents |
| site réel | 1 | 0 | 0 | 1 (un contenu privé) | `count` vaut 0 |

Aucun contenu ne contient de lien vers une page d'étiquette (`?tag=`, `/tag/`).

Côté SPIP, le plugin `mots` est livré dans `plugins-dist`, donc toujours actif ; l'usage des mots-clés sur les articles est désactivé par défaut (méta `articles_mots = non`), ce qui masque les mots-clés dans l'interface des articles.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Objet SPIP | un **mot-clé** par étiquette, dans un groupe dédié « Étiquettes » |
| Étiquettes importées | **toutes**, y compris celles sans contenu (WordPress les garde) |
| Identifiant | `id_mot` = `term_id`, `id_wordpress` = `term_id`, comme les rubriques pour les catégories (sous-projet 1) |
| Position du traitement | `importer_mots` **après `importer_hierarchie_pages`** (donc après `importer_articles`, dont il lie les articles) et avant `importer_acces` |
| Réglage du site | mots-clés activés sur les articles (`articles_mots = oui`) dès qu'une étiquette est importée |

La spec d'ensemble prévoyait `importer_mots` entre `importer_rubriques` et `importer_documents` : à cette place, les articles n'existent pas encore et ne peuvent pas être liés.

## 3. Traitement `importer_mots`

Fichier `wp2spip/importer_mots.php`, dans la liste par défaut entre `importer_hierarchie_pages` et `importer_acces`.

### 3.1 Étiquettes

1. Étiquettes lues dans `wp_term_taxonomy` (`taxonomy = post_tag`) jointe à `wp_terms`, dans l'ordre de `term_id`. Aucune : le traitement ne fait rien (ni groupe, ni réglage).
2. **Groupe** « Étiquettes » : lié aux articles (`tables_liees = articles`), plusieurs mots par article (`unseul = non`), facultatif (`obligatoire = non`), utilisable par les administrateurs et les rédacteurs. Créé au premier passage ; son identifiant est gardé dans la méta `wp2spip_groupe_etiquettes`. À une relance, le groupe est retrouvé par cette méta ; si la méta désigne un groupe qui n'existe plus, le traitement échoue (code `1`) plutôt que d'en créer un second.
3. **Identifiants libres** : avant toute création, chaque `term_id` à créer doit être libre dans `spip_mots`. Un identifiant pris par un autre objet que l'étiquette elle-même arrête le traitement en échec (code `1`), sans rien créer, comme pour les autres objets (sous-projet 1).
4. **Mot** créé par `objet_inserer('mot', $id_groupe, $set)` avec, dans `$set`, `id_mot` et `id_wordpress` égaux au `term_id` (une seule insertion), `titre` = `name` décodé de ses entités HTML, `descriptif` = `description` passée par sale (vide si la description est vide).
5. **Déjà importé** : un mot trouvé par `id_wordpress` n'est considéré comme déjà importé que s'il appartient au groupe « Étiquettes » ; il n'est alors pas retouché. Trouvé dans un autre groupe : échec (code `1`), avec l'identifiant.
6. Les noms en double sont gardés tels quels : chaque étiquette a son mot.

### 3.2 Liens avec les articles

1. Relations lues dans `wp_term_relationships`, jointes à `wp_term_taxonomy` par `term_taxonomy_id`, limitées à `taxonomy = post_tag`, et jointes à `wp_posts` limité aux contenus importés (`post_type` `post` ou `page`), quel que soit leur statut.
2. Chaque relation donne un lien du mot (`id_mot` = `term_id`) vers l'article (`id_article` = `object_id`), par `objet_associer(array('mot' => $id_mot), array('article' => $id_article))`. Un lien déjà présent n'est pas recréé.
3. **Contenu absent** : si l'article d'une relation n'existe pas dans SPIP (identifiant = ID WordPress), la relation est notée ; après avoir créé tous les liens possibles, le traitement affiche les couples `étiquette → contenu` concernés et retourne `false` (code `1`).
4. **Lien non créé** : après `objet_associer()`, la présence du lien dans `spip_mots_liens` est vérifiée ; absent, il est noté et fait échouer le traitement de la même façon.

### 3.3 Réglage et bilan

- S'il y a au moins une étiquette : `articles_mots` passe à `oui` (`ecrire_meta`), sans toucher aux autres réglages des mots-clés.
- Bilan : `N étiquettes importées en mots-clés (groupe « Étiquettes »), K déjà présentes ; M liens avec les articles.` ; en cas d'échec, les identifiants concernés.

## 4. Validation

- **Vérificateur** (`tests/integration/verifier_identifiants.php`) : chaque étiquette WordPress a son mot, de même identifiant, dans le groupe désigné par `wp2spip_groupe_etiquettes` ; aucun autre mot n'a d'`id_wordpress` ; les liens mot–article sont exactement les relations `post_tag` vers les contenus importés ; `articles_mots = oui` s'il y a des étiquettes.
- **Export** (`tests/integration/exporter_import.php`) : lignes `mot` (identifiant WordPress, titre, descriptif normalisé) et `mot_article` (étiquette, contenu).
- **WordPress 6.9 et 7.1** : depuis un dossier vide et sur leurs SPIP de test : 114 mots, dont les 50 sans contenu, 188 liens dont ceux du contenu programmé et du brouillon, les deux étiquettes de même nom présentes ; vérificateur à OK.
- **Site réel** : sur son SPIP de test et depuis un dossier vide : 1 mot, lié au contenu privé ; le reste de l'export identique à celui d'avant le sous-projet.
- **Relance** : `-t importer_mots` sur un SPIP importé ne crée ni mot ni lien.
- **Échecs** : un identifiant de mot occupé par un mot d'un autre groupe ; un article lié supprimé de SPIP ; dans les deux cas code `1` et identifiants affichés.
- Tests de la préparation (`tests/preparation/tester_preparer_spip.sh --complet`) : 0 échec.

## 5. Hors périmètre

- Formats d'article (taxonomie `post_format`).
- Taxonomies propres à un site ou à un plugin (par exemple celle d'un plugin d'agenda sur le site réel) : une extension peut les importer par son propre traitement (pipeline `wp2spip_traitements`).
- Liens vers des pages d'étiquette dans les textes : aucun dans les WordPress de test.
- Adresses des mots-clés (`spip_urls`) : calculées par SPIP.
