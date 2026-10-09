# sale 1.0.0 : texte entier perdu (motif à retour arrière exponentiel), et avertissements PHP 8 dans `extraire_images()`

Brouillon de ticket pour `spip-contrib-extensions/sale`, non publié.

- Plugin : sale 1.0.0 (paquet téléchargé par SVP, `sale_fonctions.php`)
- Constaté avec : SPIP 4.4, PHP 8.4, en convertissant des contenus WordPress (plugin wp2spip)

## 1. Texte entier perdu : `sale()` rend une chaîne vide

### Constat

Dans `correspondances_standards()`, le motif qui retire les sauts de ligne en fin de texte :

```php
",(\s*(<br( [^>]*)?".'>)?)*\\z,i' => '', //Saut de ligne en fin de texte
```

imbrique deux quantificateurs (`(\s*…)*`) : sur une suite d'espaces qui n'est pas en fin de texte, PCRE essaie toutes les façons de la découper, en nombre exponentiel. Dès une vingtaine d'espaces ou de retours à la ligne consécutifs (avec les réglages par défaut, `pcre.backtrack_limit` = 1 000 000), `preg_replace()` échoue et rend `null` ; les motifs suivants reçoivent `null`, et `sale()` rend une chaîne vide. **Le texte entier est perdu, sans erreur** (seul un avertissement `Deprecated` de PHP 8, ligne 421, le signale).

Augmenter `pcre.backtrack_limit` ne fait que repousser le seuil (à 10 000 000, 22 espaces suffisent).

### Reproduction

```bash
spip php:eval 'include_spip("sale_fonctions"); var_dump(sale("a" . str_repeat(" ", 20) . "b"));'
```

Résultat : `string(0) ""` (attendu : `"a b"` ou équivalent).

Le motif seul :

```bash
php -r 'foreach (array(10, 20) as $n) { var_dump(preg_replace(",(\s*(<br( [^>]*)?>)?)*\z,i", "", "a" . str_repeat(" ", $n) . "b")); echo preg_last_error_msg(), "\n"; }'
```

Résultat : `"a          b"` puis `NULL`, `Backtrack limit exhausted`.

Sur les 103 contenus du jeu de test *Theme Unit Test* importé dans un WordPress 6.9, deux (34 « WP 6.1 Widgets block category » et 51 « WP 6.1 Theme block category ») sont convertis en chaîne vide. Ces 103 textes, leur origine et la commande qui rejoue la vérification sont dans l'annexe [`sale-corpus-theme-unit-test.md`](sale-corpus-theme-unit-test.md).

### Correctif proposé

Même motif, sans imbrication, avec un quantificateur possessif (aucun retour arrière) : il retire la même chose (espaces et `<br>` en fin de texte).

```diff
-		",(\s*(<br( [^>]*)?".'>)?)*\\z,i' => '', //Saut de ligne en fin de texte
+		",(?:\s|<br(?: [^>]*)?".'>)*+\\z,i' => '', //Saut de ligne en fin de texte
```

Vérifié : les 103 contenus de l'annexe convertis avant et après le correctif ; seuls les deux textes perdus changent (ils sont désormais convertis), les 101 autres sont identiques à l'octet. Tous les autres motifs de `correspondances_standards()` ont été essayés sur de longues suites d'espaces, de retours à la ligne et de `<br>` : aucun autre n'échoue.

## 2. `extraire_images()` : avertissements PHP 8 pour chaque image

### Constat

Pour chaque motif (`<img>`, `<object>`), la boucle qui remplace les balises trouvées parcourt les morceaux de texte (`preg_split()`, N+1 morceaux pour N balises) au lieu des balises : au dernier tour, elle lit `$tagMatches[N]`, qui n'existe pas.

```php
$textMatches = preg_split($pattern, $texte);
foreach ($textMatches as $key => $value) {
	$tagMatches[$key][0] = retrouve_document($tagMatches[$key][1], $tagMatches[$key][0], $tagMatches[$key][2], $shortcut);
}
```

