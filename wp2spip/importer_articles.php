<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_articles_dist($command) {
	// Les contenus déjà importés ne sont pas retouchés
	$ids_wordpress = array_column(sql_allfetsel('id_wordpress', 'spip_articles', 'id_wordpress>0'), 'id_wordpress') ?: array(0);
	
	// On va chercher tous les articles Wordpress qui ont l'air pertinent
	if ($wp_posts = sql_allfetsel(
		'*',
		'wp_posts',
		array(
			sql_in('post_type', array('post', 'page')),
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
		include_spip('inc/config');
		include_spip('action/editer_liens');
		include_spip('inc/wp2spip');
		include_spip('inc/wp2spip_blocs');
		
		// Chaque article ou page prend l'identifiant de son contenu : ils doivent tous être libres
		if (!wp2spip_verifier_identifiants($command, 'article', array_column($wp_posts, 'ID'))) {
			return false;
		}
		
		$nb_posts = count($wp_posts);
		$nb_import = 0;
		$nb_liens_absents = 0;
		$nb_liens_non_convertis = 0;
		$bilan_blocs = array('convertis' => array(), 'dynamiques' => array(), 'inconnus' => array(), 'medias_introuvables' => 0, 'images_retirees' => 0, 'albums' => 0);
		$command->output->writeln("$nb_posts articles à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_posts);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		
		// Récupérer l'URL de l'époque du site Wordpress (dans le SPIP on a pu le changé depuis un premier import) pour retrouver les liens et docs internes
		$url_wordpress = sql_getfetsel('option_value', 'wp_options', 'option_name="siteurl"', '', '', '', '', $command->base);
		
		foreach ($wp_posts as $wp_post) {
			$id_wordpress = intval($wp_post['ID']);

			$id_rubrique_principale = 0;
			$page = '';
			
			// Si c'est une page on cherche même pas
			if ($wp_post['post_type'] == 'page') {
				$id_rubrique_principale = -1;
				$page = 'wordpress_page_' . $id_wordpress;
			}
			// On va chercher toutes les catégories, et on prend la première comme rubrique principale
			elseif ($ids_categories = sql_allfetsel(
				'term_id',
				'wp_term_taxonomy as tax left join wp_term_relationships as rel on tax.term_taxonomy_id=rel.term_taxonomy_id',
				array(
					'rel.object_id = '.$id_wordpress,
					'tax.taxonomy = "category"',
				),
				'',
				'rel.term_order, tax.term_id',
				'',
				'',
				$command->base
			)) {
				$ids_categories = array_column($ids_categories, 'term_id');
				
				// Pour chacune on doit retrouver l'id_rubrique chez SPIP
				if ($ids_rubriques = sql_allfetsel('id_rubrique, id_wordpress', 'spip_rubriques', sql_in('id_wordpress', $ids_categories))) {
					$ids_rubriques = array_column($ids_rubriques, 'id_rubrique', 'id_wordpress');
					
					// La principale : la première catégorie Wordpress qui existe dans le SPIP
					// (les autres sont ajoutées en rubriques secondaires par importer_polyhierarchie)
					foreach ($ids_categories as $id_categorie) {
						if (isset($ids_rubriques[$id_categorie])) {
							$id_rubrique_principale = intval($ids_rubriques[$id_categorie]);
							break;
						}
					}
				}
			}
			
			// TODO si pas de rubrique, il faudrait en créer une par défaut ?
			
			// Les blocs de l'éditeur et les galeries sont convertis avant sale(), qui ne voit pas ce qu'ils produisent en raccourcis SPIP
			$contexte_blocs = wp2spip_contexte_blocs($command, $wp_post, $url_wordpress);
			$texte = wp2spip_convertir_blocs($wp_post['post_content'], $contexte_blocs);
			$texte = wp2spip_restaurer_blocs(sale($texte), $contexte_blocs);
			$bilan_blocs = wp2spip_cumuler_bilan_blocs($bilan_blocs, $contexte_blocs);
			if ($command->output->isVerbose() and $contexte_blocs['bilan']['inconnus']) {
				$command->output->writeln("\nArticle Wordpress $id_wordpress : blocs inconnus, contenu gardé : " . join(', ', array_keys($contexte_blocs['bilan']['inconnus'])));
			}
			
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
							$doc .= "|largeur=$width";
						}
						
						// C'est fini
						$doc .= '>';
						
						// Si jamais on trouve un lien SPIP à l'intérieur du caption
						if (preg_match('#->(.+?)\]#', $matches[1][$cle], $trouve)) {
							$lien = $trouve[1];
							$doc = "[$doc->$lien]";
						}
						
						// On en profite pour mettre à jour le document lui-même avec la légende plus détaillée trouvée dans ce caption
						$contenu_caption = preg_replace('#\[caption[^\]]*?\](.*?)\[/caption\]#', '\1', $caption);
						$contenu_caption = supprimer_tags(preg_replace(array('#\[.*?\]#', '#\n-#'), '', sale($contenu_caption)));
						sql_updateq('spip_documents', array('descriptif' => $contenu_caption, 'titre' => ''), 'id_document = '.$id_document);
						
						// On remplace le shortcode [caption] complet par le doc SPIP
						$texte = str_replace($caption, $doc, $texte);
					}
				}
			}
			
			// Les lecteurs audio et vidéo (blocs de l'éditeur, ou raccourcis [audio] et [video]) deviennent des <docN>
			$remplacer_lecteur = function ($lecteur) use ($url_wordpress, $command) {
				// Un lien SPIP dont le texte commence par « audio » ou « video » n'est pas un raccourci de lecteur
				if (strpos($lecteur[0], '->') !== false) {
					return $lecteur[0];
				}
				preg_match_all('#(?:\bsrc=|\b(?:mp3|m4a|ogg|wav|wma|mp4|m4v|webm|ogv|wmv|flv|mov)=|\s)["\']?((?:https?:)?//[^\s"\'\]]+|/wp-content/[^\s"\'\]]+)#i', $lecteur[0], $urls);
				foreach ($urls[1] as $url) {
					if ($id_document = wp2spip_chercher_document($url, $url_wordpress, $command->base)) {
						return "<doc$id_document>";
					}
				}
				return $lecteur[0];
			};
			$texte = preg_replace_callback('#<(video|audio)\b[^>]*>.*?</\1>#is', $remplacer_lecteur, $texte);
			$texte = preg_replace_callback('#\[(audio|video)\b[^\]]*\](?:.*?\[/\1\])?#is', $remplacer_lecteur, $texte);

			// Les images restantes : on cherche le document d'après le fichier (src), sinon d'après la classe "wp-image-ID"
			// (le fichier d'abord : un import Wordpress vers Wordpress renumérote les médias sans corriger ces classes)
			$texte = preg_replace_callback('|<img\b[^>]*>|is', function ($img) use ($url_wordpress, $command) {
				$img = $img[0];
				$id_document = 0;
				if (preg_match('#\bsrc=["\']([^"\']+)["\']#i', $img, $trouve)) {
					$id_document = wp2spip_chercher_document($trouve[1], $url_wordpress, $command->base);
				}
				if (
					!$id_document
					and preg_match('#\bwp-image-([0-9]+)#', $img, $trouve)
				) {
					$id_document = intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . intval($trouve[1])));
				}
				if (!$id_document) {
					return $img;
				}
				$align = '';
				if (preg_match('#\balign(left|right|center)\b#', $img, $trouve)) {
					$align = '|' . $trouve[1];
				}
				return "<img$id_document$align>";
			}, $texte);

			// Les liens vers les fichiers du site qu'on n'a pas su convertir : on les signale, en distinguant
			// les fichiers absents de la médiathèque de ceux importés mais placés là où SPIP n'a pas de raccourci (image de fond…)
			foreach (wp2spip_liens_medias_restants($texte, $url_wordpress) as $reste) {
				$id_document = wp2spip_chercher_document($reste, $url_wordpress, $command->base);
				$id_document ? $nb_liens_non_convertis++ : $nb_liens_absents++;
				if ($command->output->isVerbose()) {
					$command->output->writeln("\nArticle Wordpress $id_wordpress : $reste " . ($id_document ? "(document $id_document, non converti)" : '(absent de la médiathèque)'));
				}
			}
			
			// Brouillon par défaut
			$statut = 'prepa';
			// On essaye de faire correspondre le bon statut
			$correspondance_statuts = array(
				'publish' => 'publie',
				'draft' => 'prepa',
				'pending' => 'prop',
				'future' => 'publie', // publié mais à une date future
				'private' => 'prepa', // réservé aux éditeurs : publié ensuite dans une zone par importer_acces
				'trash' => 'poubelle',
			);
			if (isset($correspondance_statuts[$wp_post['post_status']])) {
				$statut = $correspondance_statuts[$wp_post['post_status']];
			}
			// Un contenu protégé par mot de passe ne doit pas devenir public :
			// importer_acces le publiera dans une zone restreinte si Accès restreint est actif
			if ($statut == 'publie' and $wp_post['post_password'] !== '') {
				$statut = 'prepa';
			}
			
			// On compose l'article SPIP
			$article = array(
				'id_parent' => $id_rubrique_principale,
				'page' => $page,
				'titre' => $wp_post['post_title'],
				'texte' => $texte,
				'date' => $wp_post['post_date'],
				'maj' => $wp_post['post_modified'],
				'date_modif' => $wp_post['post_modified'],
				'accepter_forum' => ($wp_post['comment_status'] == 'open') ? 'pos' : 'non',
				'statut' => $statut,
			);
			$supplements = array(
				'date' => $wp_post['post_date'],
				'date_redac' => $wp_post['post_date'],
				'maj' => $wp_post['post_modified'],
				'date_modif' => $wp_post['post_modified'],
			);
			if ($page) {
				$supplements['id_rubrique'] = -1;
				$supplements['id_secteur'] = 0;
			}
			
			// Créé avec l'identifiant du contenu Wordpress et son id_wordpress, en une seule insertion :
			// aucun article n'existe sans son id_wordpress
			$id_article = $id_wordpress;
			if (objet_inserer('article', $id_rubrique_principale, array('id_article' => $id_article, 'id_wordpress' => $id_wordpress)) != $id_article) {
				return wp2spip_erreur_insertion($command, 'article', $id_article);
			}
			
			// INSUP
			autoriser_exception('modifier', 'article', $id_article, true);
			autoriser_exception('instituer', 'article', $id_article, true);
			autoriser_exception('modifier', 'rubrique', $id_rubrique_principale, true);
			autoriser_exception('instituer', 'rubrique', $id_rubrique_principale, true);
			autoriser_exception('publierdans', 'rubrique', $id_rubrique_principale, true);
			
			if ($erreur = objet_modifier('article', $id_article, $article)) {
				return wp2spip_erreur_modification($command, 'article', $id_article, $erreur);
			}
			$nb_import++;
			
			// Associer les docs, et les albums des galeries
			wp2spip_importer_articles_documents($command, $id_wordpress, $id_article);
			wp2spip_lier_albums($contexte_blocs, $id_article);
			
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
			
			// On force la modif de certains champs qui ne sont pas pris en compte par l'API,
			// en dernier : associer un auteur remet date_modif à la date du jour
			sql_updateq('spip_articles', $supplements, 'id_article = '.$id_article);
			
			$progressBar->advance();
		}
		
		// Une ligne vide à la fin
		$command->output->writeln('');
		if ($nb_liens_absents) {
			$command->output->writeln("$nb_liens_absents liens vers des fichiers du site absents de la médiathèque Wordpress, laissés tels quels (détail avec -v).");
		}
		if ($nb_liens_non_convertis) {
			$command->output->writeln("$nb_liens_non_convertis liens vers des médias importés, mais placés là où SPIP n’a pas de raccourci (image de fond…), laissés tels quels (détail avec -v).");
		}
		wp2spip_afficher_bilan_blocs($command, $bilan_blocs);
	}
}

