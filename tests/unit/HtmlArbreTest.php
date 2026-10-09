<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/wp2spip_html_arbre.php';

/**
 * Arbre HTML5 du convertisseur : par Masterminds (PHP 8.1 à 8.3), mêmes nœuds et même HTML que par Dom\HTMLDocument
 * (PHP 8.4). Les valeurs attendues sont celles de Dom\HTMLDocument::saveHtml().
 */
final class HtmlArbreTest extends TestCase
{
	public static function medias(): array
	{
		return array(
			'attribut booléen' => array('<audio controls src="a.mp3"></audio>', '<audio controls="" src="a.mp3"></audio>'),
			'guillemets et esperluette' => array('<img src="a.jpg" alt="x &amp; &quot;y&quot;" class="c">', '<img src="a.jpg" alt="x &amp; &quot;y&quot;" class="c">'),
			'adresse à paramètres' => array('<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen width="5"></iframe>', '<iframe src="https://e.test/?a=1&amp;b=2" allowfullscreen="" width="5"></iframe>'),
			'svg et attribut à préfixe' => array('<svg viewBox="0 0 1 1"><use xlink:href="#i"></use><foreignObject><p>x</p></foreignObject></svg>', '<svg viewBox="0 0 1 1"><use xlink:href="#i"></use><foreignObject><p>x</p></foreignObject></svg>'),
			'élément vide' => array('<video controls><source src="v.mp4" type="video/mp4"></video>', '<video controls=""><source src="v.mp4" type="video/mp4"></video>'),
			'picture' => array('<picture><source srcset="a.webp"><img src="a.jpg"></picture>', '<picture><source srcset="a.webp"><img src="a.jpg"></picture>'),
			'mathml' => array('<math><mi>x</mi></math>', '<math><mi>x</mi></math>'),
			'espace insécable' => array('<img src="a.jpg" alt="a&nbsp;b">', '<img src="a.jpg" alt="a&nbsp;b">'),
			'chevrons dans un attribut' => array('<img alt="a<b>c">', '<img alt="a<b>c">'),
			'texte brut d’une iframe' => array('<iframe>x &amp; <b></iframe>', '<iframe>x &amp; <b></iframe>'),
			'texte échappé' => array('<svg><title>a &lt; b</title></svg>', '<svg><title>a &lt; b</title></svg>'),
			'zone de texte' => array('<textarea>a &lt; b</textarea>', '<textarea>a &lt; b</textarea>'),
			'option choisie' => array('<select><option selected>x</option></select>', '<select><option selected="">x</option></select>'),
			'commentaire' => array('<audio><!-- c --></audio>', '<audio><!-- c --></audio>'),
			'object et param' => array('<object data="a.swf"><param name="x" value="1"></object>', '<object data="a.swf"><param name="x" value="1"></object>'),
			'texte de repli' => array('<canvas>Texte &amp; repli</canvas>', '<canvas>Texte &amp; repli</canvas>'),
		);
	}

	#[DataProvider('medias')]
	public function testSerialisationMasterminds(string $html, string $attendu): void
	{
		$this->assertSame($attendu, wp2spip_html_serialiser(wp2spip_html_arbre_body_masterminds($html)->firstChild));
	}

	#[DataProvider('medias')]
	public function testSerialisationDuParseurChoisi(string $html, string $attendu): void
	{
		$this->assertSame($attendu, wp2spip_html_serialiser(wp2spip_html_arbre_body($html)->firstChild));
	}

	public function testNoeudsMasterminds(): void
	{
		$noeuds = iterator_to_array(wp2spip_html_arbre_body_masterminds('a<B>b</B><!-- c -->')->childNodes);
		$this->assertTrue(wp2spip_html_est_texte($noeuds[0]));
		$this->assertTrue(wp2spip_html_est_element($noeuds[1]));
		$this->assertSame('b', $noeuds[1]->localName);
		$this->assertFalse(wp2spip_html_est_texte($noeuds[2]));
		$this->assertFalse(wp2spip_html_est_element($noeuds[2]));
	}

	public static function reglesAjoutees(): array
	{
		return array(
			'section et rangée implicites' => array('<table><td>a</td></table>', '<table><tbody><tr><td>a</td></tr></tbody></table>'),
			'rangée implicite' => array('<table><tr><td>a</td></tr></table>', '<table><tbody><tr><td>a</td></tr></tbody></table>'),
			'paragraphe pour un </p> orphelin' => array('a</p>b', 'a<p></p>b'),
		);
	}

	#[DataProvider('reglesAjoutees')]
	public function testReglesHtml5Ajoutees(string $html, string $attendu): void
	{
		$body = wp2spip_html_arbre_body_masterminds($html);
		$this->assertSame($attendu, join('', array_map('wp2spip_html_serialiser', iterator_to_array($body->childNodes))));
	}

	public static function erreurs(): array
	{
		return array(
			'PHP 8.4' => array(true, true, false, ''),
			'Masterminds' => array(false, true, true, ''),
			'sans DOM' => array(false, false, true, 'Extension PHP DOM absente'),
			'sans Masterminds' => array(false, true, false, 'Bibliothèque Masterminds HTML5-PHP introuvable'),
		);
	}

	#[DataProvider('erreurs')]
	public function testErreur(bool $dom_natif, bool $dom, bool $masterminds, string $debut): void
	{
		$erreur = wp2spip_html_arbre_erreur($dom_natif, $dom, $masterminds);
		if ($debut === '') {
			$this->assertSame('', $erreur);
			return;
		}
		$this->assertStringStartsWith($debut, $erreur);
		$this->assertStringContainsString(PHP_VERSION, $erreur);
	}

	public function testParseurDecrit(): void
	{
		$this->assertMatchesRegularExpression('/^(Dom\\\\HTMLDocument|Masterminds HTML5-PHP) \(PHP /', wp2spip_html_arbre_parseur());
	}
}
