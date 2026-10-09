<?php

declare(strict_types=1);

namespace Wp2spip\Tests\Integration;

/**
 * Médias joints à un article par importer_articles (wp2spip_importer_articles_documents()) : liés, et du statut de l'article
 *
 * Médias 967 et 968 du WordPress de test, rattachés au contenu 1177. L'article est créé par le test, puis retiré
 * avec ses liens ; le statut des documents est rétabli.
 */
final class DocumentsArticleTest extends WordpressTestCase
{
	private array $statuts = array();

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		include_spip('action/editer_liens');
		include_spip('wp2spip/importer_articles');
	}

	protected function setUp(): void
	{
		$this->statuts = array_column(sql_allfetsel('id_document, statut', 'spip_documents', sql_in('id_document', array(967, 968))), 'statut', 'id_document');
	}

	protected function tearDown(): void
	{
		sql_delete('spip_documents_liens', array('objet = "article"', 'id_objet = 1177'));
		sql_delete('spip_articles', 'id_article = 1177');
		foreach ($this->statuts as $id_document => $statut) {
			sql_updateq('spip_documents', array('statut' => $statut), 'id_document = ' . intval($id_document));
		}
	}

	private static function article(string $statut): void
	{
		sql_insertq('spip_articles', array('id_article' => 1177, 'id_wordpress' => 1177, 'titre' => 'Alignements', 'statut' => $statut, 'date' => '2020-01-02 03:04:05'));
	}

	public function testMediasDUnArticlePublie(): void
	{
		self::article('publie');
		wp2spip_importer_articles_documents(self::commande(), 1177, 1177);
		$this->assertSame(
			array(967 => 'publie', 968 => 'publie'),
			array_column(sql_allfetsel('d.id_document, d.statut', 'spip_documents as d join spip_documents_liens as l on l.id_document = d.id_document', array('l.objet = "article"', 'l.id_objet = 1177'), '', 'd.id_document'), 'statut', 'id_document')
		);
	}

	public function testMediasDUnArticleEnRedaction(): void
	{
		self::article('prepa');
		wp2spip_importer_articles_documents(self::commande(), 1177, 1177);
		$this->assertSame(array(), array_diff(array_column(sql_allfetsel('statut', 'spip_documents', sql_in('id_document', array(967, 968))), 'statut'), array('prop', 'prepa')));
		$this->assertSame(2, sql_countsel('spip_documents_liens', array('objet = "article"', 'id_objet = 1177')));
	}
}
