# wp2spip — sous-projet 11 : convertisseur HTML → SPIP, sans sale

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6.
Statut : réalisé. Demandé par le mainteneur le 2026-10-09 (« trop d'erreurs » dans sale ; compatibilité PHP 8.4 acceptée) ; échappement des raccourcis SPIP demandé le même jour (un site WordPress peut documenter les raccourcis de SPIP : ils doivent rester du texte) ; autres choix décidés seul, à relire à la revue finale.

## 1. Constat

wp2spip convertit le HTML de WordPress en raccourcis SPIP par le plugin **sale** 1.0.0 : une suite d'expressions régulières appliquées au texte entier. Défauts constatés pendant les essais :

- **texte entier perdu** : un motif à retour arrière exponentiel fait échouer `preg_replace()` dès une vingtaine d'espaces consécutifs ; `sale()` rend alors une chaîne vide, sans erreur. Sur le WordPress de test 6.9, l'article « WP 6.1 Widgets block category » est importé avec un texte vide, et la référence de `composer tests-import` l'a enregistré ainsi ;
- avertissements PHP 8 à chaque image (`extraire_images()`) ;
- et, par construction (expressions régulières non gourmandes sur du HTML imbriqué) : balises imbriquées mal appariées (listes, citations, tableaux), retours à la ligne simples de l'éditeur classique perdus (affichés comme des `<br>` par WordPress, sans effet dans SPIP), entités `&lt;` `&gt;` décodées en fin de traitement (un `&lt;b&gt;` affiché comme texte par WordPress devient une balise dans SPIP).

wp2spip ne garde de sale que la conversion du HTML en raccourcis : les blocs de l'éditeur sont analysés avant (`inc/wp2spip_blocs.php`), les images, légendes, lecteurs et liens internes sont convertis après, sur la sortie de sale (`importer_articles`).

## 2. Décisions (prises seul)

| Sujet | Décision |
|---|---|
| Forme | fonction de wp2spip, `wp2spip_html_spip($html, $options)` dans `inc/wp2spip_html.php`, qui remplace chaque appel à `sale()` (10 dans wp2spip, 1 dans `wp2spip_acf`) ; plus de dépendance à sale |
| Analyse | **arbre HTML5** par `Dom\HTMLDocument` (PHP 8.4, analyseur conforme à la norme HTML5, sans libxml pour l'analyse) ; parcours récursif des nœuds, aucune expression régulière sur le HTML |
| PHP | **8.4 minimum** pour wp2spip (`paquet.xml`, `composer.json`) et ses extensions (décision du mainteneur, 2026-10-09) |
| Périmètre | même rôle que sale dans wp2spip : HTML → raccourcis ; les traitements de wp2spip avant (blocs) et après (images, légendes, lecteurs, liens internes) restent inchangés et reçoivent une sortie de même forme (balises `<img>`, `<video>`, `<audio>`, `<iframe>` gardées en HTML, liens `[texte->url]`, raccourcis WordPress `[caption]`… gardés en texte) |
| Retours à la ligne | option `autop` : contenu de l'éditeur classique, commentaires, descriptions (WordPress leur applique `wpautop()`) : ligne vide = paragraphe, retour simple = saut de ligne SPIP `_ ` ; contenu à blocs (WordPress n'applique pas `wpautop()`) : blancs du HTML réduits comme par un navigateur |
| Texte | caractères décodés (UTF-8) ; un texte affiché tel quel par WordPress l'est aussi par SPIP : `&`, `<`, `>` réécrits `&amp;`, `&lt;`, `&gt;`, et les caractères des raccourcis SPIP `{` `}` `[` `]` `|` `~` en entités numériques (`&#123;`…), ainsi qu'un `-` ou un `_` en début de ligne ; sauf les raccourcis WordPress que wp2spip convertit ensuite (`[caption]`, `[gallery]`, `[audio]`, `[video]`, `[embed]`, `[playlist]`). Entités plutôt qu'accents graves : SPIP 4.4 rend `` `…` `` en code (police à chasse fixe), les entités s'affichent comme le caractère d'origine. Le code (`<code>`, `<cadre>`) est gardé brut, SPIP l'affichant tel quel |
| Balise inconnue | gardée en HTML (ouvrante et fermante, attributs d'origine), son contenu converti ; `script`, `style`, commentaires : retirés (comme sale) |
| Marqueurs | les marqueurs de blocs protégés (`wp2spipbloc<N>`, seuls sur leur ligne) traversent la conversion inchangés, sur leur propre paragraphe |

## 3. Correspondances

| HTML | SPIP |
|---|---|
| `p` | paragraphe (ligne vide avant et après) ; vide : rien |
| `br` | `_ ` en début de ligne suivante ; en fin de bloc : rien |
| `strong`, `b` | `{{…}}` ; `em`, `i` | `{…}` ; blancs de bord sortis des accolades ; vides : rien |
| `h1`, `h2`, `h3` | intertitre `{{{…}}}` (paragraphe) ; `h4` à `h6` : paragraphe en gras `{{…}}` (comme sale) |
| `a href` | `[texte->url]` (texte converti, sans `[` `]` ni retour à la ligne) ; sans texte : `[->url]` ; `a` sans `href` : contenu seul |
| `ul`, `ol`, `li` | listes SPIP `-*`, `-#` ; à la profondeur N, le caractère de la liste répété N fois (`-##` pour une liste numérotée dans une liste à puces : SPIP 4.4 ne lit pas `-*#`) ; plusieurs paragraphes dans un élément : joints par `_ ` ; liste non fermée (l'analyseur HTML5 y range la suite du texte) : contenu hors des `li` gardé, en paragraphes après la liste (au premier niveau) ou à la suite de l'élément précédent (dans une sous-liste) |
| `table` | tableau SPIP `| … |`, cellules d'en-tête `{{…}}` sur la première ligne, `caption` en `|| légende ||`, `colspan` en cellules `<` ; tableau imbriqué, cellule sur plusieurs lignes ou `rowspan` : tableau gardé en HTML jusqu'aux cellules, une rangée par ligne, contenu des cellules converti |
| `blockquote` | `<quote>…</quote>` |
| `pre` contenant seulement `code` | `<cadre>…</cadre>` (texte brut, sans conversion) ; autre `pre` : `<poesie>…</poesie>` (comme sale) |
| `code` en ligne | `<code>…</code>` (texte brut) |
| `hr` | `----` (paragraphe) |
| `img`, `video`, `audio`, `iframe`, `object`, `embed`… | gardés tels quels (traités ensuite par wp2spip, ou interprétés par SPIP) |
| `div`, `figure`, `figcaption`, `section`… | gardés en HTML, contenu converti ; sur une ligne si le contenu tient en un paragraphe |
| `dl`, `dt`, `dd` | gardés en HTML, un terme ou une définition par ligne |
| `span`, `sup`, `sub`, `del`, `ins`, `u`, `small`… | gardés en HTML dans la ligne, contenu converti, blancs de bord hors des balises |
| `[caption]`… seul sur sa ligne (éditeur classique) | pas de saut de ligne `_ ` autour : il deviendra un document |

Sortie : paragraphes séparés par une ligne vide, sans blanc en fin de ligne, sans ligne vide en tête ni en fin.

## 4. Validation

- **Rendu par SPIP** (`HtmlSpipTest`) : une page qui documente les raccourcis SPIP (dans le texte, le code, le préformaté, les listes, les tableaux, les liens) est affichée par SPIP avec le même texte que par WordPress ; structures (gras, listes mixtes, tableaux, citations) rendues comme le HTML d'origine.
- **Tests unitaires** (sans SPIP : `Dom\HTMLDocument` est dans PHP) : chaque correspondance du § 3, imbrications (listes dans listes, gras dans liens, liens dans listes, citations dans citations), `autop` (classique, à blocs), entités, marqueurs de blocs, textes qui faisaient échouer sale (vingt espaces consécutifs, image), HTML mal formé (balises non fermées, fermantes orphelines), texte vide.
- **Comparaison sur corpus** : les contenus des WordPress 6.9 et 7.1 de test et du site réel (contenus, commentaires, descriptions, légendes) convertis par sale et par le convertisseur dans la chaîne de wp2spip ; **chaque différence classée** (correction, équivalent, régression) ; aucune régression laissée.
- **Tests de wp2spip** (unitaires, intégration, `BlocsTest`) à OK ; `composer tests-import` : la référence n'est mise à jour qu'après revue de toutes ses différences, résumées par type dans le commit ; l'article « WP 6.1 Widgets block category » n'est plus vide.
- **Site réel** sur son SPIP de test : import à code 0, vérificateur à OK ; différences d'export avec l'import par sale revues et résumées par type ; aucun texte d'article vide qui ne l'était pas dans WordPress.
- Sans sale : `paquet.xml`, `outils/preparer_spip.sh`, `scripts/install-spip-test.sh` (wp2spip, `wp2spip_yoast`, `wp2spip_acf`) n'installent plus sale ; préparation complète à 0 échec.

## 5. Hors périmètre

- Conversion des images, légendes, lecteurs et liens internes dans le convertisseur (restent dans `importer_articles` ; possible ensuite, l'arbre étant disponible).