/**
 * Cherche si un lien peut être remplacé par un contenu interne au SPIP
 * 
 * Cela peut être un document si c'est un fichier ou la page d'un média Wordpress,
 * ou un article : les articles et pages SPIP ont l'identifiant de leur contenu Wordpress,
 * le lien s'écrit donc directement, que le contenu visé soit déjà importé ou non.
 * 
 * @param string $lien
 * @param string $url_wordpress
 * @param string $base
 * @return string Retourne le lien interne au SPIP, document123 ou article123, ou le lien inchangé
 */
function wp2spip_chercher_lien($lien, $url_wordpress, $base='wordpress') {
	// Seulement si c'est une URL relative OU qu'elle pointe sur le site d'origine
	if (!wp2spip_url_du_site($lien, $url_wordpress)) {
		return $lien;
	}
	// Un fichier de la médiathèque
	if ($id_document = wp2spip_chercher_document($lien, $url_wordpress, $base)) {
		return "document$id_document";
	}
	// Un fichier d'uploads introuvable n'est pas une page
	if (strpos($lien, '/wp-content/uploads/') !== false) {
		return $lien;
	}
	
	// Un contenu, désigné par son identifiant (?p=, ?page_id=) ou par son chemin
	$contenus = wp2spip_index_contenus($base);
	$id_wordpress = intval(parametre_url($lien, 'page_id')) ?: intval(parametre_url($lien, 'p'));
	if (
		!$id_wordpress
		and $chemin = trim((string) parse_url($lien, PHP_URL_PATH), '/')
	) {
		$id_wordpress = wp2spip_chercher_slug($chemin, $contenus);
	}
	
	switch ($contenus['types'][$id_wordpress] ?? '') {
		case 'post':
		case 'page':
			return "article$id_wordpress";
		case 'attachment':
			// La page d'un média : son document, s'il a été importé
			if ($id_document = intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . $id_wordpress))) {
				return "document$id_document";
			}
	}
	
	return $lien;
}

