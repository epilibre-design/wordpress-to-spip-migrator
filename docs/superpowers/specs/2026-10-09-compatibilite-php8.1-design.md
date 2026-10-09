# wp2spip — compatibilité PHP 8.1

Date : 2026-10-09

Statut : spécification à relire avant réalisation.

Complète la [spec du convertisseur HTML → SPIP](2026-10-09-wp2spip-convertisseur-html-design.md) ; elle ne modifie pas ses règles de conversion.

## 1. Objectif et périmètre

Abaisser le minimum réel de wp2spip et de ses extensions à PHP 8.1 si l'audit complet confirme cette compatibilité, pour la production **et** leur chaîne de tests. Vérifier PHP 8.2 et 8.3 et préserver le fonctionnement sous PHP 8.4. Si une dépendance ou un plugin requis bloque réellement PHP 8.1, documenter le blocage et retenir PHP 8.2 comme minimum validé. Le remplacement du parseur HTML5 est une partie de ce travail, pas son seul critère de réussite.

PHP 8.1 n'a plus de correctifs de sécurité depuis le 31 décembre 2025, mais des hébergeurs le proposent encore : la cible basse reste 8.1 tant qu'elle est techniquement possible. Ce choix implique PHPUnit 10.5, seule série compatible avec 8.1, qui ne reçoit plus de correctifs ; il est accepté pour la chaîne de tests.

