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
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		global $spip_racine;
		global $spip_loaded;
		
		// Facilité
		$this->input = $input;
		$this->output = $output;
		
		// Si on n'est pas dans un dossier SPIP, on ne peut rien faire
		if (!$spip_loaded) {
			$output->writeln('<error>Vous devez lancer la commande depuis un site SPIP pour importer le contenu Wordpress.</error>');
			return Command::FAILURE;
		}
		
		// Dossier sur le disque où se trouve les fichiers du Wordpress
		$this->dir_wordpress = rtrim($input->getArgument('dir_wordpress'), '/') . '/';
		
		// Identifiant de la base Wordpress dans SPIP
		$this->base = $input->getOption('base');
		
		// On va chercher la version de Wordpress dont il s'agit
		$fichier_version = $this->dir_wordpress . 'wp-includes/version.php';
		if (!is_readable($fichier_version)) {
			$output->writeln("<error>Impossible de lire $fichier_version : est-ce bien le dossier d’un Wordpress ?</error>");
			return Command::FAILURE;
		}
		include $fichier_version;
		$this->wp_version = $wp_version;
		
		$traitements_disponibles = array(
			'importer_metas',
			'importer_auteurs',
			'importer_rubriques',
			'importer_documents',
			'importer_articles',
			'importer_acces',
			'importer_polyhierarchie',
			'importer_commentaires',
		);
		$traitements_disponibles = pipeline('w2spip_traitements', $traitements_disponibles);
		
		// Infos
		$output->writeln(array(
			'<info>C’est parti pour importer ce Wordpress !</info>',
			'* <comment>Les contenus déjà importés ne sont pas ré-importés.</comment>',
			'* <comment>Version</comment> : ' . $this->wp_version,
			'* <comment>Base</comment> : ' . $this->base,
			'* <comment>Fichiers</comment> : ' . $this->dir_wordpress,
			'* <comment>Traitements disponibles</comment> : ' . join(', ', $traitements_disponibles),
			'',
		));
		
		// Si on cherche juste à lire les infos, on s'arrête là
		if ($input->hasParameterOption(array('--info', '-i'))) {
			return Command::SUCCESS;
		}
		
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
		
		// Un traitement en échec arrête l'import : les suivants dépendent de lui (les articles des rubriques…)
		foreach ($traitements_ok as $traitement) {
			if (!$this->appliquer_traitement($traitement)) {
				$output->writeln("\n<error>Import arrêté : le traitement « $traitement » a échoué.</error>");
				return Command::FAILURE;
			}
		}
		
		return Command::SUCCESS;
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
}
