<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Conversion des blocs de l'éditeur et des galeries (inc/wp2spip_blocs.php), sur les médias du WordPress de test
 *
 * Chaque cas : contenu Wordpress, texte attendu après conversion, sale et réinsertion des marqueurs (<albumN> pour
 * un album créé), albums créés, légendes devenues descriptifs, bilan. Albums, légendes et statuts des documents sont rétablis après chaque cas.
 */
final class BlocsTest extends WordpressTestCase
{
	private array $albums = array();
	private array $documents = array();

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('inc/wp2spip_blocs');
		include_spip('sale_fonctions');
	}

	protected function setUp(): void
	{
		$this->albums = array();
		// Descriptif (légendes) et statut (publié par un album) des documents, rétablis après le cas
		$this->documents = sql_allfetsel('id_document, descriptif, statut', 'spip_documents', 'id_wordpress > 0');
	}

	protected function tearDown(): void
	{
		foreach ($this->documents as $document) {
			sql_updateq('spip_documents', array('descriptif' => $document['descriptif'], 'statut' => $document['statut']), 'id_document = ' . intval($document['id_document']));
		}
		if ($this->albums) {
			sql_delete('spip_documents_liens', array('objet = "album"', sql_in('id_objet', $this->albums)));
			sql_delete('spip_albums_liens', sql_in('id_album', $this->albums));
			sql_delete('spip_albums', sql_in('id_album', $this->albums));
		}
	}

	#[DataProvider('cas')]
	public function testConversion(array $test): void
	{
		$wp_post = array(
			'ID' => $test['id_wordpress'] ?? 0,
			'post_title' => 'Essai',
			'post_date' => '2020-01-02 03:04:05',
			'post_content' => $test['contenu'],
		);
		$url_wordpress = sql_getfetsel('option_value', wp2spip_table('options'), 'option_name = "siteurl"', '', '', '', '', self::BASE);
		$contexte = wp2spip_contexte_blocs(self::commande(), $wp_post, $url_wordpress);
		$obtenu = trim(wp2spip_restaurer_blocs(sale(wp2spip_convertir_blocs($test['contenu'], $contexte)), $contexte));
		$this->albums = $contexte['albums'];

		$this->assertSame($test['attendu'], preg_replace('/<album\d+>/', '<albumN>', $obtenu), 'texte converti');
		$this->assertCount(count($test['albums'] ?? array()), $contexte['albums'], 'albums créés');
		foreach ($test['albums'] ?? array() as $n => $album) {
			$id_album = intval($contexte['albums'][$n]);
			$ligne = sql_fetsel('titre, descriptif, statut, date', 'spip_albums', 'id_album = ' . $id_album);
			$this->assertSame(
				array('titre' => $album['titre'], 'descriptif' => $album['descriptif'], 'statut' => 'publie', 'date' => '2020-01-02 03:04:05'),
				$ligne,
				"album $n"
			);
			$documents = sql_allfetsel('id_document', 'spip_documents_liens', array('objet = "album"', 'id_objet = ' . $id_album), '', 'rang_lien');
			$this->assertSame($album['documents'], array_map('intval', array_column($documents, 'id_document')), "documents de l’album $n");
			$this->assertSame(
				array('publie'),
				array_values(array_unique(array_column(sql_allfetsel('statut', 'spip_documents', sql_in('id_document', $album['documents'])), 'statut'))),
				"documents de l’album $n publiés"
			);
		}
		$this->assertSame($test['introuvables'] ?? 0, $contexte['bilan']['medias_introuvables'], 'médias introuvables au bilan');
		$this->assertSame($test['retirees'] ?? 0, $contexte['bilan']['images_retirees'], 'images retirées de leur album au bilan');
		if (isset($test['inconnus'])) {
			$this->assertSame($test['inconnus'], $contexte['bilan']['inconnus'], 'blocs inconnus au bilan');
		}
		foreach ($test['descriptifs'] ?? array() as $id_document => $descriptif) {
			$this->assertSame($descriptif, sql_getfetsel('descriptif', 'spip_documents', 'id_document = ' . $id_document), "descriptif du document $id_document");
		}
	}

	public static function cas(): array
	{
		return array_column(array_map(fn($test) => array($test['nom'], array($test)), array(
			array(
				'nom' => 'image centrée, retrouvée par son fichier (attribut id faux)',
				'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"center"} -->
<div class="wp-block-image"><figure class="aligncenter"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-580x300-1.jpg" alt="Image Alignment 580x300" class="wp-image-906"/></figure></div>
<!-- /wp:image -->
HTML,
				'attendu' => '<img967|center>',
			),
			array(
				'nom' => 'image liée et légendée : lien gardé, légende en descriptif',
				'contenu' => <<<'HTML'
<!-- wp:image {"id":906,"align":"left"} -->
<div class="wp-block-image"><figure class="alignleft"><a href="https://en.support.wordpress.com/images/image-settings/"><img src="http://wordpress.test/wp-content/uploads/2013/03/image-alignment-150x150-1.jpg" alt="" class="wp-image-906"/></a><figcaption>Une <strong>légende</strong></figcaption></figure></div>
<!-- /wp:image -->
HTML,
				'attendu' => '[<img968|left>->https://en.support.wordpress.com/images/image-settings/]',
				'descriptifs' => array(968 => 'Une {{légende}}'),
			),
			array(
				'nom' => 'galerie depuis Wordpress 5.9 (blocs image enfants)',
				'contenu' => <<<'HTML'
<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped"><!-- wp:image {"id":755,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://wordpress.test/wp-content/uploads/2008/06/100_5540.jpg" alt="Golden Gate Bridge" class="wp-image-755"/><figcaption class="wp-element-caption">Golden Gate Bridge</figcaption></figure>
<!-- /wp:image -->

<!-- wp:image {"id":617,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://wordpress.test/wp-content/uploads/2008/06/dsc20050813_115856_52.jpg" alt="dsc20050813_115856_52" class="wp-image-617"/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery -->
HTML,
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(755, 617))),
				'descriptifs' => array(755 => 'Golden Gate Bridge'),
			),
			array(
				'nom' => 'galerie avant Wordpress 5.9 (liste blocks-gallery-item), légende de galerie',
				'contenu' => <<<'HTML'
<!-- wp:gallery {"ids":[],"linkTo":"attachment","className":"alignfull"} -->
<figure class="wp-block-gallery columns-3 is-cropped alignfull"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="https://wpthemetestdata.wordpress.com/2010/09/10/post-format-gallery/canola2/"><img src="http://wordpress.test/wp-content/uploads/2008/06/canola2.jpg" alt="canola" data-id="611" class="wp-image-611"/></a></figure></li><li class="blocks-gallery-item"><figure><img src="http://wordpress.test/wp-content/uploads/2008/06/cep00032.jpg" alt="Sunburst Over River" data-id="756" class="wp-image-756"/><figcaption class="blocks-gallery-item__caption">Sunburst over the Clinch River</figcaption></figure></li></ul><figcaption class="blocks-gallery-caption"><em>(gallery caption)</em> 3 columns</figcaption></figure>
<!-- /wp:gallery -->
HTML,
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '{(gallery caption)} 3 columns', 'documents' => array(611, 756))),
				'descriptifs' => array(756 => 'Sunburst over the Clinch River'),
			),
			array(
				'nom' => 'deux galeries dans un contenu : titres numérotés',
				'contenu' => '[gallery ids="770,771"]' . "\n\n" . '[gallery columns=2 ids="757"]',
				'attendu' => "<albumN>\n\n<albumN>",
				'albums' => array(
					array('titre' => 'Essai (galerie 1)', 'descriptif' => '', 'documents' => array(770, 771)),
					array('titre' => 'Essai (galerie 2)', 'descriptif' => '', 'documents' => array(757)),
				),
			),
			array(
				'nom' => 'raccourci [gallery] sans ids : images rattachées au contenu, par menu_order puis ID',
				'id_wordpress' => 555,
				'contenu' => '[gallery columns="9"]',
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(611, 617, 755, 756, 761, 770, 771, 757))),
			),
			array(
				'nom' => 'galerie dont une image manque : album des autres, image manquante signalée au bilan',
				'contenu' => '[gallery ids="770,999999,771"]',
				'attendu' => '<albumN>',
				'albums' => array(array('titre' => 'Essai', 'descriptif' => '', 'documents' => array(770, 771))),
				'retirees' => 1,
			),
			array(
				'nom' => 'galerie dont aucune image n’est retrouvée : pas d’album, HTML gardé',
				'contenu' => '[gallery ids="999999"]',
				'attendu' => '[gallery ids="999999"]',
				'introuvables' => 1,
			),
			array(
				'nom' => 'couverture, ancien format : texte dans le HTML du bloc',
				'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","align":"left","id":761} -->
<div class="wp-block-cover has-background-dim alignleft" style="background-image:url(http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg)"><p class="wp-block-cover-text">This is a left aligned cover block.</p></div>
<!-- /wp:cover -->
HTML,
				'attendu' => "<div class=\"wp-block-cover\">\n\n<doc761>\n\nThis is a left aligned cover block.\n\n</div>",
			),
			array(
				'nom' => 'couverture, format actuel : texte dans un bloc enfant',
				'contenu' => <<<'HTML'
<!-- wp:cover {"url":"http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg","id":761,"dimRatio":50} -->
<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-761" alt="Wind Farm" src="http://wordpress.test/wp-content/uploads/2008/06/dsc20050102_192118_51.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Cover <strong>block</strong></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
HTML,
				'attendu' => "<div class=\"wp-block-cover\">\n\n<doc761>\n\nCover {{block}}\n\n</div>",
			),
			array(
				'nom' => 'colonnes : structure gardée, classes de présentation retirées',
				'contenu' => <<<'HTML'
<!-- wp:columns {"style":{"spacing":{"blockGap":"2em"}}} -->
<div class="wp-block-columns is-layout-flex" style="gap:2em"><!-- wp:column -->
<div class="wp-block-column has-background" style="background-color:#eee"><!-- wp:paragraph -->
<p>Première colonne</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p>Seconde colonne</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
HTML,
				'attendu' => "<div class=\"wp-block-columns\">\n\n<div class=\"wp-block-column\">\n\nPremière colonne\n\n</div>\n\n<div class=\"wp-block-column\">\n\nSeconde colonne\n\n</div>\n\n</div>",
			),
			array(
				'nom' => 'tableau légendé : légende dans son propre paragraphe',
				'contenu' => <<<'HTML'
<!-- wp:table {"className":"is-style-regular"} -->
<figure class="wp-block-table is-style-regular"><table><tbody><tr><td>a</td><td>b</td></tr></tbody></table><figcaption class="wp-element-caption">Table caption</figcaption></figure>
<!-- /wp:table -->
HTML,
				'attendu' => "<figure class=\"wp-block-table\">\n\n|  a | b |\n\n<figcaption>Table caption</figcaption>\n\n</figure>",
			),
			array(
				'nom' => 'bouton : lien SPIP dans la balise du bouton',
				'contenu' => <<<'HTML'
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link" href="https://wordpress.org/gutenberg/handbook/" style="border-radius:5px">Read <em>more</em></a></div>
<!-- /wp:button -->
HTML,
				'attendu' => '<div class="wp-block-button">[Read more->https://wordpress.org/gutenberg/handbook/]</div>',
			),
			array(
				'nom' => 'contenu embarqué : URL seule sur sa ligne, puis la légende',
				'contenu' => <<<'HTML'
<!-- wp:core-embed/youtube {"url":"https://youtu.be/ex8fMxXJDJw","type":"video","providerNameSlug":"youtube"} -->
<figure class="wp-block-embed-youtube wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper">
		https://youtu.be/ex8fMxXJDJw
</div><figcaption>Une vidéo</figcaption></figure>
<!-- /wp:core-embed/youtube -->
HTML,
				'attendu' => "https://youtu.be/ex8fMxXJDJw\n\nUne vidéo",
			),
			array(
				'nom' => 'vidéo de la médiathèque : document',
				'contenu' => <<<'HTML'
<!-- wp:video {"id":1690} -->
<figure class="wp-block-video"><video controls src="http://wordpress.test/wp-content/uploads/2013/12/2014-slider-mobile-behavior.mov"></video></figure>
<!-- /wp:video -->
HTML,
				'attendu' => '<doc1690>',
			),
			array(
				'nom' => 'blocs dynamiques retirés, espaceur et suite retirés',
				'contenu' => "<!-- wp:latest-posts /-->\n\n<!-- wp:paragraph -->\n<p>Texte</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:spacer {\"height\":70} -->\n<div style=\"height:70px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->\n\n<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->\n\n<!-- wp:query {\"queryId\":1} -->\n<div class=\"wp-block-query\"><!-- wp:post-title /--></div>\n<!-- /wp:query -->",
				'attendu' => 'Texte',
			),
			array(
				'nom' => 'bloc dynamique qui a du contenu enregistré : contenu gardé',
				'contenu' => "<!-- wp:query {\"queryId\":2} -->\n<div class=\"wp-block-query\"><!-- wp:post-title /-->\n\n<!-- wp:query-no-results -->\n<!-- wp:paragraph -->\n<p>Aucun résultat</p>\n<!-- /wp:paragraph -->\n<!-- /wp:query-no-results --></div>\n<!-- /wp:query -->",
				'attendu' => "<div class=\"wp-block-query\">\n\nAucun résultat\n\n</div>",
			),
			array(
				'nom' => 'bloc réutilisable (wp:block) : traité comme un bloc inconnu',
				'contenu' => "<p>Avant</p>\n\n<!-- wp:block {\"ref\":123} /-->",
				'attendu' => 'Avant',
				'inconnus' => array('core/block' => 1),
			),
			array(
				'nom' => 'bloc inconnu : contenu gardé, passé par sale',
				'contenu' => "<!-- wp:mon-extension/encart {\"couleur\":\"rouge\"} -->\n<div class=\"encart\"><p>Un encart</p></div>\n<!-- /wp:mon-extension/encart -->",
				'attendu' => "<div class=\"encart\">Un encart\n\n</div>",
			),
			array(
				'nom' => 'contenu sans bloc : inchangé',
				'contenu' => '<p>Texte <em>classique</em></p>',
				'attendu' => 'Texte {classique}',
			),
		)), 1, 0);
	}
}