/**
 * Index des contenus Wordpress qu'un lien peut désigner
 *
 * @param string $base
 * @return array array(
 *     'types' => array(ID => post_type),
 *     'slugs' => array(slug normalisé => array(ID, …)),
 *     'chemins' => array(ID => chemin normalisé : slug, ou parent/enfant pour une page)
 * )
 */
function wp2spip_index_contenus($base = 'wordpress') {
	static $index = array();
	if (isset($index[$base])) {
		return $index[$base];
	}
	$index[$base] = array('types' => array(), 'slugs' => array(), 'chemins' => array());

	$contenus = sql_allfetsel(
		'ID, post_type, post_name, post_parent',
		'wp_posts',
		sql_in('post_type', array('post', 'page', 'attachment')),
		'',
		'ID',
		'',
		'',
		$base
	);
	$types = array_column($contenus, 'post_type', 'ID');
	$noms = array_column($contenus, 'post_name', 'ID');
	$parents = array_column($contenus, 'post_parent', 'ID');

	foreach ($contenus as $contenu) {
		$id = intval($contenu['ID']);
		$index[$base]['types'][$id] = $contenu['post_type'];
		if ($contenu['post_name'] === '') {
			continue;
		}
		$slug = wp2spip_normaliser_slug($contenu['post_name']);
		$index[$base]['slugs'][$slug][] = $id;

		// Une page enfant a pour chemin celui de ses parents : parent/enfant
		$chemin = $slug;
		$parent = intval($contenu['post_parent']);
		$vus = array();
		while (
			$contenu['post_type'] == 'page'
			and $parent
			and ($types[$parent] ?? '') == 'page'
			and !isset($vus[$parent])
		) {
			$vus[$parent] = true;
			$chemin = wp2spip_normaliser_slug($noms[$parent]) . '/' . $chemin;
			$parent = intval($parents[$parent]);
		}
		$index[$base]['chemins'][$id] = $chemin;
	}

	return $index[$base];
}

