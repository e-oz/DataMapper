<?php
namespace Jamm\DataMapper\Tests;

class TestGateway extends \Jamm\Tester\ClassTest
{
	protected $Gateway;
	protected $inserted = false;
	protected $inserted_id;

	public function __construct(\Jamm\DataMapper\IStorageGateway $Gateway)
	{
		$this->Gateway = $Gateway;
	}

	public function setUpBeforeClass()
	{
		$this->Gateway->truncateTable();
	}

	public function tearDownAfterClass()
	{
		$this->Gateway->truncateTable();
	}

	public function testInsert()
	{
		if ($this->inserted) return true;
		$data = array('key1' => 'value1', 'key2' => 'value2');
		$id   = $this->Gateway->insert($data);
		$this->assertTrue($id);
		$data['id'] = $id;
		$this->assertEquals(print_r($this->Gateway->fetchByID($id), 1), print_r($data, 1));
		$this->inserted    = true;
		$this->inserted_id = $id;
	}

	public function testUpdate()
	{
		if (!$this->inserted) $this->testInsert();
		$data = array('key1' => 'new value1', 'key2' => 'new value2', 'id' => $this->inserted_id);
		$this->assertTrue($this->Gateway->update($data));
		$this->assertEquals(print_r($this->Gateway->fetchByID($this->inserted_id), 1), print_r($data, 1));
	}

	public function testDelete()
	{
		if (!$this->inserted) $this->testInsert();
		$this->assertTrue($this->Gateway->delete($this->inserted_id));
		$this->assertTrue(!$this->Gateway->fetchByID($this->inserted_id));
	}
}
