<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WordpressImporter extends Command {
	public $input = null;
	public $output = null;
	public $wp_version = '';
	
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
			$dir_wordpress = rtrim($input->getArgument('dir_wordpress'), '/') . '/';
			
			// On va chercher la version de Wordpress dont il s'agit
			include_once $dir_wordpress . 'wp-includes/version.php';
			$this->wp_version = $wp_version;
			
			$traitements = array(
				'importer_auteurs',
				'importer_categories',
				'importer_tags',
				'importer_documents',
				'importer_articles',
			);
			$traitements = pipeline('w2spip_traitements', $traitements);
			
			foreach ($traitements as $traitement) {
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
			$this->output->writeln("<error>Aucune fonction implémentée pour le traitement « $traitement ».</error>");
		}
		
		// On lance le traitement trouvé
		if ($fonction) {
			$this->output->writeln("<info>Lancement du traitement « $traitement »…</info>");
			$fonction($this);
		}
	}
}
