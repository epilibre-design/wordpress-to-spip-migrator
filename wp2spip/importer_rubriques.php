<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_rubriques_dist($command) {
	// S'il n'y a pas l'option update, on évite de charger pour rien les catégories déjà migrées
	$ids_wordpress = array(0);
	if (
		!$command->update
		and $ids_wordpress = sql_allfetsel('id_wordpress', 'spip_rubriques', 'id_wordpress>0')
	) {
		$ids_wordpress = array_column($ids_wordpress, 'id_wordpress');
	}
	
	// On va chercher toutes les catégories
	if ($wp_categories = sql_allfetsel(
		'tax.description as description, tax.parent as id_parent, term.name as titre, term.slug as slug, term.term_id as id_term',
		'wp_term_taxonomy as tax left join wp_terms as term on tax.term_id=term.term_id',
		array(
			sql_in('taxonomy', array('category', 'link_category')),
			sql_in('term.term_id', $ids_wordpress, 'NOT'),
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
		
		// On arrange le tableau pour gérer la hiérarchie
		$wp_categories = wp2spip_enfants_rubriques($wp_categories, 0);
		
		$nb_categories = count($wp_categories);
		$nb_import = 0;
		$nb_maj = 0;
		$command->output->writeln("$nb_categories catégories à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_categories);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		foreach ($wp_categories as $wp_category) {
			$id_wordpress_category = intval($wp_category['id_term']);
			
			// On cherche le parent *du SPIP*
			$id_parent = 0;
			if (
				$wp_category['id_parent']
				and $id_parent_spip = sql_getfetsel('id_rubrique', 'spip_rubriques', 'id_wordpress='.intval($wp_category['id_parent']))
			) {
				$id_parent = intval($id_parent_spip);
			}
			
			// On compose la rubrique SPIP
			$rubrique = array(
				'id_parent' => $id_parent,
				'confirme_deplace' => 'oui',
				'titre' => texte_backend(sale($wp_category['titre'])),
				'texte' => texte_backend(sale($wp_category['description'])),
				'id_wordpress' => $id_wordpress_category,
			);
			
			// Si ça n'a pas déjà été importé
			if (!$rubrique_old = sql_fetsel('id_rubrique, id_parent', 'spip_rubriques', 'id_wordpress = '.$id_wordpress_category)) {
				$id_rubrique = objet_inserer('rubrique', $id_parent);
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_rubrique, true);
				autoriser_exception('instituer', 'rubrique', $id_rubrique, true);
				autoriser_exception('publierdans', 'rubrique', $id_parent, true);
				autoriser_exception('creerrubriquedans', 'rubrique', $id_parent, true);
				
				if ($ok = objet_modifier('rubrique', $id_rubrique, $rubrique)) {
					$nb_import++;
				}
			}
			// Sinon on ne met à jour que si demandé
			elseif ($command->update) {
				$id_rubrique = intval($rubrique_old['id_rubrique']);
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_rubrique, true);
				autoriser_exception('instituer', 'rubrique', $id_rubrique, true);
				autoriser_exception('publierdans', 'rubrique', $id_parent, true);
				autoriser_exception('creerrubriquedans', 'rubrique', $id_parent, true);
				autoriser_exception('publierdans', 'rubrique', intval($rubrique_old['id_parent']), true);
				
				if ($ok = objet_modifier('rubrique', $id_rubrique, $rubrique)) {
					$nb_maj++;
				}
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
	}
}

function wp2spip_enfants_rubriques($wp_categories=array(), $id_parent=0) {
	$enfants = array();
	
	foreach ($wp_categories as $category) {
		if ($category['id_parent'] == $id_parent) {
			// Ajouter à la suite et non par id_term : array_merge() renumérote les clés,
			// et une affectation par clé écraserait une catégorie déjà rangée à cet indice
			$enfants[] = $category;

			$enfants = array_merge($enfants, wp2spip_enfants_rubriques($wp_categories, $category['id_term']));
		}
	}
	
	return $enfants;
}
