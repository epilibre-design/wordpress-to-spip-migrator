# Corpus de vérification des correctifs de sale : 103 contenus du jeu *Theme Unit Test*

Annexe de `sale-extraire-images.md`. Textes passés à `sale()` pour vérifier les correctifs proposés, publiés ici pour que d'autres puissent refaire la vérification.

## Origine

- Jeu de contenus de test de WordPress, *Theme Unit Test* (dépôt public [`WPTT/theme-test-data`](https://github.com/WPTT/theme-test-data), fichier `themeunittestdata.wordpress.xml`, licence GPL), importé dans un WordPress 6.9 installé en français.
- Contenus retenus : toutes les lignes de `wp_posts` dont `post_content` n'est pas vide, par `ID` croissant : 58 `post`, 22 `page`, 18 `attachment`, 4 `nav_menu_item`, 1 `wp_navigation`, soit 103.
- Textes tels que stockés dans la base, à l'octet près. L'import a remplacé l'adresse des médias du jeu par celle du WordPress local (`http://localhost:8766`) : sans effet sur la conversion.
- Deux contenus sont convertis en chaîne vide par sale 1.0.0 (point 1 du signalement) : **34** et **51**, marqués « perdu par sale » ci-dessous.

## Format

Un titre par contenu (`ID`, type, statut, titre), puis le texte dans un bloc délimité par ```` ````html ```` et ```` ```` ````. Le texte est exactement ce qui se trouve entre la ligne d'ouverture et la ligne de fermeture, saut de ligne final de la ligne d'ouverture exclu, celui qui précède la fermeture aussi.

## Rejouer

Depuis le dossier d'un SPIP où sale est actif, ce fichier copié à côté sous le nom `corpus.md` :

```bash
spip php:eval '
error_reporting(E_ALL); ini_set("display_errors", "0");
$avertissements = 0; set_error_handler(function () use (&$avertissements) { $avertissements++; return true; });
include_spip("sale_fonctions");
preg_match_all("/^### (\d+) .*?\n\n````html\n(.*?)\n````$/ms", file_get_contents("corpus.md"), $m, PREG_SET_ORDER);
foreach ($m as $c) { if (trim((string) sale($c[2])) === "" and trim(strip_tags($c[2])) !== "") echo "texte perdu : ", $c[1], "\n"; }
echo count($m), " contenus, $avertissements avertissements\n";'
```

Résultats (SPIP 4.4, PHP 8.4) :

| sale | Textes perdus | Avertissements |
|---|---|---|
| 1.0.0 | 34, 51 | 98 |
| correctif 1 seul (motif de fin de texte) | aucun | 96 |
| correctif 2 seul (`extraire_images()`) | 34, 51 | 2 |
| les deux correctifs | aucun | 0 |

Avec le correctif 1, seuls les textes 34 et 51 changent ; avec le correctif 2, aucun texte ne change. Le contenu 1832 (`wp_navigation`, commentaires de blocs sans texte) est converti en chaîne vide dans tous les cas : ce n'est pas une perte, le script ne le compte pas.

## Contenus

### 1 — post, publish — Bonjour tout le monde !

````html
<!-- wp:paragraph -->
<p>Bienvenue sur WordPress. Ceci est votre premier article. Modifiez-le ou supprimez-le, puis commencez à écrire !</p>
<!-- /wp:paragraph -->
````

### 2 — page, publish — Page d’exemple

````html
<!-- wp:paragraph -->
<p>Ceci est une page d’exemple. C’est différent d’un article de blog parce qu’elle restera au même endroit et apparaîtra dans la navigation de votre site (dans la plupart des thèmes). La plupart des gens commencent par une page « À propos » qui les présente aux personnes visitant le site. Cela pourrait ressembler à quelque chose comme cela :</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
<!-- wp:paragraph -->
<p>Bonjour ! Je suis un mécanicien qui aspire à devenir acteur, et voici mon site. J’habite à Bordeaux, j’ai un super chien baptisé Russell, et j’aime la vodka (ainsi qu’être surpris par la pluie soudaine lors de longues balades sur la plage au coucher du soleil).</p>
<!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p>…ou quelque chose comme cela :</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
<!-- wp:paragraph -->
<p>La société 123 Machin Truc a été créée en 1971, et n’a cessé de proposer au public des machins-trucs de qualité depuis lors. Située à Saint-Remy-en-Bouzemont-Saint-Genest-et-Isson, 123 Machin Truc emploie 2 000 personnes, et fabrique toutes sortes de bidules supers pour la communauté bouzemontoise.</p>
<!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p>En tant que nouvel utilisateur ou utilisatrice de WordPress, vous devriez vous rendre sur <a href="http://localhost:8766/wp-admin/">votre tableau de bord</a> pour supprimer cette page et créer de nouvelles pages pour votre contenu. Amusez-vous bien !</p>
<!-- /wp:paragraph -->
````

### 3 — page, draft — Politique de confidentialité

````html
<!-- wp:heading -->
<h2 class="wp-block-heading">Qui sommes-nous ?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>L’adresse de notre site est : http://localhost:8766.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Commentaires</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Quand vous laissez un commentaire sur notre site, les données inscrites dans le formulaire de commentaire, ainsi que votre adresse IP et l’agent utilisateur de votre navigateur sont collectés pour nous aider à la détection des commentaires indésirables.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Une chaîne anonymisée créée à partir de votre adresse e-mail (également appelée hash) peut être envoyée au service Gravatar pour vérifier si vous utilisez ce dernier. Les clauses de confidentialité du service Gravatar sont disponibles ici : https://automattic.com/privacy/. Après validation de votre commentaire, votre photo de profil sera visible publiquement à coté de votre commentaire.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Médias</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Si vous téléversez des images sur le site, nous vous conseillons d’éviter de téléverser des images contenant des données EXIF de coordonnées GPS. Les personnes visitant votre site peuvent télécharger et extraire des données de localisation depuis ces images.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Cookies</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Si vous déposez un commentaire sur notre site, il vous sera proposé d’enregistrer votre nom, adresse e-mail et site dans des cookies. C’est uniquement pour votre confort afin de ne pas avoir à saisir ces informations si vous déposez un autre commentaire plus tard. Ces cookies expirent au bout d’un an.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Si vous vous rendez sur la page de connexion, un cookie temporaire sera créé afin de déterminer si votre navigateur accepte les cookies. Il ne contient pas de données personnelles et sera supprimé automatiquement à la fermeture de votre navigateur.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Lorsque vous vous connecterez, nous mettrons en place un certain nombre de cookies pour enregistrer vos informations de connexion et vos préférences d’écran. La durée de vie d’un cookie de connexion est de deux jours, celle d’un cookie d’option d’écran est d’un an. Si vous cochez « Se souvenir de moi », votre cookie de connexion sera conservé pendant deux semaines. Si vous vous déconnectez de votre compte, le cookie de connexion sera effacé.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>En modifiant ou en publiant une publication, un cookie supplémentaire sera enregistré dans votre navigateur. Ce cookie ne comprend aucune donnée personnelle. Il indique simplement l’ID de la publication que vous venez de modifier. Il expire au bout d’un jour.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Contenu embarqué depuis d’autres sites</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Les articles de ce site peuvent inclure des contenus intégrés (par exemple des vidéos, images, articles…). Le contenu intégré depuis d’autres sites se comporte de la même manière que si le visiteur se rendait sur cet autre site.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Ces sites web pourraient collecter des données sur vous, utiliser des cookies, embarquer des outils de suivis tiers, suivre vos interactions avec ces contenus embarqués si vous disposez d’un compte connecté sur leur site web.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Utilisation et transmission de vos données personnelles</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Si vous demandez une réinitialisation de votre mot de passe, votre adresse IP sera incluse dans l’e-mail de réinitialisation.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Durées de stockage de vos données</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Si vous laissez un commentaire, le commentaire et ses métadonnées sont conservés indéfiniment. Cela permet de reconnaître et approuver automatiquement les commentaires suivants au lieu de les laisser dans la file de modération.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Pour les comptes qui s’inscrivent sur notre site (le cas échéant), nous stockons également les données personnelles indiquées dans leur profil. Tous les comptes peuvent voir, modifier ou supprimer leurs informations personnelles à tout moment (à l’exception de leur identifiant). Les gestionnaires du site peuvent aussi voir et modifier ces informations.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Les droits que vous avez sur vos données</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Si vous avez un compte ou si vous avez laissé des commentaires sur le site, vous pouvez demander à recevoir un fichier contenant toutes les données personnelles que nous possédons à votre sujet, incluant celles que vous nous avez fournies. Vous pouvez également demander la suppression des données personnelles vous concernant. Cela ne prend pas en compte les données stockées à des fins administratives, légales ou pour des raisons de sécurité.</p>
<!-- /wp:paragraph -->
<!-- wp:heading -->
<h2 class="wp-block-heading">Où vos données sont envoyées</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong class="privacy-policy-tutorial">Texte suggéré : </strong>Les commentaires des visiteurs peuvent être vérifiés à l’aide d’un service automatisé de détection des commentaires indésirables.</p>
<!-- /wp:paragraph -->

````

### 8 — post, publish — WP 6.1 Text category blocks

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1} -->
<h1>H1 Heading</h1>
<!-- /wp:heading -->

<!-- wp:heading -->
<h2>H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>H3 Heading</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":4} -->
<h4>H4 Heading</h4>
<!-- /wp:heading -->

<!-- wp:heading {"level":5} -->
<h5>H5 Heading</h5>
<!-- /wp:heading -->

<!-- wp:heading {"level":6} -->
<h6>H6 Heading</h6>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><!-- wp:list-item -->
<li>List</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>List</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol><!-- wp:list-item -->
<li>List</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>List</li>
<!-- /wp:list-item --></ol>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol><!-- wp:list-item -->
<li>List<!-- wp:list -->
<ul><!-- wp:list-item -->
<li>List</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></li>
<!-- /wp:list-item --></ol>
<!-- /wp:list -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>Quote block</p>
<!-- /wp:paragraph --><cite>citation</cite></blockquote>
<!-- /wp:quote -->

<p>classic block</p>

<!-- wp:code -->
<pre class="wp-block-code"><code>code block</code></pre>
<!-- /wp:code -->

<!-- wp:preformatted -->
<pre class="wp-block-preformatted">Preformatted block</pre>
<!-- /wp:preformatted -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>Pull quote</p><cite>Citation</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:table {"className":"is-style-regular"} -->
<figure class="wp-block-table is-style-regular"><table><tbody><tr><td>table cell</td><td>table cell two</td></tr><tr><td>table cell three</td><td>table cell four</td></tr></tbody></table><figcaption class="wp-element-caption">Table caption</figcaption></figure>
<!-- /wp:table -->

<!-- wp:table {"className":"is-style-regular"} -->
<figure class="wp-block-table is-style-regular"><table><thead><tr><th>header label one</th><th>header label two</th></tr></thead><tbody><tr><td>table cell</td><td>table cell two</td></tr><tr><td>table cell three</td><td>table cell four</td></tr></tbody><tfoot><tr><td>footer label one</td><td>footer label two</td></tr></tfoot></table><figcaption class="wp-element-caption">Table caption</figcaption></figure>
<!-- /wp:table -->

<!-- wp:verse -->
<pre class="wp-block-verse">Verse block</pre>
<!-- /wp:verse -->
````

### 21 — post, publish — WP 6.1 Media category blocks

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Image block:</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":616,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050727_091048_222.jpg" alt="dsc20050727_091048_222" class="wp-image-616"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Gallery:</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped"><!-- wp:image {"id":755,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" class="wp-image-755"/><figcaption class="wp-element-caption">Golden Gate Bridge</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":770,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg" alt="Huatulco Coastline" class="wp-image-770"/><figcaption class="wp-element-caption">Coastline in Huatulco, Oaxaca, Mexico</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":760,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc09114.jpg" alt="Sydney Harbor Bridge" class="wp-image-760"/><figcaption class="wp-element-caption">Sydney Harbor Bridge</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":757,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" class="wp-image-757"/><figcaption class="wp-element-caption">Boardwalk at Westport, WA</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":617,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" class="wp-image-617"/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>Audio:</p>
<!-- /wp:paragraph -->

<!-- wp:audio -->
<figure class="wp-block-audio"><audio controls src="https://upload.wikimedia.org/wikipedia/commons/d/dd/Armstrong_Small_Step.ogg"></audio></figure>
<!-- /wp:audio -->

<!-- wp:paragraph -->
<p>Cover:</p>
<!-- /wp:paragraph -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","id":761,"dimRatio":50} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-761" alt="Wind Farm" src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Write title...</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","id":761,"hasParallax":true,"dimRatio":50,"isDark":false} -->
<div class="wp-block-cover is-light has-parallax"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div role="img" class="wp-block-cover__image-background wp-image-761 has-parallax" style="background-position:50% 50%;background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg)"></div><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Fixed background</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg","id":1025,"isRepeated":true,"dimRatio":50,"isDark":false} -->
<div class="wp-block-cover is-light is-repeated"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div role="img" class="wp-block-cover__image-background wp-image-1025 is-repeated" style="background-position:50% 50%;background-image:url(http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg)"></div><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Repeated background</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg","id":1025,"hasParallax":true,"isRepeated":true,"dimRatio":50,"isDark":false} -->
<div class="wp-block-cover is-light has-parallax is-repeated"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div role="img" class="wp-block-cover__image-background wp-image-1025 has-parallax is-repeated" style="background-position:50% 50%;background-image:url(http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg)"></div><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Fixed and Repeated background</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050727_091048_222.jpg","id":616,"dimRatio":50,"style":{"color":{"duotone":["#8c00b7","#fcff41"]}}} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-616" alt="dsc20050727_091048_222" src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050727_091048_222.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Duotone</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"top left","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-top-left"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Top left</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"top center","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-top-center"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Top center</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"top right","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-top-right"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Top right</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"center left","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-center-left"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Center left</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"center right","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-center-right"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Center right</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"bottom left","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Bottom left</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"bottom center","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-center"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Bottom center</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"dimRatio":50,"contentPosition":"bottom right","style":{"color":{}}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-right"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-759" alt="Rain Ripples" src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Bottom right</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:file {"id":759,"href":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg"} -->
<div class="wp-block-file"><a id="wp-block-file--media-3dd94643-f537-4ae7-b7e5-7c654669ece9" href="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg">Image</a><a href="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" class="wp-block-file__button wp-element-button" download aria-describedby="wp-block-file--media-3dd94643-f537-4ae7-b7e5-7c654669ece9">Download</a></div>
<!-- /wp:file -->

<!-- wp:media-text {"mediaId":757,"mediaLink":"https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082/","mediaType":"image"} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" class="wp-image-757 size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…"} -->
<p>This is the Media &amp; Text block with an image on the left.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

<!-- wp:media-text {"mediaId":1029,"mediaLink":"https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-1200x4002/","mediaType":"image","imageFill":true} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile is-image-fill"><figure class="wp-block-media-text__media" style="background-image:url(http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg);background-position:50% 50%"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x4002" class="wp-image-1029 size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…"} -->
<p>This is the Media &amp; Text block with a cropped image on the left</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

<!-- wp:media-text {"mediaPosition":"right","mediaId":1690,"mediaLink":"https://wpthemetestdata.wordpress.com/?attachment_id=1690","mediaType":"video"} -->
<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…"} -->
<p>This is the Media &amp; Text block with a video the right.</p>
<!-- /wp:paragraph --></div><figure class="wp-block-media-text__media"><video controls src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure></div>
<!-- /wp:media-text -->

<!-- wp:video {"id":1690} -->
<figure class="wp-block-video"><video controls src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure>
<!-- /wp:video -->
````

### 24 — post, publish — WP 6.1 Design category blocks

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Button</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Outline button</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>One single column inside a columns block.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"backgroundColor":"pale-cyan-blue"} -->
<div class="wp-block-column has-pale-cyan-blue-background-color has-background"><!-- wp:paragraph -->
<p>Column one.  The background color is on the single column.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"backgroundColor":"pale-cyan-blue"} -->
<div class="wp-block-column has-pale-cyan-blue-background-color has-background"><!-- wp:paragraph -->
<p>Column two</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"backgroundColor":"pale-pink"} -->
<div class="wp-block-columns has-pale-pink-background-color has-background"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column one. The background color is on the parent columns block.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column two</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column three</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p>Group with paragraph inside. Below are the group block variations:</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-default","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group is-style-default"><!-- wp:paragraph -->
<p>Row</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Row</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p>Stack</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Stack</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph -->
<p>More block:</p>
<!-- /wp:paragraph -->

<!-- wp:more -->
<!--more-->
<!-- /wp:more -->

<!-- wp:paragraph -->
<p>Page break:</p>
<!-- /wp:paragraph -->

<!-- wp:nextpage -->
<!--nextpage-->
<!-- /wp:nextpage -->

<!-- wp:paragraph -->
<p>Separators:</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Default style, no alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Default style, wide alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"wide"} -->
<hr class="wp-block-separator alignwide has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Default style, full width:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"full"} -->
<hr class="wp-block-separator alignfull has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Default style, align center:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"center"} -->
<hr class="wp-block-separator aligncenter has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Wide style, no alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Wide style, wide alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"wide","className":"is-style-wide"} -->
<hr class="wp-block-separator alignwide has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Wide style, full width:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"full","className":"is-style-wide"} -->
<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Wide style, align center:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"center","className":"is-style-wide"} -->
<hr class="wp-block-separator aligncenter has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Dotted style, no alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"className":"is-style-dots"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Dotted style, wide alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"wide","className":"is-style-dots"} -->
<hr class="wp-block-separator alignwide has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Dotted style, full width:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"full","className":"is-style-dots"} -->
<hr class="wp-block-separator alignfull has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Dotted style, align center:</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"align":"center","className":"is-style-dots"} -->
<hr class="wp-block-separator aligncenter has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>Spacer:</p>
<!-- /wp:paragraph -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
````

### 34 — post, publish — WP 6.1 Widgets block category — **perdu par sale**

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Archives block:</p>
<!-- /wp:paragraph -->

<!-- wp:archives /-->

<!-- wp:calendar /-->

<!-- wp:paragraph -->
<p>Categories list:</p>
<!-- /wp:paragraph -->

<!-- wp:categories /-->

<!-- wp:paragraph -->
<p>Custom HTML:</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<b> test </b>
<!-- /wp:html -->

<!-- wp:paragraph -->
<p>Latest comments:</p>
<!-- /wp:paragraph -->

<!-- wp:latest-comments /-->

<!-- wp:paragraph -->
<p>Latest posts:</p>
<!-- /wp:paragraph -->

<!-- wp:latest-posts /-->

<!-- wp:paragraph -->
<p>Page list block:</p>
<!-- /wp:paragraph -->

<!-- wp:page-list /-->

