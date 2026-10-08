<?php
/**
 * Amorce des tests d'intégration : le SPIP installé dans vendor/spip/spip par scripts/install-spip-test.sh
 */
declare(strict_types=1);

$racine_spip = dirname(__DIR__) . '/vendor/spip/spip';
if (!is_file($racine_spip . '/config/connect.php')) {
	fwrite(STDERR, "SPIP de test absent : lancer composer install-spip-test\n");
	exit(1);
}
if (!defined('_SPIP_TEST_INC')) {
	define('_SPIP_TEST_INC', $racine_spip);
}
if (!defined('_SPIP_TEST_CHDIR')) {
	define('_SPIP_TEST_CHDIR', $racine_spip);
}

putenv('APP_ENV=test');
chdir($racine_spip);
require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($racine_spip . '/vendor/autoload.php')) {
	require_once $racine_spip . '/vendor/autoload.php';
}
require_once $racine_spip . '/ecrire/inc_version.php';

include_spip('inc/plugin');
actualise_plugins_actifs();
// Ne pas charger tests/bootstrap.php : ses imitations de fonctions SPIP entreraient en conflit avec SPIP
