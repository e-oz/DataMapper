<?php
namespace Jamm\DataMapper\Redis;

class Gateway implements \Jamm\DataMapper\IStorageGateway
{
	protected $redis;
	protected $prefix_indexes = 'i';
	protected $sep = ':';
	protected $prefix_auto_increment = 'ai';
	protected $internal_index_field = ':';
	protected $prefix_unique = 'u';
	protected $prefix_value = 'v';
	protected $prefix_fetch = 'f';
	/** @var \Jamm\DataMapper\IMetaTable */
	protected $Table;
	protected $dbtable_key = 'db:table';
	private $current_fetch_keys;
	private $fetch_in_progress = false;

	public function __construct(\Jamm\DataMapper\IMetaTable $MetaTable, \Jamm\Memory\IRedisServer $RedisServer)
	{
		$this->redis = $RedisServer;
		$this->Table = $MetaTable;
		$table_name  = $MetaTable->getName();
		$fields      = $MetaTable->getFields();
		if (empty($table_name) || empty($fields))
		{
			throw new \Exception("Table is not initialized");
		}
		$this->selectDB();
	}

	protected function selectDB()
	{
		$name = $this->Table->getDbName().$this->sep.$this->Table->getName();
		$this->redis->Select(0);
		$tables_data = $this->redis->get($this->dbtable_key);
		$save        = false;
		if (!empty($tables_data))
		{
			$tables = unserialize($tables_data);

			if (!in_array($name, $tables))
			{
				$tables[] = $name;
				$save     = true;
			}
			$dbtable_index = array_search($name, $tables);
		}
		else
		{
			$tables        = array(0 => 'index');
			$tables[]      = $name;
			$dbtable_index = 1;
			$save          = true;
		}
		if ($save)
		{
			$tables_data = serialize($tables);
			$this->redis->set($this->dbtable_key, $tables_data);
		}
		$this->redis->Select($dbtable_index);
		return true;
	}

	/**
	 * @param array $values
	 * @return bool|string true, false, or last inserted ID
	 */
	public function insert($values = array())
	{
		$this->prepareUniqKeys($values);
		$id_field = $this->Table->getPrimaryFieldName();
		if (!empty($id_field))
		{
			trigger_error('Empty primary field in '.$this->Table->getName());
			$id = $values[$id_field];
		}
		else
		{
			$id = $this->autoIncrementValue($this->internal_index_field);
		}

		if (empty($id))
		{
			trigger_error("ID not generated", E_USER_WARNING);
			return false;
		}
		$result = $this->redis->hMSet($this->getRecordKey($id), $values);
		if (!$result)
		{
			trigger_error("Insert error", E_USER_WARNING);
			return false;
		}
		if (($indexed_fields = $this->Table->getIndexedFields()))
		{
			foreach ($indexed_fields as $Field)
			{
				$name = $Field->getName();
				$this->addIndex($name, $values[$name], $id);
			}
		}
		return $id;
	}

	public function update($values)
	{
		$index_field = $this->Table->getPrimaryFieldName();
		if (empty($index_field) || !isset($values[$index_field]))
		{
			trigger_error("Can not update without primary key $index_field:".$values[$index_field], E_USER_WARNING);
			return false;
		}
		$index = $this->getRecordKey($values[$index_field]);
		if (($indexed_fields = $this->Table->getIndexedFields()))
		{
			if (($previous_values = $this->redis->hGetAll($index)))
			{
				foreach ($indexed_fields as $Field)
				{
					$field_name = $Field->getName();
					if ($previous_values[$field_name]!=$values[$field_name])
					{
						$this->updateIndex($field_name, $previous_values[$field_name], $values[$field_name]);
					}
				}
			}
		}
		return $this->redis->hmSet($index, $values);
	}

	/**
	 * @param array $keys_values (key1 => value1, key2 => value2)
	 *                           analog in SQL: "WHERE key1=value1 AND key2=value2"
	 */
	public function startFetchIntersection(array $keys_values)
	{
		$this->current_fetch_keys = $this->getIndexesOfIntersection($keys_values);
		if (empty($this->current_fetch_keys)) return false;
		return true;
	}

	/**
	 * @param array $keys_values (key1 => value1, key2 => value2)
	 *                           analog in SQL: "WHERE key1=value1 AND key2=value2"
	 */
	protected function getIndexesOfIntersection(array $keys_values)
	{
		$indexed_fields = $this->Table->getIndexedFields();
		if (empty($indexed_fields))
		{
			trigger_error("Can not search without indexed fields", E_USER_WARNING);
			return false;
		}
		$keys = array();
		foreach ($indexed_fields as $Field)
		{
			$name = $Field->getName();
			if (isset($keys_values[$name]))
			{
				$keys[] = $this->getIndexName($name, $keys_values[$name]);
			}
		}
		if (empty($keys))
		{
			trigger_error("Given fields are not indexed or contain null values", E_USER_NOTICE);
			return false;
		}
		return $this->redis->sInter($keys);
	}

	public function delete($values)
	{
		$primary_field = $this->Table->getPrimaryFieldName();
		if (!empty($primary_field))
		{
			return $this->deleteByID($values[$primary_field]);
		}
		else
		{
			$keys = $this->getIndexesOfIntersection($values);
			if (empty($keys)) return false;
			$deleted = 0;
			foreach ($keys as $ID)
			{
				if ($this->deleteByID($ID)) $deleted++;
			}
			return $deleted;
		}
	}

