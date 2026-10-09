# Site documentaire MkDocs — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Livrer un site français expliquant les migrations WordPress → SPIP et leurs limites au commit documenté.

**Architecture:** MkDocs Material, pages Markdown dans `docs/site`, configuration unique à la racine. Pied de page commun affichant la révision documentée, sources du code et des specs par permaliens. Site statique sans dépendance au moteur PHP.

**Tech Stack:** Python >= 3.11, MkDocs 1.6.1, Material, PyMdown Extensions, Mermaid 11.12.1 servi localement, mkdocs-static-i18n 1.3.1.

**Spec:** `docs/superpowers/specs/2026-10-09-site-documentaire-mkdocs-design.md`

## Global Constraints

- `docs_dir: docs/site`, `site_dir: site` ; specs internes exclues des sources publiques.
- `validation.links.anchors: warn` et liens internes relatifs, construction `--strict`.
- Révision de code documentée : `9f08d61f86515d80975cb6fbb228cac0142437f2`.
- Chaque état est relatif à cette révision ; importer_mots est non implémenté, prévu par la spec.
- Ne modifier ni PHP, ni Composer, ni les tests applicatifs ; ne pas déployer ni pousser.
- Réutiliser le checkout cloud isolé existant ; aucun nouveau worktree.
- i18n par suffixe : français par défaut, anglais configuré mais build désactivé ; aucune traduction anglaise publiée dans cette version.
- Exécution continue autorisée par l’utilisateur après les retouches ; pas de nouvelle revue du design.

## Review Focus

- Un lien valide vers une page mais une ancre absente doit faire échouer la construction.
- Les specs/plans hors docs/site ne doivent apparaître ni dans le site ni dans la recherche.
- Le commit affiché doit désigner le code décrit, pas un futur HEAD automatique.
- WP4–WP7 reste un objectif ; une spec ou un tag WordPress ne prouve pas la couverture.
- Les correspondances doivent expliciter les pertes, les relations et les contrôles après import.

### Task 1: Socle et sources

**Files:** Create `mkdocs.yml`, `requirements-docs.txt`, `docs/site/overrides/main.html`, `docs/site/stylesheets/extra.css` ; modify `.gitignore`.
**Interfaces:** Produit les noms des pages pour la navigation et `extra.documented_commit`, utilisés par le pied de page.

- [x] Installer les versions exactes documentaires dans `/workspace/.venvs/wp2spip-docs` ; vérifier les versions et conserver TLS.
- [x] Consulter le code officiel WordPress des versions pertinentes ; noter stockage et références exactes.
- [x] Configurer Material en français, onglets, recherche, thème clair/sombre, blocs copiables et Mermaid.
- [x] Afficher le SHA documenté sur toutes les pages ; exclure `site/` et conserver les exclusions existantes.

### Task 2: Guides et correspondances

**Files:** Create `docs/site/index.md`, `etat-projet.md`, `installer/*.md`, `migrer/*.md`, `correspondances/*.md`, `comprendre/*.md`.
**Interfaces:** Consomme la navigation et la révision du socle ; produit les pages métier liées entre elles.

- [x] Rédiger les deux parcours, la préparation, l’import et les contrôles ; exemples fictifs et dossiers explicites.
- [x] Rédiger les correspondances : source, destination, conservation, limites, état et contrôle.
- [x] Expliquer les traitements, les identifiants, la conversion HTML/blocs, les extensions prévues et les sources.
- [x] Documenter la politique de mise à jour de la matrice à chaque synchronisation du miroir.
- [x] Comparer les états au code et vérifier tous les permaliens de fichiers par existence au commit documenté.

### Task 3: Histoire et couverture WordPress

**Files:** Create `docs/site/wordpress/{modele,wp4,wp5,wp6,wp7,compatibilite}.md`.
**Interfaces:** Consomme les correspondances ; produit une matrice des évolutions et des validations restantes.

- [x] Expliquer SQL, métadonnées, taxonomies et représentation du contenu comme couches distinctes.
- [x] Sourcer WP4 (termes), WP5 (blocs/site), WP6 (compositions/édition du site), WP7 (code exact et notes 7.1).
- [x] Indiquer les données ignorées et l’absence de démonstration de couverture de toute la famille WP4–WP7.
- [x] Ne pas inventer de fonctionnalité WP7 ; décrire les observations confirmées dans ses fichiers officiels.

### Task 4: Vérification du livrable

**Files:** Modify le plan pour les résultats ; sorties ignorées dans `site/`.
**Interfaces:** Consomme tout le site ; produit build statique et bilan de validation.

- [x] Construire avec `python -m mkdocs build --strict` ; attendu : code 0, aucun avertissement.
- [x] Dans une copie temporaire, introduire lien de page inexistant et ancre inexistante séparément ; attendu : chaque build strict non nul et diagnostic correspondant.
- [x] Vérifier une traduction `index.en.md` temporaire en activant anglais dans une copie, ainsi que le repli des pages absentes ; conserver anglais désactivé dans le livrable.
- [x] Vérifier 29 pages de contenu, le pied de page, les onglets, les diagrammes et l’index de recherche ; aucun contenu superpowers.
- [x] Servir la sortie et faire une requête HTTP d’accueil et de page interne ; attendu : 200 et contenu attendu.
- [x] Faire relire le livrable ; corriger les défauts importants puis refaire les vérifications concernées.
- [x] Confirmer que seules documentation/configuration/exclusion des sorties changent ; livrer sans déploiement externe.

## Résultats de validation

- 29 pages françaises ; six onglets ; version anglaise non publiée.
- `mkdocs build --strict` : code 0, aucun avertissement.
- Copie temporaire : lien de page absent et ancre absente → code 1 attendu.
- Copie temporaire : `index.en.md`, construction `/en/`, pied de page anglais et repli français → vérifiés.
- 55 permaliens de fichiers vérifiés par existence au SHA exact ; aucun contenu superpowers/overrides dans le site ou la recherche.
- Chromium : accueil HTTP 200, recherche « galerie » avec résultats, trois diagrammes SVG, écran mobile 390px sans débordement, aucune erreur JavaScript.
- Relecture indépendante : corrections appliquées aux exemples --info, minimum Python, URL de catégories et blocs dynamiques ; instruction de machine retirée des pages publiques.
- Mermaid 11.12.1 local : archive NPM vérifiée SHA512, fichier JS identifié par SHA256, licence MIT conservée.
- Le moteur PHP et les tests applicatifs n’ont pas été modifiés. Leur exécution n’est pas une validation annoncée pour cette tâche documentaire.
- Sources et sortie statique livrées localement, sans déploiement ni push.
