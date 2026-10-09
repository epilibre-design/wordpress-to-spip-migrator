<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Noms des tables WordPress (wp2spip_table())
include_spip('inc/wp2spip');

function wp2spip_importer_documents_dist($command) {
	// Les contenus déjà importés ne sont pas retouchés
	$ids_wordpress = array_column(sql_allfetsel('id_wordpress', 'spip_documents', 'id_wordpress>0'), 'id_wordpress') ?: array(0);
	
	// On va chercher tous les auteurs Wordpress qui ont l'air pertinent
	if ($wp_attachments = sql_allfetsel(
		'*',
		wp2spip_table('posts'),
		array(
			'post_type = "attachment"',
			'post_status = "inherit"',
			// 'post_parent > 0', // venait de l'ancienne versino mais en fait il peut y avoir des docs attachés à rien !
			sql_in('ID', $ids_wordpress, 'NOT'),
		),
		'',
		'ID',
		'',
		'',
		$command->base
	)) {
		include_spip('action/editer_objet');
		include_spip('inc/autoriser');
		include_spip('inc/filtres');
		include_spip('sale_fonctions');
		include_spip('action/ajouter_documents');
		include_spip('inc/distant');
		include_spip('inc/flock');
		include_spip('inc/wp2spip');
		$ajouter_un_document = charger_fonction('ajouter_un_document', 'action');
		
		// Chaque document prend l'identifiant de son média : ils doivent tous être libres
		if (!wp2spip_verifier_identifiants($command, 'document', array_column($wp_attachments, 'ID'))) {
			return false;
		}
		
		$nb_attachments = count($wp_attachments);
		$nb_import = 0;
		$nb_refuses = 0;
		$command->output->writeln("$nb_attachments documents joints à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_attachments);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		foreach ($wp_attachments as $wp_attachment) {
			$id_wordpress = intval($wp_attachment['ID']);
			$distant = false;
			
			// On cherche en priorité dans le dossier local
			$chemin = $command->dir_wordpress . ltrim(parse_url($wp_attachment['guid'], PHP_URL_PATH), '/');
			
			// Si pas trouvé en local, on regarde si on arrive à le récupérer en ligne
			if (!is_readable($chemin)) {
				$guid = preg_replace_callback('/[^\x20-\x7f]/', function($match) { return urlencode($match[0]); }, $wp_attachment['guid']);
				$chemin = _DIR_RACINE . copie_locale($guid);
				$distant = true;
			}
			
			if (is_readable($chemin)) {
				$id_document = $id_wordpress;
				
				// On compose le document SPIP
				$document = array(
					'titre' => $wp_attachment['post_title'],
					'descriptif' => sale($wp_attachment['post_content'] ?: $wp_attachment['post_excerpt']),
					'date' => $wp_attachment['post_date'],
					'maj' => $wp_attachment['post_modified'],
				);
				
				// On compose le FILE
				$file = array(
					'tmp_name' => $chemin,
					'name' => basename($chemin),
					'titrer' => true,
					'mode' => 'auto',
				);
				
				// Document créé vide avec l'identifiant du média et son id_wordpress, en une seule insertion,
				// puis le fichier installé dedans : ajouter_un_document() met à jour un document existant
				if (objet_inserer('document', null, array('id_document' => $id_document, 'id_wordpress' => $id_wordpress)) != $id_document) {
					return wp2spip_erreur_insertion($command, 'document', $id_document);
				}
				// ajouter_un_document() de SPIP 4.4 récent vérifie autoriser('joindredocument') : en ligne de commande, sans
				// auteur connecté, tous les fichiers seraient refusés. Document joint à aucun objet : type vide, identifiant
				// null (que autoriser() cherche sous la clé '', d'où l'exception générique '*'), levée aussitôt après
				autoriser_exception('joindredocument', '', '*', true);
				try {
					$retour = $ajouter_un_document($id_document, $file, null, null, 'auto');
				} finally {
					autoriser_exception('joindredocument', '', '*', false);
				}
				
				// Fichier refusé (type non autorisé, taille…) : pas de document vide
				if (intval($retour) !== $id_document) {
					sql_delete('spip_documents', 'id_document = ' . $id_document);
					$nb_refuses++;
					if ($command->output->isVerbose()) {
						$command->output->writeln("\nMédia Wordpress $id_wordpress refusé : " . (is_string($retour) ? $retour : basename($chemin)));
					}
				}
				else {
					// INSUP
					autoriser_exception('modifier', 'document', $id_document, true);
					autoriser_exception('instituer', 'document', $id_document, true);
					
					if ($erreur = objet_modifier('document', $id_document, $document)) {
						return wp2spip_erreur_modification($command, 'document', $id_document, $erreur);
					}
					$nb_import++;
					
					// Ajouter l'URL libre
					if ($wp_attachment['post_name']) {
						sql_insertq(
							'spip_urls',
							array(
								'type' => 'document',
								'id_objet' => $id_document,
								'date' => $wp_attachment['post_date'],
								'url' => $wp_attachment['post_name'],
							)
						);
					}
				}
				
				// Si distant, on supprime la copie locale temporaire
				if ($distant) {
					supprimer_fichier($chemin);
				}
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
		if ($nb_refuses) {
			$command->output->writeln("$nb_refuses médias refusés par SPIP (type de fichier non autorisé…), non importés (détail avec -v).");
		}
	}
}
