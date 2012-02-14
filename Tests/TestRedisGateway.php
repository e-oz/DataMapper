<?php
namespace Jamm\DataMapper\Tests;

class TestRedisGateway extends TestGateway
{
	/** @var \Jamm\DataMapper\Redis\Gateway */
	protected $Gateway;

	public function testIntersection()
	{
		if (!$this->getInserted()) $this->testInsert();
		
		$this->Gateway->insert(array('key1' => 1, 'key2' => 12));
		$this->Gateway->insert(array('key1' => 2, 'key2' => 2));
		$this->Gateway->insert(array('key1' => 3, 'key2' => 32));
		$this->Gateway->insert(array('key1' => 4, 'key2' => 2));
		$this->Gateway->insert(array('key1' => 5, 'key2' => 2));
		$this->Gateway->insert(array('key1' => 5, 'key2' => 52));
		$this->Gateway->insert(array('key1' => 6, 'key2' => 2));
		$this->assertTrue(
			$this->Gateway->startFetchIntersection(array('key1' => 5, 'key2' => 52))
		);
		$result = $this->Gateway->fetchNext();
		$this->assertEquals(print_r($result, 1), print_r(array('key1' => 5, 'key2' => 52, 'id' => 7), 1));
		$result = $this->Gateway->fetchNext();
		$this->assertEquals($result, false);

		$this->assertTrue(
			$this->Gateway->startFetchIntersection(array('key1' => 5, 'key2' => NULL))
		);
		$result = $this->Gateway->fetchNext();
		$this->assertEquals(print_r($result, 1), print_r(array('key1' => 5, 'key2' => 2, 'id' => 6), 1));
	}
}
