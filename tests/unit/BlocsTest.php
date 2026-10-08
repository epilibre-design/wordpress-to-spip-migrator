<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip_blocs.php';

/**
 * Fonctions sans base de inc/wp2spip_blocs.php : analyse des blocs, nettoyage du HTML, types de blocs
 */
final class BlocsTest extends TestCase
{
	public function testAnalyserBlocsImbriques(): void
	{
		$contenu = "<p>Avant</p>\n<!-- wp:columns {\"align\":\"wide\"} --><div class=\"wp-block-columns\"><!-- wp:column --><div>A</div><!-- /wp:column --></div><!-- /wp:columns -->";
		$this->assertSame(
			array('nom' => '', 'attributs' => array(), 'morceaux' => array(
				"<p>Avant</p>\n",
				array('nom' => 'core/columns', 'attributs' => array('align' => 'wide'), 'morceaux' => array(
					'<div class="wp-block-columns">',
					array('nom' => 'core/column', 'attributs' => array(), 'morceaux' => array('<div>A</div>')),
					'</div>',
				)),
			)),
			wp2spip_analyser_blocs($contenu)
		);
	}

	public function testAnalyserBlocsAutoFermantsEtEspaceDeNom(): void
	{
		$racine = wp2spip_analyser_blocs('<!-- wp:latest-posts {"postsToShow":3} /--><!-- wp:mon-extension/encart --><p>E</p><!-- /wp:mon-extension/encart -->');
		$this->assertSame(
			array(
				array('nom' => 'core/latest-posts', 'attributs' => array('postsToShow' => 3), 'morceaux' => array()),
				array('nom' => 'mon-extension/encart', 'attributs' => array(), 'morceaux' => array('<p>E</p>')),
			),
			$racine['morceaux']
		);
	}

	public function testAnalyserBlocsAttributsAvecAccolades(): void
	{
		$racine = wp2spip_analyser_blocs('<!-- wp:paragraph {"style":{"color":{"text":"#000"}},"content":"a } b"} --><p>T</p><!-- /wp:paragraph -->');
		$this->assertSame(array('style' => array('color' => array('text' => '#000')), 'content' => 'a } b'), $racine['morceaux'][0]['attributs']);
	}

	public function testAnalyserBlocsFermantEnTrop(): void
	{
		$racine = wp2spip_analyser_blocs('<p>A</p><!-- /wp:paragraph --><p>B</p>');
		$this->assertSame(array('<p>A</p>', '<p>B</p>'), $racine['morceaux']);
	}

	#[DataProvider('balises')]
	public function testNettoyerBalises(string $attendu, string $html): void
	{
		$this->assertSame($attendu, wp2spip_nettoyer_balises($html));
	}

	public static function balises(): array
	{
		return array(
			'classes wp-block gardées, style retiré' => array('<div class="wp-block-group">', '<div class="wp-block-group is-layout-flow has-background" style="color:red">'),
			'lien : href gardé' => array('<a href="/x">', '<a class="lien" href="/x" target="_blank">'),
			'image auto-fermante' => array('<img src="a.jpg" alt="A" />', '<img src="a.jpg" alt="A" width="10"/>'),
			'cellule fusionnée' => array('<td colspan="2">', '<td colspan="2" style="x">'),
			'majuscules' => array('<p>', '<P CLASS="x">'),
		);
	}

	public function testBaliseNettoyee(): void
	{
		$this->assertSame('<figure class="wp-block-table">', wp2spip_balise_nettoyee('FIGURE', ' class="wp-block-table is-style-stripes" id="t"'));
	}

	#[DataProvider('legendes')]
	public function testBlocLegende(string $attendu, string $html, string $classe): void
	{
		$this->assertSame($attendu, wp2spip_bloc_legende($html, $classe));
	}

	public static function legendes(): array
	{
		$galerie = '<figure><figcaption class="blocks-gallery-item__caption">Image</figcaption><figcaption class="blocks-gallery-caption"> Galerie </figcaption></figure>';
		return array(
			'première légende' => array('Image', $galerie, ''),
			'légende d’une classe' => array('Galerie', $galerie, 'blocks-gallery-caption'),
			'sans légende' => array('', '<figure><img src="a.jpg"></figure>', ''),
		);
	}

	#[DataProvider('types')]
	public function testConversionBloc(string $attendu, string $type): void
	{
		$this->assertSame($attendu, wp2spip_conversion_bloc($type));
	}

	public static function types(): array
	{
		return array(
			'image' => array('wp2spip_bloc_image', 'image'),
			'galerie' => array('wp2spip_bloc_gallery', 'gallery'),
			'colonnes' => array('wp2spip_bloc_structure', 'columns'),
			'paragraphe' => array('wp2spip_bloc_laisser', 'paragraph'),
			'espaceur' => array('wp2spip_bloc_vide', 'spacer'),
			'contenu embarqué' => array('wp2spip_bloc_embed', 'embed'),
			'dynamique listé' => array('wp2spip_bloc_retirer', 'latest-posts'),
			'dynamique par préfixe' => array('wp2spip_bloc_retirer', 'post-title'),
			'inconnu' => array('', 'mon-bloc'),
		);
	}

	#[DataProvider('dynamiques')]
	public function testBlocDynamique(bool $attendu, string $type): void
	{
		$this->assertSame($attendu, wp2spip_bloc_dynamique($type));
	}

	public static function dynamiques(): array
	{
		return array(
			'query' => array(true, 'query'),
			'comment-template' => array(true, 'comment-template'),
			'site-title' => array(true, 'site-title'),
			'paragraph' => array(false, 'paragraph'),
			'block (réutilisable)' => array(false, 'block'),
		);
	}
}
