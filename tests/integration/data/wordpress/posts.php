<?php
/**
 * Contenus du WordPress de test : articles, pages (dont une hiérarchie), contenus privés, médias
 *
 * Colonnes absentes : valeurs par défaut de schema.sql. Médias : fichiers sous data/wp-content/uploads.
 */
$media = fn($id, $titre, $fichier, $parent, $ordre = 0, $type = 'image/jpeg') => array(
	'ID' => $id, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_title' => $titre,
	'post_name' => strtolower(str_replace(' ', '-', $titre)), 'post_parent' => $parent, 'menu_order' => $ordre,
	'post_mime_type' => $type, 'guid' => "http://wordpress.test/wp-content/uploads/$fichier",
	'post_date' => '2013-03-15 10:00:00', 'post_modified' => '2013-03-15 10:00:00',
);
$contenu = fn($id, $type, $titre, $nom, $champs = array()) => $champs + array(
	'ID' => $id, 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $titre, 'post_name' => $nom,
	'post_content' => "<p>Contenu de $titre</p>", 'post_date' => '2020-01-02 03:04:05', 'post_modified' => '2020-01-02 03:04:05',
);

return array(
	$contenu(1, 'post', 'Bonjour', 'bonjour'),
	$contenu(555, 'post', 'Galerie', 'galerie', array('post_content' => '[gallery]')),
	$contenu(1177, 'post', 'Alignements', 'alignements', array('post_content' => '<!-- wp:image {"id":967} --><figure class="wp-block-image"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="" class="wp-image-967"/></figure><!-- /wp:image -->')),
	// Hiérarchie : 10 a pour enfants 12 (ordre 1), puis 13 et 11 (ordre 2, par titre) ; 12 a pour enfant 14 ; 15 a pour parent un article
	$contenu(10, 'page', 'Parent', 'parent'),
	$contenu(11, 'page', 'Enfant C', 'enfant-c', array('post_parent' => 10, 'menu_order' => 2)),
	$contenu(12, 'page', 'Enfant A', 'enfant-a', array('post_parent' => 10, 'menu_order' => 1)),
	$contenu(13, 'page', 'Enfant B', 'enfant-b', array('post_parent' => 10, 'menu_order' => 2)),
	$contenu(14, 'page', 'Petite-fille', 'petite-fille', array('post_parent' => 12)),
	$contenu(15, 'page', 'Sous un article', 'sous-un-article', array('post_parent' => 1)),
	// Contenus restreints, et un brouillon
	$contenu(20, 'post', 'Privé', 'prive', array('post_status' => 'private')),
	$contenu(21, 'post', 'Protégé', 'protege', array('post_password' => 'secret')),
	$contenu(22, 'post', 'Brouillon', 'brouillon', array('post_status' => 'draft')),
	// Médias : ceux de la galerie [gallery] de 555 dans l'ordre menu_order, puis ID (757 en dernier)
	$media(611, 'canola2', '2008/06/canola2.jpg', 555),
	$media(617, 'dsc20050813_115856_52', '2008/06/dsc20050813_115856_52.jpg', 555),
	$media(755, 'Golden Gate Bridge', '2008/06/100_5540.jpg', 555),
	$media(756, 'Sunburst Over River', '2008/06/cep00032.jpg', 555),
	$media(757, 'Boardwalk', '2008/06/dcp_2082.jpg', 555, 1),
	$media(761, 'Wind Farm', '2008/06/dsc20050102_192118_51.jpg', 555),
	$media(770, 'Huatulco Coastline', '2008/06/img_0767.jpg', 555),
	$media(771, 'Boat Barco Texture', '2008/06/img_8399.jpg', 555),
	$media(967, 'Image Alignment 580x300', '2013/03/image-alignment-580x300-1.jpg', 1177),
	$media(968, 'Image Alignment 150x150', '2013/03/image-alignment-150x150-1.jpg', 1177),
	$media(1690, '2014-slider-mobile-behavior', '2013/12/2014-slider-mobile-behavior.mov', 0, 0, 'video/quicktime'),
);
