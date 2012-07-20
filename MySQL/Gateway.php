<?php
namespace Jamm\DataMapper\MySQL;
class Gateway implements \Jamm\DataMapper\IStorageGateway
{
	/** @var \PDO */
	protected $pdo;
	protected $table_name;
	/** @var \PDOStatement */
	protected $fetching_query;
	/** @var \Jamm\DataMapper\IMetaTable */
	protected $Table;
	/** @var \Jamm\DataMapper\IField[] */
	private $WritableFields;
	protected $concatenation_string = ' , ';
	/** @var \PDOStatement[] */
	private $prepared_queries;

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
			trigger_error('Primary field in table '.$this->table_name.' is empty', E_USER_WARNING);
			return false;
		}
		$query = $this->prepareWithCache("SELECT * FROM `{$this->table_name}` WHERE `$primary_key`=:ID LIMIT 0,1");
		if (!$query)
		{
			trigger_error("Can't prepare {$query->queryString}", E_USER_WARNING);
			return false;
		}
		if (!$query->execute(array(':ID' => $id)))
		{
			trigger_error("Can't execute {$query->queryString}", E_USER_WARNING);
			return false;
		}
		return $query->fetch(\PDO::FETCH_ASSOC);
	}

	public function update($values)
	{
		if (!$primary_key = $this->getPrimaryField())
		{
			trigger_error('Records can be updated only using primary key.', E_USER_WARNING);
			return false;
		}
		$this->setPreparedBindings($values, $setting, $statements);
		if (empty($setting))
		{
			return false;
		}
		$query = $this->prepareWithCache("UPDATE `{$this->table_name}` SET $setting WHERE `$primary_key`= :".$primary_key);
		if (!$query) return false;
		$statements[':'.$primary_key] = $values[$primary_key];
		$result                       = $query->execute($statements);
		return $result;
	}

	protected function setPreparedBindings($values, &$setting, &$statements)
	{
		$statements = array();
		$settings   = array();
		$setting    = '';
		if (empty($values)) return false;
		$fields = $this->getWritableFields();
		if (empty($fields)) return false;
		foreach ($fields as $Field)
		{
			$name = $Field->getName();
			if (!array_key_exists($name, $values)) continue;
			if (!$Field->isValueAcceptable($values[$name])) continue;
			$settings[]            = '`'.$name.'`= :'.$name;
			$statements[':'.$name] = $values[$name];
		}
		$setting = implode($this->concatenation_string, $settings);
		return true;
	}

	protected function getWritableFields()
	{
		if (empty($this->WritableFields))
		{
			$this->WritableFields = $this->Table->getWritableFields();
		}
		return $this->WritableFields;
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
		} while (!empty($key));
		return false;
	}

	/**
	 * @param $values
	 * @return bool|string true, false, or last inserted ID
	 */
	public function insert($values = array())
	{
		$this->setUniqueValues($values);
		$this->setPreparedBindings($values, $setting, $statements);
		if (!empty($setting))
		{
			$query = $this->prepareWithCache("INSERT INTO `{$this->table_name}` SET $setting");
			if (!$query) return false;
			$result = $query->execute($statements);
		}
		else
		{
			$result = $this->pdo->query("INSERT INTO `{$this->table_name}` () VALUES()");
		}
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

	protected function setUniqueValues(&$values)
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
		$query = $this->prepareWithCache("DELETE FROM `{$this->table_name}` WHERE `$primary_field_name`=:ID LIMIT 1");
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

	public function startFetchAll($offset = 0, $limit = 0, $filter_keys = array(), $filter_key_values = array())
	{
		$statements = array();
		$SQL        = "SELECT ";
		if (!empty($filter_keys))
		{
			$SQL .= '`'.implode('`,`', $filter_keys).'`';
		}
		else
		{
			$SQL .= '*';
		}
		$SQL .= " FROM `{$this->table_name}`";
		if (!empty($filter_key_values))
		{
			$where_string = $this->getWhereStringValue($filter_key_values, $statements);
			if (!empty($where_string)) $SQL .= ' WHERE '.$where_string;
		}
		if ($offset!=0 || $limit!=0)
		{
			$SQL .= " LIMIT ".intval($offset).", ".intval($limit);
		}
		if (!empty($statements))
		{
			$SQL = $this->getCorrectPreparedQuery($SQL, $statements);
		}
		if (!empty($statements))
		{
			if (!($query = $this->prepareWithCache($SQL)))
			{
				trigger_error("Can't prepare ".$SQL, E_USER_WARNING);
				return false;
			}
			if (!$query->execute($statements))
			{
				trigger_error("Can't execute $SQL", E_USER_WARNING);
				return false;
			}
		}
		else
		{
			$query = $this->pdo->query($SQL);
		}
		$this->setFetchingQuery($query);
		return true;
	}

	protected function getCorrectPreparedQuery($query, &$statements)
	{
		$num_replacements = $statements;
		foreach ($statements as $key=> $statement)
		{
			if (is_numeric($statement))
			{
				$num_replacements[$key] = floatval($statement);
				unset($statements[$key]);
			}
			else
			{
				$num_replacements[$key] = $key;
			}
		}
		$query = strtr($query, $num_replacements);
		return $query;
	}

	protected function setFetchingQuery(\PDOStatement $FetchingQuery)
	{
		$this->fetching_query = $FetchingQuery;
	}

	/**
	 * @return array|bool
	 */
	public function fetchNext()
	{
		if (empty($this->fetching_query)) $this->startFetchAll();
		$result = $this->fetching_query->fetch(\PDO::FETCH_ASSOC);
		return $result;
	}

	public function truncateTable()
	{
		$query = $this->pdo->query("TRUNCATE TABLE `{$this->table_name}`");
		if (!$query) return false;
		return true;
	}

	public function getTableName()
	{
		return $this->Table->getName();
	}

	private function getWhereStringValue($filter_key_values_array, &$statements)
	{
		$FilterConverter = $this->getNewFilterConverter();
		$PrepareValues   = $this->getNewPrepareValues();
		$where_string    = $FilterConverter->getSQLStringFromFilterArray($filter_key_values_array, $PrepareValues);
		$statements      = $PrepareValues->getStatements();
		if (empty($where_string))
		{
			trigger_error('Filter parsing error', E_USER_WARNING);
			return false;
		}
		return $where_string;
	}

	/**
	 * @param $SQL
	 * @return \PDOStatement
	 */
	protected function prepareWithCache($SQL)
	{
		$key = md5($SQL);
		if (!isset($this->prepared_queries[$key]))
		{
			$this->prepared_queries[$key] = $this->pdo->prepare($SQL);
		}
		return $this->prepared_queries[$key];
	}

	protected function getNewFilterConverter()
	{
		return new \Jamm\DataMapper\FilterConverter\SQL\Converter();
	}

	protected function getNewPrepareValues()
	{
		return new \Jamm\DataMapper\FilterConverter\SQL\PrepareValues();
	}
}
