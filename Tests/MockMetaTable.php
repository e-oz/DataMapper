<?php
namespace Jamm\DataMapper\Tests;

class MockMetaTable extends \Jamm\DataMapper\MetaTable
{
	public function __construct()
	{
		$this->setName('table_test');
		$this->setDbName('db_test');
		$Field = new \Jamm\DataMapper\Field('id');
		$Field->setAutoincrement(true);
		$Field->setPrimaryIndex(true);
		$this->addField($Field);
		$Field1 = new \Jamm\DataMapper\Field('key1');
		$Field1->setIndexed(true);
		$this->addField($Field1);
		$Field2 = new \Jamm\DataMapper\Field('key2');
		$Field2->setIndexed(true);
		$this->addField($Field2);
	}
}
