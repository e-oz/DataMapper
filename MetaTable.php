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
		$fields = array();
		foreach ($this->fields as $field)
		{
			if ($field->isIndexed()) $fields[] = $field;
		}
		return $fields;
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
		$query = $PDO_connection->prepare("SHOW COLUMNS FROM `{$this->name}`");
		if (!$query)
		{
			trigger_error(implode(' ', $PDO_connection->errorInfo()), E_USER_WARNING);
			return false;
		}
		if (!$query->execute())
		{
			trigger_error(implode(' ', $query->errorInfo()), E_USER_WARNING);
			return false;
		}
		$columns = $query->fetchAll(\PDO::FETCH_ASSOC);
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
			$this->addField($Field);
		}
		return true;
	}

	public function getEntityClassCode($namespace = '')
	{
		$fields = $this->getFields();
		if (empty($fields))
		{
			trigger_error('Set fields first', E_USER_WARNING);
			return false;
		}

		$code = $this->getClassDeclarationCode($namespace);

		foreach ($fields as $Field)
		{
			$code .= $this->getFieldDeclarationCode($Field);
		}

		foreach ($fields as $Field)
		{
			$code .= PHP_EOL.$this->getFieldGetterCode($Field->getName()).PHP_EOL;
			if (!$Field->isReadOnly()) $code .= PHP_EOL.$this->getFieldSetterCode($Field->getName()).PHP_EOL;
		}

		$code .= '}'.PHP_EOL;
		return $code;
	}

	protected function getClassDeclarationCode($namespace = '')
	{
		$code = '<?php'.PHP_EOL;
		if (!empty($namespace)) $code .= 'namespace '.$namespace.';'.PHP_EOL.PHP_EOL;
		$code .= '/**'.PHP_EOL.' * Table: '.$this->name.PHP_EOL.' */'.PHP_EOL;
		$code .= 'class '.$this->inCamelCase($this->getName()).PHP_EOL.'{'.PHP_EOL;
		return $code;
	}

	protected function getFieldDeclarationCode(IField $Field)
	{
		$code = "\t/** column type: ".$Field->getType();
		if (!$Field->isNotNull()) $code .= ' NULL';
		if ($Field->isAutoincrement()) $code .= ' autoincrement';
		if ($Field->isPrimaryIndex()) $code .= ' @primary';
		if ($Field->isReadOnly()) $code .= ' @readonly';
		if ($Field->isUnique()) $code .= ' @unique';
		$code .= ' */'.PHP_EOL;
		$code .= "\tprotected $".$Field->getName().';'.PHP_EOL;
		return $code;
	}

	protected function getFieldGetterCode($field_name)
	{
		return "\tpublic function get".$this->inCamelCase($field_name).'()'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'return $this->'.$field_name.';'.PHP_EOL
				."\t}";
	}

	protected function getFieldSetterCode($field_name)
	{
		return "\tpublic function set".$this->inCamelCase($field_name).'($'.$field_name.')'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'$this->'.$field_name.' = $'.$field_name.';'.PHP_EOL
				."\t}";
	}

	public function inCamelCase($string)
	{
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}
}