<!-- wp:paragraph -->
<p>RSS block:</p>
<!-- /wp:paragraph -->

<!-- wp:rss {"feedURL":"https://wordpress.org/news/feed/"} /-->

<!-- wp:search {"label":"Search","buttonText":"Search"} /-->

<!-- wp:search {"label":"Search","buttonText":"Search","buttonPosition":"button-inside"} /-->

<!-- wp:search {"label":"Search","buttonText":"Search","buttonPosition":"no-button"} /-->

<!-- wp:search {"label":"Search","buttonText":"Search","buttonUseIcon":true} /-->

<!-- wp:search {"label":"Search","buttonText":"Search","buttonPosition":"button-inside","buttonUseIcon":true} /-->

<!-- wp:paragraph -->
<p>Shortcode block:</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode /-->

<!-- wp:paragraph -->
<p>Social links:</p>
<!-- /wp:paragraph -->

<!-- wp:social-links -->
<ul class="wp-block-social-links"><!-- wp:social-link {"url":"#3fgfhgfh","service":"fivehundredpx"} /-->

<!-- wp:social-link {"url":"#","service":"wordpress"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:social-links {"className":"is-style-logos-only"} -->
<ul class="wp-block-social-links is-style-logos-only"><!-- wp:social-link {"url":"#3fgfhgfh","service":"fivehundredpx"} /-->

<!-- wp:social-link {"url":"#","service":"wordpress"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:social-links {"className":"is-style-pill-shape"} -->
<ul class="wp-block-social-links is-style-pill-shape"><!-- wp:social-link {"url":"#3fgfhgfh","service":"fivehundredpx"} /-->

<!-- wp:social-link {"url":"#","service":"wordpress"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:paragraph -->
<p>Tag cloud:</p>
<!-- /wp:paragraph -->

<!-- wp:tag-cloud /-->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
````

### 51 — post, publish — WP 6.1 Theme block category — **perdu par sale**

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Navigation block with page list:</p>
<!-- /wp:paragraph -->

<!-- wp:navigation -->
<!-- wp:page-list /-->
<!-- /wp:navigation -->

<!-- wp:paragraph -->
<p>Site logo:</p>
<!-- /wp:paragraph -->

<!-- wp:site-logo {"shouldSyncIcon":true} /-->

<!-- wp:paragraph -->
<p>Site title:</p>
<!-- /wp:paragraph -->

<!-- wp:site-title /-->

<!-- wp:paragraph -->
<p>Tagline block:</p>
<!-- /wp:paragraph -->

<!-- wp:site-tagline /-->

<!-- wp:paragraph -->
<p>Query loop "Title &amp; Date" variation:</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title /-->

<!-- wp:post-date /-->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph -->
<p>Query loop "Title &amp; Excerpt" variation:</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title /-->

<!-- wp:post-excerpt /-->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph -->
<p>Query loop "Title, Date &amp; Excerpt" variation:</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":2,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title /-->

<!-- wp:post-date /-->

<!-- wp:post-excerpt /-->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph -->
<p>Query loop "Image, Date &amp; Title" variation:</p>
<!-- /wp:paragraph -->

<!-- wp:query {"queryId":5,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-featured-image /-->

<!-- wp:post-date /-->

<!-- wp:post-title /-->
<!-- /wp:post-template -->

<!-- wp:query-pagination -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph {"placeholder":"Add text or blocks that will display when a query returns no results."} -->
<p></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->

<!-- wp:paragraph -->
<p>Avatar block:</p>
<!-- /wp:paragraph -->

<!-- wp:avatar /-->

<!-- wp:paragraph -->
<p>Post title block:</p>
<!-- /wp:paragraph -->

<!-- wp:post-title /-->

<!-- wp:paragraph -->
<p>Post excerpt:</p>
<!-- /wp:paragraph -->

<!-- wp:post-excerpt /-->

<!-- wp:paragraph -->
<p>Post featured image:</p>
<!-- /wp:paragraph -->

<!-- wp:post-featured-image /-->

<!-- wp:paragraph -->
<p>Post author:</p>
<!-- /wp:paragraph -->

<!-- wp:post-author /-->

<!-- wp:paragraph -->
<p>Post date:</p>
<!-- /wp:paragraph -->

<!-- wp:post-date /-->

<!-- wp:paragraph -->
<p>Categories:</p>
<!-- /wp:paragraph -->

<!-- wp:post-terms {"term":"category"} /-->

<!-- wp:paragraph -->
<p>Tags:</p>
<!-- /wp:paragraph -->

<!-- wp:post-terms {"term":"post_tag"} /-->

<!-- wp:paragraph -->
<p>Next post &amp; previous post:</p>
<!-- /wp:paragraph -->

<!-- wp:post-navigation-link /-->

<!-- wp:post-navigation-link {"type":"previous"} /-->

<!-- wp:paragraph -->
<p>Read More:</p>
<!-- /wp:paragraph -->

<!-- wp:read-more /-->

<!-- wp:paragraph -->
<p>Comments block:</p>
<!-- /wp:paragraph -->

<!-- wp:comments -->
<div class="wp-block-comments"><!-- wp:comments-title /-->

<!-- wp:comment-template -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"40px"} -->
<div class="wp-block-column" style="flex-basis:40px"><!-- wp:avatar {"size":40,"style":{"border":{"radius":"20px"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:comment-author-name {"fontSize":"small"} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0px","bottom":"0px"}}},"layout":{"type":"flex"}} -->
<div class="wp-block-group" style="margin-top:0px;margin-bottom:0px"><!-- wp:comment-date {"fontSize":"small"} /-->

<!-- wp:comment-edit-link {"fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:comment-content /-->

<!-- wp:comment-reply-link {"fontSize":"small"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:comment-template -->

<!-- wp:comments-pagination -->
<!-- wp:comments-pagination-previous /-->

<!-- wp:comments-pagination-numbers /-->

<!-- wp:comments-pagination-next /-->
<!-- /wp:comments-pagination -->

<!-- wp:post-comments-form /-->

<!-- wp:paragraph -->
<p>Post comments form block:</p>
<!-- /wp:paragraph --></div>
<!-- /wp:comments -->

<!-- wp:post-comments-form /-->

<!-- wp:paragraph -->
<p>Login/out:</p>
<!-- /wp:paragraph -->

<!-- wp:loginout /-->

<!-- wp:term-description /-->

<!-- wp:query-title {"type":"archive"} /-->

<!-- wp:query-title {"type":"search"} /-->

<!-- wp:paragraph -->
<p>Author biography block:</p>
<!-- /wp:paragraph -->

<!-- wp:post-author-biography /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph -->
<p><em>Term description, archive title, search results title can not be shown on single posts.</em></p>
<!-- /wp:paragraph -->
````

### 146 — page, publish — Lorem Ipsum

````html
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna. Praesent sit amet ligula id orci venenatis auctor. Phasellus porttitor, metus non tincidunt dapibus, orci pede pretium neque, sit amet adipiscing ipsum lectus et libero. Aenean bibendum. Curabitur mattis quam id urna. Vivamus dui. Donec nonummy lacinia lorem. Cras risus arcu, sodales ac, ultrices ac, mollis quis, justo. Sed a libero. Quisque risus erat, posuere at, tristique non, lacinia quis, eros.

Cras volutpat, lacus quis semper pharetra, nisi enim dignissim est, et sollicitudin quam ipsum vel mi. Sed commodo urna ac urna. Nullam eu tortor. Curabitur sodales scelerisque magna. Donec ultricies tristique pede. Nullam libero. Nam sollicitudin felis vel metus. Nullam posuere molestie metus. Nullam molestie, nunc id suscipit rhoncus, felis mi vulputate lacus, a ultrices tortor dolor eget augue. Aenean ultricies felis ut turpis. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Suspendisse placerat tellus ac nulla. Proin adipiscing sem ac risus. Maecenas nisi. Cras semper.

Praesent interdum mollis neque. In egestas nulla eget pede. Integer eu purus sed diam dictum scelerisque. Morbi cursus velit et felis. Maecenas faucibus aliquet erat. In aliquet rhoncus tellus. Integer auctor nibh a nunc fringilla tempus. Cras turpis urna, dignissim vel, suscipit pulvinar, rutrum quis, sem. Ut lobortis convallis dui. Sed nonummy orci a justo. Morbi nec diam eget eros eleifend tincidunt.

Հայերեն

Lorem Ipsum-ը տպագրության և տպագրական արդյունաբերության համար նախատեսված մոդելային տեքստ է: Սկսած 1500-ականներից` Lorem Ipsum-ը հանդիսացել է տպագրական արդյունաբերության ստանդարտ մոդելային տեքստ, ինչը մի անհայտ տպագրիչի կողմից տարբեր տառատեսակների օրինակների գիրք ստեղծելու ջանքերի արդյունք է: Այս տեքստը ոչ միայն կարողացել է գոյատևել հինգ դարաշրջան, այլև ներառվել է էլեկտրոնային տպագրության մեջ` մնալով էապես անփոփոխ: Այն հայտնի է դարձել 1960-ականներին Lorem Ipsum բովանդակող Letraset էջերի թողարկման արդյունքում, իսկ ավելի ուշ համակարգչային տպագրության այնպիսի ծրագրերի թողարկման հետևանքով, ինչպիսին է Aldus PageMaker-ը, որը ներառում է Lorem Ipsum-ի տարատեսակներ:

Български

Lorem Ipsum е елементарен примерен текст, използван в печатарската и типографската индустрия. Lorem Ipsum е индустриален стандарт от около 1500 година, когато неизвестен печатар взема няколко печатарски букви и ги разбърква, за да напечата с тях книга с примерни шрифтове. Този начин не само е оцелял повече от 5 века, но е навлязъл и в публикуването на електронни издания като е запазен почти без промяна. Популяризиран е през 60те години на 20ти век със издаването на Letraset листи, съдържащи Lorem Ipsum пасажи, популярен е и в наши дни във софтуер за печатни издания като Aldus PageMaker, който включва различни версии на Lorem Ipsum.

Català

Lorem Ipsum és un text de farciment usat per la indústria de la tipografia i la impremta. Lorem Ipsum ha estat el text estàndard de la indústria des de l'any 1500, quan un impressor desconegut va fer servir una galerada de text i la va mesclar per crear un llibre de mostres tipogràfiques. No només ha sobreviscut cinc segles, sinó que ha fet el salt cap a la creació de tipus de lletra electrònics, romanent essencialment sense canvis. Es va popularitzar l'any 1960 amb el llançament de fulls Letraset que contenien passatges de Lorem Ipsum, i més recentment amb programari d'autoedició com Aldus Pagemaker que inclou versions de Lorem Ipsum.

Hrvatski

Lorem Ipsum je jednostavno probni tekst koji se koristi u tiskarskoj i slovoslagarskoj industriji. Lorem Ipsum postoji kao industrijski standard još od 16-og stoljeća, kada je nepoznati tiskar uzeo tiskarsku galiju slova i posložio ih da bi napravio knjigu s uzorkom tiska. Taj je tekst ne samo preživio pet stoljeća, već se i vinuo u svijet elektronskog slovoslagarstva, ostajući u suštini nepromijenjen. Postao je popularan tijekom 1960-ih s pojavom Letraset listova s odlomcima Lorem Ipsum-a, a u skorije vrijeme sa software-om za stolno izdavaštvo kao što je Aldus PageMaker koji također sadrži varijante Lorem Ipsum-a.

Česky

Lorem Ipsum je demonstrativní výplňový text používaný v tiskařském a knihařském průmyslu. Lorem Ipsum je považováno za standard v této oblasti už od začátku 16. století, kdy dnes neznámý tiskař vzal kusy textu a na jejich základě vytvořil speciální vzorovou knihu. Jeho odkaz nevydržel pouze pět století, on přežil i nástup elektronické sazby v podstatě beze změny. Nejvíce popularizováno bylo Lorem Ipsum v šedesátých letech 20. století, kdy byly vydávány speciální vzorníky s jeho pasážemi a později pak díky počítačovým DTP programům jako Aldus PageMaker.

Româna

Lorem Ipsum este pur şi simplu o machetă pentru text a industriei tipografice. Lorem Ipsum a fost macheta standard a industriei încă din secolul al XVI-lea, când un tipograf anonim a luat o planşetă de litere şi le-a amestecat pentru a crea o carte demonstrativă pentru literele respective. Nu doar că a supravieţuit timp de cinci secole, dar şi a facut saltul în tipografia electronică practic neschimbată. A fost popularizată în anii '60 odată cu ieşirea colilor Letraset care conţineau pasaje Lorem Ipsum, iar mai recent, prin programele de publicare pentru calculator, ca Aldus PageMaker care includeau versiuni de Lorem Ipsum.

Српски

Lorem Ipsum је једноставно модел текста који се користи у штампарској и словослагачкој индустрији. Lorem ipsum је био стандард за модел текста још од 1500. године, када је непознати штампар узео кутију са словима и сложио их како би направио узорак књиге. Не само што је овај модел опстао пет векова, него је чак почео да се користи и у електронским медијима, непроменивши се. Популаризован је шездесетих година двадесетог века заједно са листовима летерсета који су садржали Lorem Ipsum пасусе, а данас са софтверским пакетом за прелом као што је Aldus PageMaker који је садржао Lorem Ipsum верзије.

Ελληνικά

Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός. Ϊϊϋ, Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός. ΤΑΧΙΣΤΗ ΑΛΩΠΗΞ ΒΑΦΗΣ ΨΗΜΕΝΗ ΓΗ, ΔΡΑΣΚΕΛΙΖΕΙ ΥΠΕΡ ΝΩΘΡΟΥ ΚΥΝΟΣ. Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός. Ϊϊϋ, Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός Τάχιστη αλώπηξ βαφής ψημένη γη, δρασκελίζει υπέρ νωθρού κυνός. ΤΑΧΙΣΤΗ ΑΛΩΠΗΞ ΒΑΦΗΣ ΨΗΜΕΝΗ ΓΗ, ΔΡΑΣΚΕΛΙΖΕΙ ΥΠΕΡ ΝΩΘΡΟΥ ΚΥΝΟΣ.
````

### 150 — post, publish — WP 6.1 spacing presets

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>On this page, some group blocks have border or background color set to increase visibility.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p>This group has a no background color and no additional spacing set.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background"><!-- wp:paragraph -->
<p>This group has a background color <strong>but no additional spacing set.</strong></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","right":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20"}},"border":{"width":"1px"}},"borderColor":"black","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-border-color has-black-border-color" style="border-width:1px;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph -->
<p>This group has a 1px border and padding preset 1</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","right":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph -->
<p>This group has padding preset 1</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"border":{"width":"1px"}},"borderColor":"black","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-border-color has-black-border-color" style="border-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph -->
<p>This group has a 1px border and padding preset 2</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","right":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph -->
<p>This group has a background color and padding preset 3</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph -->
<p>This group has a background color and padding preset 4</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","right":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60"}}},"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60)"><!-- wp:paragraph -->
<p>This group has a background color and padding preset 5</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70","right":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|70"}}},"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--70)"><!-- wp:paragraph -->
<p>This group has a background color and padding preset 6</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|80","right":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|80"}}},"backgroundColor":"light-green-cyan","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-light-green-cyan-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--80)"><!-- wp:paragraph -->
<p>This group has a background color and padding preset 7</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|80","right":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--80)"><!-- wp:paragraph -->
<p>This group has padding preset 7</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 1</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 2</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 3</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 4</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:var(--wp--preset--spacing--60)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 5</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--70);margin-bottom:var(--wp--preset--spacing--70)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 6</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"backgroundColor":"pale-pink","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-pink-background-color has-background" style="margin-top:var(--wp--preset--spacing--80);margin-bottom:var(--wp--preset--spacing--80)"><!-- wp:paragraph -->
<p>This group has a background color and margin preset 7</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|50"},"border":{"width":"1px"}},"borderColor":"black","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-border-color has-black-border-color" style="border-width:1px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph -->
<p>This group has a 1px border, padding preset 4 and margin preset 4</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph -->
<p>This group has padding preset 4 and margin preset 4</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
````

### 155 — page, publish — Page with comments

````html
Repository-hosted Themes are required to support display of comments on static Pages as well as on single blog Posts.  This static Page has comments, and these comments should be displayed.
If the Theme includes a custom option to prevent static Pages from displaying comments, such option must be disabled (i.e. so that static Pages display comments) by default.
Also, verify that this Page does not display taxonomy information (e.g. categories or tags) or time-stamp information (Page publish date/time).
````

### 156 — page, publish — Page with comments disabled

````html
This static Page is set not to allow comments. Verify that the Page does not display a comment list, comment reply links, or comment reply form.
Also, verify that the Page does not display a "comments are closed" type message. Such messages are not suitable for static Pages, and should only be used on blog Posts.
````

### 163 — post, publish — WP 6.1 Font size scale

````html
<!-- wp:paragraph -->
<p>This test post was generated using the block theme Emptytheme in WordPress 6.1.1.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"fontSize":"small"} -->
<h2 class="has-small-font-size">Small H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:heading {"fontSize":"medium"} -->
<h2 class="has-medium-font-size">Medium H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:heading {"fontSize":"large"} -->
<h2 class="has-large-font-size">Large H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:heading {"fontSize":"large"} -->
<h2 class="has-large-font-size">Extra Large H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Small paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">Medium paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Large paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"x-large"} -->
<p class="has-x-large-font-size">Extra Large paragraph</p>
<!-- /wp:paragraph -->
	
````

### 172 — page, publish — Level 3

````html
Level 3 of the reverse hierarchy test.
````

### 173 — page, publish — Level 2

````html
Level 2 of the reverse hierarchy test.
````

### 174 — page, publish — Level 1

````html
Level 1 of the reverse hierarchy test.  This is to make sure the importer correctly assigns parents and children even when the children come first in the export file.
````

### 358 — post, publish — Post Format: Standard

````html
All children, except one, grow up. They soon know that they will grow up, and the way Wendy knew was this. One day when she was two years old she was playing in a garden, and she plucked another flower and ran with it to her mother. I suppose she must have looked rather delightful, for Mrs. Darling put her hand to her heart and cried, "Oh, why can't you remain like this for ever!" This was all that passed between them on the subject, but henceforth Wendy knew that she must grow up. You always know after you are two. Two is the beginning of the end.

<!--more-->

Mrs. Darling first heard of Peter when she was tidying up her children's minds. It is the nightly custom of every good mother after her children are asleep to rummage in their minds and put things straight for next morning, repacking into their proper places the many articles that have wandered during the day.

