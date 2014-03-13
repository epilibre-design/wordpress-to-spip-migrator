<?php
/**
 * Plugin wp2spip
 * 
 * GNU/GPL v3
 * 
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Fonction de post clean du HTML dans les articles
 */
function action_wp2spip_html_dist(){
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!autoriser('configurer', 'plugins'))
		die('erreur');
	
	if($arg == 'dls'){
		$modifies = array();
		
		/**
		 * On cherche toute présence de <div style="text-align: center;"><dl id="attachment_1173"> <dt>...<imgXXX>.... </dt> <dd>....</dd> </dl></div> dans les textes des articles
		 */
		$articles = sql_select('texte,id_article','spip_articles',"texte REGEXP '<div.*<dl.*<dt.*</dt>.*</dl>.*</div>'");
		spip_log(sql_count($articles).' articles trouvés ayant des <div.*<dl.*<dt.*</dt>.*</dl>.*</div>');
		$pattern = "/<div(.*?)>.*?<dl.*?id=\"attachment_.*?<dt.*?>(.*?<(img|doc)(\d*).*?)<\/dt>(.*?<dd.*?>(.*)<\/dd>)?.*?<\/div>/";
		while($article = sql_fetch($articles)){
			$texte = $article['texte'];
			preg_match_all("$pattern",$texte,$matches);
			if(count($matches)>0){
				foreach($matches[0] as $i => $text){
					$id_document = $matches[4][$i];
					/**
					 * Si on a une description, on la met en titre du document SPIP
					 */
					if(isset($matches[6][$i]) && strlen($matches[6][$i]) > 0){
						$titre_document = $matches[6][$i];
						sql_updateq('spip_documents',array('titre'=>$titre_document),'id_document='.intval($id_document));
					}
					
					/**
					 * On regarde si on trouve un align à passer au modèle
					 */
					$align = false;
					if(preg_match('/(text-align|float)\s?:\s?(center|right|left)/',$matches[1][$i],$align))
						$align = $align[2];
					else if(preg_match('/mceIE(center|right|left)/',$matches[1][$i],$align))
						$align = $align[1];
						
					if($align){
						/**
						 * Préparation du modèle 
						 */
						$modele = '<'.	$matches[3][$i].$matches[4][$i];
						$search = $modele.'.*?>';
						/**
						 * Ajout de l'alignement
						 */
						$modele .= '|'.$align.'>';
						/**
						 * Remplacement de l'ancien modèle par le nouveau avec l'alignement
						 */
						$matches[2][$i] = preg_replace("/$search/",$modele, $matches[2][$i]);
						spip_log($matches[2][$i]);
					}
					
					/**
					 * Remplacement de l'ensemble par le contenu textuel
					 */
					$texte = str_replace($matches[0][$i],$matches[2][$i],$texte);
				}
			}
			/**
			 * Le texte est modifié ? on le met à jour en base de donnée
			 */
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		if(count($modifies) > 1){
			spip_log(count($modifies).' articles modifiés après correction des <div.*<dl.*<dt.*</dt>.*</dl>.*</div>','wp2spip');
			spip_log($modifies,'wp2spip');
		}
	}
	if($arg == 'catlist'){
		$modifies = array();
		
		/**
		 * On cherche toute présence de [catlist .*] dans les textes des articles
		 * 
		 * cf : CF : http://wordpress.org/plugins/list-category-posts/other_notes/
		 * 
		 * Attention : le sql_select produit du php qui va être interprété. 
		 * Donc il faut bien slasher les \\ qu'on lui envoie (@denisb), donc 3 fois \ plutôt que 2
		 *
		 */
		$articles = sql_select('texte,id_article','spip_articles',"texte REGEXP '\\\[catlist .*\\\]'");
		spip_log(sql_count($articles).' articles trouvés ayant des [catlist ...]');

		/**
		 * Tableau des attributs possibles
		 */
		$catlist_tags = array('id=\d*[\s\]]','name=\w*[\s\]]','orderby=\w*[\s\]]','order=\w*[\s\]]','numberposts=.*[\s\]]','date=.*[\s\]]','date_tag=.*[\s\]]','date_class=.*[\s\]]','dateformat=.*[\s\]]',
								'author=\d*[\s\]]','author_tag=.*[\s\]]','author_class=.*[\s\]]','template=.*[\s\]]','excerpt=\w*[\s\]]','excerpt_size=.*[\s\]]','excerpt_strip=.*[\s\]]',
								'excerpt_overwrite=.*[\s\]]','excerpt_tag=.*[\s\]]','excerpt_class=.*[\s\]]','exclude=.*[\s\]]','excludeposts=.*[\s\]]','offset=.*[\s\]]','tags=.*[\s\]]','exclude_tags=.*[\s\]]','content=.*[\s\]]',
								'content_tag=.*[\s\]]','content_class=.*[\s\]]','catlink=.*[\s\]]','catlink_string=.*[\s\]]','catlink_tag=.*[\s\]]','catlink_class=.*[\s\]]','comments=.*[\s\]]',
								'comments_tag=.*[\s\]]','comments_class=.*[\s\]]','thumbnail=.*[\s\]]','thumbnail_size=\d*[\s\]]','thumbnail_class=.*[\s\]]',
								'title_tag=.*[\s\]]','title_class=.*[\s\]]','post_type=.*[\s\]]','post_status=.*[\s\]]','post_parent=.*[\s\]]','class=.*[\s\]]',
								'customfield_value=.*[\s\]]','customfield_display=.*[\s\]]','customfield_orderby=.*[\s\]]','taxonomy=.*[\s\]]','categorypage=.*[\s\]]','category_count=\d*[\s\]]',
								'morelink=.*[\s\]]','morelink_class=.*[\s\]]','morelink_tag','posts_morelink=.*[\s\]]','posts_morelink_class=.*[\s\]]',
								'year=.*[\s\]]','monthnum=.*[\s\]]','search=.*[\s\]]','link_target=.*[\s\]]');
		/**
		 * Tableau de remplacement de l'orderby par des champs SPIP
		 */
		$order_by = array('title'=>'titre','author'=>'id_auteur','category'=>'id_rubrique','date'=>'date','ID'=>'id_article','modified' => 'maj','parent'=>'id_rubrique');
		/**
		 * En SPIP en général on cause français, c'est pour ça que les techos hype de l'aventure anglo saxonne ne nous aiment pas trop
		 * C'est pas grave on le revendique et on met oui à la place de yes
		 */
		$yes_no = array('yes'=>'oui','no'=>'non');
		while($article = sql_fetch($articles)){
			$texte = $article['texte'];
			/**
			 * On cherche les [catlist ...] dans le texte retourné
			 */
			preg_match_all("|\[catlist .*?\]|",$texte,$matches);
			if(count($matches) > 0){
				/**
				 * On cherche les attributs spécifiques
				 */
				foreach($matches[0] as $text){
					preg_match_all('#'.join('|',$catlist_tags).'#',$text,$matches_attr);
					/**
					 * On enleve le dernier caractère ' ' ou ']' inutile
					 */ 
					array_walk($matches_attr[0], function (&$v, $k) { $v = substr($v, 0, -1); });
					$attrs = array();
					/**
					 * On crée un table clé/valeur des attributs pour les filtrer ensuite
					 */
					foreach($matches_attr[0] as $attr){
						$attr = explode('=',$attr);
						$attrs[$attr[0]] = $attr[1];
					}
					/**
					 * On teste chaque attribut que l'on utilise et on le modifie si besoin
					 */
					foreach($attrs as $type => $value){
						switch($type){
							case 'id':
								$id = $value;
								unset($attrs['id']);
								break;
							case 'orderby':
								$attrs['tri'] = $order_by[$value];
								unset($attrs['orderby']);
								break;
							case 'numberposts':
								$attrs['pagination'] = $value;
								unset($attrs['numberposts']);
								break;
							case 'excerpt':
								$attrs['intro'] = $yes_no[$value];
								unset($attrs['excerpt']);
								break;
							case 'order':
								$attrs['ordre'] = ($value == 'ASC') ? 1:'-1';
								unset($attrs['order']);
								break;
						}
					}
					
					/**
					 * On génère le code de notre modèle <wp_catlistXX...>
					 */
					$replace = "<wp_catlist$id|";
					foreach($attrs as $type =>$value){
						$replace .= "$type=$value|";
					}
					$replace = substr($replace,0,-1).'>';
					
					/**
					 * On remplace [catlist..] par <wp_catlistXX|..>
					 */
					$texte = str_replace($text,$replace,$texte);
				}
			}
			/**
			 * Le texte est modifié ? on le met à jour en base de donnée
			 */
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		if(count($modifies) > 1){
			spip_log(count($modifies).' articles modifiés après correction des [catlist ...]','wp2spip');
			spip_log($modifies,'wp2spip');
		}
	}

	if($arg == 'spans'){
		/**
		 * On va tenter d'enlever le maximum de <span>...</span> modifiant le style de texte
		 * On ne laisse que les span avec underline
		 * On remplace les <span lang="...">.*</span> par <multi>[lang].*</multi>
		 * 
		 * On utilise la librairie simple_html_dom (http://simplehtmldom.sourceforge.net/)
		 * pour parser le html
		 */
		$modifies = array();
		/*$articles = sql_select('*','spip_articles','texte REGEXP "<span(.*)?>(.*)?</span>" AND id_article=386');*/
		$articles = sql_select('*','spip_articles','texte REGEXP "<span.*>.*</span>"');
		include_spip('inc/simple_html_dom');
		while($article = sql_fetch($articles)){
			$texte = trim($article['texte']);
			$maxretry = 10;
			//spip_log($texte);
			while($maxretry != 0){
				$retry = false;
				//spip_log('On passe');
				$maxretry--;
				/**
				 * On charge le dom en n'enlevant pas les \n \r\n etc... (5ème argument) 
				 * Pour que str_replace remplace bien ce que l'on souhaite
				 */
				$dom = str_get_html($texte, true, false, DEFAULT_TARGET_CHARSET, false);
				foreach($dom->find('span') as $element){
					spip_log('on a des spans?');
					if(!preg_match('|span|',$element->innertext,$match_spans)){
						if(!preg_match('|underline|',$element->outertext,$match_underline) && !preg_match('|lang=[\'"](.*?)[\'"]|',$element->outertext,$match_lang)){
							spip_log('On remplace "'.$element->outertext.'" par "'.$element->innertext.'"');
							$texte = str_replace($element->outertext,$element->innertext,$texte);
						}
						if($match_underline){
							spip_log('On match underline :');
							spip_log($match_underline);
						}
						if($match_lang){
							if(strlen(trim($element->innertext)) == 0){
								spip_log('On match lang, mais pas de texte on remplace '.$element->outertext.' par "'.$element->innertext.'"');
								$texte = str_replace($element->outertext,$element->innertext,$texte);
							}
							elseif(strlen($match_lang[1])>1){
								$langue = explode('-',strtolower($match_lang[1]));
								$langue = $langue[0];
								if(substr($element->innertext,0,9+strlen($langue)) != '<multi>['.$langue.']'){
									spip_log('On match lang, on remplace '.$element->outertext.' par <multi>['.$langue.']'.$element->innertext.'</multi>');
									$texte = str_replace($element->outertext,'<multi>['.$langue.']'.$element->innertext.'</multi>',$texte);
								}else{
									$texte = str_replace($element->outertext,$element->innertext,$texte);
									spip_log('On était déja dans un <multi>['.$langue.']');
								}
							}
						}
					}else{
						spip_log('On match span encore on va refaire un tour');
						$retry = true;
					}
				}
				if(!$retry){
					break;
				}
			}
			//spip_log($texte);
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		spip_log(count($modifies).' articles modifiés après correction des spans','wp2spip');
		spip_log($modifies,'wp2spip');
	}

	if($arg == 'font'){
		$modifies = array();
		// On va enlever les gras, italiques et intertitres vides {} { } {{}} etc...
		$articles = sql_select('*','spip_articles','texte REGEXP "<font.*>.*</font>"');
		include_spip('inc/simple_html_dom');
		while($article = sql_fetch($articles)){
			$texte = trim($article['texte']);
			$maxretry = 10;
			//spip_log($texte);
			while($maxretry != 0){
				$retry = false;
				//spip_log('On passe');
				$maxretry--;
				/**
				 * On charge le dom en n'enlevant pas les \n \r\n etc... (5ème argument) 
				 * Pour que str_replace remplace bien ce que l'on souhaite
				 */
				$dom = str_get_html($texte, true, false, DEFAULT_TARGET_CHARSET, false);
				foreach($dom->find('font') as $element){
					spip_log('on a des font?');
					if(!preg_match('|font|',$element->innertext,$match_spans)){
						spip_log('On remplace "'.$element->outertext.'" par "'.$element->innertext.'"');
						$texte = str_replace($element->outertext,$element->innertext,$texte);
					}else{
						spip_log('On match font encore on va refaire un tour');
						$retry = true;
					}
				}
				if(!$retry){
					break;
				}
			}
			//spip_log($texte);
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		spip_log(count($modifies).' articles modifiés après correction des font','wp2spip');
		spip_log($modifies,'wp2spip');
	}

	if($arg == 'accolades_vides'){
		$modifies = array();
		// On va enlever les gras, italiques et intertitres vides {} { } {{}} etc...
		$articles = sql_select('*','spip_articles','texte REGEXP "{+[[:space:]]*}+" OR texte REGEXP "{+ +}+"');
		while($article = sql_fetch($articles)){
			$texte = $article['texte'];
			$pattern = "\{{1,4}[\s ]*?\}{1,4}";
			preg_match_all("|$pattern|",$texte,$matches);
			if(is_array($matches)){
				$matches[0] = array_unique($matches[0]);
				spip_log($matches);
				foreach($matches[0] as $textevide){
					$texte = str_replace($textevide,'',$texte);
				}
				if($texte != $article['texte']){
					sql_updateq('spip_articles',array('texte'=>trim($texte)),'id_article='.intval($article['id_article']));
					$modifies[] = $article['id_article'];
				}
			}
		}
		spip_log(count($modifies).' articles modifiés après correction des accolades_vides','wp2spip');
		spip_log($modifies,'wp2spip');
	}
	
	if($arg == 'sauts'){
		
		// On va enlever \n\n\n
		$articles = sql_select('*','spip_articles','texte REGEXP "\n\n\n+"');
		while($article = sql_fetch($articles)){
			$texte = $article['texte'];
			$texte = preg_replace("/[\r\n]{3,}/","\n\n",$texte);
			if($texte != $article['texte']){
				sql_updateq('spip_articles',array('texte'=>$texte),'id_article='.intval($article['id_article']));
				$modifies[] = $article['id_article'];
			}
		}
		spip_log(count($modifies).' articles modifiés après correction des \n\n\n','wp2spip');
		spip_log($modifies,'wp2spip');
	}
}
?>