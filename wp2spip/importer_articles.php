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
		include_spip('action/editer_liens');
		
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
			$pattern_liens = '\[([^\[\]]*?)->([^\]]+)]';
			if(preg_match_all("|$pattern_liens|", $texte, $matches) and is_array($matches)) {
				foreach ($matches[2] as $cle=>$url) {
					$lien = wp2spip_chercher_lien($url, $url_wordpress, $command->base);
					
					if ($lien != $url) {
						$texte = str_replace("->$url]", "->$lien]", $texte);
					}
					
					//~ if (
						//~ $id_wordpress_doc = sql_getfetsel('ID', 'wp_posts', 'guid = '.sql_quote($url), '', '', '', '', $command->base)
						//~ and $id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.intval($id_wordpress_doc))
					//~ ) {
						//~ // Si jamais en plus le contenu du lien est en fait une image,
						//~ // on considère que c'était forcément un affichage d'image (complète ou réduite peu importe) vers le doc complet
						//~ if (strpos($matches[1][$cle], '<img') !== false) {
							//~ $texte = str_replace($matches[0][$cle], "[<img$id_document>->doc$id_document]", $texte);
						//~ }
						//~ // Sinon on laisse le contenu tel quel et on remplace uniquement vers quel lien
						//~ else {
							//~ $texte = str_replace("->$url]", "->doc$id_document]", $texte);
						//~ }
					//~ }
				}
			}
			
			// À priori tout ce qui est dans un [caption] c'est une insertion à remplacer par un document joint
			$pattern_captions = '\[caption(.*?)\[/caption\]';
			if(preg_match_all("|$pattern_captions|s", $texte, $matches) and is_array($matches)) {
				foreach ($matches[0] as $cle => $caption) {
					if (
						// Cas où on trouve direct l'identifiant WP
						(
							preg_match('#attachment_([0-9]+)#', $matches[1][$cle], $trouve)
							and $id_wordpress_doc = intval($trouve[1])
							and $id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.intval($id_wordpress_doc))
						)
						// Sinon on cherche depuis une URL
						or
						(
							preg_match('#src="(.*?)"#', $matches[1][$cle], $trouve)
							and $url = $trouve[1]
							and $lien = wp2spip_chercher_lien($url, $url_wordpress, $command->base)
							and strpos($lien, 'document') !== false
							and $id_document = intval(str_replace('document', '', $lien))
						)
					) {
						$align = 'center';
						if (
							preg_match('#align="([\w]+)"#', $matches[1][$cle], $trouve)
							and in_array($trouve[1], array('alignleft', 'alignright'))
						) {
							$align = str_replace('align', '', $trouve[1]);
						}
						
						$doc = "<doc$id_document|$align";
						
						// Si jamais on a une largeur, on va l'utiliser
						if (
							preg_match('#width="([0-9]+)"#', $matches[1][$cle], $trouve)
							and $width = intval($trouve[1])
						) {
							$doc .= "|width=$width";
						}
						
						// C'est fini
						$doc .= '>';
						
						// Si jamais on trouve un lien SPIP à l'intérieur du caption
						if (preg_match('#->(.+?)\]#', $matches[1][$cle], $trouve)) {
							$lien = $trouve[1];
							$doc = "[$doc->$lien]";
						}
						
						// On remplace le shortcode [caption] complet par le doc SPIP
						$texte = str_replace($caption, $doc, $texte);
					}
				}
			}
			
			// On gère en plus le cas des images super facile à retrouver où il ya "wp-image-ID" dedans
			$pattern_imgs = '<img[^>]*?wp-image-([0-9]+)[^>]*?>';
			if(preg_match_all("|$pattern_imgs|s", $texte, $matches) and is_array($matches)) {
				foreach ($matches[0] as $cle => $img) {
					if (
						$id_wordpress_doc = intval($matches[1][$cle])
						and $id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.$id_wordpress_doc)
					) {
						$texte = str_replace($img, "<img$id_document>", $texte);
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
			$id_article = null;
			if (!$article_old = sql_fetsel('id_article, id_rubrique', 'spip_articles', 'id_wordpress = '.$id_wordpress)) {
				$id_article = objet_inserer('article');
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_article, true);
				autoriser_exception('instituer', 'rubrique', $id_article, true);
				autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
			}
			// Sinon on ne met à jour que si demandé
			elseif ($command->update) {
				$id_article = intval($article_old['id_article']);
				
				// INSUP
				autoriser_exception('modifier', 'rubrique', $id_article, true);
				autoriser_exception('instituer', 'rubrique', $id_article, true);
				autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
				autoriser_exception('publierdans', 'rubrique', intval($article_old['id_rubrique']), true);
			}
			
			// Si on a un id_article, c'est qu'on vient d'insérer ou qu'on doit mettre à jour
			if ($id_article) {
				if ($ok = objet_modifier('article', $id_article, $article)) {
					$command->update ? $nb_maj++ : $nb_import++;
				}
				
				// Associer les docs
				wp2spip_importer_articles_documents($command, $id_wordpress, $id_article);
				
				// Ajouter l'URL libre
				if ($wp_post['post_name']) {
					sql_insertq(
						'spip_urls',
						array(
							'type' => 'article',
							'id_objet' => $id_article,
							'date' => $wp_post['post_date'],
							'url' => $wp_post['post_name'],
						)
					);
				}
				
				// Retrouver l'auteur principal dans le SPIP et l'ajouter
				if ($id_auteur = sql_getfetsel('id_auteur', 'spip_auteurs', 'id_wordpress='.$wp_post['post_author'])) {
					objet_associer(array('auteur'=>$id_auteur), array('article'=>$id_article));
				}
			}
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
	}
}