If you could keep awake (but of course you can't) you would see your own mother doing this, and you would find it very interesting to watch her. It is quite like tidying up drawers. You would see her on her knees, I expect, lingering humorously over some of your contents, wondering where on earth you had picked this thing up, making discoveries sweet and not so sweet, pressing this to her cheek as if it were as nice as a kitten, and hurriedly stowing that out of sight. When you wake in the morning, the naughtiness and evil passions with which you went to bed have been folded up small and placed at the bottom of your mind and on the top, beautifully aired, are spread out your prettier thoughts, ready for you to put on.

I don't know whether you have ever seen a map of a person's mind. Doctors sometimes draw maps of other parts of you, and your own map can become intensely interesting, but catch them trying to draw a map of a child's mind, which is not only confused, but keeps going round all the time. There are zigzag lines on it, just like your temperature on a card, and these are probably roads in the island, for the Neverland is always more or less an island, with astonishing splashes of colour here and there, and coral reefs and rakish-looking craft in the offing, and savages and lonely lairs, and gnomes who are mostly tailors, and caves through which a river runs, and princes with six elder brothers, and a hut fast going to decay, and one very small old lady with a hooked nose. It would be an easy map if that were all, but there is also first day at school, religion, fathers, the round pond, needle-work, murders, hangings, verbs that take the dative, chocolate pudding day, getting into braces, say ninety-nine, three-pence for pulling out your tooth yourself, and so on, and either these are part of the island or they are another map showing through, and it is all rather confusing, especially as nothing will stand still.

Of course the Neverlands vary a good deal. John's, for instance, had a lagoon with flamingoes flying over it at which John was shooting, while Michael, who was very small, had a flamingo with lagoons flying over it. John lived in a boat turned upside down on the sands, Michael in a wigwam, Wendy in a house of leaves deftly sewn together. John had no friends, Michael had friends at night, Wendy had a pet wolf forsaken by its parents, but on the whole the Neverlands have a family resemblance, and if they stood still in a row you could say of them that they have each other's nose, and so forth. On these magic shores children at play are for ever beaching their coracles [simple boat]. We too have been there; we can still hear the sound of the surf, though we shall land no more.

Of all delectable islands the Neverland is the snuggest and most compact, not large and sprawly, you know, with tedious distances between one adventure and another, but nicely crammed. When you play at it by day with the chairs and table-cloth, it is not in the least alarming, but in the two minutes before you go to sleep it becomes very real. That is why there are night-lights.

Occasionally in her travels through her children's minds Mrs. Darling found things she could not understand, and of these quite the most perplexing was the word Peter. She knew of no Peter, and yet he was here and there in John and Michael's minds, while Wendy's began to be scrawled all over with him. The name stood out in bolder letters than any of the other words, and as Mrs. Darling gazed she felt that it had an oddly cocky appearance.
````

### 501 — page, publish — Clearing Floats

````html
The last item in this page's content is a thumbnail floated left. There should be page links following it. Make sure any elements after the content are clearing properly.

	The float is cleared when it does not stick out the bottom of the parent container, and when other elements that follow it do not wrap around the floated element.

<img class="alignleft size-thumbnail wp-image-827" title="Camera" src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg?w=150" alt="" width="160" /> <!--nextpage-->This is the second page
````

### 555 — post, publish — Post Format: Gallery

````html
[gallery]

<!--nextpage-->

You can use this page to test the Theme's handling of the gallery shortcode, including the <code>columns</code> parameter, from 1 to 9 columns. Themes are only required to support the default setting (3 columns), so this page is entirely optional.
<h2>One Column</h2>
[gallery columns="1"]
<h2>Two Columns</h2>
[gallery columns="2"]
<h2>Three Columns</h2>
[gallery columns="3"]
<h2>Four Columns</h2>
[gallery columns="4"]
<h2>Five Columns</h2>
[gallery columns="5"]
<h2>Six Columns</h2>
[gallery columns="6"]
<h2>Seven Columns</h2>
[gallery columns="7"]
<h2>Eight Columns</h2>
[gallery columns="8"]
<h2>Nine Columns</h2>
[gallery columns="9"]
````

### 559 — post, publish — Post Format: Aside

````html
“I never tried to prove nothing, just wanted to give a good show. My life has always been my music, it's always come first, but the music ain't worth nothing if you can't lay it on the public. The main thing is to live for that audience, 'cause what you're there for is to please the people.”
````

### 562 — post, publish — Post Format: Chat

````html
Abbott: Strange as it may seem, they give ball players nowadays very peculiar names.

Costello: Funny names?

Abbott: Nicknames, nicknames. Now, on the St. Louis team we have Who's on first, What's on second, I Don't Know is on third--

Costello: That's what I want to find out. I want you to tell me the names of the fellows on the St. Louis team.

Abbott: I'm telling you. Who's on first, What's on second, I Don't Know is on third--

Costello: You know the fellows' names?

Abbott: Yes.

Costello: Well, then who's playing first?

Abbott: Yes.

Costello: I mean the fellow's name on first base.

Abbott: Who.

Costello: The fellow playin' first base.

Abbott: Who.

Costello: The guy on first base.

Abbott: Who is on first.

Costello: Well, what are you askin' me for?

Abbott: I'm not asking you--I'm telling you. Who is on first.

Costello: I'm asking you--who's on first?

Abbott: That's the man's name.

Costello: That's who's name?

Abbott: Yes.

Costello: When you pay off the first baseman every month, who gets the money?

Abbott: Every dollar of it. And why not, the man's entitled to it.

Costello: Who is?

Abbott: Yes.

Costello: So who gets it?

Abbott: Why shouldn't he? Sometimes his wife comes down and collects it.

Costello: Who's wife?

Abbott: Yes. After all, the man earns it.

Costello: Who does?

Abbott: Absolutely.

Costello: Well, all I'm trying to find out is what's the guy's name on first base?

Abbott: Oh, no, no. What is on second base.

Costello: I'm not asking you who's on second.

Abbott: Who's on first!

Costello: St. Louis has a good outfield?

Abbott: Oh, absolutely.

Costello: The left fielder's name?

Abbott: Why.

Costello: I don't know, I just thought I'd ask.

Abbott: Well, I just thought I'd tell you.

Costello: Then tell me who's playing left field?

Abbott: Who's playing first.

Costello: Stay out of the infield! The left fielder's name?

Abbott: Why.

Costello: Because.

Abbott: Oh, he's center field.

Costello: Wait a minute. You got a pitcher on this team?

Abbott: Wouldn't this be a fine team without a pitcher?

Costello: Tell me the pitcher's name.

Abbott: Tomorrow.

Costello: Now, when the guy at bat bunts the ball--me being a good catcher--I want to throw the guy out at first base, so I pick up the ball and throw it to who?

Abbott: Now, that's he first thing you've said right.

Costello: I DON'T EVEN KNOW WHAT I'M TALKING ABOUT!

Abbott: Don't get excited. Take it easy.

Costello: I throw the ball to first base, whoever it is grabs the ball, so the guy runs to second. Who picks up the ball and throws it to what. What throws it to I don't know. I don't know throws it back to tomorrow--a triple play.

Abbott: Yeah, it could be.

Costello: Another guy gets up and it's a long ball to center.

Abbott: Because.

Costello: Why? I don't know. And I don't care.

Abbott: What was that?

Costello: I said, I DON'T CARE!

Abbott: Oh, that's our shortstop!
````

### 565 — post, publish — Post Format: Link

````html
<a href="https://make.wordpress.org/themes" title="The WordPress Theme Review Team Website">The WordPress Theme Review Team Website</a>
````

### 568 — post, publish — Post Format: Image (Linked)

````html
[caption id="attachment_612" align="aligncenter" width="640" caption="Chunk of resinous blackboy husk, Clarkson, Western Australia. This burns like a spinifex log."]<a href="http://localhost:8766/wp-content/uploads/2013/09/dsc20040724_152504_532.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/09/dsc20040724_152504_532.jpg" alt="chunk of resinous blackboy husk" title="dsc20040724_152504_532" width="640" height="480" class="size-full wp-image-612" /></a>[/caption]

````

### 575 — post, publish — Post Format: Quote

````html
<blockquote>Only one thing is impossible for God: To find any sense in any copyright law on the planet.
<cite><a href="http://www.brainyquote.com/quotes/quotes/m/marktwain163473.html">Mark Twain</a></cite></blockquote>
````

### 579 — post, publish — Post Format: Status

````html
WordPress, how do I love thee? Let me count the ways (in 140 characters or less).
````

### 582 — post, publish — Post Format: Video (WordPress.tv)

````html
https://wordpress.tv/2009/03/16/anatomy-of-a-wordpress-theme-exploring-the-files-behind-your-theme/

Posted as per the <a href="https://codex.wordpress.org/Embeds" target="_blank">instructions in the Codex</a>.
````

### 587 — post, publish — Post Format: Audio

````html
Link:

<a href="http://localhost:8766/wp-content/uploads/2008/06/originaldixielandjazzbandwithalbernard-stlouisblues.mp3">St. Louis Blues</a>

Audio shortcode:

[audio http://localhost:8766/wp-content/uploads/2008/06/originaldixielandjazzbandwithalbernard-stlouisblues.mp3]
````

### 701 — page, publish — Front Page

````html
Use this static Page to test the Theme's handling of the Front Page template file.

This is the Front Page content. Use this static Page to test the Front Page output of the Theme. The Theme should properly handle both Blog Posts Index as Front Page and static Page as Front Page.

If the site is set to display the Blog Posts Index as the Front Page, then this text should not be visible. If the site is set to display a static Page as the Front Page, then this text may or may not be visible. If the Theme does not include a front-page.php template file, then this text should appear on the Front Page when set to display a static Page. If the Theme does include a front-page.php template file, then this text may or may not appear.
````

### 703 — page, publish — a Blog page

````html
Use this static Page to test the Theme's handling of the Blog Posts Index page. If the site is set to display a static Page on the Front Page, and this Page is set to display the Blog Posts Index, then this text should not appear. The title might, so make sure the theme is not supplying a hard-coded title for the Blog Post Index.
````

### 733 — page, publish — Page A

````html
Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Aenean lacinia bibendum nulla sed consectetur. Etiam porta sem malesuada magna mollis euismod. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.
````

### 735 — page, publish — Page B

````html
(lorem ipsum)
````

### 742 — page, publish — Level 2a

````html
(lorem ipsum)
````

### 744 — page, publish — Level 2b

````html
(lorem ipsum)
````

### 746 — page, publish — Level 3a

````html
(lorem ipsum)
````

### 748 — page, publish — Level 3b

````html
(lorem ipsum)
````

### 754 — attachment, inherit — Bell on Wharf

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/100_5478.JPG.html
````

### 755 — attachment, inherit — Golden Gate Bridge

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/100_5540.JPG.html
````

### 756 — attachment, inherit — Sunburst Over River

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/CEP00032.jpg.html
````

### 757 — attachment, inherit — Boardwalk

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/DCP_2082.jpg.html
````

### 758 — attachment, inherit — Yachtsody in Blue

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/dsc03149.jpg.html
````

### 759 — attachment, inherit — Rain Ripples

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/dsc04563.jpg.html
````

### 760 — attachment, inherit — Sydney Harbor Bridge

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/dsc09114.jpg.html
````

### 761 — attachment, inherit — Wind Farm

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/dsc20050102_192118_51.jpg.html
````

### 762 — attachment, inherit — Antique Farm Machinery

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/dsc20051220_160808_102.jpg.html
````

### 764 — attachment, inherit — Rusty Rail

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/dsc20051220_173257_119.jpg.html
````

### 765 — attachment, inherit — Sea and Rocks

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/dscn3316.jpg.html
````

### 766 — attachment, inherit — Big Sur

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/michelle_049.jpg.html
````

### 767 — attachment, inherit — Windmill

````html
Public domain via https://www.burningwell.org/gallery2/v/Objects/Windmill.jpg.html
````

### 768 — attachment, inherit — Huatulco Coastline

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/IMG_0513-1.JPG.html
````

### 769 — attachment, inherit — Brazil Beach

````html
Public domain via https://www.burningwell.org/gallery2/main.php?g2_view=dynamicalbum.UpdatesAlbum&amp;g2_itemId=25770
````

### 770 — attachment, inherit — Huatulco Coastline

````html
Public domain via https://www.burningwell.org/gallery2/v/Landscapes/ocean/IMG_0767.JPG.html
````

### 771 — attachment, inherit — Boat Barco Texture

````html
Public domain via https://www.burningwell.org/gallery2/main.php?g2_view=dynamicalbum.UpdatesAlbum&amp;g2_itemId=25774
````

### 821 — attachment, inherit — St. Louis Blues

````html
St. Louis Blues, by Original Dixieland Jazz Band with Al Bernard (public domain)
````

### 993 — post, publish — Template: Excerpt (Defined)

````html
This is the post content. It <strong>should</strong> be displayed in place of the user-defined excerpt in single-page views.
````

### 996 — post, publish — Template: More Tag

````html
This content is before the <a title="The More Tag" href="https://en.support.wordpress.com/splitting-content/more-tag/" target="_blank">more tag</a>.

Right after this sentence should be a "continue reading" button of some sort on list pages of themes that show full content. It won't show on single pages or on themes showing excerpts.

<!--more-->

And this content is after the more tag. (which should be the anchor link for when the button is clicked)
````

### 1000 — post, publish — Edge Case: Nested And Mixed Lists

````html
Nested and mixed lists are an interesting beast. It's a corner case to make sure that
<ul>
	<li>Lists within lists do not break the ordered list numbering order</li>
	<li>Your list styles go deep enough.</li>
</ul>
<h3>Ordered - Unordered - Ordered</h3>
<ol>
	<li>ordered item</li>
	<li>ordered item
<ul>
	<li><strong>unordered</strong></li>
	<li><strong>unordered</strong>
<ol>
	<li>ordered item</li>
	<li>ordered item</li>
</ol>
</li>
</ul>
</li>
	<li>ordered item</li>
	<li>ordered item</li>
</ol>
<h3>Ordered - Unordered - Unordered</h3>
<ol>
	<li>ordered item</li>
	<li>ordered item
<ul>
	<li><strong>unordered</strong></li>
	<li><strong>unordered</strong>
<ul>
	<li>unordered item</li>
	<li>unordered item</li>
</ul>
</li>
</ul>
</li>
	<li>ordered item</li>
	<li>ordered item</li>
</ol>
<h3>Unordered - Ordered - Unordered</h3>
<ul>
	<li>unordered item</li>
	<li>unordered item
<ol>
	<li>ordered</li>
	<li>ordered
<ul>
	<li>unordered item</li>
	<li>unordered item</li>
</ul>
</li>
</ol>
</li>
	<li>unordered item</li>
	<li>unordered item</li>
</ul>
<h3>Unordered - Unordered - Ordered</h3>
<ul>
	<li>unordered item</li>
	<li>unordered item
<ul>
	<li>unordered</li>
	<li>unordered
<ol>
	<li><strong>ordered item</strong></li>
	<li><strong>ordered item</strong></li>
</ol>
</li>
</ul>
</li>
	<li>unordered item</li>
	<li>unordered item</li>
</ul>
````

### 1011 — post, publish — Template: Featured Image (Horizontal)

````html
This post should display a <a title="Featured Images" href="https://en.support.wordpress.com/featured-images/#setting-a-featured-image" target="_blank">featured image</a>, if the theme <a title="Post Thumbnails" href="https://codex.wordpress.org/Post_Thumbnails" target="_blank">supports it</a>.

Non-square images can provide some unique styling issues.

This post tests a horizontal featured image.
````

### 1016 — post, publish — Template: Featured Image (Vertical)

````html
This post should display a <a title="Featured Images" href="https://en.support.wordpress.com/featured-images/#setting-a-featured-image" target="_blank">featured image</a>, if the theme <a title="Post Thumbnails" href="https://codex.wordpress.org/Post_Thumbnails" target="_blank">supports it</a>.

Non-square images can provide some unique styling issues.

This post tests a vertical featured image.
````

### 1031 — post, publish — Post Format: Gallery (Tiled)

````html
This is a test for Jetpack's Tiled Gallery.

Install <a title="Jetpack for WordPress" href="https://wordpress.org/plugins/jetpack/" target="_blank">Jetpack</a> to test.

[gallery type="rectangular" columns="4" ids="755,757,758,760,766,763" orderby="rand"]

This is some text after the Tiled Gallery just to make sure that everything spaces nicely.
````

### 1133 — page, publish — Page Image Alignment

````html
Welcome to image alignment! The best way to demonstrate the ebb and flow of the various image positioning options is to nestle them snuggly among an ocean of words. Grab a paddle and let's get started.

On the topic of alignment, it should be noted that users can choose from the options of <em>None</em>, <em>Left</em>, <em>Right, </em>and <em>Center</em>. In addition, they also get the options of <em>Thumbnail</em>, <em>Medium</em>, <em>Large</em> &amp; <em>Fullsize</em>. Be sure to try this page in RTL mode and it should look the same as LTR. 
<p><img class="size-full wp-image-906 aligncenter" title="Image Alignment 580x300" alt="Image Alignment 580x300" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" width="580" height="300" /></p>
The image above happens to be <em><strong>centered</strong></em>.

<img class="size-full wp-image-904 alignleft" title="Image Alignment 150x150" alt="Image Alignment 150x150" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" width="150" height="150" /> The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. 

As you can see there should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!

And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.

<img class="alignnone  wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" />

The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

<img class="aligncenter  wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" />

And we try the large image again, with the center alignment since that sometimes is a problem. The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

<img class="size-full wp-image-905 alignright" title="Image Alignment 300x200" alt="Image Alignment 300x200" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" width="300" height="200" />

And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there... Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.

In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah... Just like that. It never felt so good to be right.

And just when you thought we were done, we're going to do them all over again with captions!

[caption id="attachment_906" align="aligncenter" width="580"]<img class="size-full wp-image-906  " title="Image Alignment 580x300" alt="Image Alignment 580x300" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" width="580" height="300" /> Look at 580x300 getting some <a title="Image Settings" href="https://en.support.wordpress.com/images/image-settings/">caption</a> love.[/caption]

The image above happens to be <em><strong>centered</strong></em>. The caption also has a link in it, just to see if it does anything funky.

[caption id="attachment_904" align="alignleft" width="150"]<img class="size-full wp-image-904  " title="Image Alignment 150x150" alt="Image Alignment 150x150" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" width="150" height="150" /> Bigger caption than the image usually is.[/caption]

The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. 

As you can see the should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!

And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.

[caption id="attachment_907" align="alignnone" width="1200"]<img class=" wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" /> Comment for massive image for your eyeballs.[/caption]

The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.
[caption id="attachment_907" align="aligncenter" width="1200"]<img class=" wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" /> This massive image is centered.[/caption]

And again with the big image centered. The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

[caption id="attachment_905" align="alignright" width="300"]<img class="size-full wp-image-905 " title="Image Alignment 300x200" alt="Image Alignment 300x200" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" width="300" height="200" /> Feels good to be right all the time.[/caption]

And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there... Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.

In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah... Just like that. It never felt so good to be right.

And that's a wrap, yo! You survived the tumultuous waters of alignment. Image alignment achievement unlocked! Last thing is a small image aligned right. Whatever follows should be unaffected. <img class="size-full wp-image-904 alignright" title="Image Alignment 150x150" alt="Image Alignment 150x150" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" width="150" height="150" />
````

### 1134 — page, publish — Page Markup And Formatting

````html
<strong>Headings</strong>
<h1>Header one</h1>
<h2>Header two</h2>
<h3>Header three</h3>
<h4>Header four</h4>
<h5>Header five</h5>
<h6>Header six</h6>
<h2>Blockquotes</h2>
Single line blockquote:
<blockquote>Stay hungry. Stay foolish.</blockquote>
Multi line blockquote with a cite reference:
<blockquote cite="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/blockquote"><p>The <strong>HTML <code>&lt;blockquote&gt;</code> Element</strong> (or <em>HTML Block Quotation Element</em>) indicates that the enclosed text is an extended quotation. Usually, this is rendered visually by indentation (see <a href="https://developer.mozilla.org/en-US/docs/HTML/Element/blockquote#Notes">Notes</a> for how to change it). A URL for the source of the quotation may be given using the <strong>cite</strong> attribute, while a text representation of the source can be given using the <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite" title="The HTML Citation Element &lt;cite&gt; represents a reference to a creative work. It must include the title of a work or a URL reference, which may be in an abbreviated form according to the conventions used for the addition of citation metadata."><code>&lt;cite&gt;</code></a> element.</p></blockquote>
<cite>multiple contributors</cite> - MDN HTML element reference - blockquote
<h2>Tables</h2>
<table>
<tbody>
<tr>
<th>Employee</th>
<th class="views">Salary</th>
<th></th>
</tr>
<tr class="odd">
<td><a href="http://example.com/">Jane</a></td>
<td>$1</td>
<td>Because that's all Steve Jobs needed for a salary.</td>
</tr>
<tr class="even">
<td><a href="http://example.com">John</a></td>
<td>$100K</td>
<td>For all the blogging he does.</td>
</tr>
<tr class="odd">
<td><a href="http://example.com/">Jane</a></td>
<td>$100M</td>
<td>Pictures are worth a thousand words, right? So Tom x 1,000.</td>
</tr>
<tr class="even">
<td><a href="http://example.com/">Jane</a></td>
<td>$100B</td>
<td>With hair like that?! Enough said...</td>
</tr>
</tbody>
</table>
<h2>Definition Lists</h2>
<dl><dt>Definition List Title</dt><dd>Definition list division.</dd><dt>Startup</dt><dd>A startup company or startup is a company or temporary organization designed to search for a repeatable and scalable business model.</dd><dt>#dowork</dt><dd>Coined by Rob Dyrdek and his personal body guard Christopher "Big Black" Boykins, "Do Work" works as a self motivator, to motivating your friends.</dd><dt>Do It Live</dt><dd>I'll let Bill O'Reilly will <a title="We'll Do It Live" href="https://www.youtube.com/watch?v=O_HyZ5aW76c">explain</a> this one.</dd></dl>
<h2>Unordered Lists (Nested)</h2>
<ul>
	<li>List item one
<ul>
	<li>List item one
<ul>
	<li>List item one</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
<h2>Ordered List (Nested)</h2>
<ol start="8">
 	<li>List item one -start at 8
<ol>
 	<li>List item one
<ol reversed="reversed">
 	<li>List item one -reversed attribute</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
<h2>HTML Tags</h2>
These supported tags come from the WordPress.com code <a title="Code" href="https://en.support.wordpress.com/code/">FAQ</a>.

<strong>Address Tag</strong>

<address>1 Infinite Loop
Cupertino, CA 95014
United States</address><strong>Anchor Tag (aka. Link)</strong>

This is an example of a <a title="WordPress Foundation" href="https://wordpressfoundation.org/">link</a>.

<strong>Abbreviation Tag</strong>

The abbreviation <abbr title="Seriously">srsly</abbr> stands for "seriously".

<strong>Acronym Tag (<em>deprecated in HTML5</em>)</strong>

The acronym <acronym title="For The Win">ftw</acronym> stands for "for the win".

<strong>Big Tag</strong> (<em>deprecated in HTML5</em>)

These tests are a <big>big</big> deal, but this tag is no longer supported in HTML5.

<strong>Cite Tag</strong>

"Code is poetry." --<cite>Automattic</cite>

<strong>Code Tag</strong>

This tag styles blocks of code.
<code>.post-title {
	margin: 0 0 5px;
	font-weight: bold;
	font-size: 38px;
	line-height: 1.2;
	and here's a line of some really, really, really, really long text, just to see how it is handled and to find out how it overflows;
}</code>
You will learn later on in these tests that <code>word-wrap: break-word;</code> will be your best friend.

<strong>Delete Tag</strong>

This tag will let you <del cite="deleted it">strike out text</del>, but this tag is <em>recommended</em> supported in HTML5 (use the <code>&lt;s&gt;</code> instead).

<strong>Emphasize Tag</strong>

The emphasize tag should <em>italicize</em> <i>text</i>.

<strong>Horizontal Rule Tag</strong>

<hr />

This sentence is following a <code>&lt;hr /&gt;</code> tag.

<strong>Insert Tag</strong>

This tag should denote <ins cite="inserted it">inserted</ins> text.

<strong>Keyboard Tag</strong>

This scarcely known tag emulates <kbd>keyboard text</kbd>, which is usually styled like the <code>&lt;code&gt;</code> tag.

<strong>Preformatted Tag</strong>

This tag is for preserving whitespace as typed, such as in poetry or ASCII art.
<h2>The Road Not Taken</h2>
<pre>
 <cite>Robert Frost</cite>


	Two roads diverged in a yellow wood,
	And sorry I could not travel both          (\_/)
	And be one traveler, long I stood         (='.'=)
	And looked down one as far as I could     (")_(")
	To where it bent in the undergrowth;

	Then took the other, as just as fair,
	And having perhaps the better claim,          |\_/|
	Because it was grassy and wanted wear;       / @ @ \
	Though as for that the passing there        ( &gt; º &lt; )
	Had worn them really about the same,         `&gt;&gt;x&lt;&lt;´
	 /  O  \
	And both that morning equally lay
	In leaves no step had trodden black.
	Oh, I kept the first for another day!
	Yet knowing how way leads on to way,
	I doubted if I should ever come back.

	I shall be telling this with a sigh
	Somewhere ages and ages hence:
	Two roads diverged in a wood, and I—
	I took the one less traveled by,
	And that has made all the difference.


	and here's a line of some really, really, really, really long text, just to see how it is handled and to find out how it overflows;
</pre>
<strong>Quote Tag</strong> for short, inline quotes

<q>Developers, developers, developers...</q> --Steve Ballmer

<strong>Strike Tag</strong> (<em>deprecated in HTML5</em>) and <strong>S Tag</strong>

This tag shows <strike>strike-through</strike> <s>text</s>.

<strong>Small Tag</strong>

This tag shows <small>smaller<small> text.</small></small>

<strong>Strong Tag</strong>

This tag shows <strong>bold<strong> text.</strong></strong>

<strong>Subscript Tag</strong>

Getting our science styling on with H<sub>2</sub>O, which should push the "2" down.

<strong>Superscript Tag</strong>

Still sticking with science and Albert Einstein's E = MC<sup>2</sup>, which should lift the 2 up.

<strong>Teletype Tag </strong>(<em>obsolete in HTML5</em>)

This rarely used tag emulates <tt>teletype text</tt>, which is usually styled like the <code>&lt;code&gt;</code> tag.

<strong>Underline Tag</strong> <em>deprecated in HTML 4, re-introduced in HTML5 with other semantics</em>

This tag shows <u>underlined text</u>.

<strong>Variable Tag</strong>

This allows you to denote <var>variables</var>.
````

### 1148 — post, publish — Template: Comments

````html
This post tests comments in the following ways.
<ul>
	<li>Threaded comments up to 10 levels deep</li>
	<li>Paginated comments (set <em><strong>Settings &gt; Discussion &gt; Break comments into pages</strong></em> to <em><strong>5</strong></em> top level comments per page)</li>
	<li>Comment markup / formatting</li>
	<li>Comment images</li>
	<li>Comment videos</li>
	<li>Author comments</li>
	<li>Gravatars and default fallbacks</li>
</ul>
````

### 1149 — post, publish — Template: Pingbacks And Trackbacks

````html
This post has many pingpacks and trackbacks.

There are a few ways to list them.
<ol>
	<li>Above the comments</li>
	<li>Below the comments</li>
	<li>Included within the normal flow of comments</li>
</ol>
````

### 1150 — post, publish — Template: Comments Disabled

````html
This post has its comments, pingbacks, and trackbacks disabled.

There should be no comment reply form, but <em>should</em> display pingbacks and trackbacks.
````

### 1151 — post, publish — Edge Case: Many Tags

````html
This post has many tags.
````

### 1152 — post, publish — Edge Case: Many Categories

````html
This post has many categories.
````

### 1153 — post, future — Scheduled

````html
This post is scheduled to be published in the future.

It should not be displayed by the theme.
````

### 1158 — post, publish — Post Format: Image

````html
<dl id="attachment_612" class="wp-caption aligncenter" style="width:650px;"><dt class="wp-caption-dt"></dt></dl>&nbsp;

<a href="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg"><img class="alignnone wp-image-755 size-large" src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg?w=604" alt="" width="604" height="453" /></a>
````

### 1161 — post, publish — Post Format: Video (YouTube)

````html
https://www.youtube.com/watch?v=SQEQr7c0-dw

Learn more about <a title="WordPress Embeds" href="https://codex.wordpress.org/Embeds" target="_blank">WordPress Embeds</a>.
````

### 1163 — post, publish — Post Format: Image (Caption)

````html
[caption id="attachment_754" align="alignnone" width="604"]<a href="http://localhost:8766/wp-content/uploads/2008/06/100_5478.jpg"><img class="wp-image-754 size-large" src="http://localhost:8766/wp-content/uploads/2008/06/100_5478.jpg?w=604" alt="Bell on Wharf" width="604" height="453" /></a> Bell on wharf in San Francisco[/caption]
````

### 1164 — post, draft — Draft

````html
This post is drafted and not published yet.

It should not be displayed by the theme.
````

### 1168 — post, publish — Template: Password Protected (the password is "enter")

````html
This content, comments, pingbacks, and trackbacks should not be visible until the password is entered.
````

### 1169 — post, publish — (sans titre)

````html
This post has no title, but it still must link to the single post view somehow.

This is typically done by placing the permalink on the post date.
````

### 1171 — post, publish — Template: Paginated

````html
Post Page 1

<!--nextpage-->

Post Page 2

<!--nextpage-->

Post Page 3
````

### 1173 — post, publish — Markup: Title <em>With</em> <b>Mark<sup>up</sup></b>

````html
Verify that:
<ul>
	<li>The post title renders the word "with" in <em>italics</em> and the word "markup" in <strong>bold</strong> (and "up" is <sup>super</sup>script).</li>
	<li><strong>The post title markup should be removed from the browser window / tab.</strong></li>
</ul>
````

### 1174 — post, publish — Markup: Title With Special Characters ~`!@#$%^&*()-_=+{}[]/\;:'"?,.>

````html
Putting special characters in the title should have no adverse effect on the layout or functionality.

Special characters in the post title have been known to cause issues with JavaScript when it is minified, especially in the admin when editing the post itself (ie. issues with metaboxes, media upload, etc.).
<h2>Latin Character Tests</h2>
This is a test to see if the fonts used in this theme support basic Latin characters.
<table>
<tbody>
<tr>
<td>!</td>
<td>"</td>
<td>#</td>
<td>$</td>
<td>%</td>
<td>&amp;</td>
<td>'</td>
<td>(</td>
<td>)</td>
<td>*</td>
</tr>
<tr>
<td>+</td>
<td>,</td>
<td>-</td>
<td>.</td>
<td>/</td>
<td>0</td>
<td>1</td>
<td>2</td>
<td>3</td>
<td>4</td>
</tr>
<tr>
<td>5</td>
<td>6</td>
<td>7</td>
<td>8</td>
<td>9</td>
<td>:</td>
<td>;</td>
<td>&gt;</td>
<td>=</td>
<td>&lt;</td>
</tr>
<tr>
<td>?</td>
<td>@</td>
<td>A</td>
<td>B</td>
<td>C</td>
<td>D</td>
<td>E</td>
<td>F</td>
<td>G</td>
<td>H</td>
</tr>
<tr>
<td>I</td>
<td>J</td>
<td>K</td>
<td>L</td>
<td>M</td>
<td>N</td>
<td>O</td>
<td>P</td>
<td>Q</td>
<td>R</td>
</tr>
<tr>
<td>S</td>
<td>T</td>
<td>U</td>
<td>V</td>
<td>W</td>
<td>X</td>
<td>Y</td>
<td>Z</td>
<td>[</td>
<td>\</td>
</tr>
<tr>
<td>]</td>
<td>^</td>
<td>_</td>
<td>`</td>
<td>a</td>
<td>b</td>
<td>c</td>
<td>d</td>
<td>e</td>
<td>f</td>
</tr>
<tr>
<td>g</td>
<td>h</td>
<td>i</td>
<td>j</td>
<td>k</td>
<td>l</td>
<td>m</td>
<td>n</td>
<td>o</td>
<td>p</td>
</tr>
<tr>
<td>q</td>
<td>r</td>
<td>s</td>
<td>t</td>
<td>u</td>
<td>v</td>
<td>w</td>
<td>x</td>
<td>y</td>
<td>z</td>
</tr>
<tr>
<td>{</td>
<td>|</td>
<td>}</td>
<td>~</td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
</tbody>
</table>
````

### 1175 — post, publish — Taumatawhakatangihangakoauauotamateaturipukakapikimaungahoronukupokaiwhenuakitanatahu

````html
<h2>Title should not overflow the content area</h2>

A few things to check for:
<ul>
	<li>Non-breaking text in the title, content, and comments should have no adverse effects on layout or functionality.</li>
	<li>Check the browser window / tab title.</li>
	<li>If you are a plugin or widget developer, check that this text does not break anything.</li>
</ul>

The following CSS properties will help you support non-breaking text.

<pre>-ms-word-wrap: break-word;
word-wrap: break-word;</pre>
&nbsp;
````

### 1176 — post, publish — Markup: Text Alignment

````html
<h3>Default</h3>
This is a paragraph. It should not have any alignment of any kind. It should just flow like you would normally expect. Nothing fancy. Just straight up text, free flowing, with love. Completely neutral and not picking a side or sitting on the fence. It just is. It just freaking is. It likes where it is. It does not feel compelled to pick a side. Leave him be. It will just be better that way. Trust me.
<h3>Left Align</h3>
<p style="text-align:left;">This is a paragraph. It is left aligned. Because of this, it is a bit more liberal in it's views. It's favorite color is green. Left align tends to be more eco-friendly, but it provides no concrete evidence that it really is. Even though it likes share the wealth evenly, it leaves the equal distribution up to justified alignment.</p>

<h3>Center Align</h3>
<p style="text-align:center;">This is a paragraph. It is center aligned. Center is, but nature, a fence sitter. A flip flopper. It has a difficult time making up its mind. It wants to pick a side. Really, it does. It has the best intentions, but it tends to complicate matters more than help. The best you can do is try to win it over and hope for the best. I hear center align does take bribes.</p>

<h3>Right Align</h3>
<p style="text-align:right;">This is a paragraph. It is right aligned. It is a bit more conservative in it's views. It's prefers to not be told what to do or how to do it. Right align totally owns a slew of guns and loves to head to the range for some practice. Which is cool and all. I mean, it's a pretty good shot from at least four or five football fields away. Dead on. So boss.</p>

<h3>Justify Align</h3>
<p style="text-align:justify;">This is a paragraph. It is justify aligned. It gets really mad when people associate it with Justin Timberlake. Typically, justified is pretty straight laced. It likes everything to be in it's place and not all cattywampus like the rest of the aligns. I am not saying that makes it better than the rest of the aligns, but it does tend to put off more of an elitist attitude.</p>
````

### 1177 — post, publish — Markup: Image Alignment

````html
Welcome to image alignment! The best way to demonstrate the ebb and flow of the various image positioning options is to nestle them snuggly among an ocean of words. Grab a paddle and let's get started.

On the topic of alignment, it should be noted that users can choose from the options of <em>None</em>, <em>Left</em>, <em>Right, </em>and <em>Center</em>. In addition, they also get the options of <em>Thumbnail</em>, <em>Medium</em>, <em>Large</em> &amp; <em>Fullsize</em>. Be sure to try this page in RTL mode and it should look the same as LTR. 
<p><img class="size-full wp-image-906 aligncenter" title="Image Alignment 580x300" alt="Image Alignment 580x300" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" width="580" height="300" /></p>
The image above happens to be <em><strong>centered</strong></em>.

<img class="size-full wp-image-904 alignleft" title="Image Alignment 150x150" alt="Image Alignment 150x150" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" width="150" height="150" /> The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. 

As you can see the should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!

And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.

<img class="alignnone  wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" />

The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

<img class="aligncenter  wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" />

And we try the large image again, with the center alignment since that sometimes is a problem. The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

<img class="size-full wp-image-905 alignright" title="Image Alignment 300x200" alt="Image Alignment 300x200" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" width="300" height="200" />

And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there... Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.

In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah... Just like that. It never felt so good to be right.

And just when you thought we were done, we're going to do them all over again with captions!

[caption id="attachment_906" align="aligncenter" width="580"]<img class="size-full wp-image-906  " title="Image Alignment 580x300" alt="Image Alignment 580x300" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" width="580" height="300" /> Look at 580x300 getting some <a title="Image Settings" href="https://en.support.wordpress.com/images/image-settings/">caption</a> love.[/caption]

The image above happens to be <em><strong>centered</strong></em>. The caption also has a link in it, just to see if it does anything funky.

[caption id="attachment_904" align="alignleft" width="150"]<img class="size-full wp-image-904  " title="Image Alignment 150x150" alt="Image Alignment 150x150" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" width="150" height="150" /> Bigger caption than the image usually is.[/caption]

The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. 

As you can see the should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!

And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.

[caption id="attachment_907" align="alignnone" width="1200"]<img class=" wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" /> Comment for massive image for your eyeballs.[/caption]

The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.
[caption id="attachment_907" align="aligncenter" width="1200"]<img class=" wp-image-907" title="Image Alignment 1200x400" alt="Image Alignment 1200x400" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" width="1200" height="400" /> This massive image is centered.[/caption]

And again with the big image centered. The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.

[caption id="attachment_905" align="alignright" width="300"]<img class="size-full wp-image-905 " title="Image Alignment 300x200" alt="Image Alignment 300x200" src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" width="300" height="200" /> Feels good to be right all the time.[/caption]

And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there... Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.

In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah... Just like that. It never felt so good to be right.

And that's a wrap, yo! You survived the tumultuous waters of alignment. Image alignment achievement unlocked! One last thing: The last item in this post's content is a thumbnail floated right. Make sure any elements after the content are clearing properly.

<img class="alignright size-thumbnail wp-image-827" title="Camera" src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" width="160" />
````

### 1178 — post, publish — Markup: HTML Tags and Formatting

````html
<strong>Headings</strong>
<h1>Header one</h1>
<h2>Header two</h2>
<h3>Header three</h3>
<h4>Header four</h4>
<h5>Header five</h5>
<h6>Header six</h6>
<h2>Blockquotes</h2>
Single line blockquote:
<blockquote>Stay hungry. Stay foolish.</blockquote>
Multi line blockquote with a cite reference:
<blockquote cite="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/blockquote"><p>The <strong>HTML <code>&lt;blockquote&gt;</code> Element</strong> (or <em>HTML Block Quotation Element</em>) indicates that the enclosed text is an extended quotation. Usually, this is rendered visually by indentation (see <a href="https://developer.mozilla.org/en-US/docs/HTML/Element/blockquote#Notes">Notes</a> for how to change it). A URL for the source of the quotation may be given using the <strong>cite</strong> attribute, while a text representation of the source can be given using the <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite" title="The HTML Citation Element &lt;cite&gt; represents a reference to a creative work. It must include the title of a work or a URL reference, which may be in an abbreviated form according to the conventions used for the addition of citation metadata."><code>&lt;cite&gt;</code></a> element.</p></blockquote>
<cite>multiple contributors</cite> - MDN HTML element reference - blockquote
<h2>Tables</h2>
<table>
<thead>
<tr>
<th>Employee</th>
<th>Salary</th>
<th></th>
</tr>
</thead>
<tbody>
<tr>
<th><a href="http://example.org/">John Doe</a></th>
<td>$1</td>
<td>Because that's all Steve Jobs needed for a salary.</td>
</tr>
<tr>
<th><a href="http://example.org/">Jane Doe</a></th>
<td>$100K</td>
<td>For all the blogging she does.</td>
</tr>
<tr>
<th><a href="http://example.org/">Fred Bloggs</a></th>
<td>$100M</td>
<td>Pictures are worth a thousand words, right? So Jane x 1,000.</td>
</tr>
<tr>
<th><a href="http://example.org/">Jane Bloggs</a></th>
<td>$100B</td>
<td>With hair like that?! Enough said...</td>
</tr>
</tbody>
</table>
<h2>Definition Lists</h2>
<dl><dt>Definition List Title</dt><dd>Definition list division.</dd><dt>Startup</dt><dd>A startup company or startup is a company or temporary organization designed to search for a repeatable and scalable business model.</dd><dt>#dowork</dt><dd>Coined by Rob Dyrdek and his personal body guard Christopher "Big Black" Boykins, "Do Work" works as a self motivator, to motivating your friends.</dd><dt>Do It Live</dt><dd>I'll let Bill O'Reilly will <a title="We'll Do It Live" href="https://www.youtube.com/watch?v=O_HyZ5aW76c">explain</a> this one.</dd></dl>
<h2>Unordered Lists (Nested)</h2>
<ul>
	<li>List item one
<ul>
	<li>List item one
<ul>
	<li>List item one</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ul>
<h2>Ordered List (Nested)</h2>
<ol start="8">
 	<li>List item one -start at 8
<ol>
 	<li>List item one
<ol reversed="reversed">
 	<li>List item one -reversed attribute</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
</li>
	<li>List item two</li>
	<li>List item three</li>
	<li>List item four</li>
</ol>
<h2>HTML Tags</h2>
These supported tags come from the WordPress.com code <a title="Code" href="https://en.support.wordpress.com/code/">FAQ</a>.

<strong>Address Tag</strong>

<address>1 Infinite Loop
Cupertino, CA 95014
United States</address><strong>Anchor Tag (aka. Link)</strong>

This is an example of a <a title="WordPress Foundation" href="https://wordpressfoundation.org/">link</a>.

<strong>Abbreviation Tag</strong>

The abbreviation <abbr title="Seriously">srsly</abbr> stands for "seriously".

<strong>Acronym Tag (<em>deprecated in HTML5</em>)</strong>

The acronym <acronym title="For The Win">ftw</acronym> stands for "for the win".

<strong>Big Tag</strong> (<em>deprecated in HTML5</em>)

These tests are a <big>big</big> deal, but this tag is no longer supported in HTML5.

<strong>Cite Tag</strong>

"Code is poetry." --<cite>Automattic</cite>

<strong>Code Tag</strong>

This tag styles blocks of code.
<code>.post-title {
	margin: 0 0 5px;
	font-weight: bold;
	font-size: 38px;
	line-height: 1.2;
	and here's a line of some really, really, really, really long text, just to see how it is handled and to find out how it overflows;
}</code>
You will learn later on in these tests that <code>word-wrap: break-word;</code> will be your best friend.

<strong>Delete Tag</strong>

This tag will let you <del cite="deleted it">strike out text</del>, but this tag is <em>recommended</em> supported in HTML5 (use the <code>&lt;s&gt;</code> instead).

<strong>Emphasize Tag</strong>

The emphasize tag should <em>italicize</em> <i>text</i>.

<strong>Horizontal Rule Tag</strong>

<hr />

This sentence is following a <code>&lt;hr /&gt;</code> tag.

<strong>Insert Tag</strong>

This tag should denote <ins cite="inserted it">inserted</ins> text.

<strong>Keyboard Tag</strong>

This scarcely known tag emulates <kbd>keyboard text</kbd>, which is usually styled like the <code>&lt;code&gt;</code> tag.

<strong>Preformatted Tag</strong>

This tag is for preserving whitespace as typed, such as in poetry or ASCII art.
<h2>The Road Not Taken</h2>
<pre>
<cite>Robert Frost</cite>


	Two roads diverged in a yellow wood,
	And sorry I could not travel both          (\_/)
	And be one traveler, long I stood         (='.'=)
	And looked down one as far as I could     (")_(")
	To where it bent in the undergrowth;

	Then took the other, as just as fair,
	And having perhaps the better claim,          |\_/|
	Because it was grassy and wanted wear;       / @ @ \
	Though as for that the passing there        ( &gt; º &lt; )
	Had worn them really about the same,         `&gt;&gt;x&lt;&lt;´
	 /  O  \
	And both that morning equally lay
	In leaves no step had trodden black.
	Oh, I kept the first for another day!
	Yet knowing how way leads on to way,
	I doubted if I should ever come back.

	I shall be telling this with a sigh
	Somewhere ages and ages hence:
	Two roads diverged in a wood, and I—
	I took the one less traveled by,
	And that has made all the difference.


	and here's a line of some really, really, really, really long text, just to see how it is handled and to find out how it overflows;
</pre>
<strong>Quote Tag</strong> for short, inline quotes

<q>Developers, developers, developers...</q> --Steve Ballmer

<strong>Strike Tag</strong> (<em>deprecated in HTML5</em>) and <strong>S Tag</strong>

This tag shows <strike>strike-through</strike> <s>text</s>.

<strong>Small Tag</strong>

This tag shows <small>smaller<small> text.</small></small>

<strong>Strong Tag</strong>

This tag shows <strong>bold<strong> text.</strong></strong>

<strong>Subscript Tag</strong>

Getting our science styling on with H<sub>2</sub>O, which should push the "2" down.

<strong>Superscript Tag</strong>

Still sticking with science and Albert Einstein's E = MC<sup>2</sup>, which should lift the 2 up.

<strong>Teletype Tag </strong>(<em>obsolete in HTML5</em>)

This rarely used tag emulates <tt>teletype text</tt>, which is usually styled like the <code>&lt;code&gt;</code> tag.

<strong>Underline Tag</strong> <em>deprecated in HTML 4, re-introduced in HTML5 with other semantics</em>

This tag shows <u>underlined text</u>.

<strong>Variable Tag</strong>

This allows you to denote <var>variables</var>.
````

### 1179 — post, publish — Media: Twitter Embeds

````html
https://twitter.com/nacin/status/319508408669708289

This post tests WordPress' <a title="Twitter Embeds" href="https://en.support.wordpress.com/twitter/twitter-embeds/" target="_blank">Twitter Embeds</a> feature.
````

### 1241 — post, publish — Template: Sticky

````html
This is a sticky post.

There are a few things to verify:
<ul>
	<li>The sticky post should be distinctly recognizable in some way in comparison to normal posts. You can style the <code>.sticky</code> class if you are using the <a title="WordPress post_class() Function" href="https://developer.wordpress.org/reference/functions/post_class/" target="_blank">post_class()</a> function to generate your post classes, which is a best practice.</li>
	<li>They should show at the very top of the blog index page, even though they could be several posts back chronologically.</li>
	<li>They should still show up again in their chronologically correct postion in time, but without the sticky indicator.</li>
	<li>If you have a plugin or widget that lists popular posts or comments, make sure that this sticky post is not always at the top of those lists unless it really is popular.</li>
</ul>
````

### 1446 — post, publish — Template: Excerpt (Generated)

````html
This is the post content. It should be displayed in place of the auto-generated excerpt in single-page views. Archive-index pages should display an auto-generated excerpt of this content. Depending on Theme-defined filters, the length of the auto-generated excerpt will vary from Theme-to-Theme. The default length for auto-generated excerpts is 55 words, so to test the excerpt auto-generation, this post must have more than 55 words.

Be sure to test the formatting of the auto-generated excerpt, to ensure that it doesn't create any layout problems. Also, ensure that any filters applied to the excerpt, such as <code>excerpt_length</code> and <code>excerpt_more</code>, display properly.
````

### 1724 — post, publish — Keyboard navigation

````html
<!-- wp:paragraph -->
<p>There are many different ways to use the web besides a mouse and a pair of eyes.  Users navigate for example with a keyboard only or with their voice. </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>All the functionality, including menus, links and forms should work using a <strong>keyboard only</strong>. This is essential for all assistive technology to work properly. The only way to test this, at the moment, is manually. The best time to test this is during development.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>How to keyboard test:</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Tab through your pages, links and forms to do the following tests:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul><li>Confirm that all links can be reached and activated via keyboard, including any in dropdown submenus.</li><li>Confirm that all links get a visible focus indicator (e.g., a border highlight).</li><li>Confirm that all <a href="https://make.wordpress.org/accessibility/handbook/best-practices/markup/the-css-class-screen-reader-text/">visually hidden links</a> (e.g. <a href="https://make.wordpress.org/accessibility/handbook/best-practices/markup/skip-links/">skip links</a>) become visible when in focus.</li><li>Confirm that all form input fields and buttons can be accessed and used via keyboard.</li><li>Confirm that all interactions, buttons, and other controls can be triggered via keyboard — any action you can complete with a mouse must also be performable via keyboard.</li><li>Confirm that focus doesn’t move in unexpected ways around the page.</li><li>Confirm that using shift+tab to move backwards works as well.</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>Resources</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li><a href="https://make.wordpress.org/accessibility/handbook/">The Make WordPress Accessibility Handbook </a><ul><li><a href="https://make.wordpress.org/accessibility/handbook/test-for-web-accessibility/">Test for web accessibility</a></li></ul></li><li><a href="https://webaim.org/techniques/keyboard/">Keyboard Accessibility</a> by WebAIM</li><li><a href="http://rianrietveld.com/2016/05/10/keyboard/">Workshop keyboard accessibility</a></li><li><a href="https://make.wordpress.org/themes/handbook/review/accessibility/required/">Theme review accessibility-ready requirements: Keyboard Navigation</a></li></ul>
<!-- /wp:list -->
````

### 1725 — page, publish — About The Tests

````html
This site is using the standard WordPress Theme Unit Test Data for content. The Theme Unit Test is a series of posts and pages that match up with a checklist on the WordPress codex. You can use the data and checklist together to test your theme. It is recommended that you test your theme with the Theme Unit Test before submitting your theme to the WordPress.org theme directory.

<h2>WordPress Theme Development Resources</h2>

<ol>
	<li>See <a href="https://developer.wordpress.org/themes/">the WordPress Theme Developer Handbook</a> for examples of best practices.</li>
	<li>See <a href="https://developer.wordpress.org/reference/">the WordPress Code Reference</a> for more information about WordPress' functions, classes, methods, and hooks.</li>
	<li>See <a href="https://codex.wordpress.org/Theme_Unit_Test">Theme Unit Test</a> for a robust test suite for your Theme and get the latest version of the test data you see here.</li>
	<li>See <a href="https://developer.wordpress.org/themes/release/">Releasing Your Theme</a> for a guide to submitting your Theme to the <a href="https://wordpress.org/themes/">Theme Directory</a>.</li>
</ol>
````

### 1728 — nav_menu_item, publish — (sans titre)

````html
Posts in this category test markup tags and styles.
````

### 1729 — nav_menu_item, publish — (sans titre)

````html
Posts in this category test post formats.
````

### 1730 — nav_menu_item, publish — (sans titre)

````html
Posts in this category test unpublished posts.
````

### 1743 — nav_menu_item, publish — Menu Description

````html
Custom Menu Description
````

### 1778 — post, publish — Block category: Common

````html
<!-- wp:paragraph -->
<p>The Common category includes the following blocks:<em> Paragraph, image, headings, list, gallery, quote, audio, cover, video.</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The paragraph block is the default block type.&nbsp; It should not have any alignment of any kind. It should just flow like you would normally expect. Nothing fancy. Just straight up text, free flowing, with love.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>This paragraph is left aligned.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"right"} -->
<p class="has-text-align-right"><em>This italic paragraph is right aligned.</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"dropCap":true,"fontSize":"medium"} -->
<p class="has-drop-cap has-medium-font-size"><strong>Neither of these paragraphs care about politics, but this one is bold, medium sized and has a drop cap.</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">This paragraph is centered. </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">This paragraph prefers Jazz over Justin Timberlake. It also uses the small font size.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">This paragraph has something important to say:&nbsp; It has a large font size, which defaults to 36px.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"customFontSize":46} -->
<p style="font-size:46px">The huge text size defaults to 46px, but the size can be customized.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"very-light-gray","customBackgroundColor":"#cf2e2e"} -->
<p style="background-color:#cf2e2e" class="has-text-color has-background has-very-light-gray-color">This paragraph is colorful, with a red background and white text (maybe).  Colored blocks should have a high enough contrast, so that the text is readable. </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"customTextColor":"#1e0566"} -->
<p style="color:#1e0566" class="has-text-color"><strong>Below this block, you will see a single image with a circle mask applied.</strong></p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":968,"sizeSlug":"full","className":"is-style-circle-mask"} -->
<figure class="wp-block-image size-full is-style-circle-mask"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="Image Alignment 150x150" class="wp-image-968"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":1} -->
<h1>H1 Heading</h1>
<!-- /wp:heading -->