Sous PHP 8, chaque texte qui contient une image produit `Warning: Undefined array key 1` et `Trying to access array offset on null` (ligne 317), puis `Deprecated: preg_match_all(): Passing null to parameter #2` (ligne 15, `tag2attributs()` appelée avec `null` par `retrouve_document()`). Le résultat reste juste (l'élément ajouté en trop à `$tagMatches` n'est pas réinséré), mais une conversion par lots affiche ces avertissements par centaines.

### Reproduction

```bash
spip php:eval 'error_reporting(E_ALL); include_spip("sale_fonctions"); echo sale("<p>Texte <img src=\"a.jpg\" alt=\"\"> suite</p>"), "\n";'
```

Résultat : 8 avertissements (4 `Warning` ligne 317, puis `Deprecated` ligne 15), puis `Texte <img src="a.jpg" alt=""> suite`.

Sur les 103 contenus de l'annexe : 96 avertissements de ce type.

### Correctif proposé

Parcourir les balises trouvées ; l'assemblage qui suit est inchangé (`implode()` reprend le dernier morceau de texte).

```diff
-			foreach ($textMatches as $key => $value) {
-				$tagMatches[$key][0] = retrouve_document($tagMatches[$key][1], $tagMatches[$key][0], $tagMatches[$key][2], $shortcut);
+			foreach ($tagMatches as $key => $value) {
+				$tagMatches[$key][0] = retrouve_document($value[1], $value[0], $value[2], $shortcut);
 			}
```

Vérifié : même texte converti, sans avertissement, pour la reproduction, pour un texte à deux images et un `<object>`, et pour les 103 contenus de l'annexe (identiques à l'octet ; restent 2 avertissements, ceux des deux textes perdus du point 1).

## Correctif complet

Les deux correctifs, appliqués ensemble : 0 avertissement et aucun texte perdu sur les 103 contenus de l'annexe (avant : 98 avertissements, dont 2 `Deprecated` ligne 421 dus aux deux textes perdus, et 2 textes perdus).

```diff
--- a/sale_fonctions.php
+++ b/sale_fonctions.php
@@ -70,7 +70,7 @@
 		",(<\/no p>)(.*)(<no p[^>]*>),Uims" => '\\2', // spiperie
 		",\s*?<br( [^>]*)?".">\r-(&nbsp;|\s),Ui" => "\r- ",
 		",\s*?<br( [^>]*)?".">[[[:blank:]]*?(?=[^\s]),Ui" => "\r_ ", //Saut de ligne style suivi par du texte
-		",(\s*(<br( [^>]*)?".'>)?)*\\z,i' => '', //Saut de ligne en fin de texte
+		",(?:\s|<br(?: [^>]*)?".'>)*+\\z,i' => '', //Saut de ligne en fin de texte
 		',<hr( [^>]*)?'.'>,Uims' => "\r----\r", //Saut de page
 
 		',<(pre)( [^>]*)?'.'>(.+)</\\1>,Uims' => "<poesie>\n\\3\n</poesie>", //Poesie
@@ -313,8 +313,8 @@
 		preg_match_all($pattern, $texte, $tagMatches, PREG_SET_ORDER);
 		if ($tagMatches) {
 			$textMatches = preg_split($pattern, $texte);
-			foreach ($textMatches as $key => $value) {
-				$tagMatches[$key][0] = retrouve_document($tagMatches[$key][1], $tagMatches[$key][0], $tagMatches[$key][2], $shortcut);
+			foreach ($tagMatches as $key => $value) {
+				$tagMatches[$key][0] = retrouve_document($value[1], $value[0], $value[2], $shortcut);
 			}
 			for ($i = 0; $i < count($tagMatches); ++$i ) {
 				$textMatches[$i] = $textMatches[$i] . $tagMatches[$i][0];
```

## Impact

- Point 1 : perte silencieuse de contenus entiers lors d'une conversion (plugin wp2spip : article importé avec un texte vide). En attendant le correctif, wp2spip appelle `sale()` avec sa propre table de correspondances, où seul ce motif est remplacé.
- Point 2 : bruit dans les journaux et les sorties de commande ; aucun effet sur le résultat.
