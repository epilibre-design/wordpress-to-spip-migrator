# wp2spip — sous-projet 10 : signalements aux plugins tiers

Date : 2026-10-09
Spec d'ensemble : `2026-10-08-wp2spip-ensemble-design.md`, § 6, sous-projet 10.
Statut : réalisé (brouillons non publiés) ; le ticket de sale comprend en plus la perte de textes entiers, trouvée en reproduisant (au-delà du § 5).

## 1. Constat

Deux plugins utilisés autour de wp2spip ont des défauts relevés pendant les sous-projets précédents :

1. **sale** 1.0.0 (`spip-contrib-extensions/sale`), `extraire_images()` (`sale_fonctions.php`) : la boucle qui remplace chaque balise `<img>` (ou `<object>`) trouvée parcourt les morceaux de texte (`preg_split`, N+1 morceaux) au lieu des balises (N) ; au dernier tour, elle lit `$tagMatches[N]`, qui n'existe pas. Sous PHP 8 : `Warning: Undefined array key 1` puis `Trying to access array offset on null` à la ligne 317, pour chaque texte qui contient une image ; `retrouve_document()` est appelée avec des valeurs nulles, et un élément vide est ajouté à `$tagMatches`. Le résultat reste juste (l'élément en trop n'est pas réinséré), d'où un défaut discret mais bruyant : un import de wp2spip affiche ces avertissements par centaines.
2. **Polyhiérarchie configurable** 1.2.0 (`spip-contrib-extensions/polyhierarchie_configurable`, préfixe `polyconf`), qui étend Polyhiérarchie aux objets choisis dans `polyhier/lier_objets` :
   - `polyconf_objet_compte_enfants()` est définie dans `polyconf_pipelines.php` mais le pipeline `objet_compte_enfants` n'est pas déclaré dans `paquet.xml` : les objets rangés indirectement ne sont jamais comptés (une rubrique qui n'a que de tels enfants passe pour vide, et peut être supprimée) ;
   - dans cette fonction, la condition de statut porte sur l'alias `O`, alors que la table jointe a l'alias `A` (erreur SQL dès qu'un statut est demandé), et la condition de post-datation porte sur `A.date`, quel que soit le champ de date de l'objet ;
   - `polyconf_calculer_rubriques()` lit le champ de date déclaré de l'objet pour sa condition, mais sélectionne `max(fille.date)` en dur : erreur SQL pour un objet sans champ `date` (mots-clés, auteurs…), date fausse pour un objet dont la date a un autre nom.

Ces plugins ne sont pas modifiés par wp2spip ; leurs mainteneurs sont prévenus par des tickets.

## 2. Décisions (prises seul)

| Sujet | Décision |
|---|---|
| Forme | un **brouillon de ticket par plugin**, en Markdown, dans le dépôt de wp2spip : `docs/signalements/sale-extraire-images.md`, `docs/signalements/polyhierarchie-configurable.md` |
| Publication | **aucune** : les tickets ne sont ni postés ni proposés en demande de fusion sans l'accord du mainteneur de wp2spip (décision du 2026-10-09) |
| Contenu d'un ticket | titre ; version et fichier concernés ; constat ; reproduction minimale, lancée sur un SPIP 4.4 et son résultat réel ; cause ; correctif proposé sous forme de diff, vérifié sur une copie locale du plugin ; impact |
| Reproduction | sur le SPIP de test des tests d'intégration (`vendor/spip/spip`, sous-projet 7) : sale y est installé ; Polyhiérarchie configurable y est ajouté le temps de la vérification puis retiré |
| Langue | français, comme les dépôts concernés |

## 3. Tickets

### 3.1 sale

- Reproduction : `spip php:eval 'error_reporting(E_ALL); include_spip("sale_fonctions"); echo sale("<p>Texte <img src=\"a.jpg\" alt=\"\"> suite</p>");'` : avertissements de la ligne 317, puis le texte converti.
- Correctif : la boucle parcourt `$tagMatches` (`foreach ($tagMatches as $key => $value)`), et l'assemblage ajoute le dernier morceau de texte ; le résultat de `sale()` est identique avant et après (vérifié sur les cas de la reproduction et sur les 20 cas de conversion de wp2spip, `BlocsTest`), sans avertissement.

### 3.2 Polyhiérarchie configurable

- Reproduction, avec `polyhier/lier_objets` = `spip_mots` (objet du cœur sans champ `date`), un mot rangé dans une rubrique par `polyhier_set_parents()` : `calculer_rubriques()` produit une erreur SQL (`fille.date`) ; `pipeline('objet_compte_enfants', …)` pour cette rubrique ne compte pas le mot.
- Correctif : déclaration du pipeline dans `paquet.xml` ; alias `A` pour le statut, champ de date de l'objet (s'il en a un) pour la post-datation et pour le `max()` de `calculer_rubriques` (sans champ de date : date de la rubrique gardée) ; vérifié sur la reproduction.

## 4. Validation

- Chaque reproduction lancée, sortie réelle reportée dans le ticket ; chaque correctif appliqué à une copie locale du plugin dans le SPIP de test : plus d'erreur ni d'avertissement, résultats attendus.
- Les tests de wp2spip restent à OK (le plugin sale de leur SPIP de test est rétabli tel que téléchargé).
- Aucun ticket publié.

## 5. Hors périmètre

- Publication des tickets et demandes de fusion.
- Autres défauts de ces plugins.
