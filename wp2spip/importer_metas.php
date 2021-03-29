<?php

use Symfony\Component\Console\Helper\ProgressBar;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function wp2spip_importer_metas_dist($command) {
	// On va chercher les quelques infos importantes
	if ($options = sql_allfetsel(
		'*',
		'wp_options',
		array(
			sql_in('option_name', array('siteurl', 'blogname', 'admin_email', 'blogdescription'))
		),
		'',
		'',
		'',
		'',
		$command->base
	)) {
		include_spip('inc/config');
		include_spip('sale_fonctions');
		
		foreach ($options as $cle => $option) {
			$options[$option['option_name']] = $option['option_value'];
			unset($options[$cle]);
		}
		
		if ($options['siteurl']) {
			$command->output->writeln('* <comment>Adresse du site</comment> : ' . $options['siteurl']);
			ecrire_config('adresse_site', $options['siteurl']);
		}
		
		if ($options['blogname']) {
			$command->output->writeln('* <comment>Nom du site</comment> : ' . $options['blogname']);
			ecrire_config('nom_site', $options['blogname']);
		}
		
		if ($options['admin_email']) {
			$command->output->writeln('* <comment>Email webmaster</comment> : ' . $options['admin_email']);
			ecrire_config('email_webmaster', $options['admin_email']);
		}
		
		// dans Wordpress (wp-admin/options-general.php) il est indiqué Slogan du site qui est donc stocké dans blogdescription. On le met donc dans le slogan SPIP et pas le descriptif
		if ($options['blogdescription']) {
			$options['blogdescription'] = sale($options['blogdescription']);
			$command->output->writeln('* <comment>Slogan du site</comment> : ' . $options['blogdescription']);
			ecrire_config('slogan_site', $options['blogdescription']);
		}
	}
	
	// Une ligne vide
	$command->output->writeln('');
}
