# 2. Préparer un SPIP vierge

La destination reste hors ligne jusqu’au contrôle des accès et des contenus. Le dossier demandé au script doit être absent ou vide ; les données déjà présentes sont incompatibles avec la conservation des identifiants WordPress.

## Préparation depuis le checkout

Avec SPIP-Cli installé et corrigé, et ses dépendances disponibles :

```bash
bash outils/preparer_spip.sh --help
bash outils/preparer_spip.sh \
  --spip /chemin/vers/spip-test \
  --wordpress /chemin/vers/wordpress-fige \
  --spip-cli /chemin/vers/spip \
  --wp2spip lien
```

Cette commande prépare la destination **sans lancer l’import**. `--wp2spip lien` lie le checkout pour le développement ; `copie` est le défaut. L’option `--importer` peut enchaîner l’import, mais ne dispense pas de l’audit ni des contrôles.

## Choisir la base SPIP

| Option | Destination | Condition |
|---|---|---|
| Sans option / `--base-spip sqlite` | SQLite locale | Extension PDO SQLite disponible |
| `--base-spip mysql:base_spip` | Base MySQL existante | Accès valides et aucune table au préfixe SPIP déjà présente |
| Avec `--base-partagee` | Base aussi utilisée par WordPress | Autorisation explicite de cohabitation ; préfixes distincts |

La base source WordPress reste déclarée comme base externe, y compris quand SPIP utilise SQLite. SQLite pour la destination ne convertit pas automatiquement une installation WordPress MySQL en SQLite.

Le script lit les constantes de connexion et le préfixe dans `wp-config.php`, sans l’exécuter. Un préfixe calculé ou une configuration ambiguë nécessite une préparation adaptée ; la commande d’import permet `--prefixe` explicite. Les hôtes avec port ou socket demandent une attention particulière aux options du script, notamment `--sql-hote` pour SPIP.

## Accès et mot de passe initial

`SPIP_ADMIN_PASS` peut fournir le mot de passe initial ; sinon le script en génère un et l’affiche. `SPIP_DB_PASS` peut remplacer le mot de passe MySQL de la destination. Ne pas les écrire dans la documentation ni les versionner.

SPIP-Cli reçoit les mots de passe d’installation en argument et peut les afficher : protéger les journaux et l’accès à la machine pendant cette étape.

## Vérifier avant de poursuivre

Depuis la racine SPIP, avec le même exécutable CLI :

```bash
spip plugins:lister --short --raw --no-dist
spip wordpress:importer /chemin/vers/wordpress-fige --info
```

Confirmer Pages uniques, Polyhiérarchie et wp2spip actifs, avec PHP 8.4 et DOM. Sale n’est plus installé ni requis. Les autres plugins requis par le contenu seront détectés avant les traitements. La source est lisible et le préfixe correspond aux tables attendues.

Pour reprendre les données Yoast ou ACF, installer et activer les [extensions du migrateur](../comprendre/extensions.md) dans ce SPIP avant l’import ; elles ne sont pas ajoutées par ce script. Préparer alors sans `--importer`, puis vérifier les traitements avec `--info`.

Source : [script de préparation](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/outils/preparer_spip.sh). **Suite : [lancer l’import](importer.md).**
