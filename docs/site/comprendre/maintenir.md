# Maintenir et publier ce site

Les sources publiques sont dans `docs/site/`. Le fichier unique `mkdocs.yml` fixe **`docs_dir: docs/site`** : les plans et specs de `docs/superpowers/` restent exclus, même s’ils ne sont pas dans la navigation. Le répertoire `site/` est une sortie générée ignorée par Git.

## Installer et construire

Depuis la racine du dépôt, avec Python 3.11 ou ultérieur :

```bash
python -m venv ../.venvs/wp2spip-docs
source ../.venvs/wp2spip-docs/bin/activate
python -m pip install -r requirements-docs.txt
python -m mkdocs build --strict
python -m mkdocs serve
```

Les versions directes et transitives sont verrouillées. Le site se construit indépendamment de PHP et des bases de migration. `serve` sert à la lecture locale ; `build` produit les fichiers statiques à publier sur l’hébergeur choisi. Aucun déploiement distant n’est implicite.

## Publier sur GitHub Pages

Pour une publication par branche, utiliser le dépôt GitHub qui héberge ce site et un compte autorisé à y pousser. Après revue des sources et construction stricte, la commande suivante construit et pousse le site sur la branche `gh-pages` du remote `epilibre` :

```bash
python -m mkdocs gh-deploy --strict --remote-name epilibre --remote-branch gh-pages
```

Cette commande est une **publication distante**, contrairement à `build` ; l’exécuter seulement quand les changements sont prêts à être publiés. Dans les paramètres **Pages** du dépôt GitHub, sélectionner la publication depuis la branche `gh-pages`, dossier racine. Si Pages utilise déjà un workflow GitHub Actions, conserver cette méthode et y reprendre la construction stricte plutôt que configurer deux modes de publication.

`repo_url` et `repo_name` désignent le dépôt du code documenté. `site_url` vaut `https://epilibre-design.github.io/wordpress-to-spip-migrator/` : ce préfixe est nécessaire aux liens du sélecteur de langue sur GitHub Pages. Vérifier après publication les chemins sous le préfixe du projet, les liens, la recherche et les diagrammes. Les changements documentaires et un build local ne prouvent pas que la publication a eu lieu.

## Diagrammes locaux

Mermaid 11.12.1 est servi depuis `assets/javascripts/mermaid.min.js`, sans chargement depuis un CDN. Sa licence MIT et la provenance (archive NPM, intégrité SHA512 de l’archive et SHA256 du JavaScript) sont conservées à côté du fichier. Pour une mise à jour, vérifier l’intégrité du nouveau paquet et refaire le contrôle du rendu des trois diagrammes ; remplacer ensemble fichier, licence et provenance. Les polices utilisent celles du système.

## Liens et ancres

Écrire les liens internes vers les fichiers Markdown relatifs, par exemple `../migrer/audit.md`. Pour une section, ajouter une ancre existante. La configuration utilise notamment :

```yaml
docs_dir: docs/site
validation:
  links:
    not_found: warn
    anchors: warn
    unrecognized_links: warn
    absolute_links: warn
```

Avec MkDocs 1.6, le niveau d’ancres par défaut est `info` ; il faut `warn` pour qu’une ancre introuvable fasse échouer `--strict`. Les liens externes ne sont pas vérifiés par ce contrôle interne. Utiliser des permaliens GitHub vers le commit exact pour le code et les specs exclus du site.

## Versions française et anglaise

Le plugin **mkdocs-static-i18n** utilise la structure par **suffixe**. Le français est la version par défaut à la racine ; les traductions anglaises sont dans les fichiers `.en.md` de mêmes chemins et sont publiées sous `/en/`. Les deux versions sont activées dans `mkdocs.yml`.

Pour modifier une page, mettre à jour son équivalent dans l'autre langue, conserver les liens relatifs vers les noms canoniques (par exemple `installation.md`) et vérifier les ancres des titres traduits. `nav_translations` traduit les intitulés de navigation. Après une modification, lancer `mkdocs build --strict` et contrôler les pages et la recherche dans les deux langues.

Le repli `fallback_to_default: true` permet de servir une page française quand sa traduction manque. Avant publication, vérifier que chaque page française possède sa traduction anglaise afin qu'aucune page du parcours anglais ne dépende de ce repli.

Références : [configuration MkDocs](https://www.mkdocs.org/user-guide/configuration/), [validation](https://www.mkdocs.org/user-guide/configuration/#validation), [mkdocs-static-i18n](https://ultrabug.github.io/mkdocs-static-i18n/).
