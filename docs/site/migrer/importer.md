# 3. Lancer l’import

Exécuter SPIP-Cli depuis la **racine du SPIP de destination**, pas depuis la racine du plugin. Utiliser une source figée, une destination vierge et les plugins obligatoires actifs.

## Inspection puis import complet

```bash
spip help wordpress:importer
spip wordpress:importer /chemin/vers/wordpress-fige --info
spip wordpress:importer --garder-adresse -v /chemin/vers/wordpress-fige
```

`--garder-adresse` conserve l’adresse de la destination, utile pour une recette hors ligne. Sans cette option, la configuration SPIP reprend l’adresse WordPress. `-v` donne davantage de détails sur les anomalies et les conversions incomplètes.

## Options métier

| Option | Usage |
|---|---|
| `--base=wordpress` | Nom de la connexion externe SPIP, défaut `wordpress` |
| `--prefixe=site_` | Préfixe explicite, remplace celui lu dans `wp-config.php` |
| `--traitements=nom1,nom2` | Sous-ensemble des traitements existants ; l’ordre de dépendance reste celui du moteur |
| `--info` | Version et liste de traitements, sans import |
| `--garder-adresse` | Préserve l’adresse du SPIP |

La commande prépare d’abord les plugins supplémentaires requis ; elle peut se relancer après leur activation. Lire les messages jusqu’au résultat final.

## Traitements disponibles

Métas → auteurs → rubriques → documents → articles → hiérarchie des pages → **étiquettes (`importer_mots`)** → accès → polyhiérarchie → commentaires. Voir [le flux détaillé](../comprendre/traitements.md).

Les [extensions Yoast et ACF](../comprendre/extensions.md), si elles sont actives, ajoutent leurs traitements à la même commande. Yoast ajuste la catégorie principale après les articles et ajoute les métadonnées SEO en fin de liste ; ACF ajoute `importer_acf` en fin de liste. Utiliser `--info` pour connaître la liste effective de votre installation.

## Comprendre la sortie

Un code **1** signifie notamment option de traitement invalide, prérequis manquant ou traitement en échec ; les suivants ne sont pas exécutés. Un code **0** signifie que la commande a terminé selon son contrat, pas que tous les liens, fichiers et rendus ont une équivalence complète.

Les bilans indiquent notamment médias refusés, blocs dynamiques retirés, blocs inconnus ou liens résiduels. Conserver et examiner ces informations avant [la validation](valider.md).

## Relancer ou refaire

Une relance évite généralement de recréer les contenus tracés, mais ne met pas à jour leur texte. Certains traitements recalculent configuration ou relations. Ce mécanisme n’est pas une reprise garantie après interruption : pour refaire un import après échec, interruption ou évolution du moteur, repartir d’une destination remise à zéro avec des sauvegardes vérifiées.

Source : [commande](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/spip-cli/WordpressImporter.php).
