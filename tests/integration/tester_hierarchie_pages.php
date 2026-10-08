<?php
/**
 * Teste, sur un SPIP où wp2spip est actif, la hiérarchie des pages quand le Wordpress n'a pas de page enfant
 *
 * Une base SQLite temporaire, déclarée comme base externe, imite un Wordpress dont les pages n'ont pas
 * de parent (et dont une page a pour parent un article) : a2a ne doit pas être requis, et le traitement
 * importer_hierarchie_pages, appelé directement, ne doit rien faire. La base est retirée à la fin.
 *
 * Usage, depuis le site SPIP :
 *   spip php:eval 'include "<wp2spip>/tests/integration/tester_hierarchie_pages.php";'
 * Affiche OK, ou ECHEC et la liste des écarts avec le code de sortie 1.
 */
include_spip('inc/wp2spip_plugins');
include_spip('wp2spip/importer_hierarchie_pages');

$connexion = 'wp2spip_test_sans_enfant';
// Dossier des bases SQLite : _DIR_DB n'est défini qu'à la première connexion SQLite
$fichier_base = (defined('_DIR_DB') ? _DIR_DB : _DIR_ETC . 'bases/') . $connexion . '.sqlite';
$fichier_connexion = _DIR_CONNECT . $connexion . '.php';
$echecs = array();

$pdo = new PDO('sqlite:' . $fichier_base);
$pdo->exec('CREATE TABLE wp_posts (ID INTEGER PRIMARY KEY, post_type TEXT, post_status TEXT, post_password TEXT, post_parent INTEGER, menu_order INTEGER, post_title TEXT, post_content TEXT)');
$pdo->exec('CREATE TABLE wp_comments (comment_ID INTEGER PRIMARY KEY, comment_approved TEXT, comment_type TEXT)');
$pdo->exec("INSERT INTO wp_posts VALUES (1, 'post', 'publish', '', 0, 0, 'Article', ''), (2, 'page', 'publish', '', 0, 0, 'Page', ''), (3, 'page', 'publish', '', 1, 0, 'Page sous un article', '')");
$pdo = null;
file_put_contents($fichier_connexion, "<?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\nspip_connect_db('', '', '', '', '$connexion', 'sqlite3', 'spip', '', '');\n");

try {
	$requis = wp2spip_plugins_requis($connexion);
	echo (isset($requis['a2a']) ? 'ECHEC' : 'ok   ') . " a2a non requis sans page enfant\n";
	if (isset($requis['a2a'])) {
		$echecs[] = 'a2a requis alors qu’aucune page n’a pour parent une page';
	}

	$commande = new stdClass();
	$commande->base = $connexion;
	$commande->output = new Symfony\Component\Console\Output\BufferedOutput();
	$compter_liens = fn() => sql_showtable('spip_articles_lies', true) ? sql_countsel('spip_articles_lies') : 0;
	$liens_avant = $compter_liens();
	$retour = wp2spip_importer_hierarchie_pages_dist($commande);
	$ok = ($retour === null and $commande->output->fetch() === '' and $compter_liens() == $liens_avant);
	echo ($ok ? 'ok   ' : 'ECHEC') . " traitement sans effet sans page enfant\n";
	if (!$ok) {
		$echecs[] = 'importer_hierarchie_pages a agi alors qu’aucune page n’a pour parent une page';
	}
}
finally {
	@unlink($fichier_connexion);
	@unlink($fichier_base);
}

if ($echecs) {
	echo "ECHEC\n- " . join("\n- ", $echecs) . "\n";
	exit(1);
}
echo "OK\n";
