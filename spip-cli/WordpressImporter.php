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
	
	protected function configure() {
		$this
			->setName('wordpress:importer')
			->setDescription('Importe un site Wordpress dans un site SPIP')
			->setHelp('spip wordpress:importer ../site_wordpress')
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
				'Liste de traitements séparés par des virgules, si on veut ne faire que certains.'
			)
			->addOption(
				'info',
				'i',
				InputOption::VALUE_OPTIONAL,
				'Affiche la version du Wordpress et les traitements disponibles.'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		global $spip_racine;
		global $spip_loaded;
		
		// Facilité
		$this->input = $input;
		$this->output = $output;
		
		// Si on est bien dans un dossier SPIP
		if ($spip_loaded) {
			// Dossier sur le disque où se trouve les fichiers du Wordpress
			$this->dir_wordpress = rtrim($input->getArgument('dir_wordpress'), '/') . '/';
			
			// Identifiant de la base Wordpress dans SPIP
			$this->base = $input->getOption('base');
			
			// On va chercher la version de Wordpress dont il s'agit
			include_once $this->dir_wordpress . 'wp-includes/version.php';
			$this->wp_version = $wp_version;
			
			$traitements_disponibles = array(
				'importer_auteurs',
				'importer_categories',
				'importer_tags',
				'importer_documents',
				'importer_articles',
			);
			$traitements_disponibles = pipeline('w2spip_traitements', $traitements_disponibles);
			
			// Infos
			$output->writeln(array(
				'<info>C’est parti pour importer ce Wordpress :</info>',
				'* <comment>Version</comment> : ' . $this->wp_version,
				'* <comment>Base</comment> : ' . $this->base,
				'* <comment>Fichiers</comment> : ' . $this->dir_wordpress,
				'* <comment>Traitements disponibles</comment> : ' . join(', ', $traitements_disponibles),
				'',
			));
			
			// Si on cherche juste à lire les infos, on s'arrête là
			if ($input->hasParameterOption(array('--info', '-i'))) {
				exit;
			}
			
			// Peut-être qu'on veut lancer seulement certains traitements
			if ($traitements_ok = $input->getOption('traitements')) {
				$traitements_ok = array_map('trim', explode(',', $traitements_ok));
				$traitements_ok = array_intersect($traitements_disponibles, $traitements_ok);
			}
			else {
				$traitements_ok = $traitements_disponibles;
			}
			
			foreach ($traitements_ok as $traitement) {
				$this->appliquer_traitement($traitement);
			}
		}
		else{
			$output->writeln('<error>Vous devez lancer la commande depuis un site SPIP pour importer le contenu Wordpress.</error>');
		}
	}
	
	protected function appliquer_traitement($traitement) {
		$decoupe_version = explode('.', $this->wp_version);
		$version_X = $decoupe_version[0];
		$version_Y = $decoupe_version[1];
		
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
			$this->output->writeln("\n<error>Aucune fonction implémentée pour le traitement « $traitement ».</error>");
		}
		
		// On lance le traitement trouvé
		if ($fonction) {
			$this->output->writeln("\n<info>Lancement du traitement « $traitement »…</info>");
			$fonction($this);
		}
	}
}