<!-- wp:heading -->
<h2>H2 Heading</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>H3 Heading</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":4} -->
<h4>H4 Heading</h4>
<!-- /wp:heading -->

<!-- wp:heading {"level":5} -->
<h5>H5 Heading</h5>
<!-- /wp:heading -->

<!-- wp:heading {"level":6} -->
<h6>H6 Heading</h6>
<!-- /wp:heading -->

<!-- wp:heading -->
<h2>Ordered list</h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true,"start":0} -->
<ol start="0"><li>The software should be licensed under the&nbsp;<a href="http://www.gnu.org/copyleft/gpl.html">GNU Public License</a>.</li><li>The software should be freely available to anyone to use for any purpose, and without permission.</li><li>The software should be open to modifications.<ol><li>Any modifications should be freely distributable at no cost and without permission from its creators.</li></ol></li><li>The software should provide a framework for translation to make it globally accessible to speakers of all languages.</li><li>The software should provide a framework for extensions so modifications and enhancements can be made without modifying core code</li></ol>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>Unordered list</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>One</li><li>Two</li><li>Three<ul><li>Four</li></ul></li><li>Five</li></ul>
<!-- /wp:list -->

<!-- wp:gallery {"ids":["769","768","767","770","807","766"],"columns":2} -->
<figure class="wp-block-gallery columns-2 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0747.jpg" alt="Brazil Beach" data-id="769" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0747/" class="wp-image-769"/><figcaption class="blocks-gallery-item__caption">Jericoacoara Ceara Brasil</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0513-1.jpg" alt="Huatulco Coastline" data-id="768" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/alas-i-have-found-my-shangri-la/" class="wp-image-768"/><figcaption class="blocks-gallery-item__caption">Sunrise over the coast in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/windmill.jpg" alt="Windmill" data-id="767" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery//dcf-1-0/" class="wp-image-767"/><figcaption class="blocks-gallery-item__caption">Windmill shrouded in fog at a farm outside of Walker, Iowa</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg" alt="Huatulco Coastline" data-id="770" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0767/" class="wp-image-770"/><figcaption class="blocks-gallery-item__caption">Coastline in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg" alt="" data-id="807" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/" class="wp-image-807"/></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/michelle_049.jpg" alt="Big Sur" data-id="766" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/michelle_049/" class="wp-image-766"/><figcaption class="blocks-gallery-item__caption">Beach at Big Sur, CA</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption">images are not linked</figcaption></figure>
<!-- /wp:gallery -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>Quote</p><cite>Cite</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:audio {"id":821} -->
<figure class="wp-block-audio"><audio controls src="http://localhost:8766/wp-content/uploads/2008/06/originaldixielandjazzbandwithalbernard-stlouisblues.mp3"></audio></figure>
<!-- /wp:audio -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","id":759,"minHeight":274} -->
<div class="wp-block-cover has-background-dim" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg);min-height:274px"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Cover block with background image</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

