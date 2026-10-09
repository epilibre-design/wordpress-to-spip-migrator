# Polyhiérarchie configurable 1.2.0 : erreurs SQL et erreur fatale selon l'objet configuré, objets rangés jamais comptés

Brouillon de signalement pour `spip-contrib-extensions/polyhierarchie_configurable`, en deux parties indépendantes (chacune avec sa reproduction et son correctif), non publié.

- Plugin : Polyhiérarchie configurable 1.2.0 (préfixe `polyconf`), avec Polyhiérarchie 4.2.0
- Constaté avec : SPIP 4.4, PHP 8.4, SQLite

Les reproductions s'exécutent sur un SPIP 4.4 où Polyhiérarchie et Polyhiérarchie configurable sont actifs, par `spip php:eval "include 'repro.php';"`. Elles configurent `polyhier/lier_objets`, créent leurs objets, puis les suppriment.

## 1. `calculer_rubriques` : erreur SQL pour un objet sans champ `date`

### Constat

`polyconf_calculer_rubriques()` teste `isset($tables_objets[$table]['date'])`. Ce test est toujours vrai : SPIP déclare `'date' => 'date'` par défaut, même pour une table sans ce champ, comme `spip_mots`. La requête sélectionne ensuite `max(fille.date)`, écrit en dur.

- Pour un objet sans champ `date` (mots-clés, auteurs…), la requête échoue (`no such column: fille.date`) : les rubriques ne sont pas recalculées.
- Pour un objet dont la date porte un autre nom, la date retenue est fausse.

### Reproduction

```php
<?php
include_spip('inc/config');
include_spip('inc/polyhier');
include_spip('inc/rubriques');
include_spip('action/editer_objet');
ecrire_config('polyhier/lier_objets', array('spip_mots'));
$id_rubrique = objet_inserer('rubrique', 0, array('titre' => 'Rubrique de mots'));
$id_groupe = objet_inserer('groupe_mots', 0, array('titre' => 'Groupe'));
$id_mot = objet_inserer('mot', $id_groupe, array('titre' => 'Mot rangé'));
polyhier_set_parents($id_mot, 'mot', array($id_rubrique));
calculer_rubriques();
// Nettoyage
sql_delete('spip_rubriques_liens', "objet='mot' AND id_objet=$id_mot");
sql_delete('spip_mots', "id_mot=$id_mot");
sql_delete('spip_groupes_mots', "id_groupe=$id_groupe");
sql_delete('spip_rubriques', "id_rubrique=$id_rubrique");
effacer_config('polyhier/lier_objets');
```

Résultat dans `tmp/log/spip.log` : `Erreur SQL HY000 / 1` … `no such column: fille.date` (requête `… max(fille.date) AS date_h … WHERE rub.date_tmp <= fille.date`).

### Correctif proposé

Dans `polyconf_pipelines.php`, utiliser le champ de date de l'objet seulement s'il existe dans la table, pour la condition comme pour le `max()`. Sans champ de date, la rubrique garde la sienne.

```diff
--- a/polyconf_pipelines.php
+++ b/polyconf_pipelines.php
@@ -86,19 +86,22 @@
 			
 			// On vérifie si l'objet a un statut et une date
 			$where = [];
+			$champ_date = null;
 			if (isset($tables_objets[$table]['statut'][0])) {
 				$champ_statut = $tables_objets[$table]['statut'][0]['champ'];
 				$valeurs = explode(',', $tables_objets[$table]['statut'][0]['publie']);
 				$where[] = "fille.$champ_statut IN ('" . implode("', '", $valeurs) . "')";
 			}
-			if (isset($tables_objets[$table]['date'])) {
+			// SPIP déclare le champ date par défaut, même absent de la table
+			if (isset($tables_objets[$table]['field'][$tables_objets[$table]['date'] ?? ''])) {
 				$champ_date = $tables_objets[$table]['date'];
 				$where[] = "rub.date_tmp <= fille.$champ_date";
 			}
 			$where = implode(' and ', $where);
 			
 			$r = sql_select(
-				'rub.id_rubrique AS id, max(fille.date) AS date_h',
+				// Sans champ de date, la rubrique garde la sienne
+				'rub.id_rubrique AS id, ' . ($champ_date ? "max(fille.$champ_date)" : 'max(rub.date_tmp)') . ' AS date_h',
 				"spip_rubriques AS rub
 								JOIN spip_rubriques_liens as RL ON rub.id_rubrique = RL.id_parent
 								JOIN $table as fille ON (RL.objet='$objet' AND RL.id_objet=fille.$cle)",
```

