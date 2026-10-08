# wp2spip — sous-projet 3 : balisage des blocs de l'éditeur

Date : 2026-10-08
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 3.
Statut : design validé, à planifier.

## 1. Constat

Depuis WordPress 5.0, l'éditeur de blocs enregistre chaque contenu comme une suite de blocs : du HTML entouré de commentaires `<!-- wp:nom {attributs JSON} -->` … `<!-- /wp:nom -->`. Aujourd'hui, sale retire les commentaires et les `<p>`, et `importer_articles` convertit les `<img>` en `<imgN>`, mais il reste dans les textes SPIP (contenu *Theme Unit Test*, WordPress 6.9) :

- les enveloppes `<figure>`, `<div class="wp-block-…">`, `<figcaption>` (157 `div`, 139 `figure`, 67 `figcaption`) ;
- l'alignement des images, perdu : il est porté par la `<figure>`, pas par l'`<img>` ;
- les galeries, devenues des listes de `<figure>` ;
- les blocs Couverture, dont l'image reste en `style="background-image:url(…)"` ;
- colonnes, groupes, boutons avec leurs classes et styles de présentation ;
- les blocs embarqués (YouTube…) ;
- le raccourci `[gallery]` de l'éditeur classique, laissé tel quel.

Les blocs dynamiques (requêtes, derniers articles, widgets, blocs de thème) n'ont pas de contenu enregistré et ne produisent rien.

Un site sous éditeur classique n'est presque pas concerné (le site réel n'a qu'un bloc), hormis `[gallery]`.

## 2. Décisions

| Sujet | Décision |
|---|---|
| Mise en page (colonnes, groupe, couverture, média et texte, boutons…) | **structure conservée**, avec les seules classes `wp-block-…` utiles ; styles en ligne, classes de couleur, de taille, attributs `aria`/`role` retirés. Le squelette SPIP stylera ces classes |
| Galeries (bloc `gallery`, raccourci `[gallery]`) | un **objet album** du plugin Albums par galerie, toujours écrit `<albumN>` |
| Plugin Albums | dès qu'une galerie est détectée, wp2spip le **télécharge et l'active** au début de l'import (le SPIP de départ est vierge), puis relance l'import (§ 4) |
| Contenus embarqués | l'URL seule sur sa ligne, puis la légende ; le plugin **oEmbed** (`<utilise>`) en fait un lecteur, sans lui SPIP en fait un lien |
| Méthode | **analyse des blocs avant sale**, d'après les commentaires et leurs attributs JSON (§ 3) |

## 3. Conversion des blocs

### 3.1 Principe

`importer_articles` passe le contenu brut à `wp2spip_convertir_blocs()` **avant** sale :

1. **Analyse** : le contenu est découpé en arbre de blocs, comme le fait `parse_blocks()` de WordPress : nom (`core/` implicite), attributs JSON, HTML interne, blocs enfants ; les blocs auto-fermants (`<!-- wp:nom /-->`) sont pris en compte ; le HTML hors bloc est gardé tel quel. Un contenu sans `<!-- wp:` n'est pas modifié.
2. **Conversion** : chaque bloc est confié à la fonction de son type, qui reçoit le bloc (nom, attributs, HTML interne, enfants déjà convertis) et retourne du texte.
3. **Protection** : ce qu'une conversion produit en raccourcis SPIP ou en balises de structure est remplacé par un marqueur (`wp2spipbloc<N>`, sur sa propre ligne) le temps du passage par sale, puis réinséré ; le texte libre à l'intérieur des blocs passe par sale comme aujourd'hui.

Fichier : `inc/wp2spip_blocs.php` (analyseur, aiguillage, conversions). Les conversions existantes de `importer_articles` (liens, `[caption]`, `<img>`, lecteurs audio et vidéo) s'appliquent ensuite inchangées au texte obtenu.