<!-- wp:paragraph -->
<p>The file block has a setting that lets us show or hide a download button with editable text: </p>
<!-- /wp:paragraph -->

<!-- wp:file {"id":1690,"href":"https://wpthemetestdata.files.wordpress.com/2018/11/file_block.pdf"} -->
<div class="wp-block-file"><a href="https://wpthemetestdata.files.wordpress.com/2018/11/file_block.pdf">File Block PDF</a><a href="https://wpthemetestdata.files.wordpress.com/2018/11/file_block.pdf" class="wp-block-file__button" download>Download</a></div>
<!-- /wp:file -->

<!-- wp:file {"id":1690,"href":"https://wpthemetestdata.files.wordpress.com/2018/11/file_block.pdf","showDownloadButton":false} -->
<div class="wp-block-file"><a href="https://wpthemetestdata.files.wordpress.com/2018/11/file_block.pdf">File Block PDF</a></div>
<!-- /wp:file -->

<!-- wp:paragraph -->
<p>Video blocks have settings for showing and hiding the playback controls. Use autoplay and playback controls responsibly. </p>
<!-- /wp:paragraph -->

<!-- wp:video {"id":1690} -->
<figure class="wp-block-video"><video controls loop src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video><figcaption>This is a video block caption.</figcaption></figure>
<!-- /wp:video -->

