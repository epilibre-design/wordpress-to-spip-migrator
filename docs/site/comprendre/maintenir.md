# Maintenir et publier ce site

Les sources publiques sont dans `docs/site/`. Le fichier unique `mkdocs.yml` fixe **`docs_dir: docs/site`** : les plans et specs de `docs/superpowers/` restent exclus, même s’ils ne sont pas dans la navigation. Le répertoire `site/` est une sortie générée ignorée par Git.

## Installer et construire

Depuis la racine du miroir, avec Python 3.11 ou ultérieur :

```bash
python -m venv ../.venvs/wp2spip-docs
source ../.venvs/wp2spip-docs/bin/activate
python -m pip install -r requirements-docs.txt
python -m mkdocs build --strict
python -m mkdocs serve
```

Les versions directes et transitives sont verrouillées. Le site se construit indépendamment de PHP et des bases de migration. `serve` sert à la lecture locale ; `build` produit les fichiers statiques à publier sur l’hébergeur choisi. Aucun déploiement distant n’est implicite.

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

## Préparation multilingue

Le plugin **mkdocs-static-i18n** utilise la structure par **suffixe**. Le français est la version par défaut, sans suffixe ; l’anglais est déclaré mais sa construction est désactivée tant que les traductions ne sont pas rédigées.

```text
mkdocs.yml
docs/site/
├── index.md
├── index.en.md                  # À créer pour la version anglaise
└── installer/
    ├── installation.md
    └── installation.en.md      # À créer pour la version anglaise
```

Les fichiers `.en.md` de cet exemple sont des futurs fichiers, pas des traductions déjà livrées. Pour la publication anglaise :

1. Créer les traductions par suffixe, en conservant les mêmes chemins et concepts.
2. Traduire les intitulés dans `languages.en.nav_translations` et les contenus, exemples et renvois éditoriaux.
3. Passer `build: false` à `build: true` pour `locale: en`, puis construire avec `--strict`.
4. Vérifier les URL sous `/en/`, le sélecteur de langue, les ancres de titres traduits et la recherche anglaise.

Le repli `fallback_to_default: true` fournit une page française quand sa traduction manque. Pour une publication partielle, expliquer ce repli aux lecteurs ; il ne faut pas présenter ces pages comme traduites. Les liens internes gardent leurs noms canoniques (par exemple `installation.md`) : le plugin les adapte à la langue. Une ancre française peut changer dans le texte anglais ; vérifier chaque lien de section.

## Réviser les états après synchronisation

1. Relever le nouveau SHA du **code du miroir** ; examiner son diff avec la révision documentée précédente.
2. Vérifier chaque fonction de [l’état du projet](../etat-projet.md) contre le code, l’orchestration et les tests.
3. Réviser les pages concernées et leurs références, sans confondre spec et implémentation.
4. Mettre à jour `extra.documented_commit`, la date de revue, les permaliens et le tableau ensemble.
5. Construire et contrôler le site avant de publier.

Le commit documenté est celui du code analysé, pas nécessairement le commit ajoutant ou publiant la documentation. Si la revue n’est pas terminée, garder la révision précédente affichée. La même politique s’appliquera aux traductions anglaises ; signaler toute traduction devenue obsolète.

Références : [configuration MkDocs](https://www.mkdocs.org/user-guide/configuration/), [validation](https://www.mkdocs.org/user-guide/configuration/#validation), [mkdocs-static-i18n](https://ultrabug.github.io/mkdocs-static-i18n/).
