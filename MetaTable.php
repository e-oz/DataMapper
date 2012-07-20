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
		$table_info = $this->fetchTableInfoFromDB($PDO_connection);
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
			if ($column['Key']==='PRI') $Field->setPrimaryIndex(true);
			if ($column['Key']==='UNI') $Field->setUnique(true);
			if ($column['Extra']==='auto_increment') $Field->setAutoincrement(true);
			if ($column['Null']!='NO') $Field->setNotNull(false);
			if (!empty($column['Comment'])) $Field->setCommentary($column['Comment']);
			$this->addField($Field);
		}
		return true;
	}

	protected function fetchTableColumnsInfoFromDB(\PDO $PDO_connection)
	{
		$query = $PDO_connection->query("SHOW FULL COLUMNS FROM `{$this->name}`");
		if (!$query)
		{
			trigger_error(implode(' ', $PDO_connection->errorInfo()), E_USER_WARNING);
			return false;
		}
		return $query->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function fetchTableInfoFromDB(\PDO $PDO_connection)
	{
		$sql   = "SHOW TABLE STATUS WHERE Name='".$this->name."'";
		$query = $PDO_connection->query($sql);
		if (!$query)
		{
			trigger_error($sql.'; '.implode(' ', $PDO_connection->errorInfo()), E_USER_WARNING);
			return false;
		}
		$result = $query->fetch(\PDO::FETCH_ASSOC);
		$query->closeCursor();
		return $result;
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
}