/**
 * Contenu désigné par le chemin d'une URL
 *
 * Le dernier segment est le slug. Si plusieurs contenus ont ce slug (pages de parents
 * différents, article et page…), on garde ceux dont le chemin complet termine l'URL,
 * et parmi eux le plus long (parent/enfant plutôt qu'enfant). Toujours ambigu : 0,
 * le lien reste tel quel plutôt que de viser peut-être le mauvais contenu.
 *
 * @param string $chemin chemin de l'URL, sans / au début ni à la fin
 * @param array $contenus index de wp2spip_index_contenus()
 * @return int ID Wordpress, ou 0
 */
function wp2spip_chercher_slug($chemin, $contenus) {
	$chemin = join('/', array_map('wp2spip_normaliser_slug', explode('/', $chemin)));
	$candidats = $contenus['slugs'][basename($chemin)] ?? array();
	if (count($candidats) > 1) {
		$longueurs = array();
		foreach ($candidats as $id) {
			$complet = $contenus['chemins'][$id];
			if ($chemin === $complet or substr($chemin, -strlen($complet) - 1) === "/$complet") {
				$longueurs[$id] = strlen($complet);
			}
		}
		$candidats = $longueurs ? array_keys($longueurs, max($longueurs)) : array();
	}
	return (count($candidats) == 1) ? intval($candidats[0]) : 0;
}

