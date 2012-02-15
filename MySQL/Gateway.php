<?php
namespace Jamm\DataMapper\MySQL;

class Gateway implements \Jamm\DataMapper\IStorageGateway
{
	/** @var \PDO */
	protected $pdo;
	protected $pdo_prep_prefix = ':';
	protected $table_name;
	protected $prepared_setting;
	protected $prepared_values;
	/** @var \PDOStatement */
	protected $fetching_query;
	/** @var \Jamm\DataMapper\IMetaTable */
	protected $Table;

	public function __construct(\Jamm\DataMapper\IMetaTable $Table, \PDO $PDO_connection)
	{
		$this->Table      = $Table;
		$this->table_name = $Table->getName();
		$this->pdo        = $PDO_connection;
	}

	/**
	 * @param $id
	 * @return bool|array
	 */
	public function fetchByID($id)
	{
		if (!$primary_key = $this->getPrimaryField())
		{
			trigger_error('Primary field is empty', E_USER_WARNING);
			return false;
		}

		$query = $this->pdo->prepare("SELECT * FROM `{$this->table_name}` WHERE `$primary_key`=:ID LIMIT 0,1");
		if (!$query) return false;
		if (!$query->execute(array(':ID' => $id))) return false;
		return $query->fetch(\PDO::FETCH_ASSOC);
	}

	public function getTable()
	{
		return $this->Table;
	}

	public function setTable(\Jamm\DataMapper\IMetaTable $MetaTable)
	{
		$this->Table = $MetaTable;
	}

	public function update($values)
	{
		if (!$primary_key = $this->getPrimaryField())
		{
			trigger_error('Records can be updated only using primary key.', E_USER_WARNING);
			return false;
		}

		$this->setPreparedBindings($values);
		$setting = $this->prepared_setting;
		if (empty($setting))
		{
			return false;
		}

		$query = $this->pdo->prepare("UPDATE `{$this->table_name}` SET $setting WHERE `$primary_key`=".$this->pdo_prep_prefix.$primary_key);
		if (!$query) return false;
		$this->prepared_values[$this->pdo_prep_prefix.$primary_key] = $values[$primary_key];

		$result = $query->execute($this->prepared_values);
		return $result;
	}

	protected function setPreparedBindings($values, $concatenation_string = ' , ')
	{
		$this->prepared_setting = NULL;
		$this->prepared_values  = NULL;

		$fields = $this->Table->getWritableFields();
		if (empty($fields)) return false;

		$params   = array();
		$settings = array();

		foreach ($fields as $Field)
		{
			$name = $Field->getName();
			if (!array_key_exists($name, $values)) continue;
			if (!$Field->isValueAcceptable($values[$name])) continue;
			$settings[]                           = '`'.$name.'`= '.$this->pdo_prep_prefix.$name;
			$params[$this->pdo_prep_prefix.$name] = $values[$name];
		}

		$this->prepared_setting = implode($concatenation_string, $settings);
		$this->prepared_values  = $params;
		return true;
	}

	protected function generateUniqueKey(\Jamm\DataMapper\IField $Field)
	{
		$name  = $Field->getName();
		$query = $this->pdo->prepare("SELECT `$name` FROM `{$this->table_name}` WHERE `$name`=:KEY LIMIT 0,1");
		if (empty($query)) return false;

		do
		{
			$key   = $Field->getRandomKeyGenerator()->getKey();
			$check = $query->execute(array(':KEY' => $key));
			if (!$check)
			{
				return false;
			}
			$result = $query->fetch(\PDO::FETCH_ASSOC);
			if (empty($result)) return $key;
		}
		while (!empty($key));
		return false;
	}

	protected function getWritableFieldsArray()
	{
		return $this->Table->getNamesOfFields($this->Table->getWritableFields());
	}

	/**
	 * @param $values
	 * @return bool|string true, false, or last inserted ID
	 */
	public function insert($values)
	{
		$this->prepareUniqKeys($values);
		$this->setPreparedBindings($values);
		$setting = $this->prepared_setting;
		if (!empty($setting))
		{
			$query = $this->pdo->prepare("INSERT INTO `{$this->table_name}` SET $setting");
		}
		else
		{
			$query = $this->pdo->prepare("INSERT INTO `{$this->table_name}` () VALUES()");
		}

		if (!$query) return false;

		$result = $query->execute($this->prepared_values);
		if (!$result) return false;

		$id_field = $this->Table->getPrimaryFieldName();
		if (!empty($id_field))
		{
			if ($this->Table->getFieldByName($id_field)->isAutoincrement())
			{
				$result = $this->pdo->lastInsertId();
			}
			else
			{
				$result = $values[$id_field];
			}
		}
		return $result;
	}

	protected function prepareUniqKeys(&$values)
	{
		$fields = $this->Table->getWritableFields();
		if (empty($fields)) return false;

		foreach ($fields as $Field)
		{
			$name = $Field->getName();
			if ($Field->isUnique() && $Field->isPrimaryIndex() && !$Field->isAutoincrement())
			{
				if (!isset($values[$name]))
				{
					$values[$name] = $this->generateUniqueKey($Field);
				}
			}
		}
	}

	public function delete($id)
	{
		$primary_field_name = $this->getPrimaryField();
		if (empty($primary_field_name))
		{
			trigger_error('Can not delete without primary field', E_USER_WARNING);
			return false;
		}
		$query = $this->pdo->prepare("DELETE FROM `{$this->table_name}` WHERE `$primary_field_name`=:ID LIMIT 1");
		if (!$query) return false;
		if (!$query->execute(array(':ID' => $id))) return false;
		return true;
	}

	/**
	 * @return string
	 */
	public function getPrimaryField()
	{
		return $this->Table->getPrimaryFieldName();
	}

	public function dropTable()
	{
		$query = $this->pdo->query("DROP TABLE `{$this->table_name}`");
		if (!$query) return false;
		return true;
	}

	protected function startFetchAll()
	{
		$query = $this->pdo->query("SELECT * FROM `{$this->table_name}`");
		if (!$query) return false;
		$this->setFetchingQuery($query);
		return true;
	}

	protected function setFetchingQuery(\PDOStatement $FetchingQuery)
	{
		if (!empty($this->fetching_query))
		{
			$this->fetching_query->closeCursor();
			unset($this->fetching_query);
		}
		$this->fetching_query = $FetchingQuery;
	}

	/**
	 * @return array
	 */
	public function fetchNext()
	{
		if (empty($this->fetching_query)) $this->startFetchAll();
		return $this->fetching_query->fetch(\PDO::FETCH_ASSOC);
	}

	public function truncateTable()
	{
		$query = $this->pdo->query("TRUNCATE TABLE `{$this->table_name}`");
		if (!$query) return false;
		return true;
	}
}
