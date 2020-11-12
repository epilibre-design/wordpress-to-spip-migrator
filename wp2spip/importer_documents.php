<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_documents_dist($command) {
	// S'il n'y a pas l'option update, on évite de charger pour rien les documents déjà migrés
	$ids_wordpress = array(0);
	if (
		!$command->update
		and $ids_wordpress = sql_allfetsel('id_wordpress', 'spip_documents', 'id_wordpress>0')
	) {
		$ids_wordpress = array_map('reset', $ids_wordpress);
	}
	
	// On va chercher tous les auteurs Wordpress qui ont l'air pertinent
	if ($wp_attachments = sql_allfetsel(
		'*',
		'wp_posts',
		array(
			'post_type = "attachment"',
			'post_status = "inherit"',
			'post_parent > 0',
			sql_in('ID', $ids_wordpress, 'NOT'),
		),
		'',
		'',
		'',
		'',
		$command->base
	)) {
		include_spip('action/editer_objet');
		include_spip('inc/autoriser');
		include_spip('inc/filtres');
		include_spip('sale_fonctions');
		include_spip('action/ajouter_documents');
		$ajouter_un_document = charger_fonction('ajouter_un_document', 'action');
		
		$nb_attachments = count($wp_attachments);
		$nb_import = 0;
		$nb_maj = 0;
		$command->output->writeln("$nb_attachments documents joints à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_attachments);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		foreach ($wp_attachments as $wp_attachment) {
			$id_wordpress = intval($wp_attachment['ID']);
			
			// Seulement si on trouve bien le fichier dans le dossier fourni
			$chemin = $command->dir_wordpress . ltrim(parse_url($wp_attachment['guid'], PHP_URL_PATH), '/');
			if (is_readable($chemin)) {
				// On compose le document SPIP
				$document = array(
					'titre' => $wp_attachment['post_title'],
					'descriptif' => sale($wp_attachment['post_content'] ?: $wp_attachment['post_excerpt']),
					'date' => $wp_attachment['post_date'],
					'maj' => $wp_attachment['post_modified'],
					'id_wordpress' => $id_wordpress,
				);
				
				// On compose le FILE
				$file = array(
					'tmp_name' => $chemin,
					'name' => basename($chemin),
					'titrer' => true,
					'mode' => 'auto',
				);
				
				// Si ça n'a pas déjà été importé c'est un ajout
				if (!$id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.$id_wordpress)) {
					// On ajoute le document avec la fonction dédiée
					if ($id_document = $ajouter_un_document('new', $file, null, null, 'auto')) {
						// INSUP
						autoriser_exception('modifier', 'document', $id_document, true);
						autoriser_exception('instituer', 'document', $id_document, true);
					}
				}
				// Sinon on ne met à jour que si demandé
				elseif ($command->update) {
					// On met aussi à jour le fichier ?
					// On peut considérer que pour un update le fichier ne bouge pas chez WP
					// $ajouter_un_document($id_document, $file, null, null, 'auto');
					
					// INSUP
					autoriser_exception('modifier', 'document', $id_document, true);
					autoriser_exception('instituer', 'document', $id_document, true);
				}
				
				// Si on a un id_document, c'est qu'on vient d'insérer ou qu'on doit mettre à jour
				if ($id_document) {
					if ($ok = objet_modifier('document', $id_document, $document)) {
						$command->update ? $nb_maj++ : $nb_import++;
					}
					
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
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
	}
}