	protected function deleteByID($ID)
	{
		$key            = $this->getRecordKey($ID);
		$indexed_fields = $this->Table->getIndexedFields();
		if (!empty($indexed_fields))
		{
			$data_array = $this->redis->hGetAll($key);
			if (empty($data_array)) return true;
			$this->redis->Multi();
			$this->redis->del($key);
			$this->removeIndexedValues($indexed_fields, $data_array, $key);
			return $this->redis->Exec();
		}
		else
		{
			return $this->redis->del($key);
		}
	}

	/**
	 * @param \Jamm\DataMapper\IField[] $fields
	 * @param array $data_array
	 * @param string|int $index_key
	 */
	protected function removeIndexedValues(array $fields, array $data_array, $index_key)
	{
		foreach ($fields as $Field)
		{
			$name = $Field->getName();
			$this->removeIndex($name, $data_array[$name], $index_key);
		}
	}

	/**
	 * @param int|string $ID value of primary key
	 * @return string v:$ID
	 */
	protected function getRecordKey($ID)
	{
		return $this->prefix_value.$this->sep.$ID;
	}

	/** @return string */
	public function getPrimaryField()
	{
		return $this->Table->getPrimaryFieldName();
	}

	/** @return array|bool */
	public function fetchNext()
	{
		if (empty($this->current_fetch_keys))
		{
			if (!$this->fetch_in_progress) $this->startFetchAll();
			else
			{
				$this->fetch_in_progress = false;
				return false;
			}
		}
		$this->fetch_in_progress = true;
		$result                  = $this->fetchByID(array_shift($this->current_fetch_keys));
		return $result;
	}

	public function startFetchAll($offset = 0, $limit = 0, $filter_keys = array(), $filter_key_values = array())
	{
		if (!($this->current_fetch_keys = $this->redis->Keys($this->prefix_value.$this->sep.'*')))
		{
			return false;
		}
		if ($offset > 0 || $limit > 0)
		{
			$this->current_fetch_keys = array_slice($this->current_fetch_keys, $offset, $limit);
		}
		return true;
	}

	public function isCurrentFetchEmpty()
	{
		return empty($this->current_fetch_keys);
	}

	/**
	 * @param int|string $id
	 * @return array|boolean
	 */
	public function fetchByID($id)
	{
		if (strpos($id, $this->sep)!==false)
		{
			return $this->redis->hGetAll($id);
		}
		else return $this->redis->hGetAll($this->getRecordKey($id));
	}

	public function truncateTable()
	{
		return $this->redis->FlushDB();
	}

	protected function prepareUniqKeys(&$values)
	{
		$fields = $this->Table->getFields();
		if (empty($fields)) return false;

		foreach ($fields as $Field)
		{
			$name = $Field->getName();
			if (empty($values[$name]) && $Field->isUnique() && $Field->isPrimaryIndex())
			{
				if (!$Field->isAutoincrement())
				{
					$values[$name] = $this->generateUniqueKey($Field);
				}
				else
				{
					$values[$name] = $this->autoIncrementValue($name);
				}
			}
		}
	}

	protected function generateUniqueKey(\Jamm\DataMapper\IField $Field)
	{
		$name = $Field->getName();
		if (empty($name))
		{
			trigger_error('Empty field name');
		}

		do
		{
			$unique_key = $Field->getRandomKeyGenerator()->getKey();
			$check      = $this->addUniqueKey($name, $unique_key);
			if (empty($check)) return $unique_key;
		}
		while (!empty($unique_key));
		return false;
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 * @param int|string $primary_key_value
	 * @return bool
	 */
	protected function addIndex($field, $value, $primary_key_value)
	{
		return $this->redis->sAdd($this->getIndexName($field, $value), $primary_key_value);
	}

	protected function getIndexName($field, $value)
	{
		//i:field:value
		$name = $this->prefix_indexes.$this->sep.$field.$this->sep.md5($value);
		return $name;
	}

	protected function removeIndex($field, $value, $primary_key_value)
	{
		return $this->redis->sRem($this->getIndexName($field, $value), $primary_key_value);
	}

	protected function updateIndex($field, $old_value, $new_value)
	{
		$old_name = $this->getIndexName($field, $old_value);
		$new_name = $this->getIndexName($field, $new_value);
		if (($content = $this->redis->sMembers($old_name)))
		{
			foreach ($content as $member)
			{
				$this->redis->sMove($old_name, $new_name, $member);
			}
			return true;
		}
		return false;
	}

	protected function autoIncrementValue($field)
	{
		//ai:field
		$name = $this->prefix_auto_increment.$this->sep.$field;
		return $this->redis->Incr($name);
	}

	protected function addUniqueKey($field, $value)
	{
		//u:field
		$name = $this->prefix_unique.$this->sep.$field;
		return $this->redis->sAdd($name, $value);
	}

	public function getCurrentFetchKeys()
	{
		return $this->current_fetch_keys;
	}

	public function setFetchKeys(array $fetch_keys)
	{
		$this->current_fetch_keys = $fetch_keys;
	}

	public function getTableName()
	{
		return $this->Table->getName();
	}
}
