# WordPress 6 : compositions et structures de site

La famille 6 étend l’usage de l’édition du site et des compositions. Pour la migration, la difficulté porte souvent sur les types, références et métadonnées plutôt que sur l’apparition d’une nouvelle table.

## Contenu et présentation se côtoient

Les types de modèles, parties, styles globaux et navigation déjà présents en 5.9 continuent d’utiliser `posts`. Les articles éditoriaux et la structure de présentation partagent donc un stockage, avec des sens différents.

Le moteur ne doit pas transformer arbitrairement un `wp_template` en article publié. Le rendu final dépend des squelettes SPIP et d’une reprise des menus et styles.

## 6.3 : compositions synchronisées ou non

Le type `wp_block` sert aux compositions et la méta `wp_pattern_sync_status`, ajoutée en 6.3, distingue notamment une composition non synchronisée. Une composition synchronisée peut être référencée ; une insertion non synchronisée peut fournir une copie de blocs dans le contenu.

| Forme source | Conséquence |
|---|---|
| Blocs copiés dans l’article | Le convertisseur peut traiter les blocs effectivement enregistrés |
| Référence à une composition synchronisée | Résolution du contenu référencé nécessaire ; non garantie dans le moteur documenté |
| Définition dans un thème/plugin | Les fichiers et le contexte WordPress peuvent être nécessaires |

Cette distinction importe plus pour l’import qu’un simple libellé « composition ». Le partage d’un contenu entre plusieurs articles est une relation à conserver ou à transformer explicitement.

## Données typographiques et métadonnées

Dans le code de 6.9, `wp_font_family` et `wp_font_face` sont également des types enregistrés. Ils concernent la présentation et ne deviennent pas des contenus éditoriaux SPIP. Les polices et styles du futur site demandent une décision de thème distincte.

Des attributs et métadonnées ajoutés par l’éditeur ou des extensions peuvent modifier le comportement d’un bloc sans changer `posts`. Auditer le contenu réel et ses références reste indispensable.

## État de la couverture

Le dépôt fournit un scénario d’import complet d’un jeu WordPress 6.9 et une référence versionnée ; les plans rapportent sa validation, puis la revue des différences après remplacement de Sale. Les tests de blocs et d’intégration donnent des cas contrôlables, mais ne démontrent pas que tous les blocs, versions mineures et thèmes de la famille 6 sont convertis. Rejouer le scénario demande une installation WordPress externe configurée.

Sources : [types 6.0](https://github.com/WordPress/WordPress/blob/cc101b64012b16d087780657a2b828ccd7794a63/wp-includes/post.php), [compositions et méta 6.3](https://github.com/WordPress/WordPress/blob/ac3899153a790a6f060dc816ff94812e0fd99875/wp-includes/post.php), [types 6.9](https://github.com/WordPress/WordPress/blob/ec24ee6087dad52052c7d8a11d50c24c9ba89a3b/wp-includes/post.php), [sélection des articles](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [tests de blocs](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/integration/BlocsTest.php).
