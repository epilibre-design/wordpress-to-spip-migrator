<?php
/**
 * Taxonomies : term_taxonomy_id différent de term_id, comme sur un site où des termes ont été partagés puis séparés
 */
return array(
	array('term_taxonomy_id' => 101, 'term_id' => 1, 'taxonomy' => 'category', 'count' => 1),
	array('term_taxonomy_id' => 130, 'term_id' => 30, 'taxonomy' => 'post_tag', 'description' => 'Une <em>boisson</em>', 'count' => 1),
	array('term_taxonomy_id' => 131, 'term_id' => 31, 'taxonomy' => 'post_tag', 'count' => 0),
	array('term_taxonomy_id' => 132, 'term_id' => 32, 'taxonomy' => 'post_tag', 'count' => 1),
	array('term_taxonomy_id' => 133, 'term_id' => 33, 'taxonomy' => 'post_tag', 'count' => 1),
);
