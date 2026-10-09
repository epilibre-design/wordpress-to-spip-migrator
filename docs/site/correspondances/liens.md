# Liens, slugs et adresse du site

**État : implémenté avec limites.** Distinguer la conversion d’un lien dans le texte, l’enregistrement d’un slug et la redirection d’une ancienne URL publique.

## Liens dans les contenus

| Source | Conversion |
|---|---|
| `?p=42` ou `?page_id=42` | Raccourci vers l’article `42` |
| URL de contenu reconnue par son slug | Raccourci vers l’objet identifié |
| URL de média reconnue dans uploads | Raccourci vers le document correspondant |
| URL ambiguë ou média non reconnu | Peut rester vers la source ; contrôle nécessaire |

La conservation des identifiants permet de désigner un article importé plus tard. La résolution par slug prend en compte les chemins des pages parentes ; si plusieurs candidats restent possibles, le lien n’est pas forcé vers un objet arbitraire.

Les variantes de chemins de fichiers, protocoles, `www.` et miniatures sont normalisées pour identifier des médias. Cette reconnaissance a un périmètre : elle ne remplace pas une exploration des liens de l’ensemble du site.

## Slug et URL publique

Les slugs des articles/pages et documents sont enregistrés dans `spip_urls`. Le traitement des rubriques lit le slug source mais ne l’enregistre pas comme URL : les URL de catégories doivent être reconstruites et leurs redirections préparées. Leur présence ne signifie pas que les règles de permaliens WordPress, le chemin complet des pages ou les préfixes de catégories seront automatiquement identiques dans le site SPIP.

Exemple : `[Lire->article42]` reste lié à l’objet SPIP, tandis qu’une ancienne URL publique `/2020/03/un-titre/` peut nécessiter une redirection HTTP. Ce sont deux mécanismes différents.

## Adresse de la destination

Les métadonnées reprennent `siteurl` comme `adresse_site` par défaut. Utiliser `--garder-adresse` pour conserver l’URL SPIP de recette. Les autres métas sélectionnées sont `blogname` → nom du site, `admin_email` → email du webmestre et `blogdescription` → slogan converti.

## Contrôler avant bascule

Inventorier les anciennes URL importantes ; comparer les URL finales SPIP et préparer les redirections sur le serveur. Chercher les liens résiduels vers WordPress, notamment les images de fond, les médias hors médiathèque et les slugs ambigus. Tester ces adresses après la bascule.

Sources : [liens et index](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_articles.php), [métas](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_metas.php), [rubriques](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/wp2spip/importer_rubriques.php).