Vérifié avec ce correctif seul : plus d'erreur SQL dans le journal. Pour un objet avec statut et date (`polyhier/lier_objets` = `spip_articles`, article publié daté du 2020-05-06 rangé dans une seconde rubrique), `calculer_rubriques()` publie la seconde rubrique avec la date de l'article, comme avant le correctif.

## 2. Objets rangés jamais comptés ; le compte provoque une erreur fatale

### Constat

1. `polyconf_objet_compte_enfants()` est définie, mais le pipeline `objet_compte_enfants` n'est pas déclaré dans `paquet.xml`. La fonction n'est jamais appelée, et les objets rangés indirectement ne sont pas comptés : une rubrique qui ne contient que de tels objets passe pour vide, et peut être supprimée.
2. La fonction elle-même échoue :
   - la condition de statut porte sur l'alias `O`, alors que la table de l'objet est jointe sous l'alias `A` (`no such column: O.statut`) ;
   - la condition de post-datation porte sur `A.date`, quel que soit le champ de date de l'objet, et même s'il n'en a pas (`no such column: A.date` pour les mots-clés, quand `post_dates` vaut `non`) ;
   - après l'erreur SQL, `sql_countsel()` rend `''`, et l'addition qui suit provoque une **erreur fatale** sous PHP 8 (`Unsupported operand types: int + string`).

**Les deux points se corrigent ensemble.** Déclarer le pipeline sans corriger la fonction aggrave la situation : avec des mots-clés configurés et `post_dates` à `non`, chaque compte des enfants d'une rubrique provoque l'erreur fatale.

### Reproduction

`post_dates` à `non` :

```php
<?php
include_spip('inc/config');
include_spip('inc/polyhier');
include_spip('action/editer_objet');
ecrire_config('polyhier/lier_objets', array('spip_mots'));
$id_rubrique = objet_inserer('rubrique', 0, array('titre' => 'Rubrique de mots'));
$id_groupe = objet_inserer('groupe_mots', 0, array('titre' => 'Groupe'));
$id_mot = objet_inserer('mot', $id_groupe, array('titre' => 'Mot rangé'));
polyhier_set_parents($id_mot, 'mot', array($id_rubrique));
// Nettoyage en fin de script, même après une erreur fatale
register_shutdown_function(function () use ($id_mot, $id_groupe, $id_rubrique) {
	sql_delete('spip_rubriques_liens', "objet='mot' AND id_objet=$id_mot");
	sql_delete('spip_mots', "id_mot=$id_mot");
	sql_delete('spip_groupes_mots', "id_groupe=$id_groupe");
	sql_delete('spip_rubriques', "id_rubrique=$id_rubrique");
	effacer_config('polyhier/lier_objets');
});

// a. Enfants de la rubrique, par le pipeline
echo json_encode(pipeline('objet_compte_enfants', array('args' => array('objet' => 'rubrique', 'id_objet' => $id_rubrique), 'data' => array()))), "\n";

// b. Enfants publiés, par la fonction du plugin
include_spip('polyconf_pipelines');
echo json_encode(polyconf_objet_compte_enfants(array('args' => array('objet' => 'rubrique', 'id_objet' => $id_rubrique, 'statut' => 'publie'), 'data' => array()))['data']), "\n";
```

Résultat :

- a. `{"site":0,"articles_indirects":0,"rubriques_indirectes":0,"document":0}` : aucune entrée pour les mots, le mot rangé n'est pas compté ;
- b. `PHP Fatal error: Uncaught TypeError: Unsupported operand types: int + string in …/polyconf_pipelines.php:66` (la requête a échoué sur `O.statut`, voir le constat).

