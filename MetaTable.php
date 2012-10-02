<?php
namespace Jamm\DataMapper;
class MetaTable implements IMetaTable
{
	private $name;
	/** @var IField[] */
	private $fields;
	private $primary_field;
	private $db_name;
	private $writable_fields;
	private $indexed_fields;
	private $commentary;
	/** @var \Jamm\Memory\IMemoryStorage */
	private $CacheObject;
	private $cache_key_columns = 'columns';
	private $cache_key_info = 'info';
	private $cache_ttl = 604800; //7 days
	private $info_rows;

	public function __construct($table_name = '')
	{
		$this->name = $table_name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function addField(IField $Field)
	{
		$this->fields[$Field->getName()] = $Field;
	}

	/**
	 * @return IField[]
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param string $name
	 * @return Field
	 */
	public function getFieldByName($name)
	{
		if (!isset($this->fields[$name])) return NULL;
		return $this->fields[$name];
	}

	/**
	 * @param IField[] $fields
	 * @return array
	 */
	public function getNamesOfFields(array $fields)
	{
		$names = array();
		foreach ($fields as $field) $names[] = $field->getName();
		return $names;
	}

	/**
	 * @return string
	 */
	public function getPrimaryFieldName()
	{
		if (empty($this->primary_field))
		{
			foreach ($this->fields as $field)
			{
				if ($field->isPrimaryIndex())
				{
					$this->primary_field = $field->getName();
					break;
				}
			}
		}
		return $this->primary_field;
	}

	/**
	 * @return IField[]
	 */
	public function getWritableFields()
	{
		if (empty($this->writable_fields))
		{
			$this->writable_fields = array();
			foreach ($this->fields as $field)
			{
				if (!$field->isReadOnly()) $this->writable_fields[] = $field;
			}
		}
		return $this->writable_fields;
	}

	public function getIndexedFields()
	{
		if (empty($this->indexed_fields))
		{
			$this->indexed_fields = array();
			foreach ($this->fields as $field)
			{
				if ($field->isIndexed()) $this->indexed_fields[] = $field;
			}
		}
		return $this->indexed_fields;
	}

	public function getDbName()
	{
		return $this->db_name;
	}

	public function setDbName($db_name)
	{
		$this->db_name = $db_name;
	}

	public function getSchemeArray()
	{
		$data = get_object_vars($this);
		foreach ($data as $key => $value)
		{
			if (!is_scalar($value)) unset($data[$key]);
		}
		$data['fields'] = array();
		foreach ($this->fields as $Field) $data['fields'][$Field->getName()] = $Field->getSchemeArray();
		return $data;
	}

	public function mapSchemeArray(array $data)
	{
		$properties = get_object_vars($this);
		foreach ($properties as $property)
		{
			if (!array_key_exists($property, $data)) continue;
			$value = $data[$property];
			if ($property==='fields')
			{
				if (!is_array($data['fields'])) continue;
				foreach ($data['fields'] as $field_name => $field_scheme)
				{
					$Field = $this->getNewFieldObject($field_name);
					$Field->mapSchemeArray($field_scheme);
					$this->fields[] = $Field;
				}
				continue;
			}
			$this->$property = $value;
		}
	}

	protected function getNewFieldObject($name)
	{
		return new Field($name);
	}

	public function mapFromDB(\PDO $PDO_connection)
	{
		$table_info      = $this->fetchTableInfoFromDB($PDO_connection);
		$this->info_rows = $table_info;
		if (!empty($table_info['Comment']))
		{
			$this->setCommentary($table_info['Comment']);
		}
		$columns = $this->fetchTableColumnsInfoFromDB($PDO_connection);
		foreach ($columns as $column)
		{
			$name = $column['Field'];
			if (empty($name)) continue;
			$Field = $this->getNewFieldObject($name);
			$Field->setType($column['Type']);
			if (($length = $this->getLengthFromTypeDefinition($column['Type'])) > 0)
			{
				$Field->setLength($length);
			}
			if ($column['Key']==='PRI') $Field->setPrimaryIndex(true);
			if ($column['Key']==='UNI') $Field->setUnique(true);
			if ($column['Extra']==='auto_increment') $Field->setAutoincrement(true);
			if ($column['Null']!='NO') $Field->setNotNull(false);
			if (!empty($column['Comment'])) $Field->setCommentary($column['Comment']);
			$this->addField($Field);
		}
		return true;
	}

	protected function getLengthFromTypeDefinition($type)
	{
		$bracket_start = strpos($type, '(');
		if ($bracket_start===false)
		{
			return false;
		}
		$bracket_end = strpos($type, ')');
		if (!$bracket_end)
		{
			$bracket_end = strlen($type);
		}
		$length = substr($type, $bracket_start+1, $bracket_end-$bracket_start-1);
		if (($comma = strpos($length, ','))!==false)
		{
			$length = substr($length, 0, $comma);
		}
		return intval($length);
	}

	protected function fetchTableColumnsInfoFromDB(\PDO $PDO_connection)
	{
		$columns = $this->getColumnsFromCache();
		if (empty($columns))
		{
			$query = $PDO_connection->query("SHOW FULL COLUMNS FROM `{$this->name}`");
			if (!$query)
			{
				trigger_error(implode(' ', $PDO_connection->errorInfo()), E_USER_WARNING);
				return false;
			}
			$columns = $query->fetchAll(\PDO::FETCH_ASSOC);
			$this->setColumnsInCache($columns);
		}
		return $columns;
	}

	protected function getColumnsFromCache()
	{
		if (empty($this->CacheObject))
		{
			return false;
		}
		return $this->CacheObject->read($this->cache_key_columns.':'.$this->name);
	}

	protected function setColumnsInCache($columns)
	{
		if (empty($this->CacheObject))
		{
			return false;
		}
		return $this->CacheObject->save($this->cache_key_columns.':'.$this->name, $columns, $this->cache_ttl);
	}

	protected function fetchTableInfoFromDB(\PDO $PDO_connection)
	{
		$info = $this->getTableInfoFromCache();
		if (empty($info))
		{
			$sql   = "SHOW TABLE STATUS WHERE Name='".$this->name."'";
			$query = $PDO_connection->query($sql);
			if (!$query)
			{
				trigger_error($sql.'; '.implode(' ', $PDO_connection->errorInfo()), E_USER_WARNING);
				return false;
			}
			$info = $query->fetch(\PDO::FETCH_ASSOC);
			$this->setTableInfoInCache($info);
		}
		return $info;
	}

	protected function getTableInfoFromCache()
	{
		if (empty($this->CacheObject))
		{
			return false;
		}
		return $this->CacheObject->read($this->cache_key_info.':'.$this->name);
	}

	protected function setTableInfoInCache($info)
	{
		if (empty($this->CacheObject))
		{
			return false;
		}
		return $this->CacheObject->save($this->cache_key_info.':'.$this->name, $info, $this->cache_ttl);
	}

	public function removeFieldByName($name)
	{
		if (!isset($this->fields[$name])) return false;
		unset($this->fields[$name]);
		return true;
	}

	public function getCommentary()
	{
		return $this->commentary;
	}

	public function setCommentary($commentary)
	{
		$this->commentary = $commentary;
	}

	public function setCacheObject(\Jamm\Memory\IMemoryStorage $CacheObject)
	{
		$this->CacheObject = $CacheObject;
	}

	public function setCacheTtl($cache_ttl)
	{
		$this->cache_ttl = $cache_ttl;
	}

	public function getInfoRows()
	{
		return $this->info_rows;
	}
}