<!-- wp:paragraph -->
<p>The video block below is muted and has a poster image that displays before the video starts:</p>
<!-- /wp:paragraph -->

<!-- wp:video {"id":1690} -->
<figure class="wp-block-video"><video controls muted poster="http://localhost:8766/wp-content/uploads/2008/06/dsc20050727_091048_222.jpg" src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure>
<!-- /wp:video -->

````

### 1779 — post, publish — Block category: Formatting

````html
<!-- wp:paragraph -->
<p>The formatting category includes the following blocks:</p>
<!-- /wp:paragraph -->

<!-- wp:code -->
<pre class="wp-block-code"><code>The code block starts with
&lt;!-- wp:code -->
&lt;?php echo 'Hello World'; ?>
</code></pre>
<!-- /wp:code -->

<p>The classic block can have <em>almost</em> anything in it.</p>
<hr />
<h6>a heading</h6>

<!-- wp:html -->
<div style="width: 45%">The custom HTML block lets you put HTML that isn't configured like blocks in it. (this div has a width of 45%)</div>
<!-- /wp:html -->

<!-- wp:preformatted -->
<pre class="wp-block-preformatted">The preformatted block.<br><br>The Road Not Taken<br><br>Robert Frost <br>Two roads diverged in a yellow wood,<br>And sorry I could not travel both          (\_/)  <br>And be one traveler, long I stood         (='.'=)  <br>And looked down one as far as I could     (")_(")  <br>To where it bent in the undergrowth; <br><br>Then took the other, as just as fair,  <br>And having perhaps the better claim,          |\_/|  <br>Because it was grassy and wanted wear;       / @ @ \  <br>Though as for that the passing there        ( &gt; º &lt; )  <br>Had worn them really about the same,         `&gt;&gt;x&lt;&lt;´  <br>                                             /  O  \  <br>And both that morning equally lay  <br>In leaves no step had trodden black.  <br>Oh, I kept the first for another day!  <br>Yet knowing how way leads on to way,  <br>I doubted if I should ever come back.  <br>I shall be telling this with a sigh  <br>Somewhere ages and ages hence:  <br>Two roads diverged in a wood, and I—  <br>I took the one less traveled by,  <br>And that has made all the difference.  <br><br><br><br>and here's a line of some really, really, really, really long text, just to see how it is handled and to find out how it overflows;<br></pre>
<!-- /wp:preformatted -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>The pull quote can be aligned or wide or neither.</p><cite>Theme Reviewer</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:table -->
<figure class="wp-block-table"><table class=""><tbody><tr><td>The table block</td><td>This is the default style.</td></tr><tr><td></td><td>The cell next to this is empty.</td></tr><tr><td>Cell #5<br></td><td>Cell #6</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:table {"hasFixedLayout":true,"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table class="has-fixed-layout"><tbody><tr><td>This is the striped style.</td><td>This row should have a background color.</td></tr><tr><td>The cell next to this is empty.</td><td><br><br></td></tr><tr><td></td><td>This table has fixed width table cells.<br></td></tr><tr><td><br>Make sure that the text wraps correctly.<br><br></td><td></td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:verse -->
<pre class="wp-block-verse">The Verse block<br><br>A block for haiku? <br>  Why not? <br>    Blocks for all the things!</pre>
<!-- /wp:verse -->
````

### 1780 — post, publish — Block category: Layout Elements

````html
<!-- wp:group {"customBackgroundColor":"#d8f6ec"} -->
<div class="wp-block-group has-background" style="background-color:#d8f6ec"><div class="wp-block-group__inner-container"><!-- wp:paragraph -->
<p>The Layout Elements category includes the following blocks: <em>Group, Button, Columns, Media &amp; Text, separator, spacer, read more, and page break.</em></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>This group block has a light green background color.</p>
<!-- /wp:paragraph -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link">A button</a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>The read more block should be right below this text, but only on list pages of themes that show the full content. It won't show on the single page or on themes showing excerpts.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:group -->

<!-- wp:more -->
<!--more-->
<!-- /wp:more -->

<!-- wp:columns {"className":"has-4-columns"} -->
<div class="wp-block-columns has-4-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>The columns:</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column two.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column three.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column four.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:media-text {"mediaId":757,"mediaType":"image"} -->
<div class="wp-block-media-text alignwide"><figure class="wp-block-media-text__media"><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" class="wp-image-757"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…","fontSize":"large"} -->
<p class="has-large-font-size">Media &amp;Text</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>For displaying media and text next to each other. By default, the media is to the left.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

<!-- wp:media-text {"align":"full","customBackgroundColor":"#ebf5fe","mediaPosition":"right","mediaId":755,"mediaType":"image","isStackedOnMobile":true} -->
<div class="wp-block-media-text alignfull has-media-on-the-right has-background is-stacked-on-mobile" style="background-color:#ebf5fe"><figure class="wp-block-media-text__media"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" class="wp-image-755"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…","fontSize":"large"} -->
<p class="has-large-font-size">This time our block is full width, and the image is to the right.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The background color is a pale blue.&nbsp;</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

<!-- wp:paragraph -->
<p>Test to make sure that the editor and the front match.&nbsp;To test the <em>Stack on mobile </em>setting, reduce the browser window width.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The control these settings, the block uses the css classes "has-media-on-the-right" and "is-stacked-on-mobile".</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The separator has three styles: default, wide line, and dots.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator"/>
<!-- /wp:separator -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:separator {"className":"is-style-dots"} -->
<hr class="wp-block-separator is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:paragraph -->
<p>The spacer block has a default height of 100 pixels:</p>
<!-- /wp:paragraph -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph -->
<p>And finally, the page break:</p>
<!-- /wp:paragraph -->

<!-- wp:nextpage -->
<!--nextpage-->
<!-- /wp:nextpage -->

<!-- wp:paragraph -->
<p>This paragraph block is on page two, after the page break.</p>
<!-- /wp:paragraph -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link" href="https://wpthemetestdata.wordpress.com/2018/11/03/block-button/">another button</a></div>
<!-- /wp:button -->
````

### 1781 — post, publish — Block category: Embeds

````html

	<!-- wp:paragraph -->
<p>This post tests various embed blocks:</p>
<!-- /wp:paragraph -->
	
<!-- wp:core-embed/twitter {"url":"https://twitter.com/WordPress/status/1057136472321613824","type":"rich","providerNameSlug":"twitter","align":"wide","className":""} -->
<figure class="wp-block-embed-twitter alignwide wp-block-embed is-type-rich is-provider-twitter"><div class="wp-block-embed__wrapper">
https://twitter.com/WordPress/status/1057136472321613824
</div><figcaption>Twitter,&nbsp; wide width</figcaption></figure>
<!-- /wp:core-embed/twitter -->

<!-- wp:core-embed/youtube {"url":"https://youtu.be/ex8fMxXJDJw","type":"video","providerNameSlug":"youtube","className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
<figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
https://youtu.be/ex8fMxXJDJw
</div><figcaption>YouTube<br></figcaption></figure>
<!-- /wp:core-embed/youtube -->

<!-- wp:core-embed/facebook {"url":"https://www.facebook.com/6427302910/posts/10156380423617911/","type":"rich","providerNameSlug":"facebook","className":""} -->
<figure class="wp-block-embed-facebook wp-block-embed is-type-rich is-provider-facebook"><div class="wp-block-embed__wrapper">
https://www.facebook.com/6427302910/posts/10156380423617911/
</div></figure>
<!-- /wp:core-embed/facebook -->

<!-- wp:core-embed/instagram {"url":"https://www.instagram.com/p/BpmueLLgEn_/?utm_source=ig_share_sheet\u0026igshid=1hcxphic7p9e2","type":"rich","providerNameSlug":"instagram","className":""} -->
<figure class="wp-block-embed-instagram wp-block-embed is-type-rich is-provider-instagram"><div class="wp-block-embed__wrapper">
https://www.instagram.com/p/BpmueLLgEn_/?utm_source=ig_share_sheet&amp;igshid=1hcxphic7p9e2
</div></figure>
<!-- /wp:core-embed/instagram -->

<!-- wp:core-embed/wordpress-tv {"url":"https://wordpress.tv/2018/10/14/kjell-reigstad-allan-cole-how-we-made-our-first-gutenberg-powered-theme/","type":"video","providerNameSlug":"","align":"full","className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
<figure class="wp-block-embed-wordpress-tv alignfull wp-block-embed is-type-video wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper">
https://wordpress.tv/2018/10/14/kjell-reigstad-allan-cole-how-we-made-our-first-gutenberg-powered-theme/
</div><figcaption>WordPress TV, full width<br></figcaption></figure>
<!-- /wp:core-embed/wordpress-tv -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
````

### 1782 — post, publish — Block category: Widgets

````html

	<!-- wp:paragraph -->
<p>The shortcode widget:</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gallery columns=2 ids="770,771"]
<!-- /wp:shortcode -->

<!-- wp:paragraph -->
<p>The Archive Widget:</p>
<!-- /wp:paragraph -->

<!-- wp:archives {"className":"extraclass","showPostCounts":true} /-->

<!-- wp:paragraph -->
<p>The same Archive widget but as a dropdown:</p>
<!-- /wp:paragraph -->

<!-- wp:archives {"displayAsDropdown":true,"showPostCounts":true} /-->

<!-- wp:calendar /-->

<!-- wp:paragraph -->
<p>The Category widget block has an additional option for showing category hierarchies:</p>
<!-- /wp:paragraph -->

<!-- wp:categories {"displayAsDropdown":true,"showHierarchy":true,"showPostCounts":true} /-->

<!-- wp:paragraph -->
<p>The Latest Comments widget can display or hide the avatars, the date, and the comment excerpt:</p>
<!-- /wp:paragraph -->

<!-- wp:latest-comments {"commentsToShow":4} /-->

<!-- wp:paragraph -->
<p>Here is an example of the Comments widget with all the options disabled. The number of comments has been reduced to two.</p>
<!-- /wp:paragraph -->

<!-- wp:latest-comments {"commentsToShow":2,"displayAvatar":false,"displayDate":false,"displayExcerpt":false} /-->

<!-- wp:paragraph -->
<p>And here is the Latest Posts widget in the list view, with dates:</p>
<!-- /wp:paragraph -->

<!-- wp:latest-posts {"displayPostDate":true} /-->

<!-- wp:paragraph -->
<p>Grid view, now sorted from A -Z.</p>
<!-- /wp:paragraph -->

<!-- wp:latest-posts {"postLayout":"grid","order":"asc","orderBy":"title"} /-->

<!-- wp:paragraph -->
<p>You can also change the number of columns used to display the latest posts. The block below only displays posts from the Block category:</p>
<!-- /wp:paragraph -->

<!-- wp:latest-posts {"categories":"6","postsToShow":10,"displayPostDate":true,"postLayout":"grid","columns":5} /-->

<!-- wp:paragraph -->
<p>Search widget:</p>
<!-- /wp:paragraph -->

<!-- wp:search /-->

<!-- wp:paragraph -->
<p>Tag Cloud widget:</p>
<!-- /wp:paragraph -->

<!-- wp:tag-cloud /-->

<!-- wp:paragraph -->
<p>RSS Feed widget:</p>
<!-- /wp:paragraph -->

<!-- wp:rss {"feedURL":"https://make.wordpress.org/themes/feed/"} /-->
	
````

### 1783 — post, publish — Block: Columns

````html
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>This page tests how the theme displays the columns block. The first block tests a two column block with paragraphs.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>This is the <strong>second</strong> column. It should align next to the first column. Reduce the browser window width to test the responsiveness.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>This is the second column block. It has <strong>3</strong> columns.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Paragraph 2 is in the middle.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Paragraph 3 is in the last column.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p> The third column block has <strong>4</strong> columns. Make sure that all the text is visible and that it is not cut off. </p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Now the columns are getting narrower.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>The margins between the columns should be wide enough,</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>so that the content of the columns does not run into or overlap each other.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column one.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column two.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column three.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column four.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column <strong>five</strong>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p>To change the number of columns, select the column block to open the settings panel. You can show up to 6 columns. If the theme has support for wide align, you can also set the alignments to wide and full width.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Below is a column block with six columns, and no alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column one.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column two.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column three.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column four.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column five.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column six.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p>Next is a 3 column block, with a wide alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column one.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column two.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Column three.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p>And here is a two column block with full width, and a longer text. Make sure that the text wraps correctly.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"full","className":"has-2-columns"} -->
<div class="wp-block-columns alignfull has-2-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>This is column one. Sometimes, you may want to use columns to display a larger text, so, lets add some <strong>more words</strong>.   Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna. Praesent sit amet ligula id orci venenatis auctor. Phasellus porttitor, metus non tincidunt dapibus, orci pede pretium neque, sit amet adipiscing ipsum lectus et libero. <em>Aenean</em> bibendum. Curabitur mattis quam id urna. Vivamus dui. Donec nonummy lacinia lorem. Cras risus arcu, sodales ac, ultrices ac, mollis quis, justo. Sed a libero. Quisque risus erat, posuere at, tristique non, lacinia quis, eros.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><strong>Column two.</strong> Cras volutpat, lacus quis semper pharetra, nisi enim dignissim est, et sollicitudin quam ipsum vel mi. Sed commodo urna ac urna. Nullam eu tortor. Curabitur sodales scelerisque magna. Donec ultricies tristique pede. Nullam libero. Nam sollicitudin f<em>elis vel metus. Nullam posuere molestie metus. Nullam molestie, nunc id suscipit rhoncus, felis mi </em>vulputate lacus, a ultrices tortor dolor eget augue. Aenean ultricies felis ut turpis. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Suspendisse placerat tellus ac nulla. Proin adipiscing sem ac risus. Maecenas nisi. Cras semper.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p>We can also add blocks inside columns:</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:list {"ordered":true} -->
<ol><li>This is a numbered list,</li><li>inside a 3 column block</li><li>with a wide alignment.</li></ol>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>The middle column has a paragraph with an image block below.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":611} -->
<figure class="wp-block-image"><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" class="wp-image-611"/><figcaption>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna.</figcaption></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote -->
<blockquote class="wp-block-quote"><p>-This third column has a quote</p><cite>Theme Reviewer<br></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph -->
<p><strong>But wait there is more!</strong>&nbsp; We also have a block called <em>Media &amp; Text,</em> which is a two column block that helps you display media and text content next to each other, without having to first setup a column block:</p>
<!-- /wp:paragraph -->

<!-- wp:media-text {"mediaId":617,"mediaType":"image"} -->
<div class="wp-block-media-text alignwide"><figure class="wp-block-media-text__media"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" class="wp-image-617"/></figure><div class="wp-block-media-text__content"><!-- wp:paragraph {"placeholder":"Content…","fontSize":"large"} -->
<p class="has-large-font-size">Media &amp; Text</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>A paragraph block sits ready to be used, below your headline.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->
````

### 1784 — post, publish — Block: Cover

````html

	<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","align":"left","id":761} -->
<div class="wp-block-cover has-background-dim alignleft" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg)"><p class="wp-block-cover-text">This is a left aligned cover block with a background image.</p></div>
<!-- /wp:cover -->

<!-- wp:paragraph -->
<p>The cover block lets you add text on top of images or videos.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>This blocktype has several alignment options, and you can also align or center the text inside the block.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The background image can be fixed and you can change its opacity and add an overlay color.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Make sure that the text wraps correctly over the image, and that text markup and alignments are working.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>The next image should have a pink overlay color, the text should be bold and aligned to the left:</p>
<!-- /wp:paragraph -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg","align":"center","contentAlign":"left","id":611,"overlayColor":"pale-pink"} -->
<div class="wp-block-cover has-pale-pink-background-color has-background-dim has-left-content aligncenter" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg)"><p class="wp-block-cover-text"><strong>A center aligned cover image block, with a left aligned text.</strong></p></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg","align":"full","id":759,"hasParallax":true,"dimRatio":20} -->
<div class="wp-block-cover has-background-dim-20 has-background-dim has-parallax alignfull" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg)"><p class="wp-block-cover-text">This is a full width cover block with a fixed background image with a 20% opacity.</p></div>
<!-- /wp:cover -->

<!-- wp:paragraph {"align":"center"} -->
<p style="text-align:center">Make sure that all the text is readable.</p>
<!-- /wp:paragraph -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2008/06/dsc03149.jpg","align":"wide","id":758} -->
<div class="wp-block-cover has-background-dim alignwide" style="background-image:url(http://localhost:8766/wp-content/uploads/2008/06/dsc03149.jpg)"><p class="wp-block-cover-text">Our last cover image block has a wide width.</p></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov","align":"wide","id":1800,"backgroundType":"video"} -->
<div class="wp-block-cover has-background-dim alignwide"><video class="wp-block-cover__video-background" autoplay muted loop src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video><p class="wp-block-cover-text">This is a wide cover block with a video background.</p></div>
<!-- /wp:cover -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov","align":"center","id":1800,"backgroundType":"video"} -->
<div class="wp-block-cover has-background-dim aligncenter"><video class="wp-block-cover__video-background" autoplay muted loop src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video><p class="wp-block-cover-text">Compare the video and image blocks.<br>This block is centered.</p></div>
<!-- /wp:cover -->

<!-- wp:paragraph -->
<p>The block below has no alignment, and the text is a link. Overlay colors must also work with video backgrounds.</p>
<!-- /wp:paragraph -->

<!-- wp:cover {"url":"http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov","id":1800,"dimRatio":60,"customOverlayColor":"#f4399d","backgroundType":"video"} -->
<div class="wp-block-cover has-background-dim-60 has-background-dim" style="background-color:#f4399d"><video class="wp-block-cover__video-background" autoplay muted loop src="http://localhost:8766/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video><p class="wp-block-cover-text"><a href="https://wordpress.org/gutenberg/">This page needed more pink</a>. </p></div>
<!-- /wp:cover -->
````

### 1785 — post, publish — Block: Button

````html
<!-- wp:paragraph -->
<p>Button blocks are not semantically <em>buttons</em>, but links inside a styled div.&nbsp;</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"left"} -->
<p style="text-align:left">If you do not add a link, a link tag without an anchor will be used.</p>
<!-- /wp:paragraph -->

<!-- wp:button {"align":"left"} -->
<div class="wp-block-button alignleft"><a class="wp-block-button__link">Left aligned<br></a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>Check to make sure that the text wraps correctly when the button has more than one line of text, and when it is extra long.</p>
<!-- /wp:paragraph -->

<!-- wp:button {"align":"center"} -->
<div class="wp-block-button aligncenter"><a class="wp-block-button__link">A centered button with <br>more than <br>one line of text</a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>Buttons have three styles:&nbsp;</p>
<!-- /wp:paragraph -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link">Rounded</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link">Outline<br></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-squared"} -->
<div class="wp-block-button is-style-squared"><a class="wp-block-button__link">Square<br></a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>If the theme has a custom color palette, test that background color and text color settings work correctly.&nbsp;</p>
<!-- /wp:paragraph -->

<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link" href="https://wordpress.org/gutenberg/handbook/extensibility/theme-support/#block-color-palette">Read more about the color palettes in the handbook.</a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>Now lets test how buttons display together with large texts.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. </p>
<!-- /wp:paragraph -->

<!-- wp:button {"align":"right"} -->
<div class="wp-block-button alignright"><a class="wp-block-button__link">Right aligned<br></a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna. Praesent sit amet ligula id orci venenatis auctor. Phasellus porttitor, metus non tincidunt dapibus, orci pede pretium neque, sit amet adipiscing ipsum lectus et libero. Aenean bibendum. Curabitur mattis quam id urna. </p>
<!-- /wp:paragraph -->

<!-- wp:button {"align":"left"} -->
<div class="wp-block-button alignleft"><a class="wp-block-button__link">Left aligned<br></a></div>
<!-- /wp:button -->

<!-- wp:paragraph -->
<p>Vivamus dui. Donec nonummy lacinia lorem. Cras risus arcu, sodales ac, ultrices ac, mollis quis, justo. Sed a libero. Quisque risus erat, posuere at, tristique non, lacinia quis, eros.</p>
<!-- /wp:paragraph -->
````

### 1786 — post, publish — Block: Quote

````html
<!-- wp:paragraph -->
<p>The quote block has two styles,&nbsp;regular:</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>Gutenberg is more than an editor.</p><cite>The Gutenberg Team<br></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p>and large:</p>
<!-- /wp:paragraph -->

<!-- wp:quote {"className":"is-style-large"} -->
<blockquote class="wp-block-quote is-style-large"><p>Yes, it is a press, certainly, but a press from which shall flow in inexhaustible streams, the most abundant and most marvelous liquor that has ever flowed to relieve the thirst of men! </p><cite><br><em>Johannes Gutenberg</em><br></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p>The quote blocks themselves have no alignments but the text can be aligned, bold, italic, and linked:</p>
<!-- /wp:paragraph -->

<!-- wp:quote {"align":"right","className":"extraclass"} -->
<blockquote class="wp-block-quote has-text-align-right extraclass"><p><strong><em><a href="https://developer.wordpress.org/block-editor/developers/themes/theme-support/">Right</a></em></strong></p><cite>Theme Review<br></cite></blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p>In addition to the quote block, we also have the <em>pull quote</em>, with a regular and a solid color style.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>You can change the color of the border and the text with the regular style:</p>
<!-- /wp:paragraph -->

<!-- wp:pullquote {"customMainColor":"#b80000","textColor":"light-gray"} -->
<figure class="wp-block-pullquote" style="border-color:#b80000"><blockquote class="has-text-color has-light-gray-color"><p>In addition to the quote block, we also have the pull quote.</p><cite>Theme Reviewer</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:paragraph -->
<p>Or change the background color and text color with the solid color style:</p>
<!-- /wp:paragraph -->

<!-- wp:pullquote {"mainColor":"cyan-bluish-gray","textColor":"very-dark-gray","className":"has-cyan-bluish-gray-background-color is-style-solid-color"} -->
<figure class="wp-block-pullquote has-background has-cyan-bluish-gray-background-color is-style-solid-color"><blockquote class="has-text-color has-very-dark-gray-color"><p>a solid color style</p><cite>Theme Reviewer</cite></blockquote></figure>
<!-- /wp:pullquote -->
````

### 1787 — post, publish — Block: Gallery

````html
<!-- wp:paragraph -->
<p>Gallery blocks have two settings: the number of columns, and whether or not images should be cropped. The default number of columns is three, and the maximum number of columns is eight.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Below is a three column gallery at full width, with cropped images.</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":[],"linkTo":"attachment","className":"alignfull"} -->
<figure class="wp-block-gallery columns-3 is-cropped alignfull"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/"><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/" class="wp-image-611"/></a><figcaption class="blocks-gallery-item__caption">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, 
et adipiscing orci velit quis magna.</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/cep00032/"><img src="http://localhost:8766/wp-content/uploads/2008/06/cep00032.jpg" alt="Sunburst Over River" data-id="756" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/cep00032/" class="wp-image-756"/></a><figcaption class="blocks-gallery-item__caption">Sunburst over the Clinch River, 
Southwest Virginia.</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" data-id="757" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082/" class="wp-image-757"/></a><figcaption class="blocks-gallery-item__caption">Boardwalk at Westport, WA</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5478/"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5478.jpg" alt="Bell on Wharf" data-id="754" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5478/" class="wp-image-754"/></a><figcaption class="blocks-gallery-item__caption">Bell on wharf in San 
Francisco</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0767/"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg" alt="Huatulco Coastline" data-id="770" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0767/" class="wp-image-770"/></a><figcaption class="blocks-gallery-item__caption">Coastline in Huatulco, Oaxaca, Mexico</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption"><em>(gallery caption)</em> 3 column, full width, cropped, <strong>linked to attachment pages</strong></figcaption></figure>
<!-- /wp:gallery -->

<!-- wp:gallery {"ids":[],"columns":2,"linkTo":"media","className":"alignleft extraclass"} -->
<figure class="wp-block-gallery columns-2 is-cropped alignleft extraclass"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2014/01/spectacles.gif"><img src="http://localhost:8766/wp-content/uploads/2014/01/spectacles.gif" alt="" data-id="1692" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/spectacles-2/" class="wp-image-1692"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg" alt="Unicorn Wallpaper" data-id="1045" data-link="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/" class="wp-image-1045"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg"><img src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" data-id="827" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/olympus-digital-camera/" class="wp-image-827"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg" alt="" data-id="807" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/" class="wp-image-807"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/img_8399.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_8399.jpg" alt="Boat Barco Texture" data-id="771" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_8399/" class="wp-image-771"/></a><figcaption class="blocks-gallery-item__caption">Boat BW PB Barco Texture Beautiful Fishing</figcaption></figure></li></ul></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>Some more text for taking up space.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>A two column gallery, aligned to the left, linked to media file.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>In the editor, the image captions can be edited directly by clicking on the text.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If the number of images cannot be divided into the number of columns you have selected, the default is to have the last image(s) automatically stretch to the width of your gallery.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":70,"className":"clear"} -->
<div style="height:70px" aria-hidden="true" class="wp-block-spacer clear"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph -->
<p>A four column gallery with a wide width:</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":["1687","1691","768","611","617","616"],"columns":4,"className":"alignwide featured"} -->
<figure class="wp-block-gallery columns-4 is-cropped alignwide featured"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg" alt="" data-id="1687" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050604_133440_34211/" class="wp-image-1687"/></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2014/01/dsc20050315_145007_132.jpg" alt="" data-id="1691" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050315_145007_132-2/" class="wp-image-1691"/></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0513-1.jpg" alt="Huatulco Coastline" data-id="768" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/alas-i-have-found-my-shangri-la/" class="wp-image-768"/><figcaption class="blocks-gallery-item__caption">Sunrise over the coast in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/" class="wp-image-611"/><figcaption class="blocks-gallery-item__caption">
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, 
tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna.</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" data-id="617" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050813_115856_52/" class="wp-image-617"/></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050727_091048_222.jpg" alt="dsc20050727_091048_222" data-id="616" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050727_091048_222/" class="wp-image-616"/></figure></li></ul></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>A five column gallery with normal  images:</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":[],"columns":5,"imageCrop":false,"linkTo":"media"} -->
<figure class="wp-block-gallery columns-5"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/featured-image-vertical.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/featured-image-vertical.jpg" alt="Horizontal Featured Image" data-id="1027" data-link="https://wpthemetestdata.wordpress.com/2012/03/15/template-featured-image-vertical/featured-image-vertical-2/" class="wp-image-1027"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" alt="Image Alignment 300x200" data-id="1025" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-300x200/" class="wp-image-1025"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="Image Alignment 150x150" data-id="968" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-150x150/" class="wp-image-968"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" data-id="967" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-580x300/" class="wp-image-967"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/featured-image-horizontal.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/featured-image-horizontal.jpg" alt="Horizontal Featured Image" data-id="1022" data-link="https://wpthemetestdata.wordpress.com/2012/03/15/template-featured-image-horizontal/featured-image-horizontal-2/" class="wp-image-1022"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x4002" data-id="1029" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-1200x4002/" class="wp-image-1029"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg" alt="Unicorn Wallpaper" data-id="1045" data-link="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/" class="wp-image-1045"/></a></figure></li></ul></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>This is the same gallery, but <b>with cropped images</b>.</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":[],"columns":5,"linkTo":"media"} -->
<figure class="wp-block-gallery columns-5 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/featured-image-vertical.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/featured-image-vertical.jpg" alt="Horizontal Featured Image" data-id="1027" data-link="https://wpthemetestdata.wordpress.com/2012/03/15/template-featured-image-vertical/featured-image-vertical-2/" class="wp-image-1027"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" alt="Image Alignment 300x200" data-id="1025" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-300x200/" class="wp-image-1025"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="Image Alignment 150x150" data-id="968" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-150x150/" class="wp-image-968"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" data-id="967" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-580x300/" class="wp-image-967"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/featured-image-horizontal.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/featured-image-horizontal.jpg" alt="Horizontal Featured Image" data-id="1022" data-link="https://wpthemetestdata.wordpress.com/2012/03/15/template-featured-image-horizontal/featured-image-horizontal-2/" class="wp-image-1022"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x4002" data-id="1029" data-link="https://wpthemetestdata.wordpress.com/2013/01/10/markup-image-alignment/image-alignment-1200x4002/" class="wp-image-1029"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg" alt="Unicorn Wallpaper" data-id="1045" data-link="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/" class="wp-image-1045"/></a></figure></li></ul></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>Six columns: does it work at all window sizes?</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":["757","755","760","754","764","758","762","827","759","761","611","767","769","768"],"columns":6,"className":"extraclass"} -->
<figure class="wp-block-gallery columns-6 is-cropped extraclass"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" data-id="757" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082/" class="wp-image-757"/><figcaption class="blocks-gallery-item__caption">Boardwalk at Westport, WA</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" data-id="755" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5540/" class="wp-image-755"/><figcaption class="blocks-gallery-item__caption">Golden Gate Bridge</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc09114.jpg" alt="Sydney Harbor Bridge" data-id="760" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc09114/" class="wp-image-760"/><figcaption class="blocks-gallery-item__caption">Sydney Harbor Bridge</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5478.jpg" alt="Bell on Wharf" data-id="754" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5478/" class="wp-image-754"/><figcaption class="blocks-gallery-item__caption">Bell on wharf in San Francisco</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_173257_119.jpg" alt="Rusty Rail" data-id="764" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_173257_119/" class="wp-image-764"/><figcaption class="blocks-gallery-item__caption">
Rusty rails with fishplate, Kojonup</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc03149.jpg" alt="Yachtsody in Blue" data-id="758" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc03149/" class="wp-image-758"/><figcaption class="blocks-gallery-item__caption">Boats and reflections, Royal Perth Yacht Club</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_160808_102.jpg" alt="Antique Farm Machinery" data-id="762" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_160808_102/" class="wp-image-762"/><figcaption class="blocks-gallery-item__caption">Antique farm machinery, Mount Barker Museum, Western Australia</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" data-id="827" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/olympus-digital-camera/" class="wp-image-827"/></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" alt="Rain Ripples" data-id="759" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc04563/" class="wp-image-759"/><figcaption class="blocks-gallery-item__caption">
Raindrop ripples on a pond</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" alt="Wind Farm" data-id="761" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050102_192118_51/" class="wp-image-761"/><figcaption class="blocks-gallery-item__caption">Albany wind-farm against the sunset, Western Australia</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/" class="wp-image-611"/><figcaption class="blocks-gallery-item__caption">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna.</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/windmill.jpg" alt="Windmill" data-id="767" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery//dcf-1-0/" class="wp-image-767"/><figcaption class="blocks-gallery-item__caption">Windmill shrouded in fog at a farm outside of Walker, Iowa</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0747.jpg" alt="Brazil Beach" data-id="769" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0747/" class="wp-image-769"/><figcaption class="blocks-gallery-item__caption">Jericoacoara Ceara Brasil</figcaption></figure></li><li class="blocks-gallery-item"><figure><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0513-1.jpg" alt="Huatulco Coastline" data-id="768" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/alas-i-have-found-my-shangri-la/" class="wp-image-768"/><figcaption class="blocks-gallery-item__caption">Sunrise over the coast in Huatulco, Oaxaca, Mexico</figcaption></figure></li></ul></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>Seven columns: how does this look on a narrow window?</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":[],"columns":7,"linkTo":"media"} -->
<figure class="wp-block-gallery columns-7 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2014/01/spectacles.gif"><img src="http://localhost:8766/wp-content/uploads/2014/01/spectacles.gif" alt="" data-id="1692" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/spectacles-2/" class="wp-image-1692"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2014/01/dsc20050315_145007_132.jpg"><img src="http://localhost:8766/wp-content/uploads/2014/01/dsc20050315_145007_132.jpg" alt="" data-id="1691" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050315_145007_132-2/" class="wp-image-1691"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg" alt="" data-id="1687" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050604_133440_34211/" class="wp-image-1687"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg" alt="" data-id="1686" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/" class="wp-image-1686"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2010/08/triforce-wallpaper.jpg"><img src="http://localhost:8766/wp-content/uploads/2010/08/triforce-wallpaper.jpg" alt="" data-id="1628" data-link="https://wpthemetestdata.wordpress.com/2010/08/07/post-format-image-caption/triforce-wallpaper/" class="wp-image-1628"/></a><figcaption class="blocks-gallery-item__caption">It’s dangerous to go alone! Take this.</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg" alt="Unicorn Wallpaper" data-id="1045" data-link="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/" class="wp-image-1045"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg"><img src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" data-id="827" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/olympus-digital-camera/" class="wp-image-827"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg"><img src="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg" alt="" data-id="1687" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050604_133440_34211/" class="wp-image-1687"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg"><img src="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg" alt="" data-id="807" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/" class="wp-image-807"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/img_8399.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_8399.jpg" alt="Boat Barco Texture" data-id="771" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_8399/" class="wp-image-771"/></a><figcaption class="blocks-gallery-item__caption">Boat BW PB Barco Texture Beautiful Fishing</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg" alt="Huatulco Coastline" data-id="770" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery//img_0767/" class="wp-image-770"/></a><figcaption class="blocks-gallery-item__caption">Coastline in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/img_0747.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0747.jpg" alt="Brazil Beach" data-id="769" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0747/" class="wp-image-769"/></a><figcaption class="blocks-gallery-item__caption">Jericoacoara Ceara Brasil</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/img_0513-1.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0513-1.jpg" alt="Huatulco Coastline" data-id="768" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/alas-i-have-found-my-shangri-la/" class="wp-image-768"/></a><figcaption class="blocks-gallery-item__caption">Sunrise over the coast in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/michelle_049.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/michelle_049.jpg" alt="Big Sur" data-id="766" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/michelle_049/" class="wp-image-766"/></a><figcaption class="blocks-gallery-item__caption">Beach at Big Sur, CA</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/windmill.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/windmill.jpg" alt="Windmill" data-id="767" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery//dcf-1-0/" class="wp-image-767"/></a><figcaption class="blocks-gallery-item__caption">Windmill shrouded in fog at a farm outside of Walker, Iowa</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/dscn3316.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/dscn3316.jpg" alt="Sea and Rocks" data-id="765" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dscn3316/" class="wp-image-765"/></a><figcaption class="blocks-gallery-item__caption">Sea and rocks, Plimmerton, New Zealand</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_173257_119.jpg"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_173257_119.jpg" alt="Rusty Rail" data-id="764" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_173257_119/" class="wp-image-764"/></a><figcaption class="blocks-gallery-item__caption">Rusty rails with fishplate, Kojonup</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption">images linked to media file - do captions obscure links?</figcaption></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p>Eight columns:</p>
<!-- /wp:paragraph -->

<!-- wp:gallery {"ids":["611","757","755","762","763","761","754","760","617","759","827","1045","771","807","758","764","765","770","1687","1691"],"columns":8,"linkTo":"attachment"} -->
<figure class="wp-block-gallery columns-8 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/"><img src="http://localhost:8766/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/" class="wp-image-611"/></a><figcaption class="blocks-gallery-item__caption">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec mollis. Quisque convallis libero in sapien pharetra tincidunt. Aliquam elit ante, malesuada id, tempor eu, gravida id, odio. Maecenas suscipit, risus et eleifend imperdiet, nisi orci ullamcorper massa, et adipiscing orci velit quis magna.</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082"><img src="http://localhost:8766/wp-content/uploads/2008/06/dcp_2082.jpg" alt="Boardwalk" data-id="757" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dcp_2082" class="wp-image-757"/></a><figcaption class="blocks-gallery-item__caption">Boardwalk at Westport, WA</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5540/"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" data-id="755" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5540/" class="wp-image-755"/></a><figcaption class="blocks-gallery-item__caption">Golden Gate Bridge</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_160808_102/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_160808_102.jpg" alt="Antique Farm Machinery" data-id="762" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_160808_102/" class="wp-image-762"/></a><figcaption class="blocks-gallery-item__caption">Antique farm machinery, Mount Barker Museum, Western Australia</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050102_192118_51/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" alt="Wind Farm" data-id="761" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050102_192118_51/" class="wp-image-761"/></a><figcaption class="blocks-gallery-item__caption">Albany wind-farm against the sunset, Western Australia</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5478/"><img src="http://localhost:8766/wp-content/uploads/2008/06/100_5478.jpg" alt="Bell on Wharf" data-id="754" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/100_5478/" class="wp-image-754"/></a><figcaption class="blocks-gallery-item__caption">Bell on wharf in San Francisco</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc09114/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc09114.jpg" alt="Sydney Harbor Bridge" data-id="760" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc09114/" class="wp-image-760"/></a><figcaption class="blocks-gallery-item__caption">Sydney Harbor Bridge</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050813_115856_52/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" data-id="617" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050813_115856_52/" class="wp-image-617"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc04563/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc04563.jpg" alt="Rain Ripples" data-id="759" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc04563/" class="wp-image-759"/></a><figcaption class="blocks-gallery-item__caption">Raindrop ripples on a pond</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/about/clearing-floats/olympus-digital-camera/"><img src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" data-id="827" data-link="https://wpthemetestdata.wordpress.com/about/clearing-floats/olympus-digital-camera/" class="wp-image-827"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/"><img src="http://localhost:8766/wp-content/uploads/2012/12/unicorn-wallpaper.jpg" alt="Unicorn Wallpaper" data-id="1045" data-link="https://wpthemetestdata.wordpress.com/2010/08/08/post-format-image/unicorn-wallpaper/" class="wp-image-1045"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_8399/"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_8399.jpg" alt="Boat Barco Texture" data-id="771" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_8399/" class="wp-image-771"/></a><figcaption class="blocks-gallery-item__caption">Boat BW PB Barco Texture Beautiful Fishing</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/"><img src="http://localhost:8766/wp-content/uploads/2012/06/dsc20040724_152504_532.jpg" alt="" data-id="807" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20040724_152504_532-2/" class="wp-image-807"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc03149/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc03149.jpg" alt="Yachtsody in Blue" data-id="758" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc03149/" class="wp-image-758"/></a><figcaption class="blocks-gallery-item__caption">Boats and reflections, Royal Perth Yacht Club</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_173257_119/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dsc20051220_173257_119.jpg" alt="Rusty Rail" data-id="764" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20051220_173257_119/" class="wp-image-764"/></a><figcaption class="blocks-gallery-item__caption">Rusty rails with fishplate, Kojonup</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dscn3316/"><img src="http://localhost:8766/wp-content/uploads/2008/06/dscn3316.jpg" alt="Sea and Rocks" data-id="765" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dscn3316/" class="wp-image-765"/></a><figcaption class="blocks-gallery-item__caption">Sea and rocks, Plimmerton, New Zealand</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0767/"><img src="http://localhost:8766/wp-content/uploads/2008/06/img_0767.jpg" alt="Huatulco Coastline" data-id="770" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/img_0767/" class="wp-image-770"/></a><figcaption class="blocks-gallery-item__caption">Coastline in Huatulco, Oaxaca, Mexico</figcaption></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050604_133440_34211/"><img src="http://localhost:8766/wp-content/uploads/2013/09/dsc20050604_133440_34211.jpg" alt="" data-id="1687" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050604_133440_34211/" class="wp-image-1687"/></a></figure></li><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050315_145007_132-2/"><img src="http://localhost:8766/wp-content/uploads/2014/01/dsc20050315_145007_132.jpg" alt="" data-id="1691" data-link="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/dsc20050315_145007_132-2/" class="wp-image-1691"/></a></figure></li></ul><figcaption class="blocks-gallery-caption">images are linked, do the links work?</figcaption></figure>
<!-- /wp:gallery -->
````

### 1788 — post, publish — Block: Image

````html
<!-- wp:paragraph -->
<p>Welcome to image alignment! If you recognize this post, it is because these are blocks that have been converted from the classic <em>Markup: Image Alignment</em> post. The best way to demonstrate the ebb and flow of the various image positioning options is to nestle them snuggly among an ocean of words. Grab a paddle and let's get started. Be sure to try it in RTL mode. Left should stay left and right should stay right for both reading directions.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>On the topic of alignment, it should be noted that users can choose from the options of <em>None</em>, <em>Left</em>, <em>Right, </em>and <em>Center</em>. If the theme has added support for <em>align wide</em>,&nbsp;images can also be <em>wide</em> and <em>full width</em>. Be sure to test this page in RTL mode.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>In addition, they also get the options of the image dimensions 25%, 50%, 75%, 100% or a set width and height.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":906,"align":"center"} -->
<div class="wp-block-image"><figure class="aligncenter"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" class="wp-image-906"/></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The image above happens to be <em><strong>centered</strong></em>.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":904,"align":"left"} -->
<div class="wp-block-image"><figure class="alignleft"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="Image Alignment 150x150" class="wp-image-904"/></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>As you can see the should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":907} -->
<figure class="wp-block-image"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x400" class="wp-image-907"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":905,"align":"right"} -->
<div class="wp-block-image"><figure class="alignright"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" alt="Image Alignment 300x200" class="wp-image-905"/></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there… Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah… Just like that. It never felt so good to be right.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>And just when you thought we were done, we're going to do them all over again with captions!</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":906,"align":"center","className":"size-full wp-image-906"} -->
<div class="wp-block-image size-full wp-image-906"><figure class="aligncenter"><a href="https://en.support.wordpress.com/images/image-settings/"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" class="wp-image-906"/></a><figcaption>Look at 580x300 getting some <a title="Image Settings" href="https://en.support.wordpress.com/images/image-settings/">caption</a> love.</figcaption></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The image above happens to be <em><strong>centered</strong></em>. The caption also has a link in it, just to see if it does anything funky.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":904,"align":"left","className":"size-full wp-image-904"} -->
<div class="wp-block-image size-full wp-image-904"><figure class="alignleft"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="Image Alignment 150x150" class="wp-image-904"/><figcaption>Itty-bitty caption.</figcaption></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The rest of this paragraph is filler for the sake of seeing the text wrap around the 150x150 image, which is <em><strong>left aligned</strong></em>. </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>As you can see the should be some space above, below, and to the right of the image. The text should not be creeping on the image. Creeping is just not right. Images need breathing room too. Let them speak like you words. Let them do their jobs without any hassle from the text. In about one more sentence here, we'll see that the text moves from the right of the image down below the image in seamless transition. Again, letting the do it's thang. Mission accomplished!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>And now for a <em><strong>massively large image</strong></em>. It also has <em><strong>no alignment</strong></em>.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":907,"align":"none","className":"wp-image-907"} -->
<figure class="wp-block-image alignnone wp-image-907"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x400" class="wp-image-907"/><figcaption>Massive image comment for your eyeballs.</figcaption></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>The image above, though 1200px wide, should not overflow the content area. It should remain contained with no visible disruption to the flow of content.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":905,"align":"right","className":"size-full wp-image-905"} -->
<div class="wp-block-image size-full wp-image-905"><figure class="alignright"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-300x200-1.jpg" alt="Image Alignment 300x200" class="wp-image-905"/><figcaption>Feels good to be right all the time.</figcaption></figure></div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>And now we're going to shift things to the <em><strong>right align</strong></em>. Again, there should be plenty of room above, below, and to the left of the image. Just look at him there… Hey guy! Way to rock that right side. I don't care what the left aligned image says, you look great. Don't let anyone else tell you differently.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>In just a bit here, you should see the text start to wrap below the right aligned image and settle in nicely. There should still be plenty of room and everything should be sitting pretty. Yeah… Just like that. It never felt so good to be right.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Imagine that we would find a use for the extra wide image! This image has the <em>wide width</em> alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":1029,"align":"wide"} -->
<figure class="wp-block-image alignwide"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x4002" class="wp-image-1029"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><strong>Can we go bigger?</strong> This image has the <em>full width</em> alignment:</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":1029,"align":"full"} -->
<figure class="wp-block-image alignfull"><img src="http://localhost:8766/wp-content/uploads/2013/03/image-alignment-1200x4002-1.jpg" alt="Image Alignment 1200x4002" class="wp-image-1029"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>And that's a wrap, yo! You survived the tumultuous waters of alignment. Image alignment achievement unlocked! One last thing: The last item in this post's content is a thumbnail floated right. Make sure any elements after the content are clearing properly.</p>
<!-- /wp:paragraph -->

<!-- wp:image {"id":827,"align":"right","width":160,"height":120} -->
<div class="wp-block-image"><figure class="alignright is-resized"><img src="http://localhost:8766/wp-content/uploads/2010/08/manhattansummer.jpg" alt="" class="wp-image-827" width="160" height="120"/></figure></div>
<!-- /wp:image -->
````

### 1809 — page, publish — Ελληνικά-Greek

````html
Typography tests for Greek Ελληνική σελίδα 1ου επιπέδου και δείγμα τυπογραφίας.

<strong>Headings Επικεφαλίδες</strong>
<h1>Επικεφαλίδα 1 Header one</h1>
<h2>Επικεφαλίδα 2 Header two</h2>
<h3>Επικεφαλίδα 3 Header three</h3>
<h4>Επικεφαλίδα 2 Header four</h4>
<h5>Επικεφαλίδα 5 Header five</h5>
<h6>Επικεφαλίδα 6Header six</h6>
<h2>Παράθεση άλλου Blockquotes</h2>
Single line blockquote: Μια γραμμή
<blockquote>Πάντα να είναι περίεργος.</blockquote>
Πολλές γραμμέ με αναφορά Multi line blockquote with a cite reference:
<blockquote>Το <strong>HTML <code>&lt;blockquote&gt;</code> Element</strong> (ή <em>HTML Block Quotation Element</em>) καταδεικνύει ότι το κείμενο έχει μια παράθεση. Συνήθως οπτικοποιείται με εσοχή (δείτε <a href="https://developer.mozilla.org/en-US/docs/HTML/Element/blockquote#Notes">Σημειώσεις</a> για το πως να το αλλάξετε. Ίσως να δίνεται και URL πηγής με την χρήση του <strong>cite</strong> attribute, μπλα, μπλα <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite"><code>&lt;cite&gt;</code></a> .</blockquote>
<cite>multiple contributors - MDN HTML element reference - blockquote</cite>
<h2>Πίνακες Tables</h2>
<table>
<thead>
<tr>
<th>Υπάλληλος Employee</th>
<th>Μισθός Salary</th>
<th></th>
</tr>
</thead>
<tbody>
<tr>
<th><a href="http://example.org/">Τάδε κάποιος</a></th>
<td>$1</td>
<td>Γιατί τόσα χρειάζεται για να ζήσει</td>
</tr>
<tr>
<th><a href="http://example.org/">Jane Doe</a></th>
<td>$100K</td>
<td>For all the blogging she does.</td>
</tr>
<tr>
<th><a href="http://example.org/">Fred Bloggs</a></th>
<td>$100M</td>
<td>Pictures are worth a thousand words, right? So Jane x 1,000.</td>
</tr>
<tr>
<th><a href="http://example.org/">Jane Bloggs</a></th>
<td>$100B</td>
<td>With hair like that?! Enough said...</td>
</tr>
</tbody>
</table>
<h2>Λίστες Definition Lists</h2>
<dl>
 	<dt>Τίτλος λίστας Definition List Title</dt>
 	<dd>Υποδιαίρεση λίστας Definition list division.</dd>
</dl>
<h2>Λίστα με κουκίδες Unordered Lists (Nested)</h2>
<ul>
 	<li>Πρώτο στοιχείο List item one
<ul>
 	<li>Στοιχείο πρώτο List item one
<ul>
 	<li>Στοιχείο λίστα ένα List item one</li>
 	<li>Στοιχείο λίστας δύο List item two</li>
</ul>
</li>
 	<li>Στοιχείο δεύτερο -item two</li>
</ul>
</li>
 	<li>Στοιχειο δύο List item two</li>
</ul>
<h2>Αριθμημένη λίστα(Nested)</h2>
<ol start="8">
 	<li>Στοιχειο ξεκινά με 8-start at 8
<ol>
 	<li>Στοιχείο λίστας ενα List item one
<ol>
 	<li>Στοιχείο λίστας ενα  -reversed attribute</li>
 	<li>Στοιχείο λίστας δύο</li>
</ol>
</li>
 	<li>Δεύτερο στοιχείο</li>
</ol>
</li>
 	<li>Στοιχείο δύο</li>
</ol>
<h2>Ετικέττες HTML Tags</h2>
<strong>Διεύθυνση Address Tag</strong>

<address>1 Απέραντη διαδρομή Infinite Loop
Απλωπολή , ΤΚ 95014
Ελλάδα</address><strong>Αγκυρωση Anchor Tag (aka. Link)</strong>

Πάραδειγμα <a title="WordPress Foundation" href="https://wordpressfoundation.org/">συνδέσμου</a>.

<strong>Συντομογραφία Abbreviation Tag</strong>

Η συντομογραφία <abbr title="Και τα λοιπά">κτλ</abbr> σημαίνει "Και τα λοιπά".

<strong>Ακρωνύμιο Acronym Tag</strong>

Το ακρωνύμιο <acronym title="Κύριος">κυρ</acronym> σημαίνει "Κύριος".

<strong>Big Tag</strong>

Αυτό είναι <big>μεγάλο</big> θέμα

<strong>Cite Tag</strong>

"Φάε το φαϊ σου" --<cite>Όλες οι μαμάδες</cite>

<strong>Code Tag</strong>

This tag styles blocks of code.
<code>.post-title {
margin: 0 0 5px;
font-weight: bold;
font-size: 38px;
line-height: 1.2;
και μία γραμμή με πολύ πάρα πολύ υπερβολικά πάρα πολύ μεγάλο κείμενο που πρέπει να δούμε πως το χειρίζεται η γραμματοσειρά και αν ξεχειλίζει από τις γραμμές και δημιουργεί πρόβλημα;
}</code>

<strong>Διαγραφή Delete Tag</strong>

Μπορείτε να <del>διαγράφεται κείμενο</del>, αλλά δεν <em>συνιστάται</em>.

<strong>Έμφαση Emphasize Tag</strong>

Θα πρέπει να κάνει <em>ιταλικ italicize το</em> <i>κείμενο</i>.

<strong>Εισαγωγή Insert Tag</strong>

Αυτό το tag υποδηλώνει <ins cite="Εισαγωγή inserted it">εισηγμένο inserted </ins> κείμενο.

<strong>Keyboard Tag</strong>

Αυτό το ελάχιστο γνωστό <kbd>κείμενο πληκτρολογίου keyboard Tag</kbd>, συνήθως μορφοποιείται όμοια με το <code>&lt;κώδικα code&gt;</code> tag.

<strong>Προδιαμορφωμένο Preformatted Tag</strong>
<h2>Ο Δρόμος που δεν διάλεξα - The Road Not Taken</h2>
<pre><cite>Robert Frost</cite>
	 Δυο δρόμοι διασταυρώθηκαν σ' ένα χρυσαφένιο δάσος ,
	 Και προς λύπη μου και τους δυο τα πόδια μου να ταξιδέψουν δεν μπορούσαν
	 Κι επί μακρόν εστάθηκα , καθώς ένας ήμουν ταξιδευτής μονάχος ,
	 κι έστρεψα το βλέμμα μου στον πρώτο όσο να χαθεί στο βάθος
	 μέχρι εκεί που χάνονταν στα άγρια χόρτα που βλαστούσαν.

	Two roads diverged in a yellow wood,
	And sorry I could not travel both          (\_/)
	And be one traveler, long I stood         (='.'=)
	And looked down one as far as I could     (")_(")
	To where it bent in the undergrowth;

	Then took the other, as just as fair,
	And having perhaps the better claim,          |\_/|
	Because it was grassy and wanted wear;       / @ @ \
	Though as for that the passing there        ( &gt; º &lt; )
	Had worn them really about the same,         `&gt;&gt;x&lt;&lt;´
	 /  O  \
	And both that morning equally lay
	In leaves no step had trodden black.
	Oh, I kept the first for another day!
	Yet knowing how way leads on to way,
	I doubted if I should ever come back.

	I shall be telling this with a sigh
	Somewhere ages and ages hence:
	Two roads diverged in a wood, and I—
	I took the one less traveled by,
	And that has made all the difference.


	και μία μακριά, πάρα πολύ μακριά, υπερβολικά μακροσκελής δίχως νόημα πρόταση για να δούμε πως το χειρίζεται το θέμα εμφάνισης και αν αναδιπλώνεται, κρύβεται ή ξεχειλίζει;
