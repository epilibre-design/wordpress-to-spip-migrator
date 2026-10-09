<?php
$fichier = $argv[1] ?? '';
if (!is_readable($fichier) or ($source = file_get_contents($fichier)) === false) {
	fwrite(STDERR, "Impossible de lire $fichier\n");
	exit(1);
}
function wp_config_chaine($jeton) {
	$contenu = substr($jeton, 1, -1);
	if ($jeton[0] === "'") {
		return strtr($contenu, array('\\\\' => '\\', "\\'" => "'"));
	}
	return stripcslashes($contenu);
}
$jetons = array();
foreach (token_get_all($source) as $jeton) {
	if (is_array($jeton) and in_array($jeton[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
		continue;
	}
	$jetons[] = is_array($jeton) ? array($jeton[0], $jeton[1]) : array(null, $jeton);
}
$valeurs = array();
$affectations_prefixe = 0;
$attendus = array('DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST');
foreach ($jetons as $i => $jeton) {
	if (
		$jeton[0] === T_STRING and strtolower($jeton[1]) === 'define'
		and ($jetons[$i + 1][1] ?? '') === '('
		and ($jetons[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING
		and ($jetons[$i + 3][1] ?? '') === ','
		and ($jetons[$i + 4][0] ?? null) === T_CONSTANT_ENCAPSED_STRING
		and ($jetons[$i + 5][1] ?? '') === ')'
		and in_array($nom = wp_config_chaine($jetons[$i + 2][1]), $attendus)
		and !isset($valeurs[$nom])
	) {
		$valeurs[$nom] = wp_config_chaine($jetons[$i + 4][1]);
	}
	if ($jeton[0] === T_VARIABLE and $jeton[1] === '$table_prefix' and ($jetons[$i + 1][1] ?? '') === '=') {
		$affectations_prefixe++;
		if (($jetons[$i + 2][0] ?? null) === T_CONSTANT_ENCAPSED_STRING and ($jetons[$i + 3][1] ?? '') === ';') {
			$valeurs['table_prefix'] = wp_config_chaine($jetons[$i + 2][1]);
		}
	}
}
// Préfixe affecté plusieurs fois (par exemple selon une condition), ou par une valeur calculée : pas de préfixe supposé
if ($affectations_prefixe !== 1) {
	unset($valeurs['table_prefix']);
}
foreach (array_merge($attendus, array('table_prefix')) as $nom) {
	if (!isset($valeurs[$nom])) {
		fwrite(STDERR, "$nom introuvable dans $fichier (valeur littérale attendue)\n");
		exit(1);
	}
	echo "$nom=$valeurs[$nom]\0";
}
