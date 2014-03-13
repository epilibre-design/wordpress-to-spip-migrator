<?php

// Ceci est un fichier langue de SPIP -- This is a SPIP language file

///  Fichier produit par PlugOnet
// Module: paquet-wp2spip
// Langue: fr
// Date: 13-03-2014 22:16:23
// Items: 2

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

// W
	'wp2spip_description' => 'Importer un blog Wordpress dans un site SPIP local vide.

{{{Mode d\'emploi}}}
-* installer un SPIP à côté de Wordpress sur le serveur ;
-* configurer selon vos besoins, l\'utilisation des mots clefs des documents joints aux articles, les forums ;
-* installer le plugin comme d\'habitude ;
-* installer les plugins optionnels ([voir doc->http://www.spip-contrib.net/Wordpress-2-SPIP]) ;
-* menu \'configuration / migration depuis wordpress\' lancer la conversion ;
-* depuis le menu \'configuration / maintenance du site\' restaurer le fichier wp2spip.xml ({{Si l\'import se fige}}rafraîchissez la page) ;
-* si vous refaites la manipulation, repartez toujours depuis un site spip vierge.
-* une fois la base importée, se reconnecter avec son compte webmestre SPIP, et pour les auteurs Wordpress, ils doivent simplement recréer leur mot de passe via la page de login avec \'mot de passe perdu\' ;
-* Choisisez les rubriques des articles proposés à la publication (les pages importées) et publiez les ;
-* Choisisez les rubriques (depuis edition auteurs) des admins restreints signalés et devenus rédacteurs ;
	
http://contrib.spip.net/Wordpress-2-SPIP',
	'wp2spip_slogan' => 'Importer un blog Wordpress dans un site SPIP local vide',
);
?>