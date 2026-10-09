<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class WordpressImporter extends Command {
	public $input = null;
	public $output = null;
	public $dir_wordpress = null;
	public $wp_version = null;
	public $base = 'wordpress';
	public $garder_adresse = false;
	public $prefixe = 'wp_';
	// Exécutable SPIP-Cli en cours, pour lancer des sous-commandes
	protected $spip_cli = '';
	
	protected function configure(): void {
		$this
			->setName('wordpress:importer')
			->setDescription('Importe un site Wordpress dans un site SPIP')
			->setHelp('Pour lancer la commande, vous devez avoir préalablement ajouté la base de données du Wordpress en tant que base externe dans votre SPIP, et fournir en argument le dossier où se trouve les fichiers du Wordpress.

Lorsqu’un contenu est déjà importé (auteur, article, etc), une trace est gardée et il ne sera jamais réimporté : on peut relancer la commande, ou un traitement seul. L’import part d’un Wordpress figé. Pour refaire un import (import interrompu, nouvelle version de wp2spip), remettre le SPIP à zéro, puis relancer un import complet.')
			->addArgument(
				'dir_wordpress',
				InputArgument::REQUIRED,
				'Chemin vers le dossier d’installation du Wordpress'
			)
			->addOption(
				'base',
				'b',
				InputOption::VALUE_OPTIONAL,
				'Identifiant de la base Wordpress déclarée dans SPIP',
				'wordpress'
			)
			->addOption(
				'traitements',
				't',
				InputOption::VALUE_OPTIONAL,
				'Liste de traitements séparés par des virgules, si on veut n’en lancer que certains.'
			)
			->addOption(
				'info',
				'i',
				InputOption::VALUE_OPTIONAL,
				'Affiche la version du Wordpress et les traitements disponibles.'
			)
			->addOption(
				'prefixe',
				null,
				InputOption::VALUE_REQUIRED,
				'Préfixe des tables Wordpress, s’il diffère de celui de wp-config.php ou n’y est pas lisible.'
			)
			->addOption(
				'garder-adresse',
				null,
				InputOption::VALUE_NONE,
				'Ne pas remplacer l’adresse du site SPIP par celle du Wordpress.'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		global $spip_racine;
		global $spip_loaded;
		
		// Facilité
		$this->input = $input;
		$this->output = $output;
		$this->spip_cli = realpath($_SERVER['argv'][0] ?? '') ?: ($_SERVER['argv'][0] ?? 'spip');
		
		// Si on n'est pas dans un dossier SPIP, on ne peut rien faire
		if (!$spip_loaded) {
			$output->writeln('<error>Vous devez lancer la commande depuis un site SPIP pour importer le contenu Wordpress.</error>');
			return Command::FAILURE;
		}
		
		// Dossier sur le disque où se trouve les fichiers du Wordpress
		$this->dir_wordpress = rtrim($input->getArgument('dir_wordpress'), '/') . '/';
		
		// Identifiant de la base Wordpress dans SPIP
		$this->base = $input->getOption('base');
		
		// Garder l'adresse du site SPIP (import dans un site déjà à sa future adresse)
		$this->garder_adresse = $input->getOption('garder-adresse');
		
		// On va chercher la version de Wordpress dont il s'agit
		$fichier_version = $this->dir_wordpress . 'wp-includes/version.php';
		if (!is_readable($fichier_version)) {
			$output->writeln("<error>Impossible de lire $fichier_version : est-ce bien le dossier d’un Wordpress ?</error>");
			return Command::FAILURE;
		}
		include $fichier_version;
		$this->wp_version = $wp_version;
		
		// Préfixe des tables Wordpress : lu dans wp-config.php ou donné par --prefixe, puis contrôlé
		if (($code = $this->determiner_prefixe()) !== null) {
			return $code;
		}
		
		$traitements_disponibles = array(
			'importer_metas',
			'importer_auteurs',
			'importer_rubriques',
			'importer_documents',
			'importer_articles',
			'importer_hierarchie_pages',
			'importer_mots',
			'importer_acces',
			'importer_polyhierarchie',
			'importer_commentaires',
		);
		$traitements_disponibles = pipeline('wp2spip_traitements', $traitements_disponibles);
		// Ancien nom du pipeline, gardé pour les extensions qui l'utilisent
		$traitements_disponibles = pipeline('w2spip_traitements', $traitements_disponibles);
		
		// Infos
		$output->writeln(array(
			'<info>C’est parti pour importer ce Wordpress !</info>',
			'* <comment>Les contenus déjà importés ne sont pas ré-importés.</comment>',
			'* <comment>Version</comment> : ' . $this->wp_version,
			'* <comment>Base</comment> : ' . $this->base,
			'* <comment>Préfixe des tables</comment> : ' . $this->prefixe,
			'* <comment>Fichiers</comment> : ' . $this->dir_wordpress,
			'* <comment>Traitements disponibles</comment> : ' . join(', ', $traitements_disponibles),
			'',
		));
		
		// Si on cherche juste à lire les infos, on s'arrête là
		if ($input->hasParameterOption(array('--info', '-i'))) {
			return Command::SUCCESS;
		}
		
		// Préfixe contrôlé : noté pour ce SPIP, tous les traitements le lisent (wp2spip_table())
		include_spip('inc/meta');
		ecrire_meta('wp2spip_prefixe_tables', $this->prefixe);
		
		// Peut-être qu'on veut lancer seulement certains traitements
		if ($traitements_ok = $input->getOption('traitements')) {
			$traitements_ok = array_filter(array_map('trim', explode(',', $traitements_ok)));
			if ($inconnus = array_diff($traitements_ok, $traitements_disponibles)) {
				$output->writeln('<error>Traitements inconnus : ' . join(', ', $inconnus) . '. Traitements disponibles : ' . join(', ', $traitements_disponibles) . '</error>');
				return Command::FAILURE;
			}
			// Toujours dans l'ordre de la liste : les traitements dépendent des précédents
			$traitements_ok = array_values(array_intersect($traitements_disponibles, $traitements_ok));
		}
		else {
			$traitements_ok = $traitements_disponibles;
		}
		
		// Plugins requis par le contenu : téléchargés et activés avant le premier traitement, quels que soient
		// les traitements demandés ; l'import est alors relancé, et son code devient celui de la commande
		if (($code = $this->verifier_plugins()) !== null) {
			return $code;
		}
		
		// Un traitement en échec arrête l'import : les suivants dépendent de lui (les articles des rubriques…)
		foreach ($traitements_ok as $traitement) {
			if (!$this->appliquer_traitement($traitement)) {
				$output->writeln("\n<error>Import arrêté : le traitement « $traitement » a échoué.</error>");
				return Command::FAILURE;
			}
		}
		
		return Command::SUCCESS;
	}
	
	/**
	 * Préfixe des tables Wordpress : option --prefixe, sinon $table_prefix de wp-config.php, puis contrôles
	 *
	 * Format (règle de Wordpress), présence de toutes les tables lues par wp2spip, et cohérence avec un import
	 * déjà commencé dans ce SPIP. Contrôlé avant tout traitement et avant la vérification des plugins requis,
	 * qui lit déjà les tables.
	 *
	 * @return int|null null si le préfixe est utilisable, sinon le code de sortie de la commande
	 */
	protected function determiner_prefixe(): ?int {
		include_spip('inc/wp2spip');
		$fichier_config = $this->dir_wordpress . 'wp-config.php';
		$source = is_readable($fichier_config) ? file_get_contents($fichier_config) : false;
		$prefixe_config = ($source !== false) ? wp2spip_prefixe_wp_config($source) : null;
		
		if (($option = $this->input->getOption('prefixe')) !== null) {
			$this->prefixe = $option;
			$origine = 'option --prefixe' . (($prefixe_config !== null and $prefixe_config !== $option) ? " ; wp-config.php annonce $prefixe_config" : '');
		}
		elseif ($prefixe_config !== null) {
			$this->prefixe = $prefixe_config;
			$origine = 'wp-config.php';
		}
		else {
			$this->output->writeln('<error>Préfixe des tables Wordpress introuvable dans wp-config.php : indiquer --prefixe.</error>');
			return Command::FAILURE;
		}
		
		if (!wp2spip_prefixe_valide($this->prefixe)) {
			$this->output->writeln("<error>Préfixe des tables « {$this->prefixe} » invalide : lettres, chiffres et _ seulement.</error>");
			return Command::FAILURE;
		}
		$this->output->writeln("Préfixe des tables : {$this->prefixe} ($origine).");
		
		if ($absentes = wp2spip_tables_wordpress_absentes($this->base, $this->prefixe)) {
			$this->output->writeln('<error>Tables absentes de la base Wordpress « ' . $this->base . ' » : ' . join(', ', $absentes) . '. Vérifier le préfixe (option --prefixe).</error>');
			return Command::FAILURE;
		}
		
		$prefixe_importe = $GLOBALS['meta']['wp2spip_prefixe_tables'] ?? null;
		if ($prefixe_importe !== null and $prefixe_importe !== $this->prefixe) {
			$this->output->writeln("<error>Ce SPIP a été importé depuis les tables $prefixe_importe ; pour importer depuis {$this->prefixe}, remettre le SPIP à zéro.</error>");
			return Command::FAILURE;
		}
		return null;
	}
	
	protected function appliquer_traitement($traitement): bool {
		$decoupe_version = explode('.', $this->wp_version);
		$version_X = $decoupe_version[0];
		$version_Y = $decoupe_version[1] ?? 0;
		
		$fonction = '';
		// S'il existe une fonction wp2spip_$fonction_X_Y
		if ($f = charger_fonction("{$version_X}_{$version_Y}", "wp2spip/$traitement", true)) {
			$fonction = $f;
		}
		// S'il existe une fonction wp2spip_$fonction_X
		elseif ($f = charger_fonction("$version_X", "wp2spip/$traitement", true)) {
			$fonction = $f;
		}
		// Sinon une fonction générique wp2spip_$fonction qui vaudrait pour toutes les versions
		elseif ($f = charger_fonction("$traitement", "wp2spip", true)) {
			$fonction = $f;
		}
		// Sinon rien, on peut pas faire cette opération
		else {
			$this->output->writeln("\n<error>Aucune fonction implémentée pour le traitement « $traitement » pour cette version {$this->wp_version}.</error>");
			return false;
		}
		
		// On lance le traitement trouvé : il signale un échec en retournant false
		$this->output->writeln("\n<info>Lancement du traitement « $traitement »…</info>");
		return $fonction($this) !== false;
	}
	
	/**
	 * Télécharge et active les plugins requis par le contenu Wordpress, puis relance l'import
	 *
	 * Le processus en cours ne connaît pas un plugin activé après son démarrage (tables, API, pipelines) :
	 * l'import est relancé dans un processus neuf, une seule fois (variable d'environnement WP2SPIP_RELANCE).
	 *
	 * @return int|null null pour continuer l'import, sinon le code de sortie de la commande
	 */
	protected function verifier_plugins(): ?int {
		include_spip('inc/wp2spip_plugins');
		include_spip('inc/meta');
		$manquants = array_filter(
			wp2spip_plugins_requis($this->base),
			fn($plugin, $prefixe) => !wp2spip_plugin_pret($prefixe, $plugin),
			ARRAY_FILTER_USE_BOTH
		);
		if (!$manquants) {
			// Après l'installation (relance), toutes les tables déclarées doivent exister
			if (getenv('WP2SPIP_RELANCE') and $tables = wp2spip_tables_manquantes()) {
				$this->output->writeln('<error>Tables ou champs absents de la base après l’installation des plugins : ' . join(', ', $tables) . '.</error>');
				return Command::FAILURE;
			}
			return null;
		}
		if (getenv('WP2SPIP_RELANCE')) {
			return $this->echec_plugins($manquants, 'toujours inactifs, ou sans leurs tables, après leur installation');
		}
		foreach ($manquants as $prefixe => $plugin) {
			$this->output->writeln("<info>Plugin requis : {$plugin['nom']} ($prefixe), pour {$plugin['raison']}.</info>");
		}
		// Plugins actifs avant : SPIP désactive un plugin qui utilise un plugin requis dont l'activation échoue
		$actifs_avant = array_keys(@unserialize($GLOBALS['meta']['plugin'] ?? '') ?: array());
		
		// Pour télécharger, SVP demande le dossier plugins/auto et un dépôt : sur un SPIP qui ne les a pas
		// (installé sans outils/preparer_spip.sh), on les prépare avec les commandes de SPIP-Cli
		$a_telecharger = array_filter(array_keys($manquants), fn($prefixe) => !wp2spip_plugin_present($prefixe));
		if ($a_telecharger and ($code = $this->preparer_telechargement($manquants)) !== null) {
			return $code;
		}
		
		// Un appel de plugins:svp:telecharger par plugin : dans un même appel, SPIP-Cli retente les téléchargements
		// des plugins précédents. Son code de sortie ne dit pas si le téléchargement a réussi : le plugin est cherché sur le disque.
		foreach ($a_telecharger as $prefixe) {
			$this->lancer_spip_cli(array('plugins:svp:telecharger', $prefixe, '-y'));
			if (!wp2spip_plugin_present($prefixe)) {
				return $this->echec_plugins($manquants, "$prefixe absent après plugins:svp:telecharger");
			}
			// SVP l'a installé dans un processus qui ne connaissait pas encore ses tables : la version de son schéma
			// est notée sans que ses tables soient créées. Effacée, elle fait installer le plugin par plugins:maj:bdd.
			effacer_meta($prefixe . '_base_version');
		}
		if (($code = $this->activer_plugins($manquants)) !== null) {
			return $code;
		}
		
		// plugins:activer n'active pas les dépendances d'un plugin déjà présent (Champs Extras sans Saisies…) : SVP
		// les active, en téléchargeant celles qui manquent
		if ($inactifs = $this->plugins_inactifs(array_keys($manquants))) {
			if (($code = $this->preparer_telechargement($manquants)) !== null) {
				return $code;
			}
			foreach ($inactifs as $prefixe) {
				$this->lancer_spip_cli(array('plugins:svp:telecharger', $prefixe, '-y'));
			}
			if (($code = $this->activer_plugins($manquants)) !== null) {
				return $code;
			}
			if ($inactifs = $this->plugins_inactifs(array_keys($manquants))) {
				return $this->echec_plugins($manquants, 'inactifs après plugins:activer et plugins:svp:telecharger : ' . join(', ', $inactifs));
			}
		}
		if ($desactives = $this->plugins_inactifs($actifs_avant)) {
			return $this->echec_plugins($manquants, 'plugins désactivés par l’activation : ' . strtolower(join(', ', $desactives)));
		}
		
		$this->output->writeln("\n<info>Plugins requis installés : l’import est relancé.</info>\n");
		return $this->lancer_spip_cli(array_slice($_SERVER['argv'], 1), array('WP2SPIP_RELANCE' => '1'));
	}
	
	/**
	 * Activation des plugins requis, puis installation de leurs tables
	 *
	 * @param array $manquants plugins requis non prêts
	 * @return int|null null si les commandes ont réussi, sinon le code d'échec
	 */
	protected function activer_plugins(array $manquants): ?int {
		if (
			$this->lancer_spip_cli(array_merge(array('plugins:activer'), array_keys($manquants), array('-y'))) !== 0
			or $this->lancer_spip_cli(array('plugins:maj:bdd')) !== 0
		) {
			return $this->echec_plugins($manquants, 'échec de plugins:activer ou de plugins:maj:bdd');
		}
		return null;
	}
	
	/**
	 * Plugins inactifs selon la base : les commandes de SPIP-Cli, lancées dans d'autres processus, y notent les
	 * plugins actifs (le code de sortie de plugins:activer ne dit pas si l'activation a réussi)
	 *
	 * @param array $prefixes
	 * @return array préfixes des plugins inactifs
	 */
	protected function plugins_inactifs(array $prefixes): array {
		include_spip('inc/meta');
		lire_metas();
		$actifs = @unserialize($GLOBALS['meta']['plugin'] ?? '') ?: array();
		return array_values(array_filter($prefixes, fn($prefixe) => !isset($actifs[strtoupper($prefixe)])));
	}
	
	/**
	 * Dossier plugins/auto et dépôt de plugins, préparés s'ils manquent
	 *
	 * Seul plugins/auto est créé, avec les droits de plugins/ : core:preparer --auto alignerait aussi les droits
	 * de config, IMG, local et tmp, ce qui pourrait les rendre inaccessibles au serveur web d'un site déjà installé.
	 *
	 * @param array $manquants plugins requis non prêts
	 * @return int|null null si le téléchargement est possible, sinon le code d'échec
	 */
	protected function preparer_telechargement(array $manquants): ?int {
		include_spip('inc/plugin');
		if (!is_dir(_DIR_PLUGINS_AUTO)) {
			$droits = fileperms(_DIR_PLUGINS) & 0777;
			$this->output->writeln('<info>Création de plugins/auto, où SVP télécharge les plugins (droits ' . decoct($droits) . ').</info>');
			if (@mkdir(_DIR_PLUGINS_AUTO)) {
				@chmod(_DIR_PLUGINS_AUTO, $droits);
			}
			clearstatcache();
		}
		if (!is_dir(_DIR_PLUGINS_AUTO) or !is_writable(_DIR_PLUGINS_AUTO)) {
			return $this->echec_plugins($manquants, 'plugins/auto absent ou non accessible en écriture');
		}
		if (!sql_countsel('spip_depots')) {
			$this->output->writeln('<info>Aucun dépôt de plugins : ajout de ' . _WP2SPIP_DEPOT_SVP . '.</info>');
			$this->lancer_spip_cli(array('plugins:svp:depoter', _WP2SPIP_DEPOT_SVP));
			if (!sql_countsel('spip_depots')) {
				return $this->echec_plugins($manquants, 'dépôt de plugins impossible à ajouter');
			}
		}
		return null;
	}
	
	/**
	 * Échec de l'installation des plugins requis : les commandes à lancer à la main
	 *
	 * @param array $manquants préfixe => description (wp2spip_plugins_requis())
	 * @param string $raison
	 * @return int
	 */
	protected function echec_plugins(array $manquants, string $raison): int {
		$prefixes = join(' ', array_keys($manquants));
		$lignes = array(
			"<error>Plugins requis par le contenu Wordpress non installés ($raison) : $prefixes.</error>",
			'Aucun traitement n’a été lancé. Pour les installer à la main, depuis le dossier du SPIP :',
			'  mkdir plugins/auto    (si plugins/auto n’existe pas ; accessible en écriture)',
			'  spip plugins:svp:depoter ' . _WP2SPIP_DEPOT_SVP . '    (si aucun dépôt n’est déclaré)',
		);
		foreach ($manquants as $prefixe => $plugin) {
			if (empty($plugin['dist']) and !wp2spip_plugin_present($prefixe)) {
				$lignes[] = "  spip plugins:svp:telecharger $prefixe -y";
				$lignes[] = "  spip php:eval 'include_spip(\"inc/meta\"); effacer_meta(\"{$prefixe}_base_version\");'";
			}
		}
		$lignes[] = "  spip plugins:activer $prefixes -y";
		$lignes[] = '  spip plugins:maj:bdd';
		$lignes[] = 'puis relancer l’import. plugins:svp:telecharger demande une version de SPIP-Cli qui comporte ses correctifs (sélection du plugin, autorisation).';
		$this->output->writeln($lignes);
		return Command::FAILURE;
	}
	
	/**
	 * Lance une commande SPIP-Cli dans un processus neuf, qui hérite des entrée et sorties de la commande
	 *
	 * Aucun descripteur n'est passé à proc_open() : le sous-processus hérite directement de ceux du processus en cours.
	 * Passer STDOUT ferait écrire le sous-processus au début d'un fichier de sortie, par-dessus ce qui précède.
	 *
	 * @param array $arguments
	 * @param array $environnement variables ajoutées à l'environnement
	 * @return int code de sortie
	 */
	protected function lancer_spip_cli(array $arguments, array $environnement = array()): int {
		$processus = proc_open(
			array_merge(array(PHP_BINARY, $this->spip_cli), $arguments),
			array(),
			$tubes,
			null,
			$environnement ? array_merge(getenv(), $environnement) : null
		);
		return is_resource($processus) ? proc_close($processus) : 1;
	}
}
