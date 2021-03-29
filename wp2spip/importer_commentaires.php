<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 *  Structure de la table  wp_comments sur un WordPress 5.6.2 : 
 *  comment_ID, 
 *  comment_post_ID, 
 *  comment_author, 
 *  comment_author_email, 
 *  comment_author_url, 
 *  comment_author_IP, 
 *  comment_date, 
 *  comment_date_gmt, 
 *  comment_content, 
 *  comment_karma, 
 *  comment_approved, 
 *  comment_agent, 
 *  comment_type, 
 *  comment_parent, 
 *  user_id)
*/

    function wp2spip_importer_commentaires_dist($command) {

    // On va chercher tous les commentaires Wordpress 
    if ($wp_comments  = sql_allfetsel(
		'*',
		'wp_comments',
		array(
			sql_in('comment_approved', array('1')), // on évite de prendre les spam 
			sql_in('comment_type', array('comment')), // on évite de prendre les trackback et pingback 
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
		include_spip('inc/forum');
        
		$nb_comments = count($wp_comments);
		$nb_import = 0;
		$nb_maj = 0;
		$command->output->writeln("$nb_comments commentaires à importer.");
		
		$progressBar = new ProgressBar($command->output, $nb_comments);
		$progressBar->setFormat('verbose');
		$progressBar->setRedrawFrequency(1);
		$progressBar->start();
		

		foreach ($wp_comments  as $wp_comment) {
			$comment_post_ID = intval($wp_comment['comment_post_ID']);
            $comment_ID = intval($wp_comment['comment_ID']);
            unset($id_article);
			$l_article = sql_fetsel('id_article', 'spip_articles', 'id_wordpress = '.$comment_post_ID) ;
            $id_article=intval($l_article['id_article']);      
            //$command->output->writeln("$id_article ");
        
            // récuperation des données
            $set_forum = array(
                'id_objet' => $id_article,
                'objet' => 'article',
               'date_heure' => $wp_comment['comment_date'],
                'date_thread' => $wp_comment['comment_date'] ,
                'texte' => sale($wp_comment['comment_content']),
                'auteur' => $wp_comment['comment_author'],
                'email_auteur' => $wp_comment['comment_author_email'],
                'statut' => 'publie',
            );
        
            // Insertion des forums
            $id_forum = sql_insertq('spip_forum', $set_forum);
        
            // mise à jour de la valeur id_thread = id_forum
            $res = sql_updateq('spip_forum', array('id_thread' => $id_forum), 'id_forum='.intval($id_forum));
			$progressBar->advance();

        }    
        
		// Une ligne vide à la fin
		$command->output->writeln('');
    }
}

