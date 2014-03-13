<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(
	// A
	'alerte_generation_fichier' => 'Le fichier de restauration sera place dans le répertoire tmp/dump',

	// B
	'bouton_recuperer_documents' => 'Récupérer les documents',
	'bouton_reparer_catlist' => 'Réparer les listes de catégories',
	'bouton_reparer_liens_internes' => 'Réparer les liens internes',
	
	// C
	
	// E
	'erreur_aucun_tag' => 'Aucun tag trouvé.',
	'erreur_fichier_non_cree' => 'Le fichier de restauration n\'a pas été créé.',
	'erreur_non_webmestre' => 'Vous devez avoir les droits de webmestre.',
	'explication_post_import' => 'Cette page permet de relever les incohérence suite à l\'importation de la base Wordpress.',
	
	// I
	'info_erreur_aucun_mot' => 'Aucun mot-clé trouvé!',
	'info_erreur_aucune_rubrique' => 'Veuillez créer au moins une catégorie dans Wordpress.',
	'info_nombre_documents' => '@nb@ documents.',
	'info_nombre_documents_orphelins' => '@nb@ documents orphelins qui ne seront pas importés',
	'info_nombre_evenements' => '@nb@ évènements.',
	'info_nombre_posts' => '@nb@ posts ou articles publiés.',
	'info_nombre_pages' => '@nb@ pages uniques (installez le plugin pages uniques si ce n\'est pas déjà fait, sinon ce seront des articles à classer et à publier).',

	// L
	'label_evenements' => 'Évènements',
	'label_generer_fichier' => 'Générer le fichier au format SPIP',
	
	// M
	'message_aller_maintenance' => 'Rendez-vous à la page <a href=\'@url@\' title=\'\'>Maintenance</a> du site et importer la base wp2spip.xml.',
	'message_attention_import' => 'Attention, cette restauration remplacera votre base SPIP actuelle.',
	'message_conversion_ok' => 'Base Wordpress convertie au format xml SPIP 2.1.12 !',
	'message_notice_site_vide' => 'Votre site SPIP ne semble pas vide, tout son contenu sera écrasé si vous restaurez la base avec le fichier créé depuis Wordpress.',
	'mot' => '1 mot-clé',
	'mots' => '@nb@ mots-clés',
	
	// T
	'tag' => '1 tag',
	'tags' => '@nb@ tags',
	'titre_contenu_base' => 'Contenu de la base de données Wordpress',
	'titre_page_migration' => 'Migration de Wordpress vers SPIP',
	'titre_post_import' => 'Actions de post-import de Wordpress',
);
?>