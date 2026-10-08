<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base des tests qui lisent un WordPress : une base SQLite construite à partir de data/wordpress,
 * déclarée comme base externe du SPIP de test, et les médias importés en documents (identifiant = ID WordPress)
 */
abstract class WordpressTestCase extends TestCase
{
	/** Identifiant de la base WordPress de test dans SPIP */
	public const BASE = 'wp2spip_tests';

	/** Dossier WordPress de test : ses médias sont sous wp-content/uploads */
	public const DOSSIER = __DIR__ . '/data/';

	/** Bases construites dans ce processus : SPIP garde leur connexion ouverte */
	private static array $construites = array();

	public static function setUpBeforeClass(): void
	{
		if (!isset(self::$construites[self::BASE])) {
			self::construireBase(self::BASE);
			self::importerDocuments();
		}
	}

	/**
	 * Construit une base WordPress SQLite et la déclare comme base externe de SPIP
	 *
	 * Une seule fois par identifiant et par processus : SPIP garde la connexion ouverte, sur l'ancien fichier.
	 *
	 * @param string $connexion identifiant de la base dans SPIP
	 * @param string $prefixe préfixe des tables
	 * @param array $tables tables à remplir (nom sans préfixe => lignes) ; par défaut, les fichiers de data/wordpress
	 */
	public static function construireBase(string $connexion, string $prefixe = 'wp_', ?array $tables = null): void
	{
		if (isset(self::$construites[$connexion])) {
			throw new \LogicException("Base $connexion déjà construite dans ce processus : choisir un autre identifiant");
		}
		self::$construites[$connexion] = true;
		$fichier = self::fichierBase($connexion);
		@unlink($fichier);
		$pdo = new PDO('sqlite:' . $fichier);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->exec(str_replace('{prefixe}', $prefixe, file_get_contents(__DIR__ . '/data/wordpress/schema.sql')));
		if ($tables === null) {
			$tables = array();
			foreach (glob(__DIR__ . '/data/wordpress/*.php') as $donnees) {
				$tables[basename($donnees, '.php')] = require $donnees;
			}
		}
		foreach ($tables as $table => $lignes) {
			foreach ($lignes as $ligne) {
				$colonnes = array_keys($ligne);
				$requete = $pdo->prepare(
					"INSERT INTO $prefixe$table (" . join(', ', $colonnes) . ') VALUES (' . join(', ', array_fill(0, count($colonnes), '?')) . ')'
				);
				$requete->execute(array_values($ligne));
			}
		}
		$pdo = null;
		file_put_contents(
			_DIR_CONNECT . $connexion . '.php',
			"<?php\nif (!defined('_ECRIRE_INC_VERSION')) return;\nspip_connect_db('', '', '', '', '$connexion', 'sqlite3', 'spip', '', '');\n"
		);
	}

	/**
	 * Retire les fichiers d'une base construite par construireBase() (sa connexion reste ouverte jusqu'à la fin du processus)
	 */
	public static function retirerBase(string $connexion): void
	{
		@unlink(_DIR_CONNECT . $connexion . '.php');
		@unlink(self::fichierBase($connexion));
	}

	public static function fichierBase(string $connexion): string
	{
		// _DIR_DB n'est défini qu'à la première connexion SQLite
		return (defined('_DIR_DB') ? _DIR_DB : _DIR_ETC . 'bases/') . $connexion . '.sqlite';
	}

	/**
	 * Commande d'import imitée, telle que la reçoivent les traitements
	 */
	public static function commande(string $connexion = self::BASE): stdClass
	{
		$commande = new stdClass();
		$commande->base = $connexion;
		$commande->dir_wordpress = self::DOSSIER;
		$commande->output = new BufferedOutput();
		return $commande;
	}

	/**
	 * Médias du WordPress de test importés en documents, s'ils ne le sont pas déjà
	 */
	public static function importerDocuments(): void
	{
		include_spip('wp2spip/importer_documents');
		$commande = self::commande();
		if (wp2spip_importer_documents_dist($commande) === false) {
			throw new \RuntimeException("Import des documents de test impossible :\n" . $commande->output->fetch());
		}
	}
}
