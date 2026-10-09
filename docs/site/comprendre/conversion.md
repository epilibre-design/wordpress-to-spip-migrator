# Conversion des contenus

Le texte WordPress combine souvent HTML, shortcodes, sérialisation de blocs et références à des objets. Les traiter comme une simple chaîne à copier perd les relations avec les médias et les contenus.

## Avant et après

Exemple source, enregistré dans `post_content` :

```html
<!-- wp:image {"id":72,"align":"left"} -->
<figure class="wp-block-image alignleft">
  <img src="https://exemple.test/wp-content/uploads/photo.jpg" class="wp-image-72">
  <figcaption>Une légende</figcaption>
</figure>
<!-- /wp:image -->
```

Avec le document `72` importé, la cible est un modèle de type `<img72|left>` et une légende enregistrée comme descriptif. La relation au fichier est explicite ; son affichage dépend du modèle et du squelette SPIP.

## Les étapes de conversion

1. Analyser les commentaires `<!-- wp:… -->` et les attributs JSON en arbre de blocs.
2. Convertir les blocs reconnus et les galeries, en conservant leur ordre.
3. Protéger temporairement les fragments déjà convertis avec des marqueurs `wp2spipblocN`.
4. Convertir le HTML restant avec `wp2spip_html_spip()`, à partir de l’arbre HTML5 construit par `Dom\HTMLDocument` de PHP 8.4.
5. Restaurer les fragments protégés et réécrire les références reconnues aux contenus et médias.

Les marqueurs empêchent le convertisseur HTML de traiter une deuxième fois un raccourci déjà produit. Les structures sélectionnées gardent des classes `wp-block-…` pour être stylées dans SPIP. Sale n’intervient plus dans cette chaîne.

## HTML5 → raccourcis SPIP

Le convertisseur est inclus dans wp2spip (`inc/wp2spip_html.php`). Il parcourt les nœuds de l’arbre HTML5, notamment pour traiter les structures imbriquées et le HTML mal formé. Les articles, commentaires, descriptions de catégories et d’étiquettes, légendes et métas textuelles utilisent ce même convertisseur.

| HTML enregistré | Résultat |
|---|---|
| Paragraphes et `<br>` | Paragraphes séparés et sauts de ligne SPIP `_ ` |
| `<strong>`, `<b>` ; `<em>`, `<i>` | Gras `{{…}}` ; italique `{…}` |
| `<h1>` à `<h3>` ; `<h4>` à `<h6>` | Intertitres `{{{…}}}` ; paragraphes en gras |
| Liens et ancres | Raccourcis SPIP, puis réécriture des références internes reconnues |
| Listes simples ou imbriquées | Listes SPIP à puces ou numérotées |
| Tableaux simples | Tableaux SPIP ; tableaux imbriqués, cellules multilignes ou `rowspan` gardés en HTML |
| Citations | `<quote>…</quote>` |
| Code en ligne ; `<pre><code>` ; autre `<pre>` | `<code>…</code>` ; `<cadre>…</cadre>` ; `<poesie>…</poesie>` |
| Images, audio, vidéo et contenus embarqués | Balises conservées pour les conversions de médias suivantes |
| Autres structures et balises | HTML conservé avec contenu converti ; scripts, styles et commentaires retirés |

### Retours à la ligne

Le mode `autop` suit les deux représentations de WordPress. Pour l’éditeur classique, les commentaires et les descriptions, une ligne vide devient un paragraphe et un retour simple devient un saut de ligne SPIP. Pour un article contenant des blocs, les blancs du HTML sont réduits comme dans le navigateur ; ses retours de ligne de sérialisation ne deviennent pas des sauts visibles. Les légendes de blocs sont aussi converties sans `autop`.

### Texte qui montre des raccourcis SPIP

Les caractères affichés comme texte par WordPress restent du texte dans SPIP. Le convertisseur échappe les caractères des raccourcis (`{`, `}`, `[`, `]`, `|`, `~`, ainsi que `-` ou `_` en début de ligne) et les caractères HTML au moyen d’entités. Par exemple, le texte littéral `{{gras}}` ne devient pas du gras, tandis que `<strong>gras</strong>` devient le raccourci `{{gras}}`.

Le code et le préformaté gardent leur contenu brut, selon le format SPIP produit. Les shortcodes WordPress reconnus (`[caption]`, `[gallery]`, `[audio]`, `[video]`, `[embed]`, `[playlist]`) restent disponibles pour leur conversion par les étapes dédiées.

Les tests unitaires `HtmlTest` contrôlent la conversion ; `HtmlSpipTest` contrôle le texte visible et les structures après rendu par `propre()` de SPIP. Le remplacement de Sale corrige notamment une perte de texte sur de longues suites d’espaces ; les références d’import ont été mises à jour après comparaison des différences, selon le [plan de réalisation](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/docs/superpowers/plans/2026-10-09-wp2spip-convertisseur-html.md).

## Trois cas à distinguer

| Forme enregistrée | Possibilité de conversion |
|---|---|
| HTML autonome | Peut être converti, avec vérification du résultat |
| Bloc sérialisé avec attributs et HTML | Peut recevoir un traitement spécialisé |
| Bloc calculé ou référence vers un autre objet | Demande une résolution ou une reconstruction ; aucun rendu complet n’est garanti |

Le moteur n’exécute pas WordPress pour calculer tous ses blocs. Les blocs dynamiques identifiés conservent leur texte ou média enregistré exploitable et sont retirés lorsqu’ils n’en ont pas ; le HTML enregistré des blocs inconnus est conservé et signalé. Un bloc inconnu sans contenu enregistré peut donc ne fournir aucun texte à conserver.

## Compléter une conversion

Le pipeline `wp2spip_bloc` reçoit le bloc et la sortie produite, ou `null` si aucun traitement ne l’a reconnu. Une extension peut ajouter une conversion adaptée à un type précis. Documenter sa structure source et tester médias, attributs, contenu et relations.

Cette possibilité d’extension n’implémente pas les fonctions de plugins WordPress par elle-même. Les shortcodes propres à un thème ou une extension demandent un traitement explicite.

Sources : [convertisseur HTML5](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_html.php), [analyse des blocs](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/inc/wp2spip_blocs.php), [intégration dans les articles](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [tests unitaires](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/unit/HtmlTest.php), [tests de rendu](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/HtmlSpipTest.php).