</pre>
<strong>Quote Tag</strong> for short, inline quotes

<q>Προγραμματιστές, προγραμματιστές, developers...</q> --Steve Ballmer

<strong>Strike Tag</strong> (<em>deprecated in HTML5</em>) and <strong>S Tag</strong>

Αυτή η ετικέτα είναι <span style="text-decoration: line-through;">με διαγράμμιση strike-through</span> <s>κείμενο text</s>.

<strong>Μικρά Small Tag</strong>

Αυτή η ετικέτα είναι <small>μικρότερο smaller<small> κείμενο text.</small></small>

<strong>Strong Tag</strong>

Αυτή η ετικέτα δείχνει <strong> έντονο bold<strong> κείμενο text.</strong></strong>

<strong>Subscript Tag</strong>

Getting our science styling on with H<sub>2 δύο</sub>O, which should push the "2" down.

<strong>Superscript Tag</strong>

Still sticking with science and Albert Einstein's E = MC<sup>2 δύο</sup>, which should lift the 2 up.

<strong>Teletype Tag </strong>(<em>obsolete in HTML5</em>)

Αυτή η ετικέτα δείχνει <tt>τυλετυπος teletype κείμενο</tt>, which is usually styled like the <code>&lt;κώδικα code&gt;</code> tag.

