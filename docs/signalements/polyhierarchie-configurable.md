# Polyhiérarchie configurable 1.2.0 : objets rangés jamais comptés, erreurs SQL et erreur fatale selon l'objet configuré

Brouillon de ticket pour `spip-contrib-extensions/polyhierarchie_configurable`, non publié.

- Plugin : Polyhiérarchie configurable 1.2.0 (préfixe `polyconf`), avec Polyhiérarchie 4.2.0
- Constaté avec : SPIP 4.4, PHP 8.4, SQLite

## Constat

Le plugin étend Polyhiérarchie aux objets choisis dans `polyhier/lier_objets`. Dans `polyconf_pipelines.php` :

1. `polyconf_objet_compte_enfants()` est définie, mais le pipeline `objet_compte_enfants` n'est pas déclaré dans `paquet.xml` : la fonction n'est jamais appelée, et les objets rangés indirectement ne sont pas comptés. Une rubrique qui n'a que de tels enfants passe pour vide (et peut être supprimée).
2. Dans cette fonction, la condition de statut porte sur l'alias `O`, alors que la table de l'objet est jointe sous l'alias `A` : dès qu'un statut est demandé, la requête échoue (`no such column: O.statut`), `sql_countsel()` rend `''`, et l'addition qui suit provoque une **erreur fatale** sous PHP 8 (`Unsupported operand types: int + string`). La condition de post-datation porte sur `A.date`, quel que soit le champ de date de l'objet, et même s'il n'en a pas.
3. `polyconf_calculer_rubriques()` teste `isset($tables_objets[$table]['date'])`, toujours vrai (SPIP déclare `'date' => 'date'` par défaut, même pour une table sans ce champ, comme `spip_mots`), puis sélectionne `max(fille.date)` en dur : pour un objet sans champ `date` (mots-clés, auteurs…), `calculer_rubriques()` échoue (`no such column: fille.date`) et les rubriques ne sont pas recalculées ; pour un objet dont la date a un autre nom, la date retenue est fausse.

## Reproduction

Sur un SPIP 4.4 avec Polyhiérarchie et Polyhiérarchie configurable actifs, `post_dates` à `non`, script lancé par `spip php:eval "include 'repro.php';"` :

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

// 1. Recalcul des rubriques
calculer_rubriques();

// 2. Enfants de la rubrique
echo json_encode(pipeline('objet_compte_enfants', array('args' => array('objet' => 'rubrique', 'id_objet' => $id_rubrique), 'data' => array()))), "\n";

// 3. Enfants publiés, par la fonction du plugin
include_spip('polyconf_pipelines');
echo json_encode(polyconf_objet_compte_enfants(array('args' => array('objet' => 'rubrique', 'id_objet' => $id_rubrique, 'statut' => 'publie'), 'data' => array()))), "\n";
```

Résultat :

1. dans `tmp/log/spip.log` : `Erreur SQL HY000 / 1` … `no such column: fille.date` (requête `… max(fille.date) AS date_h … WHERE rub.date_tmp <= fille.date`) ;
2. `{"site":0,"articles_indirects":0,"rubriques_indirectes":0,"document":0}` : aucune entrée pour les mots, le mot rangé n'est pas compté ;
3. `no such column: O.statut` dans le journal, puis `PHP Fatal error: Uncaught TypeError: Unsupported operand types: int + string in …/polyconf_pipelines.php:66`.

## Correctif proposé

- Déclarer le pipeline dans `paquet.xml` :

```diff
 	<pipeline nom="calculer_rubriques" inclure="polyconf_pipelines.php" />
+	<pipeline nom="objet_compte_enfants" inclure="polyconf_pipelines.php" />
```

- Dans `polyconf_pipelines.php` : champ de statut de l'objet sur l'alias `A`, champ de date de l'objet seulement s'il existe dans la table (pour la post-datation et pour le `max()` de `calculer_rubriques` ; sans champ de date, la rubrique garde la sienne), compte converti en entier :

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
@@ -86,19 +94,22 @@
 			
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

Vérifié sur une copie locale du plugin, avec le script de reproduction : plus d'erreur SQL (journal vide) ni d'erreur fatale ; `{"site":0,"articles_indirects":0,"rubriques_indirectes":0,"mots_indirect":1,"document":0}` (le mot rangé est compté), et `{"mots_indirect":1}` avec un statut (les mots n'en ont pas). Cas d'un objet avec statut et date (`polyhier/lier_objets` = `spip_articles`, article publié daté du 2020-05-06 rangé dans une seconde rubrique) : `calculer_rubriques()` publie la seconde rubrique avec la date de l'article, et le compte vaut 1 pour le statut `publie`, 0 pour `prop`.

## Impact

Toute configuration qui range dans les rubriques un objet sans champ `date` empêche le recalcul des rubriques ; les rubriques qui ne contiennent que des objets rangés indirectement sont comptées vides ; tout appel du compte avec un statut provoque une erreur fatale sous PHP 8, une fois le pipeline déclaré.