**Pipeline `wp2spip_bloc`** : reçoit `args` (le bloc) et `data` (le texte produit, ou `null` si aucune conversion ne le prend en charge) ; une extension peut ajouter ou remplacer la conversion d'un type de bloc.

### 3.2 Retrouver un média

Les identifiants des attributs (`id`, `ids`, `data-id`) et des classes `wp-image-N` ne sont pas fiables : l'import du contenu de test les renumérote sans les corriger. Comme pour les `<img>` aujourd'hui, un média se retrouve **d'abord par son fichier** (`src`, `url`, via `wp2spip_chercher_document()`), **puis par son identifiant**. Un média introuvable laisse son HTML d'origine, compté dans le bilan des liens non convertis.

### 3.3 Table des conversions

| Bloc | Résultat SPIP |
|---|---|
| `image` | `<imgN\|alignement>` (attribut `align` : left, right, center ; wide et full → center) ; légende (`figcaption`) → descriptif du document ; lien sur l'image → `[<imgN\|alignement>->lien]` (lien interne converti comme les autres) |
| `gallery` | album (§ 3.4) → `<albumN>` ; légende de la galerie → descriptif de l'album |
| `cover` | `<div class="wp-block-cover">` contenant `<docN>` de l'image ou de la vidéo de fond, puis le texte du bloc |
| `media-text` | `<div class="wp-block-media-text">` contenant `<docN>` puis le texte |
| `columns`, `column`, `group`, `buttons`, `pullquote`, `table`, `quote` | balise d'origine avec sa seule classe `wp-block-…` (et `is-style-…` retiré) ; contenu converti |
| `button` | `[texte->url]` dans `<div class="wp-block-button">` |
| `audio`, `video`, `file` | `<docN>` |
| `embed`, `core-embed/*` | URL seule entre deux lignes vides, puis la légende |
| `paragraph`, `heading`, `list`, `list-item`, `code`, `preformatted`, `verse`, `html`, `separator`, `spacer`, `more`, `nextpage`, `shortcode` | HTML interne laissé à sale, comme aujourd'hui (`spacer`, `more`, `nextpage` : rien) |
| blocs dynamiques sans HTML interne (`query`, `latest-posts`, `latest-comments`, `archives`, `categories`, `calendar`, `tag-cloud`, `rss`, `search`, `page-list`, `navigation`, `social-links`, `post-*`, `comment*`, `site-*`, `loginout`, `avatar`…) | retirés ; comptés dans le bilan |
| bloc inconnu | HTML interne conservé et passé par sale ; nom compté dans le bilan, détail avec `-v` |

Classes conservées : `wp-block-<nom>` du bloc et celles qui décrivent sa structure (`wp-block-column`, `wp-block-cover__inner-container`…) ; tout le reste (couleurs, tailles, `has-…`, `is-…`, `align…` déjà traduits, `style`, `aria-*`, `role`, `data-*`) est retiré.

### 3.4 Galeries et albums

- **Bloc `gallery`** : images dans l'ordre du bloc (blocs `image` enfants depuis WordPress 5.9, liste `blocks-gallery-item` avant).
- **Raccourci `[gallery]`** (éditeur classique) : images de l'attribut `ids`, dans cet ordre ; sans `ids`, les médias image rattachés au contenu (`post_parent`), dans l'ordre de `menu_order` puis de leur identifiant. Les autres attributs (`columns`, `size`, `orderby`…) ne sont pas repris.
- Pour chaque galerie, un album est créé (`objet_inserer('album')`, identifiant automatique : une galerie n'a pas d'identifiant WordPress) :
  - titre : titre du contenu, suivi de « (galerie n) » s'il en contient plusieurs ;
  - descriptif : légende de la galerie ;
  - statut `publie`, date du contenu ;
  - documents liés à l'album dans l'ordre (`rang_lien`), la légende de chaque image devenant le descriptif de son document ;
  - album lié à l'article.
