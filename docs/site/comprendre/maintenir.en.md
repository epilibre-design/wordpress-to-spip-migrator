# Maintaining and publishing this site

The public sources are in `docs/site/`. The single `mkdocs.yml` file sets **`docs_dir: docs/site`**: plans and specs in `docs/superpowers/` remain excluded, even if they are not in the navigation. The `site/` directory is a generated output ignored by Git.

## Installing and building

From the repository root, using Python 3.11 or later:

```bash
python -m venv ../.venvs/wp2spip-docs
source ../.venvs/wp2spip-docs/bin/activate
python -m pip install -r requirements-docs.txt
python -m mkdocs build --strict
python -m mkdocs serve
```

Direct and transitive versions are locked. The site is built independently of PHP and migration databases. `serve` is used for local reading; `build` produces the static files to be published on the chosen host. No remote deployment is implied.

## Publishing on GitHub Pages

For branch-based publishing, use the GitHub repository hosting this site and an account authorized to push to it. After reviewing the sources and performing a strict build, the following command builds and pushes the site to the `gh-pages` branch of the `epilibre` remote:

```bash
python -m mkdocs gh-deploy --strict --remote-name epilibre --remote-branch gh-pages
```

This command is a **remote publication**, unlike `build`; execute it only when changes are ready to be published. In the GitHub repository **Pages** settings, select publication from the `gh-pages` branch, root folder. If Pages already uses a GitHub Actions workflow, keep that method and perform the strict build there rather than configuring two publication modes.

`repo_url` and `repo_name` designate the repository of the documented code. `site_url` is `https://epilibre-design.github.io/wordpress-to-spip-migrator/`; this project prefix is needed for the language switcher links on GitHub Pages. After publication, verify the paths under the project prefix, links, search, and diagrams. Documentary changes and a local build do not prove that publication has occurred.

## Local diagrams

Mermaid 11.12.1 is served from `assets/javascripts/mermaid.min.js`, without loading from a CDN. Its MIT license and provenance (NPM archive, SHA512 integrity of the archive, and SHA256 of the JavaScript) are kept next to the file. For an update, verify the integrity of the new package and re-check the rendering of the three diagrams; replace the file, license, and provenance together. Fonts use system fonts.

## Links and anchors

Write internal links to relative Markdown files, for example `../migrer/audit.md`. For a section, add an existing anchor. The configuration uses, among others:

```yaml
docs_dir: docs/site
validation:
  links:
    not_found: warn
    anchors: warn
    unrecognized_links: warn
    absolute_links: warn
```

With MkDocs 1.6, the default anchor level is `info`; `warn` is required for a missing anchor to cause `--strict` to fail. External links are not verified by this internal check. Use GitHub permalinks to the exact commit for code and specs excluded from the site.

## French and English versions

The **mkdocs-static-i18n** plugin uses the **suffix** structure. French is the default version at the site root; English translations use `.en.md` files at the same paths and are published under `/en/`. Both builds are enabled in `mkdocs.yml`.

When editing a page, update its counterpart in the other language, keep relative links pointing to canonical filenames (for example, `installation.md`), and verify anchors for translated headings. `nav_translations` translates navigation labels. After a change, run `mkdocs build --strict` and check pages and search in both languages.

The `fallback_to_default: true` setting can serve a French page when its English translation is missing. Before publishing, check that every French page has an English counterpart so no page in the English journey relies on that fallback.

References: [MkDocs configuration](https://www.mkdocs.org/user-guide/configuration/), [validation](https://www.mkdocs.org/user-guide/configuration/#validation), [mkdocs-static-i18n](https://ultrabug.github.io/mkdocs-static-i18n/).
