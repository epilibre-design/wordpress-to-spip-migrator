# Installer le migrateur

## Récupérer le dépôt

Sur une machine de développement :

```bash
git clone --branch main https://github.com/epilibre-design/wordpress-to-spip-migrator.git
cd wordpress-to-spip-migrator
```

## Installation dans un SPIP existant

1. Préparer une destination SPIP vierge, hors ligne.
2. Installer SPIP-Cli et vérifier que son correctif `plugins:svp:telecharger` est présent.
3. Vérifier PHP 8.1 ou plus récent et son extension DOM, puis installer et activer Pages uniques et Polyhiérarchie.
4. Placer ce plugin sous `plugins/wp2spip` — en copie, ou par lien vers le checkout pour le développer — puis l’activer.
5. Déclarer la base WordPress comme base externe dans l’administration de SPIP. Le nom reconnu par défaut est `wordpress`.

Depuis la **racine du site SPIP**, et avec le bon exécutable CLI :

```bash
spip plugins:lister --short --raw --no-dist
spip help wordpress:importer
spip wordpress:importer /chemin/vers/wordpress --info
```

Placer le chemin avant `--info` : cette option accepte une valeur facultative et peut sinon absorber l’argument WordPress.

`--info` vérifie notamment le dossier, la version, le préfixe et les tables avant d’afficher les traitements. La reconnaissance de la commande et des données ne remplace pas un import de validation.

## Préparation automatisée

Le dépôt fournit `outils/preparer_spip.sh`, qui installe SPIP 4.4 par défaut, les dépendances obligatoires, le migrateur et la connexion externe. Le [guide de préparation](../migrer/preparer.md) décrit son utilisation et la distinction entre SQLite et MySQL.

## Ajouter Yoast SEO ou ACF

Les plugins [wp2spip_yoast et wp2spip_acf](../comprendre/extensions.md) sont publiés dans des dépôts séparés. Les placer dans `plugins/` du SPIP de destination et les activer à côté de wp2spip **avant** de lancer l’import. La commande `--info` doit alors afficher leurs traitements supplémentaires. Le script de préparation n’installe pas ces extensions du migrateur.

La détection de contenu se charge des plugins cibles nécessaires : SEO pour les métadonnées Yoast, Champs Extras et son interface pour les champs ACF importables. Vérifier leur configuration et leur rendu après import.

## SPIP-Cli et correctif SVP

L’environnement de tests installe SPIP-Cli avec Composer puis lui applique [le patch fourni](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/tests/spip-cli.patch). Ce patch corrige la sélection du plugin, les autorisations et la gestion des erreurs de téléchargement. Une installation indépendante doit disposer des mêmes corrections ; ne pas supposer qu’un autre exécutable `spip` du PATH est corrigé.

Le script [install-spip-test.sh](https://github.com/epilibre-design/wordpress-to-spip-migrator/blob/dc1963eb54b317e7e9e7b445bfee81199a7804bb/scripts/install-spip-test.sh) applique ce patch dans `vendor/`, pas dans un SPIP-Cli système.

## Avant le premier import

Vérifier les accès à la base source, les fichiers médias, les plugins et le préfixe. Préparer un plan pour les données [non converties](../correspondances/index.md), puis suivre [l’audit](../migrer/audit.md).
