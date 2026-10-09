# wp2spip — sous-projet 11 : convertisseur HTML → SPIP, sans sale

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6.
Statut : demandé par le mainteneur le 2026-10-09 (« trop d'erreurs » dans sale ; compatibilité PHP 8.4 acceptée) ; design décidé seul (mandat de réalisation autonome), à relire à la revue finale.

## 1. Constat

wp2spip convertit le HTML de WordPress en raccourcis SPIP par le plugin **sale** 1.0.0 : une suite d'expressions régulières appliquées au texte entier. Défauts constatés (sous-projet 10, `docs/signalements/sale-extraire-images.md`) :

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
| Texte | caractères décodés (UTF-8) ; `&`, `<`, `>` du texte réécrits `&amp;`, `&lt;`, `&gt;` (comme `wp2spip_decoder_entites()`) : un texte affiché tel quel par WordPress l'est aussi par SPIP |
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
| `ul`, `ol`, `li` | listes SPIP `-*`, `-#`, imbriquées `-**`, `-*#`… ; plusieurs paragraphes dans un élément : joints par `_ ` |
| `table` | tableau SPIP `| … |`, cellules d'en-tête `{{…}}` sur la première ligne, `caption` en `|| légende ||` ; `|` des cellules en `&#124;` ; tableau imbriqué ou cellule à plusieurs paragraphes : tableau gardé en HTML (contenu des cellules converti) |
| `blockquote` | `<quote>…</quote>` |
| `pre` contenant seulement `code` | `<cadre>…</cadre>` (texte brut, sans conversion) ; autre `pre` : `<poesie>…</poesie>` (comme sale) |
| `code` en ligne | `<code>…</code>` (texte brut) |
| `hr` | `----` (paragraphe) |
| `img`, `video`, `audio`, `iframe`, `object`, `embed`, `source`, `figure`, `figcaption`, `div`, `span`, `sup`, `sub`, `del`, `ins`, `u`, `small`… | gardés en HTML (traités ensuite par wp2spip, ou interprétés par SPIP) |

Sortie : paragraphes séparés par une ligne vide, sans blanc en fin de ligne, sans ligne vide en tête ni en fin.

## 4. Validation

- **Tests unitaires** (sans SPIP : `Dom\HTMLDocument` est dans PHP) : chaque correspondance du § 3, imbrications (listes dans listes, gras dans liens, liens dans listes, citations dans citations), `autop` (classique, à blocs), entités, marqueurs de blocs, textes qui faisaient échouer sale (vingt espaces consécutifs, image), HTML mal formé (balises non fermées, fermantes orphelines), texte vide.
- **Comparaison sur corpus** : les contenus des WordPress 6.9 et 7.1 de test et du site réel (contenus, commentaires, descriptions, légendes) convertis par sale et par le convertisseur dans la chaîne de wp2spip ; **chaque différence classée** (correction, équivalent, régression) ; aucune régression laissée.
- **Tests de wp2spip** (unitaires, intégration, `BlocsTest`) à OK ; `composer tests-import` : la référence n'est mise à jour qu'après revue de toutes ses différences, résumées par type dans le commit ; l'article « WP 6.1 Widgets block category » n'est plus vide.
- **Site réel** sur son SPIP de test : import à code 0, vérificateur à OK ; différences d'export avec l'import par sale revues et résumées par type ; aucun texte d'article vide qui ne l'était pas dans WordPress.
- Sans sale : `paquet.xml`, `outils/preparer_spip.sh`, `scripts/install-spip-test.sh` (wp2spip, `wp2spip_yoast`, `wp2spip_acf`) n'installent plus sale ; préparation complète à 0 échec.

## 5. Hors périmètre

- Conversion des images, légendes, lecteurs et liens internes dans le convertisseur (restent dans `importer_articles` ; possible ensuite, l'arbre étant disponible).
- Échappement des caractères que SPIP interprète dans un texte (`{`, `[`, `|`, `-` en début de ligne…) : même comportement que sale.
- Publication des signalements à sale (le défaut reste à signaler à ses mainteneurs).