- Une galerie dont aucune image n'est retrouvée ne crée pas d'album : son HTML reste, compté dans le bilan.
- Limite : un album n'est pas couvert par les zones d'Accès restreint ; les images d'une galerie d'un contenu privé restent accessibles par l'album, comme le sont déjà les documents par leur URL.

## 4. Téléchargement et activation d'Albums

Avant le premier traitement, **quels que soient les traitements demandés** (`--traitements` compris), la commande vérifie les plugins requis par le contenu WordPress :

1. Elle cherche, dans les contenus à importer (`post`, `page`), un bloc `wp:gallery` ou un raccourci `[gallery`.
2. S'il y en a une, et qu'Albums n'est pas actif — le cas normal, le SPIP de départ étant vierge —, elle l'annonce, puis lance en sous-processus, avec l'exécutable SPIP-Cli en cours : `plugins:svp:telecharger albums -y`, `plugins:activer albums -y`, `plugins:maj:bdd`.
3. Le processus en cours ne connaît pas un plugin activé après son démarrage (tables, API, pipelines). La commande d'import est donc **relancée** dans un processus neuf, avec les mêmes arguments et la variable d'environnement `WP2SPIP_RELANCE=1` ; son code de sortie devient celui de la commande.
4. **Échec** (dépôt SVP absent, réseau, plugin introuvable, Albums toujours inactif après la relance) : message qui donne les commandes à lancer à la main, et code `1`, avant tout traitement.

Cette étape est une fonction de la commande (`WordpressImporter::verifier_plugins()`), pas un traitement de la liste : elle ne peut pas être écartée par `--traitements`.

`plugins:svp:telecharger` ne fonctionne qu'avec la version corrigée de SPIP-Cli (correctifs proposés en amont : sélection du plugin, autorisation, remontée des erreurs). Le message d'échec le mentionne.

## 5. Dépendances

| Plugin | Lien | Rôle |
|---|---|---|
| albums (≥ 4.0) | téléchargé et activé par wp2spip si une galerie est détectée (`<utilise>` dans `paquet.xml`) | galeries |
| oembed | `<utilise>` | lecteurs pour les contenus embarqués |

## 6. Bilan de l'import

`importer_articles` ajoute à son bilan : nombre de blocs convertis par type, albums créés, blocs dynamiques retirés, blocs inconnus (détail par contenu avec `-v`).

## 7. Validation

- **`tests/integration/tester_blocs.php`** (lancé par `spip php:eval`, code `1` en cas d'écart) : fragments tirés du contenu *Theme Unit Test* (image alignée et légendée, galerie avant et après 5.9, couverture, colonnes, bouton, embarqué, bloc dynamique, bloc inconnu, `[gallery]` avec et sans `ids`) et texte attendu après conversion.
- **`verifier_identifiants.php`**, complété : aucun `<!-- wp:` ni attribut `style=` dans les textes ; classes `wp-block-…` limitées à la liste conservée ; chaque `<albumN>` désigne un album existant, lié à l'article et contenant au moins un document.
- **WordPress 6.9 et 7.1** : import depuis un SPIP vierge sans Albums (téléchargement, activation et relance automatiques), y compris avec `--traitements=importer_articles` seul, puis vérification ; relecture des textes des contenus *Block: …* et *WP 6.1 … blocks*.
- **Site réel** : export identique à la référence, hors le contenu qui a un bloc.
- **Échec du téléchargement** simulé (dépôt SVP absent) : code `1`, aucun traitement lancé.

## 8. Hors périmètre

- Reproduire l'apparence WordPress (feuille de style des classes `wp-block-…`) : à la charge du squelette.
- Les attributs de présentation des galeries (`columns`, `size`, recadrage) : l'album s'affiche selon sa propre configuration.
- Les blocs réutilisables (`wp:block {"ref":N}`) : traités comme des blocs inconnus ; à reprendre si un site en dépend.