/**
 * Forme comparable d'un slug : Wordpress stocke les caractères non ASCII encodés en minuscules (%c3%a9),
 * les liens les écrivent encodés en majuscules ou en clair
 *
 * @param string $slug
 * @return string
 */
function wp2spip_normaliser_slug($slug) {
	return strtolower(rawurlencode(rawurldecode($slug)));
}

/**
 * Teste si une URL pointe sur le site Wordpress d'origine (ou est relative)
 *
 * Indépendamment du protocole et du "www." : un site passé en https
 * a souvent encore des liens en http dans ses contenus.
 *
 * @param string $url
 * @param string $url_wordpress URL du site Wordpress (option siteurl)
 * @return bool
 */
function wp2spip_url_du_site($url, $url_wordpress) {
	if (!tester_url_absolue($url)) {
		return true;
	}
	$hote = fn($u) => preg_replace('/^www\./', '', strtolower((string) parse_url($u, PHP_URL_HOST)));
	return $hote($url) !== '' and $hote($url) === $hote($url_wordpress);
}

/**
 * Retrouve le document SPIP correspondant à l'URL d'un fichier de la médiathèque Wordpress
 *
 * On compare le chemin sous wp-content/uploads/ (décodé) à tous les noms connus
 * de chaque média : fichier réel, tailles dérivées, original d'une image retouchée
 * ou réduite, guid. Ce qui couvre les accents encodés dans les URL, les miniatures
 * et les images retouchées (dont le guid garde l'ancien nom).
 *
 * @param string $url
 * @param string $url_wordpress
 * @param string $base
 * @return int id_document, ou 0 si introuvable
 */
function wp2spip_chercher_document($url, $url_wordpress, $base = 'wordpress') {
	if (
		!wp2spip_url_du_site($url, $url_wordpress)
		or !$chemin = wp2spip_chemin_upload($url)
	) {
		return 0;
	}

	$index = wp2spip_index_medias($base);
	$id_wordpress = $index[$chemin] ?? 0;

	// Variantes non référencées : taille dérivée (-300x200), image retouchée (-e1479812986279), réduite (-scaled)
	if (!$id_wordpress) {
		$original = preg_replace('/(-\d+x\d+|-e\d{10,}|-scaled)+(\.\w+)$/', '$2', $chemin);
		$id_wordpress = $index[$original] ?? 0;
	}
	if (!$id_wordpress) {
		return 0;
	}

	static $documents = array();
	if (!isset($documents[$id_wordpress])) {
		$documents[$id_wordpress] = intval(sql_getfetsel('id_document', 'spip_documents', 'id_wordpress = ' . intval($id_wordpress)));
	}
	return $documents[$id_wordpress];
}

/**
 * Chemin d'un fichier relatif à wp-content/uploads/, décodé, sans paramètres
 *
 * @param string $url
 * @return string chemin, ou '' si l'URL n'est pas dans uploads
 */
