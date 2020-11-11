<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_articles_dist($command) {
	// S'il n'y a pas l'option update, on évite de charger pour rien les auteurs déjà migrés
	$ids_wordpress = array(0);
	if (
		!$command->update
		and $ids_wordpress = sql_allfetsel('id_wordpress', 'spip_articles', 'id_wordpress>0')
	) {
		$ids_wordpress = array_map('reset', $ids_wordpress);
	}
	
	// On va chercher tous les auteurs Wordpress qui ont l'air pertinent
	if ($wp_posts = sql_allfetsel(
		'*',
		'wp_posts',
		array(
			'post_type = "post"',
			'post_status = "publish"',
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
		include_spip('inc/config');
		
		$nb_posts = count($wp_posts);
		$nb_import = 0;
		$nb_maj = 0;
		$command->output->writeln("$nb_posts articles à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_posts);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		// Récupérer l'URL de l'époque du site Wordpress (dans le SPIP on a pu le changé depuis un premier import) pour retrouver les liens et docs internes
		$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', $command->base);
		
		foreach ($wp_posts as $wp_post) {
			$id_wordpress = intval($wp_post['ID']);
			
			// On va chercher toutes les catégories, et on prend la première comme rubrique principale
			if ($ids_categories = sql_allfetsel(
				'term_id',
				'wp_term_taxonomy as tax left join wp_term_relationships as rel on tax.term_taxonomy_id=rel.term_taxonomy_id',
				array(
					'rel.object_id = '.$id_wordpress,
					'tax.taxonomy = "category"',
				),
				'',
				'',
				'',
				'',
				$command->base
			)) {
				$ids_categories = array_map('reset', $ids_categories);
				
				// Pour chacune on doit retrouver l'id_rubrique chez SPIP
				if ($ids_rubriques = sql_allfetsel('id_rubrique', 'spip_rubriques', sql_in('id_wordpress', $ids_categories))) {
					$ids_rubriques = array_map('reset', $ids_rubriques);
					
					// La principale
					$id_rubrique_principale = array_shift($ids_rubriques);
				}
			}
			
			// TODO si pas de rubrique, il faudrait en créer une par défaut ?
			if (!$id_rubrique_principale) {
				$id_rubrique_principale = 0;
			}
			
			// On passe déjà sale() en premier pour y voir plus clair
			$texte = sale($wp_post['post_content']);
			
			// On cherche tous les liens internes et on cherche s'il s'agit d'un document qu'on a déjà importé
			$pattern_liens = '->(' . preg_quote($url_wordpress) . "\/wp-content\/uploads\/(.*?))\]";
			preg_match_all("|$pattern_liens|", $texte, $matches);
			if(is_array($matches)) {
				foreach ($matches[1] as $url) {
					if (
						$id_wordpress_doc = sql_getfetsel('ID', 'wp_posts', 'guid = '.sql_quote($url), '', '', '', '', $command->base)
						and $id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.intval($id_wordpress_doc))
					) {
						$texte = str_replace("->$url]", "->doc$id_document]", $texte);
					}
				}
			}
			
			// On compose l'article SPIP
			$article = array(
				'id_rubrique' => $id_rubrique_principale,
				'titre' => $wp_post['post_title'],
				'texte' => $texte,
				'date' => $wp_post['post_date'],
				'maj' => $wp_post['post_modified'],
				'accepter_forum' => ($wp_post['comment_status'] == 'open') ? 'pos' : 'non',
				'statut' => 'publie',
				'id_wordpress' => $id_wordpress,
			);
			
			// Si ça n'a pas déjà été importé c'est un ajout
			if (!$article_old = sql_fetsel('id_article, id_rubrique', 'spip_articles', 'id_wordpress = '.$id_wordpress)) {
				$id_article = objet_inserer('article');
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_article, true);
				autoriser_exception('instituer', 'rubrique', $id_article, true);
				autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
				
				if ($ok = objet_modifier('article', $id_article, $article)) {
					$nb_import++;
				}
			}
			// Sinon on ne met à jour que si demandé
			elseif ($command->update) {
				$id_article = intval($article_old['id_article']);
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_article, true);
				autoriser_exception('instituer', 'rubrique', $id_article, true);
				autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
				autoriser_exception('publierdans', 'rubrique', intval($article_old['id_rubrique']), true);
				
				if ($ok = objet_modifier('article', $id_article, $article)) {
					$nb_maj++;
				}
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
	}
}