/**
 * Cherche si un lien peut être remplacé par un contenu interne au SPIP
 * 
 * Cela peut être un document si c'est un upload WP, ou un article interne.
 * 
 * @param string $lien
 * @param string $base
 * @return string Retourne le lien interne au SPIP, doc123 ou article123
 */
function wp2spip_chercher_lien($lien, $url_wordpress, $base='wordpress') {
	$pattern_doc = 'wp-content/uploads/';
	
	// Seulement si c'est une URL relative OU qu'il y a le domaine du site dedans
	if (!tester_url_absolue($lien) or strpos($lien, $url_wordpress) !== false) {
		// Si c'est un document du site d'origine
		if (
			preg_match("#$pattern_doc#s", $lien)
			and $id_wordpress_doc = sql_getfetsel('ID', 'wp_posts', 'guid = '.sql_quote($lien), '', '', '', '', $base)
			and $id_document = sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = '.intval($id_wordpress_doc))
		) {
			$lien = "document$id_document";
		}
		// Si on trouve un id de post directement easy
		elseif (
			($id_wordpress = parametre_url($lien, 'page_id') or $id_wordpress = parametre_url($lien, 'p'))
			and $id_article = sql_getfetsel('id_article', 'spip_articles', 'id_wordpress = '.$id_wordpress)
		) {
			$lien = "article$id_article";
		}
		// Sinon faut chercher une sorte de slug
		elseif (
			$chemin = parse_url($lien, PHP_URL_PATH)
			and $slug = trim(basename($chemin), '/')
			and $url = sql_fetsel('type, id_objet', 'spip_urls', 'url='.sql_quote($slug))
		) {
			$lien = $url['type'] . $url['id_objet'];
		}
	}
	
	return $lien;
}

function wp2spip_importer_articles_documents($command, $id_wordpress, $id_article) {
	// On va chercher tous les attachments liés à ce post
	if ($ids_attachments = sql_allfetsel(
		'ID',
		'wp_posts',
		array(
			'post_type="attachment"',
			'post_status="inherit"',
			'post_parent='.$id_wordpress,
		),
		'',
		'',
		'',
		'',
		$command->base
	)) {
		$ids_attachments = array_map('reset', $ids_attachments);
		
		// Ensuite on va chercher tous les documents SPIP qui correspondent
		if ($ids_documents = sql_allfetsel('id_document', 'spip_documents', sql_in('id_wordpress', $ids_attachments))) {
			$ids_documents = array_map('reset', $ids_documents);
			
			// On met tout ça en lien de l'article
			objet_associer(array('document'=>$ids_documents), array('article'=>$id_article));
		}
	}
}