### Correctif proposé

- Déclarer le pipeline dans `paquet.xml` :

```diff
 	<pipeline nom="calculer_rubriques" inclure="polyconf_pipelines.php" />
+	<pipeline nom="objet_compte_enfants" inclure="polyconf_pipelines.php" />
```

- Dans `polyconf_pipelines.php` : champ de statut de l'objet sur l'alias `A`, champ de date de l'objet seulement s'il existe dans la table, compte converti en entier.

```diff
--- a/polyconf_pipelines.php
+++ b/polyconf_pipelines.php
@@ -51,19 +51,27 @@
  */
 function polyconf_objet_compte_enfants($flux) {
 	if ($flux['args']['objet'] == 'rubrique' and $liaisons = lire_config('polyhier/lier_objets', [])) {
+		$tables_objets = lister_tables_objets_sql();
 		// Pour chaque objet configuré comme pouvant avoir des rubriques indirectes
 		foreach ($liaisons as $table) {
 			$objet = objet_type($table);
 			$objets = table_objet($table);
 			$cle = id_table_objet($table);
-			$statut = (isset($flux['args']['statut']) ? ' AND O.statut=' . sql_quote($flux['args']['statut']) : '');
-			$postdates = ($GLOBALS['meta']['post_dates'] == 'non') ? ' AND A.date <= ' . sql_quote(date('Y-m-d H:i:s')) : '';
+			// Statut et date de l'objet, s'il en a
+			$champ_statut = $tables_objets[$table]['statut'][0]['champ'] ?? null;
+			// SPIP déclare le champ date par défaut, même absent de la table
+			$champ_date = $tables_objets[$table]['date'] ?? null;
+			if (!isset($tables_objets[$table]['field'][$champ_date])) {
+				$champ_date = null;
+			}
+			$statut = (isset($flux['args']['statut']) and $champ_statut) ? " AND A.$champ_statut=" . sql_quote($flux['args']['statut']) : '';
+			$postdates = ($GLOBALS['meta']['post_dates'] == 'non' and $champ_date) ? " AND A.$champ_date <= " . sql_quote(date('Y-m-d H:i:s')) : '';
 			
 			if (!isset($flux['data']["{$objets}_indirect"])) {
 				$flux['data']["{$objets}_indirect"] = 0;
 			}
 			
-			$flux['data']["{$objets}_indirect"] += sql_countsel(
+			$flux['data']["{$objets}_indirect"] += (int) sql_countsel(
 				"spip_rubriques_liens as RL join $table as A ON (RL.objet='$objet' AND RL.id_objet=A.$cle)",
 				'RL.id_parent=' . $flux['args']['id_objet'] . $statut . $postdates
 			);
```

Vérifié, les deux changements appliqués ensemble :

- a. `{"site":0,"articles_indirects":0,"rubriques_indirectes":0,"document":0,"mots_indirect":1}` : le mot rangé est compté ;
- b. `{"mots_indirect":1}`, sans erreur (les mots n'ont pas de statut) ;
- pour un objet avec statut et date (`spip_articles`, article publié rangé dans une seconde rubrique), le compte vaut 1 pour le statut `publie`, 0 pour `prop`.

Avec la déclaration du pipeline seule : erreur fatale dès a. (requête en échec sur `A.date`). Avec le correctif de la fonction seul : b. est juste, a. ne compte toujours pas le mot.

## Vérification d'ensemble

Les deux parties appliquées ensemble, sur une copie locale du plugin : aucune erreur SQL dans le journal, aucune erreur fatale, et les résultats indiqués dans chaque partie. Chaque partie a aussi été vérifiée seule.

## Impact

- Partie 1 : toute configuration qui range dans les rubriques un objet sans champ `date` empêche le recalcul des rubriques.
- Partie 2 : les rubriques qui ne contiennent que des objets rangés indirectement sont comptées vides ; une fois le pipeline déclaré, tout compte des enfants échoue tant que la fonction n'est pas corrigée.