<strong>Underline Tag</strong> <em>deprecated in HTML 4, re-introduced in HTML5 with other semantics</em>

Αυτή η ετικέτα δείχνει <u>υπογράμμιση underlined text</u>.

<strong>Variable Tag</strong>

Αυτή η ετικέτα δείχνει <var>παράμετροι variables</var>.
````

### 1811 — page, publish — Επίπεδο 2 -Second Greek level

````html
Σελίδα 2ου επιπέδου - Second level page

````

### 1832 — wp_navigation, publish — Social menu

````html
<!-- wp:navigation-link {"className":" menu-item menu-item-type-custom menu-item-object-custom","description":"","id":null,"kind":"custom","label":"twitter.com","opensInNewTab":false,"rel":null,"title":"","type":"custom","url":"https://twitter.com/wordpress"} /--><!-- wp:navigation-link {"className":" menu-item menu-item-type-custom menu-item-object-custom","description":"","id":null,"kind":"custom","label":"facebook.com","opensInNewTab":false,"rel":null,"title":"","type":"custom","url":"https://www.facebook.com/WordPress/"} /--><!-- wp:navigation-link {"className":" menu-item menu-item-type-custom menu-item-object-custom","description":"","id":null,"kind":"custom","label":"github.com","opensInNewTab":false,"rel":null,"title":"","type":"custom","url":"https://github.com/WordPress/"} /--><!-- wp:navigation-link {"className":" menu-item menu-item-type-custom menu-item-object-custom","description":"","id":null,"kind":"custom","label":"linkedin.com","opensInNewTab":false,"rel":null,"title":"","type":"custom","url":"https://www.linkedin.com/company/wordpress/"} /--><!-- wp:navigation-link {"className":" menu-item menu-item-type-custom menu-item-object-custom","description":"","id":null,"kind":"custom","label":"instagram.com","opensInNewTab":false,"rel":null,"title":"","type":"custom","url":"https://www.instagram.com/photomatt/"} /-->
````
