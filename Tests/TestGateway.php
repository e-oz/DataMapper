<?php
namespace Jamm\DataMapper\Tests;

class TestGateway extends \Jamm\Tester\ClassTest
{
	private $Gateway;
	private $inserted = false;
	private $inserted_id;
	private $MetaTable;
	private $values_for_types;
	private $default_type_value = '1default1';

	public function __construct(\Jamm\DataMapper\IStorageGateway $Gateway, \Jamm\DataMapper\IMetaTable $MetaTable)
	{
		$this->Gateway   = $Gateway;
		$this->MetaTable = $MetaTable;
		$this->initializeValuesForTypes();
	}

	protected function initializeValuesForTypes()
	{
		$this->values_for_types['BIT']       = 1;
		$this->values_for_types['BOOL']      = 1;
		$this->values_for_types['INT']       = 5;
		$this->values_for_types['SERIAL']    = 6;
		$this->values_for_types['NUMERIC']   = 5;
		$this->values_for_types['DECIMAL']   = 5.0;
		$this->values_for_types['DEC']       = 5.0;
		$this->values_for_types['FLOAT']     = 5.5;
		$this->values_for_types['DOUBLE']    = 5.55;
		$this->values_for_types['CHAR']      = 'char';
		$this->values_for_types['VARCHAR']   = 'varchar';
		$this->values_for_types['BINARY']    = 'BINARY';
		$this->values_for_types['BLOB']      = 'Text';
		$this->values_for_types['TEXT']      = 'Text';
		$this->values_for_types['ENUM']      = 'Enum1';
		$this->values_for_types['SET']       = 'Set1';
		$this->values_for_types['DATE']      = date('Y-m-d');
		$this->values_for_types['DATETIME']  = date('Y-m-d H:i:s');
		$this->values_for_types['TIMESTAMP'] = time();
		$this->default_type_value            = '1default1';
	}

	protected function updateValuesForTypes()
	{
		$this->values_for_types['BIT']       = 0;
		$this->values_for_types['BOOL']      = 0;
		$this->values_for_types['INT']       = 10;
		$this->values_for_types['SERIAL']    = 11;
		$this->values_for_types['NUMERIC']   = 10;
		$this->values_for_types['DECIMAL']   = 10.0;
		$this->values_for_types['DEC']       = 10.0;
		$this->values_for_types['FLOAT']     = 10.5;
		$this->values_for_types['DOUBLE']    = 15.55;
		$this->values_for_types['CHAR']      = 'char_upd';
		$this->values_for_types['VARCHAR']   = 'varchar_upd';
		$this->values_for_types['BINARY']    = 'BINARY_upd';
		$this->values_for_types['BLOB']      = 'Text_upd';
		$this->values_for_types['TEXT']      = 'Text_upd';
		$this->values_for_types['ENUM']      = 'Enum2';
		$this->values_for_types['SET']       = 'Set2';
		$this->values_for_types['DATE']      = date('Y-m-d', time()+86401);
		$this->values_for_types['DATETIME']  = date('Y-m-d H:i:s', time()+10);
		$this->values_for_types['TIMESTAMP'] = time()+11;
		$this->default_type_value            = '2default2';
	}

	protected function setValueForType($value, $type)
	{
		$this->values_for_types[$type] = $value;
	}

	protected function getValueForType($type)
	{
		list($clear_type) = explode('(', $type);
		$sharp_type = strtoupper($clear_type);
		if (isset($this->values_for_types[$sharp_type]))
		{
			return $this->values_for_types[$sharp_type];
		}
		foreach ($this->values_for_types as $type_pattern => $value)
		{
			if (stripos($type, $type_pattern)!==false)
			{
				return $value;
			}
		}
		return $this->default_type_value;
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

		$id_field = $this->MetaTable->getPrimaryFieldName();
		$data     = array($id_field => 0);
		$this->fillDataArrayByFieldsTypes($data, $this->MetaTable->getWritableFields());
		$id = $this->Gateway->insert($data);
		$this->assertTrue($id);
		$data[$id_field] = $id;
		$this->assertEquals(print_r($this->Gateway->fetchByID($id), 1), print_r($data, 1));
		$this->inserted    = true;
		$this->inserted_id = $id;
	}

	public function testFetchByID()
	{
		if (!$this->inserted) $this->testInsert();
		$data = $this->Gateway->fetchByID($this->inserted_id);
		$this->assertIsArray($data);
	}

	public function testFetchNext()
	{
		if (!$this->inserted) $this->testInsert();
		$data = $this->Gateway->fetchNext();
		$this->assertIsArray($data);
	}

	/**
	 * @param array $data_array
	 * @param \Jamm\DataMapper\IField[] $Fields
	 */
	protected function fillDataArrayByFieldsTypes(array &$data_array, $Fields)
	{
		foreach ($Fields as $Field)
		{
			$data_array[$Field->getName()] = $this->getValueForType($Field->getType());
		}
	}

	public function testUpdate()
	{
		if (!$this->inserted) $this->testInsert();

		$data = $this->Gateway->fetchByID($this->inserted_id);
		$this->updateValuesForTypes();
		$this->fillDataArrayByFieldsTypes($data, $this->MetaTable->getWritableFields());
		$this->assertTrue($this->Gateway->update($data));
		$this->assertEquals(print_r($this->Gateway->fetchByID($this->inserted_id), 1), print_r($data, 1));
	}

	public function testDelete()
	{
		if (!$this->inserted) $this->testInsert();
		$this->assertTrue($this->Gateway->delete($this->inserted_id));
		$this->assertTrue(!$this->Gateway->fetchByID($this->inserted_id));
	}

	public function testSelectLimit()
	{
		for ($i = 0; $i < 99; $i++)
		{
			$this->Gateway->insert(array());
		}
		$id_field = $this->MetaTable->getPrimaryFieldName();
		$results  = array();
		for ($j = 0; $j < 10; $j++)
		{
			if (!$this->Gateway->startFetchAll(0, 10))
			{
				break;
			}
			while (($result = $this->Gateway->fetchNext()))
			{
				$results[] = $result;
			}
		}
		$this->assertEquals(count($results), 100);
		foreach ($results as $result)
		{
			$this->Gateway->delete($result[$id_field]);
		}
	}

	protected function getInserted()
	{
		return $this->inserted;
	}

	protected function getMetaTable()
	{
		return $this->MetaTable;
	}
}