Sous PHP 8.1 à 8.3, [Masterminds HTML5-PHP](https://github.com/Masterminds/html5-php) fournit l'arbre HTML5 utilisé par `wp2spip_html_spip()`. Sous PHP 8.4 et au-delà, conserver `Dom\HTMLDocument`. Le choix du parseur se fait automatiquement, sans option utilisateur.

Le contrat public de `wp2spip_html_spip($html, $options)` reste inchangé : même entrée, mêmes règles de conversion et même forme de sortie pour les étapes suivantes (images, légendes, lecteurs, liens internes et extensions). La prise en charge de PHP 7.4 et 8.0 ne fait pas partie de ce changement.

## 2. Constat dans le dépôt

- `inc/wp2spip_html.php` crée directement un `Dom\HTMLDocument`, part de sa propriété `body`, teste `Dom\Text` et `Dom\Element` (sept `instanceof`), sérialise des médias avec `saveHtml($noeud)` et utilise une fois `querySelector()` pour reconnaître les tableaux complexes. Ces API n'existent pas sous PHP 8.1–8.3 : un `DOMDocument` n'a notamment pas de propriété `body`.
- `wp2spip_html_ouvrante()` recopie les attributs par `$attribut->name`. Pour un attribut à préfixe (`xlink:href` en SVG), `Dom\Attr` et `DOMAttr` peuvent ne pas renvoyer le même nom.
- Le code de production appelle aussi `str_contains()`, `str_starts_with()` et `str_ends_with()`. Ces fonctions sont disponibles dès PHP 8.0 : elles n'empêchent pas la cible 8.1–8.3.
- `composer.json` et `paquet.xml` demandent PHP ≥ 8.4. Les deux extensions `wp2spip_acf` et `wp2spip_yoast` portent le même minimum. Les tests emploient PHPUnit 13, dont la version verrouillée requiert PHP ≥ 8.4.1.
- Le `vendor/` de développement est ignoré par Git et exclu de la copie de `outils/preparer_spip.sh`. Une simple dépendance Composer dans `require` ne rendrait donc pas Masterminds disponible dans un plugin SPIP installé depuis ses sources ou copié par cet outil.
- Dans les verrous actuels de wp2spip et wp2spip_acf, `spip/tests` demande PHP ≥ 8.1 et `spip/spip-cli` accepte PHP 8 ; ces contraintes déclarées ne bloquent donc pas la cible basse. `wp2spip_yoast` n'a pas de `composer.lock` dans le dépôt examiné : sa résolution reste à contrôler.
- Les 18 classes de test des trois dépôts utilisent uniquement des assertions, hooks et attributs présents dans la documentation de PHPUnit 10.5. Les 24 emplois de `#[DataProvider]` désignent chacun une méthode `public static`. Les assertions examinées sont `assertArrayNotHasKey`, `assertCount`, `assertEqualsCanonicalizing`, `assertFalse`, `assertMatchesRegularExpression`, `assertNotContains`, `assertNotEmpty`, `assertNotFalse`, `assertNull`, `assertSame`, `assertStringContainsString`, `assertStringNotContainsString` et `assertTrue`. Aucun usage repéré ne demande PHPUnit 11, 12 ou 13.
- Tous les fichiers PHP du code et des tests des trois dépôts passent `php8.1 -l` dans l'environnement examiné. Ce contrôle syntaxique ne prouve ni la résolution Composer ni l'exécution. Après installation de `php8.1-sqlite3`, le PHP 8.1 local charge `pdo_sqlite` et `sqlite3` ; une création et une lecture en base SQLite mémoire ont réussi. L'environnement peut désormais servir aux tests d'intégration, une fois les contraintes Composer et le parseur adaptés.
- Aucun des trois dépôts n'a d'intégration continue. La machine de développement dispose de `php8.1` et `php8.4` seulement.
- `php8.1 composer check-platform-reqs --no-dev` échoue aujourd'hui sur le minimum `>=8.4` du plugin. Avec les dépendances de développement, la vérification échoue aussi sur `phpunit/php-code-coverage` du verrou actuel (`>=8.4`). Les extensions DOM, libxml, mbstring et xmlwriter passent la vérification.

## 3. Audit préalable à la baisse du minimum PHP

| Élément | Constat | Vérification et décision attendues |
| --- | --- | --- |
| Sources de production et CLI des trois plugins | Le seul appel repéré à `Dom\HTMLDocument` est dans `inc/wp2spip_html.php` ; les fonctions `str_*` repérées existent sous PHP 8.1 et la syntaxe de tous les fichiers passe `php8.1 -l`. | Contrôler les API à l'exécution avec PHP 8.1, y compris `spip-cli/`, `outils/` et les deux extensions ; corriger chaque incompatibilité constatée. |
| Manifests SPIP et Composer | `>=8.4` dans les trois `composer.json` et `[8.4.0;]` dans les trois `paquet.xml`. | Aligner les six déclarations sur 8.1 **après** validation ; contrôler que les autres plugins requis et les versions SPIP annoncées fonctionnent sur chaque couple testé. |
| PHPUnit | `phpunit/phpunit:^13.0` dans les trois dépôts ; les deux verrous présents fixent 13.4.1 et ses dépendances PHP ≥ 8.4. | Cibler `^10.5` dans les trois `require-dev` : PHPUnit 10.5 accepte PHP ≥ 8.1 et couvre les API de test recensées. Mettre à jour les deux verrous existants et créer celui de Yoast si sa procédure de développement le nécessite ; résoudre sur PHP 8.1 sans ignorer les contraintes de plateforme. |
| Tests et configuration | `#[DataProvider]`, assertions et hooks courants, `phpunit.xml`, amorces distinctes pour unitaires et intégration ; aucune fonctionnalité postérieure à PHPUnit 10.5 repérée. | Exécuter les suites avec PHPUnit 10.5, vérifier la découverte des 24 fournisseurs de données et la configuration XML ; adapter seulement les usages incompatibles constatés. Conserver la même couverture fonctionnelle. |
| Dépendances de développement | `spip/tests:dev-master`, `spip/spip-cli:dev-master` et leurs dépendances transitives peuvent changer ; le verrou actuel n'est pas une preuve de résolution sur 8.1. | Vérifier les contraintes transitives et les extensions PHP requises par `composer install`, `composer check-platform-reqs` et les scripts SPIP-Cli sur PHP 8.1. Fixer ou borner une révision compatible si une branche mouvante cesse de l'être. |
| Dépendances SPIP de production | Pages uniques, Polyhiérarchie et plugins requis selon le contenu ; extensions ACF et Yoast avec leurs propres plugins. | Tester l'activation et l'import sur les combinaisons PHP/SPIP réellement annoncées ; réduire les plages de compatibilité déclarées si une combinaison ne peut être validée. |
| Distribution et documentation | `vendor/` est exclu de la copie et plusieurs pages annoncent 8.4 obligatoire. | Vérifier le chargement de Masterminds sans Composer sur le site cible, la copie par le préparateur et le paquet publié ; mettre à jour guides, messages d'erreur et prérequis de test. |

Une contrainte Composer compatible en théorie ne valide pas un exécutable : l'audit distingue la résolution des dépendances, le chargement du plugin, l'exécution des tests et l'import réel.

## 4. Choix de conception

| Sujet | Décision |
| --- | --- |
| Parseur | `Dom\HTMLDocument` si PHP ≥ 8.4 et classe présente ; Masterminds HTML5-PHP si PHP 8.1, 8.2 ou 8.3. Absence de l'extension DOM ou de Masterminds : erreur explicite avant conversion. |
| Options de Masterminds | `disable_html_ns` activé : les éléments sont créés hors de l'espace de noms XHTML, comme les noms recherchés par le parcours. |
| Version de Masterminds | Dépendance `masterminds/html5` en version 2.x compatible avec PHP 8.1 à 8.3, verrouillée pour la distribution et les tests. Vérifier sa contrainte PHP et `ext-dom` à la résolution Composer. |
| Frontière interne | De petites fonctions d'accès isolent ce qui diffère entre les deux parseurs : création du document et obtention du `body`, `est_texte()` et `est_element()` à la place des `instanceof Dom\Text` / `Dom\Element`, nom qualifié d'un attribut, sérialisation HTML. Les règles HTML → SPIP manipulent les nœuds uniquement par ces fonctions et par les propriétés communes aux deux familles de classes (`localName`, `childNodes`, `textContent`, `data`, `getAttribute()`, `attributes`). Aucun nom de classe `Dom\*` ou `DOM*` n'apparaît hors des fonctions d'accès. |
| Nœuds Masterminds | Son parseur renvoie les classes DOM historiques (`DOMDocument`, `DOMElement`, `DOMText`). Ne pas passer ce document à `DOMDocument::loadHTML()` : cette méthode utilise les règles HTML4. |
| Sérialisation | Garder `Dom\HTMLDocument::saveHtml()` sur la branche native. Sur la branche 8.1–8.3, ne pas utiliser le sérialiseur de Masterminds : un essai (2026-10-09) a montré qu'il écrit autrement les attributs booléens (`controls` au lieu de `controls=""`) et le SVG (`<use … />` avec un `xmlns:xlink` ajouté). wp2spip sérialise lui-même les sous-arbres conservés selon l'algorithme de sérialisation du standard HTML5, celui que suit `saveHtml()` : même HTML caractère pour caractère, vérifié par des cas de test (balises vides, attributs booléens, entités, texte brut, SVG/MathML). |
| Sélection des tableaux | Remplacer l'unique `querySelector('table, [rowspan]:not([rowspan="1"])')` par un parcours des descendants, **commun aux deux parseurs** ; même condition fonctionnelle : tableau imbriqué ou `rowspan` différent de `1`. Pas de `DOMXPath`, sensible à l'espace de noms des éléments, ni de composant Symfony. |
| Attributs | `wp2spip_html_ouvrante()` écrit le nom qualifié de l'attribut (`nodeName`), identique sur les deux parseurs ; un cas SVG avec `xlink:href` le vérifie. |
| Chargement en production | Placer une copie de la version verrouillée de Masterminds (`src/` et licence MIT) dans `lib/masterminds-html5/`, suivie par Git et incluse dans le paquet SPIP. Un chargeur propre au plugin résout les classes `Masterminds\` depuis cet emplacement sur PHP 8.1–8.3. Ne pas dépendre du `vendor/` de développement ni d'un `composer install` effectué par l'administrateur du site. Si une autre copie de Masterminds est déjà chargée par le site ou un autre plugin, elle est utilisée telle quelle : sa version 2.x offre la même API ; la commande d'import l'indique en mode verbeux. |
| Verrou Composer | `config.platform.php` fixé à `8.1` dans les trois `composer.json` : le verrou est résolu pour la cible basse quelle que soit la version de PHP qui lance `composer update`, et reste installable de 8.1 à 8.4. |

Le script de copie `outils/preparer_spip.sh` doit garder `lib/masterminds-html5/`. La version copiée dans `lib/` et celle du verrou Composer doivent être identiques ; un fichier `lib/masterminds-html5/VERSION` note la version copiée (les sources de Masterminds n’en exposent pas) ; un test PHPUnit la compare à celle du `composer.lock` et échoue en cas de divergence. Le plugin publié embarque ce dossier et sa notice de licence MIT.

## 5. Comportement attendu

1. `wp2spip_html_spip()` normalise les retours à la ligne et retourne `''` pour une entrée vide, comme aujourd'hui.
2. Le HTML est analysé comme document HTML5 enveloppé dans `<!DOCTYPE html><html><body>…</body></html>`. Le parcours commence sur le `body` obtenu ; le contenu WordPress est en UTF-8.
3. Le parcours reconnaît texte, éléments et autres nœuds avec les classes du parseur choisi. Commentaires et instructions restent ignorés. L'option `autop`, les marqueurs `wp2spipbloc<N>`, les entités et les raccourcis SPIP/WordPress gardent leur sens actuel.
4. Le même contenu produit une sortie SPIP équivalente sur 8.1, 8.2, 8.3 et 8.4. Les cas courants gardent la sortie exacte des tests actuels. Une différence due à la construction d'arbre sur du HTML mal formé ou à la sérialisation doit être documentée par une fixture, examinée sur le rendu SPIP et acceptée seulement sans perte de texte ni régression fonctionnelle.
5. Si le parseur attendu est indisponible, l'import échoue avec un message indiquant la dépendance manquante et la version de PHP ; il ne poursuit pas avec un parseur HTML4 et ne produit pas silencieusement un texte vide.

## 6. Distribution et compatibilité

- Abaisser à `>=8.1` les prérequis PHP de `wp2spip` dans `composer.json` et `paquet.xml`, puis aligner `wp2spip_acf` et `wp2spip_yoast` après validation de leurs sources et dépendances. Si PHP 8.1 ne peut pas être validé, retenir `>=8.2` et documenter précisément le blocage. Vérifier aussi les dépendances SPIP et le verrou Composer de chaque dépôt ; la compatibilité du cœur ne suffit pas à garantir celle de toute l'installation.
- Ajouter Masterminds comme dépendance de production dans Composer pour la résolution et les tests, puis fournir sa version verrouillée dans l'artefact SPIP. Vérifier les trois parcours : copie par `outils/preparer_spip.sh`, plugin installé depuis un paquet publié et dépôt cloné pour le développement.
- Adapter les suites à PHPUnit 10.5 et à ses dépendances de développement compatibles avec PHP 8.1. Les attributs `DataProvider`, les signatures des tests et la configuration XML sont vérifiés à l'exécution. La matrice PHP 8.1, 8.2, 8.3 et 8.4 s'exécute sur la machine de développement, où `php8.2` et `php8.3` sont installés avec `ext-dom`, `sqlite3` et les extensions requises par SPIP et PHPUnit, à côté des 8.1 et 8.4 présents. Mettre en place une intégration continue est hors périmètre ; elle reprendrait les mêmes commandes.
- Mettre à jour le README et les pages du site documentaire qui annoncent aujourd'hui PHP 8.4 obligatoire : Prérequis, Installer le migrateur, Développer et tester, Conversion HTML et Dépanner. Les anciennes specs réalisées restent des archives de leur décision initiale ; cette spec documente l'évolution.

## 7. Validation et critères d'acceptation

- Tous les cas de `tests/unit/HtmlTest.php` sont exécutés sur PHP 8.1 à 8.4. Les cas de HTML mal formé, listes non fermées, tableaux imbriqués, médias, entités et UTF-8 sont particulièrement comparés entre les deux parseurs. Masterminds n'applique pas tout l'algorithme de construction d'arbre HTML5 : ajouter des fixtures pour les balises de mise en forme mal imbriquées (`<b><i></b></i>`), le contenu égaré dans un tableau et les fins de balise implicites, et un cas SVG avec attribut à préfixe.
- `tests/integration/HtmlSpipTest.php`, les tests des blocs, des extensions et l'import complet passent sur les quatre versions, dans la mesure où les dépendances de test prennent en charge ces versions. Si une dépendance de test empêche une version, résoudre ce blocage avant de déclarer la compatibilité.
- Un essai d'installation SPIP sans `vendor/` de développement, sous PHP 8.1 à 8.3, confirme que Masterminds se charge et qu'un article contenant HTML5 et médias s'importe. Un essai sous 8.4 confirme l'utilisation du parseur natif.
- Comparer sur un corpus WordPress représentatif les sorties 8.1–8.3 à la référence 8.4, classer chaque différence et vérifier qu'aucun texte ni attribut nécessaire aux traitements suivants n'est perdu.
- Tester explicitement le cas de bibliothèque absente : erreur lisible, arrêt de l'import, aucune conversion partielle considérée comme réussie.
- Sur PHP 8.1, `composer install` et `composer check-platform-reqs` passent dans les trois dépôts, sans `--ignore-platform-reqs`. Les suites unitaires et d'intégration sont lancées avec le PHPUnit verrouillé ; `phpunit --version` confirme la série 10.5. Répéter la résolution et les suites sur PHP 8.2, 8.3 et 8.4.
- Les commandes SPIP-Cli et les scripts de préparation/import sont exécutés avec le PHP de la matrice, et pas seulement avec le PHP par défaut de la machine. Pour chaque combinaison PHP/SPIP annoncée, installation, activation et import se terminent sans erreur de version ou d'API.
- Les trois `composer.json` et `paquet.xml`, les verrous présents et les prérequis documentés donnent le même minimum PHP. Aucune extension ne reste artificiellement bloquée à 8.4 si elle passe les critères précédents.

## 8. Hors périmètre

Modifier les règles de conversion HTML → SPIP, réintroduire Sale, mettre en place une intégration continue, ajouter une option de sélection manuelle du parseur ou promettre une compatibilité PHP 7.4 ou 8.0.

## Références

- [Masterminds HTML5-PHP : API, sérialisation et limites connues](https://github.com/Masterminds/html5-php)
- [Contraintes du paquet Composer](https://packagist.org/packages/masterminds/html5)
- [PHP : `DOMDocument::loadHTML()` et ses règles HTML4](https://www.php.net/manual/en/domdocument.loadhtml.php)
- [Versions PHP prises en charge par PHPUnit](https://phpunit.de/supported-versions.html)
- [PHPUnit 11 : attributs et minimum PHP](https://phpunit.de/announcements/phpunit-11.html)
- [PHPUnit 10.5 : attribut `DataProvider`](https://docs.phpunit.de/en/10.5/attributes.html#data-provider)
- [PHPUnit 10.5 : assertions](https://docs.phpunit.de/en/10.5/assertions.html)
- [PHPUnit 10.5 : configuration XML](https://docs.phpunit.de/en/10.5/configuration.html)