function wp2spip_chemin_upload($url) {
	$chemin = (string) parse_url(html_entity_decode($url), PHP_URL_PATH);
	if (!preg_match('#/wp-content/uploads/(.+)$#', $chemin, $trouve)) {
		return '';
	}
	return rawurldecode($trouve[1]);
}

/**
 * Index de tous les noms de fichiers connus pour chaque média Wordpress
 *
 * @param string $base
 * @return array chemin relatif à uploads/ => ID du média Wordpress
 */
function wp2spip_index_medias($base = 'wordpress') {
	static $index = array();
	if (isset($index[$base])) {
		return $index[$base];
	}
	$index[$base] = array();

	// Le guid, souvent l'URL d'origine du fichier
	foreach (sql_allfetsel('ID, guid', 'wp_posts', 'post_type = "attachment"', '', 'ID', '', '', $base) as $media) {
		if ($chemin = wp2spip_chemin_upload($media['guid'])) {
			$index[$base][$chemin] = intval($media['ID']);
		}
	}

	$metas = sql_allfetsel(
		'post_id, meta_key, meta_value',
		'wp_postmeta',
		sql_in('meta_key', array('_wp_attached_file', '_wp_attachment_metadata', '_wp_attachment_backup_sizes')),
		'',
		'meta_id',
		'',
		'',
		$base
	);
	// Le fichier réel, prioritaire sur tous les autres noms
	$fichiers_reels = array();
	foreach ($metas as $meta) {
		if ($meta['meta_key'] == '_wp_attached_file') {
			$fichiers_reels[intval($meta['post_id'])] = $meta['meta_value'];
			$index[$base][$meta['meta_value']] = intval($meta['post_id']);
		}
	}

	// Les autres noms (tailles dérivées, original d'une image réduite ou retouchée) sont dans le même dossier
	foreach ($metas as $meta) {
		$id = intval($meta['post_id']);
		if (
			$meta['meta_key'] == '_wp_attached_file'
			or !isset($fichiers_reels[$id])
			or !is_array($infos = @unserialize($meta['meta_value'], array('allowed_classes' => false)))
		) {
			continue;
		}
		$dossier = dirname($fichiers_reels[$id]);
		$dossier = ($dossier === '.') ? '' : "$dossier/";

		if ($meta['meta_key'] == '_wp_attachment_metadata') {
			// array('file' => …, 'sizes' => array('medium' => array('file' => …)), 'original_image' => …)
			$fichiers = array_column($infos['sizes'] ?? array(), 'file');
			if (!empty($infos['original_image'])) {
				$fichiers[] = $infos['original_image'];
			}
		}
		else {
			// _wp_attachment_backup_sizes : array('full-orig' => array('file' => …), 'thumbnail-orig' => …)
			$fichiers = array_column($infos, 'file');
		}
		foreach ($fichiers as $fichier) {
			$index[$base][$dossier . $fichier] ??= $id;
		}
	}

	return $index[$base];
}

/**
 * Liste les liens vers des fichiers d'uploads du site d'origine restés dans un texte
 *
 * @param string $texte
 * @param string $url_wordpress
 * @return array
 */
function wp2spip_liens_medias_restants($texte, $url_wordpress) {
	preg_match_all('#[^\s"\'<>\[\]()|]*/wp-content/uploads/[^\s"\'<>\[\]()|]+#', $texte, $trouves);
	return array_values(array_unique(array_filter($trouves[0], fn($url) => wp2spip_url_du_site($url, $url_wordpress))));
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
		'ID',
		'',
		'',
		$command->base
	)) {
		$ids_attachments = array_column($ids_attachments, 'ID');
		
		// Ensuite on va chercher tous les documents SPIP qui correspondent
		if ($ids_documents = sql_allfetsel('id_document', 'spip_documents', sql_in('id_wordpress', $ids_attachments))) {
			$ids_documents = array_column($ids_documents, 'id_document');
			
			// On met tout ça en lien de l'article
			objet_associer(array('document'=>$ids_documents), array('article'=>$id_article));
		}
	}
}
