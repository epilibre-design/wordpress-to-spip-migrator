# wp2spip — compatibilité PHP 8.2

Date : 2026-10-09

Statut : spécification à relire avant réalisation.

Complète la [spec du convertisseur HTML → SPIP](2026-10-09-wp2spip-convertisseur-html-design.md) ; elle ne modifie pas ses règles de conversion.

## 1. Objectif et périmètre

Abaisser à PHP 8.2 le minimum réel de wp2spip et de ses extensions, pour la production **et** leur chaîne de tests. Vérifier PHP 8.3 et préserver le fonctionnement sous PHP 8.4. Le remplacement du parseur HTML5 est une partie de ce travail, pas son seul critère de réussite.

Sous PHP 8.2 et 8.3, [Masterminds HTML5-PHP](https://github.com/Masterminds/html5-php) fournit l'arbre HTML5 utilisé par `wp2spip_html_spip()`. Sous PHP 8.4 et au-delà, conserver `Dom\HTMLDocument`. Le choix du parseur se fait automatiquement, sans option utilisateur.

Le contrat public de `wp2spip_html_spip($html, $options)` reste inchangé : même entrée, mêmes règles de conversion et même forme de sortie pour les étapes suivantes (images, légendes, lecteurs, liens internes et extensions). La prise en charge de PHP 7.4 et 8.0–8.1 ne fait pas partie de ce changement.

## 2. Constat dans le dépôt

- `inc/wp2spip_html.php` crée directement un `Dom\HTMLDocument`, teste `Dom\Text` et `Dom\Element`, sérialise des médias avec `saveHtml($noeud)` et utilise une fois `querySelector()` pour reconnaître les tableaux complexes. Ces API n'existent pas sous PHP 8.2–8.3.
- Le code de production appelle aussi `str_contains()`, `str_starts_with()` et `str_ends_with()`. Ces fonctions sont disponibles dès PHP 8.0 : elles n'empêchent pas la cible 8.2–8.3.
- `composer.json` et `paquet.xml` demandent PHP ≥ 8.4. Les deux extensions `wp2spip_acf` et `wp2spip_yoast` portent le même minimum. Les tests emploient PHPUnit 13, dont la version verrouillée requiert PHP ≥ 8.4.1.
- Le `vendor/` de développement est ignoré par Git et exclu de la copie de `outils/preparer_spip.sh`. Une simple dépendance Composer dans `require` ne rendrait donc pas Masterminds disponible dans un plugin SPIP installé depuis ses sources ou copié par cet outil.
- Dans les verrous actuels de wp2spip et wp2spip_acf, `spip/tests` demande PHP ≥ 8.1 et `spip/spip-cli` accepte PHP 8 ; ces contraintes déclarées ne bloquent donc pas PHP 8.2. `wp2spip_yoast` n'a pas de `composer.lock` dans le dépôt examiné : sa résolution reste à contrôler.
- Les tests des trois dépôts utilisent `PHPUnit\Framework\Attributes\DataProvider` ; PHPUnit 11 conserve les attributs de métadonnées et accepte PHP 8.2. Les méthodes de fourniture des données observées sont `public static`, mais le reste de l'API de test et les fichiers `phpunit.xml` restent à exécuter sous cette version.

## 3. Audit préalable à la baisse du minimum PHP

| Élément | Constat | Vérification et décision attendues |
| --- | --- | --- |
| Sources de production et CLI des trois plugins | Le seul appel repéré à `Dom\HTMLDocument` est dans `inc/wp2spip_html.php` ; les fonctions `str_*` repérées existent sous PHP 8.2. | Parcourir tous les fichiers PHP, y compris `spip-cli/`, `outils/` et les deux extensions ; contrôler syntaxe et API avec un vrai interpréteur 8.2, puis corriger chaque incompatibilité constatée. |
| Manifests SPIP et Composer | `>=8.4` dans les trois `composer.json` et `[8.4.0;]` dans les trois `paquet.xml`. | Aligner les six déclarations sur 8.2 **après** validation ; contrôler que les autres plugins requis et les versions SPIP annoncées fonctionnent sur chaque couple testé. |
| PHPUnit | `phpunit/phpunit:^13.0` dans les trois dépôts ; les deux verrous présents fixent 13.4.1 et ses dépendances PHP ≥ 8.4. | Cibler `^11.5` dans les trois `require-dev` : PHPUnit 11 accepte PHP ≥ 8.2 et les attributs `DataProvider`. Mettre à jour les deux verrous existants et créer celui de Yoast si sa procédure de développement le nécessite ; résoudre sur PHP 8.2 sans ignorer les contraintes de plateforme. |
| Tests et configuration | `#[DataProvider]`, classes de base de tests, `phpunit.xml`, amorces distinctes pour unitaires et intégration. | Exécuter les suites avec PHPUnit 11 ; adapter seulement les usages incompatibles, vérifier la découverte des jeux de données et migrer les schémas XML si nécessaire. Conserver la même couverture fonctionnelle. |
| Dépendances de développement | `spip/tests:dev-master`, `spip/spip-cli:dev-master` et leurs dépendances transitives peuvent changer ; le verrou actuel n'est pas une preuve de résolution sur 8.2. | Vérifier les contraintes transitives et les extensions PHP requises par `composer install`, `composer check-platform-reqs` et les scripts SPIP-Cli sur PHP 8.2. Fixer ou borner une révision compatible si une branche mouvante cesse de l'être. |
| Dépendances SPIP de production | Pages uniques, Polyhiérarchie et plugins requis selon le contenu ; extensions ACF et Yoast avec leurs propres plugins. | Tester l'activation et l'import sur les combinaisons PHP/SPIP réellement annoncées ; réduire les plages de compatibilité déclarées si une combinaison ne peut être validée. |
| Distribution et documentation | `vendor/` est exclu de la copie et plusieurs pages annoncent 8.4 obligatoire. | Vérifier le chargement de Masterminds sans Composer sur le site cible, la copie par le préparateur et le paquet publié ; mettre à jour guides, messages d'erreur et prérequis de test. |

Une contrainte Composer compatible en théorie ne valide pas un exécutable : l'audit distingue la résolution des dépendances, le chargement du plugin, l'exécution des tests et l'import réel.

## 4. Choix de conception

| Sujet | Décision |
| --- | --- |
| Parseur | `Dom\HTMLDocument` si PHP ≥ 8.4 et classe présente ; Masterminds HTML5-PHP si PHP 8.2 ou 8.3. Absence de l'extension DOM ou de Masterminds : erreur explicite avant conversion. |
| Version de Masterminds | Dépendance `masterminds/html5` en version 2.x compatible avec PHP 8.2 et 8.3, verrouillée pour la distribution et les tests. Vérifier sa contrainte PHP et `ext-dom` à la résolution Composer. |
| Frontière interne | Isoler création du document, reconnaissance des nœuds, sérialisation HTML et détection des tableaux complexes derrière de petites fonctions internes. Le parcours et les règles HTML → SPIP restent dans `inc/wp2spip_html.php`. Aucun objet `Dom\*` ou `DOM*` ne sort de cette frontière. |
| Nœuds Masterminds | Son parseur renvoie les classes DOM historiques (`DOMDocument`, `DOMElement`, `DOMText`). Ne pas passer ce document à `DOMDocument::loadHTML()` : cette méthode utilise les règles HTML4. |
| Sérialisation | Sérialiser les sous-arbres conservés en HTML avec le sérialiseur HTML5 de Masterminds sur la branche 8.2–8.3 ; garder `Dom\HTMLDocument::saveHtml()` sur la branche native. Vérifier les balises vides, attributs booléens, entités et SVG/MathML. |
| Sélection des tableaux | Remplacer l'unique `querySelector('table, [rowspan]:not([rowspan="1"])')` par un parcours des descendants ou par un `DOMXPath` local à la branche Masterminds ; même condition fonctionnelle : tableau imbriqué ou `rowspan` différent de `1`. Aucun composant Symfony requis. |
| Chargement en production | Placer une copie de la version verrouillée de Masterminds (`src/` et licence MIT) dans `lib/masterminds-html5/`, suivie par Git et incluse dans le paquet SPIP. Un chargeur propre au plugin résout les classes `Masterminds\` depuis cet emplacement sur PHP 8.2–8.3. Ne pas dépendre du `vendor/` de développement ni d'un `composer install` effectué par l'administrateur du site. |

Le script de copie `outils/preparer_spip.sh` doit garder `lib/masterminds-html5/`. La version copiée dans `lib/` et celle du verrou Composer doivent être identiques ; une vérification automatisée empêche leur divergence lors d'une mise à jour. Le plugin publié embarque ce dossier et sa notice de licence MIT.

## 5. Comportement attendu

1. `wp2spip_html_spip()` normalise les retours à la ligne et retourne `''` pour une entrée vide, comme aujourd'hui.
2. Le HTML est analysé comme document HTML5 enveloppé dans `<!DOCTYPE html><html><body>…</body></html>`. Le parcours commence sur le `body` obtenu ; le contenu WordPress est en UTF-8.
3. Le parcours reconnaît texte, éléments et autres nœuds avec les classes du parseur choisi. Commentaires et instructions restent ignorés. L'option `autop`, les marqueurs `wp2spipbloc<N>`, les entités et les raccourcis SPIP/WordPress gardent leur sens actuel.
4. Le même contenu produit une sortie SPIP équivalente sur 8.2, 8.3 et 8.4. Les cas courants gardent la sortie exacte des tests actuels. Une différence due à la construction d'arbre sur du HTML mal formé ou à la sérialisation doit être documentée par une fixture, examinée sur le rendu SPIP et acceptée seulement sans perte de texte ni régression fonctionnelle.
5. Si le parseur attendu est indisponible, l'import échoue avec un message indiquant la dépendance manquante et la version de PHP ; il ne poursuit pas avec un parseur HTML4 et ne produit pas silencieusement un texte vide.

## 6. Distribution et compatibilité

- Abaisser à `>=8.2` les prérequis PHP de `wp2spip` dans `composer.json` et `paquet.xml`, puis aligner `wp2spip_acf` et `wp2spip_yoast` après validation de leurs sources et dépendances. Vérifier aussi les dépendances SPIP et le verrou Composer de chaque dépôt ; la compatibilité du cœur ne suffit pas à garantir celle de toute l'installation.
- Ajouter Masterminds comme dépendance de production dans Composer pour la résolution et les tests, puis fournir sa version verrouillée dans l'artefact SPIP. Vérifier les trois parcours : copie par `outils/preparer_spip.sh`, plugin installé depuis un paquet publié et dépôt cloné pour le développement.
- Adapter les suites à PHPUnit 11.5 et à ses dépendances de développement compatibles avec PHP 8.2. Les attributs `DataProvider`, les signatures des tests et la configuration XML sont vérifiés lors de cette migration. La matrice CI exécute PHP 8.2, 8.3 et 8.4 avec `ext-dom`.
- Mettre à jour README, documentation d'installation, de développement, de conversion et de dépannage qui annoncent aujourd'hui PHP 8.4 obligatoire. Les anciennes specs réalisées restent des archives de leur décision initiale ; cette spec documente l'évolution.

## 7. Validation et critères d'acceptation

- Tous les cas de `tests/unit/HtmlTest.php` sont exécutés sur PHP 8.2, 8.3 et 8.4. Les cas de HTML mal formé, listes non fermées, tableaux imbriqués, médias, entités et UTF-8 sont particulièrement comparés entre les deux parseurs.
- `tests/integration/HtmlSpipTest.php`, les tests des blocs, des extensions et l'import complet passent sur les trois versions, dans la mesure où les dépendances de test prennent en charge ces versions. Si une dépendance de test empêche une version, résoudre ce blocage avant de déclarer la compatibilité.
- Un essai d'installation SPIP sans `vendor/` de développement, sous PHP 8.2 puis 8.3, confirme que Masterminds se charge et qu'un article contenant HTML5 et médias s'importe. Un essai sous 8.4 confirme l'utilisation du parseur natif.
- Comparer sur un corpus WordPress représentatif les sorties 8.2/8.3 à la référence 8.4, classer chaque différence et vérifier qu'aucun texte ni attribut nécessaire aux traitements suivants n'est perdu.
- Tester explicitement le cas de bibliothèque absente : erreur lisible, arrêt de l'import, aucune conversion partielle considérée comme réussie.
- Sur PHP 8.2, `composer install` et `composer check-platform-reqs` passent dans les trois dépôts, sans `--ignore-platform-reqs`. Les suites unitaires et d'intégration sont lancées avec le PHPUnit verrouillé ; `phpunit --version` confirme la série 11.5. Répéter au moins la résolution et les suites sur PHP 8.3 et 8.4.
- Les commandes SPIP-Cli et les scripts de préparation/import sont exécutés avec le PHP de la matrice, et pas seulement avec le PHP par défaut de la machine. Pour chaque combinaison PHP/SPIP annoncée, installation, activation et import se terminent sans erreur de version ou d'API.
- Les trois `composer.json` et `paquet.xml`, les verrous présents et les prérequis documentés donnent le même minimum PHP. Aucune extension ne reste artificiellement bloquée à 8.4 si elle passe les critères précédents.

## 8. Hors périmètre

Modifier les règles de conversion HTML → SPIP, réintroduire Sale, ajouter une option de sélection manuelle du parseur ou promettre une compatibilité PHP 7.4.

## Références

- [Masterminds HTML5-PHP : API, sérialisation et limites connues](https://github.com/Masterminds/html5-php)
- [Contraintes du paquet Composer](https://packagist.org/packages/masterminds/html5)
- [PHP : `DOMDocument::loadHTML()` et ses règles HTML4](https://www.php.net/manual/en/domdocument.loadhtml.php)
- [Versions PHP prises en charge par PHPUnit](https://phpunit.de/supported-versions.html)
- [PHPUnit 11 : attributs et minimum PHP](https://phpunit.de/announcements/phpunit-11.html)
