<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_auteurs_dist($command) {
	// Les contenus déjà importés ne sont pas retouchés
	$ids_wordpress = array_column(sql_allfetsel('id_wordpress', 'spip_auteurs', 'id_wordpress>0'), 'id_wordpress') ?: array(0);
	
	// On va chercher tous les auteurs Wordpress qui ont l'air pertinent
	if ($wp_users = sql_allfetsel(
		'*',
		'wp_users',
		array(
			'(user_email != "" or user_nicename not like "\_%")', // à cause d'un plugin ou un hack qui peut créer des millions de lignes de faux users
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
		
		$nb_users = count($wp_users);
		$nb_import = 0;
		$command->output->writeln("$nb_users comptes utilisateurs à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_users);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		foreach ($wp_users as $wp_user) {
			$id_wordpress_user = intval($wp_user['ID']);
			
			// On fait un tableau avec toutes ses métas par clé
			$metas = array();
			if ($usermetas = sql_allfetsel('*', 'wp_usermeta', 'user_id = '.$id_wordpress_user, '', '', '', '', $command->base)) {
				foreach ($usermetas as $meta) {
					$metas[$meta['meta_key']] = $meta['meta_value'];
				}
			}
			
			// On compose l'auteur SPIP
			$auteur = array(
				'email' => $wp_user['user_email'],
				'nom' => trim(($metas['first_name'] ?? '').' '.($metas['last_name'] ?? '')) ?: $wp_user['display_name'] ?: $wp_user['user_nicename'] ?: $wp_user['user_login'],
				'login' => $wp_user['user_login'] ?: uniqid(), // Toujours login non vide pour avoir le droit de faire un rappel avec son email
				'url_site' => $wp_user['user_url'],
				'id_wordpress' => $id_wordpress_user,
			);
			
			// Pour les statuts
			$wp_statut = '';
			if (
				isset($metas['wp_capabilities'])
				and $capabilities = unserialize($metas['wp_capabilities'], array('allowed_classes' => false))
				and is_array($capabilities)
			) {
				$wp_statut = array_key_first($capabilities);
			}
			switch ($wp_statut) {
				case 'administrator':
					$auteur['statut'] = '0minirezo';
					$auteur['webmestre'] = 'oui';
					break;
				case 'editor':
					$auteur['statut'] = '0minirezo';
					break;
				case 'author':
					$auteur['statut'] = '1comite'; // en fait ça devrait être un admin restreint, mais restreint à quoi ?
					break;
				case 'contributor':
					$auteur['statut'] = '1comite';
					break;
				case 'subscriber':
					$auteur['statut'] = '6forum';
					break;
				default:
					$auteur['statut'] = '6forum';
					break;
			}
			
			// Si ça n'a pas déjà été importé c'est un ajout
			if (!$id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'id_wordpress = '.$id_wordpress_user)) {
				$id_auteur = objet_inserer('auteur');
				
				// INSUP
				autoriser_exception('modifier', 'auteur', $id_auteur, true);
				autoriser_exception('instituer', 'auteur', $id_auteur, true);
				
				// Mot de passe non vide (et inconnu) pour avoir le droit de faire un rappel,
				// seulement à la création pour ne pas écraser un mot de passe choisi depuis
				$auteur['pass'] = bin2hex(random_bytes(16));
				
				if ($ok = objet_modifier('auteur', $id_auteur, $auteur)) {
					$nb_import++;
				}
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
	}
}
