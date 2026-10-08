<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip.php';

/**
 * Fonctions de inc/wp2spip.php
 */
final class Wp2spipTest extends TestCase
{
	#[DataProvider('entites')]
	public function testDecoderEntites(string $attendu, string $texte): void
	{
		$this->assertSame($attendu, wp2spip_decoder_entites($texte));
	}

	public static function entites(): array
	{
		return array(
			'entité nommée' => array('Été', '&Eacute;t&eacute;'),
			'entité numérique' => array('é', '&#233;'),
			'entité hexadécimale' => array('é', '&#xe9;'),
			'chevrons et esperluette gardés' => array('&lt;b&gt; &amp; &#60;', '&lt;b&gt; &amp; &#60;'),
			'guillemets' => array('« l’été »', '&laquo; l&rsquo;été &raquo;'),
			'texte sans entité' => array('Texte', 'Texte'),
		);
	}
}
