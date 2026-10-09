<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Conversion du HTML de Wordpress (wp2spip_html_spip()) puis rendu par SPIP (propre()) : le texte affiché par SPIP
 * est celui qu'affiche Wordpress, et la structure (gras, listes, tableaux…) celle du HTML d'origine
 */
final class HtmlSpipTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		include_spip('inc/wp2spip_html');
		include_spip('inc/texte');
	}

	/**
	 * Texte visible d'un HTML : sans balises, entités décodées, blancs réduits
	 */
	private static function visible(string $html): string
	{
		$html = preg_replace('#<span class=.spip-puce[^>]*>.*?</span>#s', '', $html);
		$texte = html_entity_decode(strip_tags(str_replace(array('<br', '<p', '<li', '<td', '<th', '<h'), array(' <br', ' <p', ' <li', ' <td', ' <th', ' <h'), $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		return trim(preg_replace('/[\s\x{a0}\x{202f}]+/u', ' ', $texte));
	}

	public static function documentationSpip(): array
	{
		return array(
			'gras et italique' => array('<p>Le gras s’écrit {{gras}}, l’italique {italique}, l’intertitre {{{Intertitre}}}.</p>'),
			'liens et notes' => array('<p>Un lien : [texte->https://www.spip.net], une ancre [nom&lt;-], une note [[texte de la note]].</p>'),
			'listes et tableaux' => array("<p>-* premier élément<br>-** sous-élément<br>-# numéroté</p>\n<p>| {{Titre}} | colonne |<br>| case | case |</p>"),
			'saut de ligne et filet' => array('<p>Ligne<br>_ suite forcée<br>----<br>~ espace insécable</p>'),
			'balises SPIP en texte' => array('<p>&lt;code&gt;, &lt;cadre&gt;, &lt;quote&gt;, &lt;poesie&gt;, &lt;img12|left&gt; et &lt;html&gt;</p>'),
			'code en ligne' => array('<p>Exemple : <code>{{gras}} [lien->url]</code> dans le texte.</p>'),
			'bloc de code' => array("<pre class=\"wp-block-code\"><code>{{{Intertitre}}}\n-* liste\n[lien->https://www.spip.net]</code></pre>"),
			'préformaté' => array("<pre>{{gras}}\n-* liste</pre>"),
			'liste HTML de raccourcis' => array('<ul><li>{{gras}}</li><li>[lien->url]</li><li>| a | b |</li></ul>'),
			'tableau HTML de raccourcis' => array('<table><tr><th>Raccourci</th><th>Effet</th></tr><tr><td>{{x}}</td><td>gras</td></tr><tr><td>[[note]]</td><td>note</td></tr></table>'),
			'lien qui montre un raccourci' => array('<p><a href="https://www.spip.net/fr_article1578.html">[->url] et {{x}}</a></p>'),
		);
	}

	#[DataProvider('documentationSpip')]
	public function testRaccourcisAffichesTelsQuels(string $html): void
	{
		foreach (array(true, false) as $autop) {
			$spip = wp2spip_html_spip($html, array('autop' => $autop));
			$this->assertSame(self::visible($html), self::visible(propre($spip)), "autop " . var_export($autop, true) . " : $spip");
		}
	}

	public static function structures(): array
	{
		return array(
			'gras' => array('<p>a <strong>b</strong></p>', '#<strong[^>]*>b</strong>#'),
			'gras et italique imbriqués' => array('<p><em><strong>c</strong></em></p>', '#<i[^>]*>\s*<strong[^>]*>c</strong>\s*</i>#'),
			'intertitre' => array('<h2>Titre <em>x</em></h2>', '#<h\d[^>]*>Titre <i[^>]*>x</i>\s*</h\d>#'),
			'lien' => array('<p><a href="https://example.test/a">texte</a></p>', '#<a href=["\']https://example.test/a["\'][^>]*>texte</a>#'),
			'liste imbriquée mixte' => array('<ul><li>a<ol><li>b</li></ol></li></ul>', '#<ul[^>]*><li[^>]*>\s*a\s*<ol[^>]*><li[^>]*>\s*b</li></ol></li></ul>#'),
			'tableau' => array('<table><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>', '#<thead>.*A.*</thead>.*<td[^>]*>1</td>#s'),
			'citation' => array('<blockquote><p>cité</p></blockquote>', '#<blockquote[^>]*>\s*<p>\s*cité</p>\s*</blockquote>#'),
			'saut de ligne' => array("<p>a<br>b</p>", '#a<br[^>]*>\s*b#'),
		);
	}

	#[DataProvider('structures')]
	public function testStructureRendue(string $html, string $motif): void
	{
		$this->assertMatchesRegularExpression($motif, propre(wp2spip_html_spip($html, array('autop' => false))));
	}
}
