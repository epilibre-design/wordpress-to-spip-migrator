<?php
/**
 * Plugin wp2spip
 * 
 * GNU/GPL v3
 * 
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Fonction de récupération des documents internes dans les textes issus de WordPress
 * 
 * Les documents dans wordpress sont en dur dans la base de donnée, on tente de les réécrire sous une 
 * forme SPIP [texte->docXX] :
 * 
 */
function action_wp2spip_documents_internes_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!autoriser('configurer','plugins'))
		die('erreur');

	$url_wordpress = sql_getfetsel('option_value','wp_options','option_name='.sql_quote('siteurl'));
	$modifies = array();
	// On va remplacer les url/wp-content/uploads/ par ->docXX
	$articles = sql_select('*','spip_articles','texte LIKE "%->'.$url_wordpress.'/wp-content/uploads/%"');
	while($article = sql_fetch($articles)){
		$texte = $article['texte'];
		$pattern = "->(".preg_quote($url_wordpress)."\/wp-content\/uploads\/(.*?))]";
		preg_match_all("|$pattern|",$texte,$matches);
		if(is_array($matches)){
			foreach($matches[1] as $i=>$id){
				if($id_document = sql_getfetsel('id_document','spip_documents','fichier='.sql_quote($id)))
					$texte = str_replace('->'.$id.']','->doc'.$id_document.']',$texte);
				else{
					include_spip('action/joindre');
					$joindre2 = charger_fonction('joindre2', 'inc');
					if($id_document = $joindre2($id, 'document', 'article', $article['id_article'], 0, $hash, $redirect, $documents_actifs, $iframe_redirect))
						$texte = str_replace('->'.$id.']','->doc'.$id_document.']',$texte);
				}
			}
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
	}
	spip_log(count($modifies).' articles modifiés après correction des '.$url_wordpress.'/wp-content/uploads/...','wp2spip');
	spip_log($modifies,'wp2spip');
	
	$modifies = array();
	// On va remplacer les ../wp-content/uploads/ ou /wp-content/uploads/ par ->docXX
	$articles = sql_select('*','spip_articles','texte LIKE "%->../wp-content/uploads/%" OR texte LIKE "%->/wp-content/uploads/%"');
	while($article = sql_fetch($articles)){
		spip_log($article['id_article']);
		$texte = $article['texte'];
		$pattern = "->(\.?\.?\/wp-content\/uploads\/(.*?))]";
		preg_match_all("|$pattern|",$texte,$matches);
		if(is_array($matches)){
			foreach($matches[1] as $i=>$id){
				$fichier = $url_wordpress.str_replace('../','/',$id);
				if($id_document = sql_getfetsel('id_document','spip_documents','fichier='.sql_quote($id)))
					$texte = str_replace('->'.$id.']','->doc'.$id_document.']',$texte);
				else{
					include_spip('action/joindre');
					$joindre2 = charger_fonction('joindre2', 'inc');
					if($id_document = $joindre2($fichier, 'document', 'article', $article['id_article'], 0, $hash, $redirect, $documents_actifs, $iframe_redirect))
						$texte = str_replace('->'.$id.']','->doc'.$id_document.']',$texte);
				}
			}
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
	}
	
	// On va remplacer les <img src=.. <imgXX>
	$articles = sql_select('*','spip_articles','texte LIKE "%<img %"');
	$images = array();
	while($article = sql_fetch($articles)){
		$texte = $article['texte'];
		$pattern = "<img.*?src=['\"](.*?)['\"].*?>";
		preg_match_all("|$pattern|",$texte,$matches);
		$images_article = array();
		if(is_array($matches)){
			foreach($matches[0] as $img){
				$image = array();
				if(preg_match('|src=["\'](.*?)([\'"]\s)|',$img,$match_href)){
					if(strlen($match_href[1]) > 2 && !preg_match('|data:(.*?);base64,(.*)|',$match_href[1],$data_uri)){
						$match_href[1] = str_replace('../','/',$match_href[1]);
						$image['complete'] = $img;
						if(preg_match('|^\/?wp-content\/|',$match_href[1],$img_wpcontent)){
							$image['href'] = $url_wordpress.(($match_href[1][0] == '/')?'':'/').$match_href[1];
							$image['statut'] = 'ok';
						}
						else if(preg_match('|^file:\/\/|',$match_href[1],$img_wpexplorer)){
							$image['href'] = $match_href[1];
							$image['statut'] = 'supprimer';
						}
						else if(preg_match('|\/wp-includes\/|',$match_href[1],$img_wpinclude)){
							$image['href'] = $match_href[1];
							$image['statut'] = 'supprimer';
						}
						else{
							$image['href'] = $match_href[1];
							$image['statut'] = 'ok';
						}
					}
					elseif(isset($data_uri[1])){
						$image['complete'] = $img;
						$image['statut'] = 'base64';
						$image['datas'] = $data_uri;
						spip_log($data_uri[1]);
					}
				}
				if(isset($image['href']) && preg_match('|title=[\'"](.*?)[\'"]|',$img,$match_title)){
					if(strlen($match_title[1]) > 2)
						$image['title'] = $match_title[1];
				}
				if(isset($image['href']) && preg_match('|alt=[\'"](.*?)[\'"]|',$img,$match_alt)){
					if(strlen($match_alt[1]) > 2)
						$image['alt'] = $match_alt[1];
				}
				if(isset($image['statut']))
					$images_article[] = $image;
			}
			foreach($images_article as $i => $image){
				if($image['statut']=='base64'){
					include_spip('inc/flock');
					if(isset($image['datas']['1'])){
						switch($image['datas']['1']){
							case 'image/jpeg':
								$extension = '.jpg';
								break;
							case 'image/png':
								$extension = '.png';
								break;
							case 'image/gif':
								$extension = '.gif';
								break;
						}
						// Répertoire où stocker l'image temporaire
						$dest = sous_repertoire(_DIR_VAR, 'cache-wp2spip');
						$dest = $dest.'image_'.$i.'_art'.$article['id_article'].$extension;
						if ($ok = ecrire_fichier($dest, base64_decode($image['datas']['2']))) {
							$image['href'] = array(array('name'=>basename($dest), 'type' => $image['datas']['1'],'tmp_name' => $dest));
							$image['statut'] = 'ok';
							$image['distante'] = 'non';
							spip_log($image['href']);
							unset($images_article[$i]['datas']);
						}
					}
				}
				if($image['statut']=='ok'){
					if($id_document = is_array($image['href']) ? false : sql_getfetsel('id_document','spip_documents','fichier='.sql_quote($image['href'])))
						$texte = str_replace($image['complete'],'<img'.$id_document.'>',$texte);
					else{
						include_spip('action/joindre');
						if(isset($image['distante']) && $image['distante'] == 'non')
							$joindre2 = charger_fonction('joindre1', 'inc');
						else
							$joindre2 = charger_fonction('joindre2', 'inc');
						if(isset($image['title']))
							$titre = $image['title'];
						elseif(isset($image['alt']))
							$titre = $image['alt'];
						if($id_document = $joindre2($image['href'], 'image', 'article', $article['id_article'], 0, $hash, $redirect, $documents_actifs, $iframe_redirect)){
							$texte = str_replace($image['complete'],'<img'.$id_document.'>',$texte);
							if(isset($titre))
								sql_updateq('spip_documents',array('titre'=>$titre),'id_document='.intval($id_document));
						}
					}
				}else if($image['statut']=='supprimer')
					$texte = str_replace($image['complete'],'',$texte);
			}
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		$images[$article['id_article']] = $images_article;
	}
	spip_log($images,'wp2spip');
	spip_log(count($modifies).' articles modifiés après correction des '.$url_wordpress.'/wp-content/uploads/...','wp2spip');
	spip_log($modifies,'wp2spip');
	
	// On va remplacer les <object... src=..jpg...</object> par <imgXX>
	$articles = sql_select('*','spip_articles','texte LIKE "%<object %"');
	$images = array();
	while($article = sql_fetch($articles)){
		$texte = $article['texte'];
		spip_log($article['id_article']);
		$pattern = "<object.*?<param.*?src=['\"](.*?)['\"].*?>.*?</object>";
		preg_match_all("#$pattern#",$texte,$matches);
		$images_article = array();
		if(is_array($matches)){
			foreach($matches[0] as $img){
				$image = array();
				if(preg_match('#<param.*?src.*?value=["\'](.*?)[\'"].*?>#',$img,$match_href) && preg_match('#(\.jpg|\.png|\.gif)#',$match_href[1],$extension)){
					spip_log($match_href);
					if(strlen($match_href[1]) > 2){
						$match_href[1] = str_replace('../','/',$match_href[1]);
						$image['complete'] = $img;
						if(preg_match('|^\/?wp-content\/|',$match_href[1],$img_wpcontent)){
							$image['href'] = $url_wordpress.(($match_href[1][0] == '/')?'':'/').$match_href[1];
							$image['statut'] = 'ok';
						}
						else if(preg_match('|^file:\/\/|',$match_href[1],$img_wpexplorer)){
							$image['href'] = $match_href[1];
							$image['statut'] = 'supprimer';
						}
						else if(preg_match('|\/wp-includes\/|',$match_href[1],$img_wpinclude)){
							$image['href'] = $match_href[1];
							$image['statut'] = 'supprimer';
						}
						else{
							$image['href'] = $match_href[1];
							$image['statut'] = 'ok';
						}
					}
				}
				if(isset($image['statut']))
					$images_article[] = $image;
			}

			foreach($images_article as $i => $image){
				if($image['statut']=='ok'){
					if($id_document = is_array($image['href']) ? false : sql_getfetsel('id_document','spip_documents','fichier='.sql_quote($image['href'])))
						$texte = str_replace($image['complete'],'<img'.$id_document.'>',$texte);
					else{
						include_spip('action/joindre');
						if(isset($image['distante']) && $image['distante'] == 'non')
							$joindre2 = charger_fonction('joindre1', 'inc');
						else
							$joindre2 = charger_fonction('joindre2', 'inc');

						if(isset($image['title']))
							$titre = $image['title'];
						elseif(isset($image['alt']))
							$titre = $image['alt'];
						if($id_document = $joindre2($image['href'], 'image', 'article', $article['id_article'], 0, $hash, $redirect, $documents_actifs, $iframe_redirect)){
							$texte = str_replace($image['complete'],'<img'.$id_document.'>',$texte);
							if(isset($titre))
								sql_updateq('spip_documents',array('titre'=>$titre),'id_document='.intval($id_document));
						}
					}
				}else if($image['statut']=='supprimer')
					$texte = str_replace($image['complete'],'',$texte);
			}
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		$images[$article['id_article']] = $images_article;
	}
	spip_log($images,'wp2spip');
	spip_log(count($modifies).' articles modifiés après correction des '.$url_wordpress.'/wp-content/uploads/...','wp2spip');
	spip_log($modifies,'wp2spip');

}
?>