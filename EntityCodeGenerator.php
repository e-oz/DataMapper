<?php
namespace Jamm\DataMapper;

class EntityCodeGenerator
{
	protected $table_name_const = 'table_name';

	public function getEntityClassCode(IMetaTable $Table, $namespace = '', $parent_class_name = '')
	{
		$fields = $Table->getFields();
		if (empty($fields))
		{
			trigger_error('Set fields first', E_USER_WARNING);
			return false;
		}

		if (!empty($parent_class_name))
		{
			if (class_exists($parent_class_name))
			{
				$fields = $this->getNotParentFields($parent_class_name, $fields);
			}
			else
			{
				$parent_class_name = '';
			}
		}

		$code = $this->getClassDeclarationCode($Table, $namespace, $parent_class_name);
		if (!empty($fields))
		{
			$code .= $this->getConstantsCode($fields, $Table, $parent_class_name);
			$code .= $this->getFieldsCode($fields);
			$code .= $this->getAccessorsCode($fields);
		}
		$code .= $this->getClassEnding();
		return $code;
	}

	/**
	 * @param string $parent_class_name
	 * @param IField[] $fields
	 * @return array
	 */
	protected function getNotParentFields($parent_class_name, $fields)
	{
		$existing_fields = $this->getExistingFields($parent_class_name);
		if (!empty($existing_fields))
		{
			$new_fields = array();
			foreach ($fields as $Field)
			{
				if (!in_array($Field->getName(), $existing_fields))
				{
					$new_fields[] = $Field;
				}
			}
			return $new_fields;
		}
		return $fields;
	}

	/**
	 * @param IField[] $fields
	 * @param IMetaTable $Table
	 * @param $parent_class_name
	 * @return string
	 */
	protected function getConstantsCode($fields, $Table, $parent_class_name)
	{
		$code       = '';
		$table_name = $Table->getName();
		if (empty($parent_class_name) || !defined($parent_class_name.'::'.$this->table_name_const))
		{
			if (!empty($table_name))
			{
				$code .= $this->getTableConstantDeclaration($table_name);
			}
		}
		foreach ($fields as $Field)
		{
			$code .= $this->getConstantDeclarationCode($Field);
		}
		return $code;
	}

	protected function getClassEnding()
	{
		return '}'.PHP_EOL;
	}

	/**
	 * @param IField[] $fields
	 * @return string
	 */
	protected function getFieldsCode($fields)
	{
		$code = '';
		foreach ($fields as $Field)
		{
			$code .= $this->getFieldDeclarationCode($Field);
		}
		return $code;
	}

	/**
	 * @param IField[] $fields
	 * @return string
	 */
	protected function getAccessorsCode($fields)
	{
		$code = '';
		foreach ($fields as $Field)
		{
			$code .= $this->getFieldGetterCode($Field->getName());
			if (!$Field->isReadOnly())
			{
				$code .= $this->getFieldSetterCode($Field->getName());
			}
		}
		return $code;
	}

	protected function getExistingFields($class_name)
	{
		$Reflection = new \ReflectionClass($class_name);
		$properties = $Reflection->getProperties();
		$fields     = array();
		if (!empty($properties))
		{
			foreach ($properties as $property)
			{
				$fields[] = $property->getName();
			}
		}
		return $fields;
	}

	protected function getClassDeclarationCode(IMetaTable $Table, $namespace = '', $parent_class_name = '')
	{
		$code = '<?php'.PHP_EOL;
		if (!empty($namespace)) $code .= 'namespace '.$namespace.';'.PHP_EOL.PHP_EOL;
		$code .= '/**'.PHP_EOL.' * Table: '.$Table->getName().PHP_EOL.' */'.PHP_EOL;
		$code .= 'class '.$this->inCamelCase($Table->getName());
		if (!empty($parent_class_name)) $code .= ' extends '.$parent_class_name;
		$code .= PHP_EOL.'{'.PHP_EOL;
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

	protected function getTableConstantDeclaration($table_name)
	{
		return "\tconst ".$this->table_name_const." = '".$table_name."';".PHP_EOL;
	}

	protected function getConstantDeclarationCode(IField $Field)
	{
		$code = "\tconst field_".$Field->getName()." = '".$Field->getName()."';".PHP_EOL;
		return $code;
	}

	protected function getFieldGetterCode($field_name)
	{
		return PHP_EOL."\tpublic function get".$this->inCamelCase($field_name).'()'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'return $this->'.$field_name.';'.PHP_EOL
				."\t}".PHP_EOL;
	}

	protected function getFieldSetterCode($field_name)
	{
		return PHP_EOL."\tpublic function set".$this->inCamelCase($field_name).'($'.$field_name.')'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'$this->'.$field_name.' = $'.$field_name.';'.PHP_EOL
				."\t}".PHP_EOL;
	}

	public function inCamelCase($string)
	{
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}
}
