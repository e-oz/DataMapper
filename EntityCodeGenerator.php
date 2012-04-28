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
			$code .= $this->getFieldGetterCode($Field);
			if (!$Field->isReadOnly())
			{
				$code .= $this->getFieldSetterCode($Field);
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
		if (empty($parent_class_name) && ($commentary = $Table->getCommentary()))
		{
			$code .= '/**'.PHP_EOL.' * '.$commentary.PHP_EOL.' */'.PHP_EOL;
		}
		$code .= 'class '.$this->inCamelCase($Table->getName());
		if (!empty($parent_class_name)) $code .= ' extends '.$parent_class_name;
		$code .= PHP_EOL.'{'.PHP_EOL;
		return $code;
	}

	protected function getFieldDeclarationCode(IField $Field)
	{
		$code  = '';
		$lines = array();
		if (($commentary = $Field->getCommentary()))
		{
			$lines[] = $commentary;
		}
		$attributes = array();
		if (($type = $this->getPHPTypeFromCustom($Field->getType()))!==false)
		{
			$attributes[] = "@var ".$type;
		}
		else
		{
			$attributes[] = "@type ".$Field->getType();
		}
		if (!$Field->isNotNull()) $attributes[] = '@NULL';
		if ($Field->isAutoincrement()) $attributes[] = '@autoincrement';
		if ($Field->isPrimaryIndex()) $attributes[] = '@primary';
		if ($Field->isReadOnly()) $attributes[] = '@readonly';
		if ($Field->isUnique()) $attributes[] = '@unique';
		if (!empty($attributes))
		{
			$lines[] = implode(' ', $attributes);
		}
		if (!empty($lines))
		{
			if (count($lines) > 1)
			{
				$code = PHP_EOL."\t/**"
						.PHP_EOL."\t * "
						.implode(PHP_EOL."\t * ", $lines)
						.PHP_EOL."\t */".PHP_EOL;
			}
			else
			{
				$code = "\t/** ".$lines[0]." */".PHP_EOL;
			}
			$code .= "\tprotected $".$Field->getName().";".PHP_EOL;
		}
		return $code;
	}

	/**
	 * @param string $type
	 */
	protected function getPHPTypeFromCustom($type)
	{
		$mysql_types = array(
			'BIT'       => 'int',
			'BOOL'      => 'int',
			'INT'       => 'int',
			'TINYINT'   => 'int',
			'BIGINT'    => 'int',
			'SERIAL'    => 'int',
			'NUMERIC'   => 'int',
			'DECIMAL'   => 'float',
			'DEC'       => 'float',
			'FLOAT'     => 'float',
			'DOUBLE'    => 'float',
			'CHAR'      => 'string',
			'VARCHAR'   => 'string',
			'BINARY'    => 'string',
			'BLOB'      => 'string',
			'TEXT'      => 'string',
			'TINYTEXT'  => 'string',
			'ENUM'      => 'string',
			'SET'       => 'string',
			'DATE'      => 'string',
			'DATETIME'  => 'string',
			'TIMESTAMP' => 'int');
		$type        = strtoupper(trim($type));
		if (($space_pos = strpos($type, ' '))!==false)
		{
			$type = substr($type, 0, $space_pos);
		}
		if (($brace_pos = strpos($type, '('))!==false)
		{
			$type = substr($type, 0, $brace_pos);
		}
		if (isset($mysql_types[$type]))
		{
			return $mysql_types[$type];
		}
		else return false;
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

	protected function getFieldGetterCode(IField $Field)
	{
		$field_name = $Field->getName();
		return PHP_EOL."\tpublic function get".$this->inCamelCase($field_name).'()'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'return $this->'.$field_name.';'.PHP_EOL
				."\t}".PHP_EOL;
	}

	protected function getFieldSetterCode(IField $Field)
	{
		$field_name = $Field->getName();
		$code       = '';
		if (($type = $this->getPHPTypeFromCustom($Field->getType())))
		{
			$code .= PHP_EOL
					."\t/**".PHP_EOL
					."\t * @param $type $$field_name".PHP_EOL
					."\t */";
		}
		$code .= PHP_EOL."\tpublic function set".$this->inCamelCase($field_name).'($'.$field_name.')'.PHP_EOL
				."\t{".PHP_EOL
				."\t\t".'$this->'.$field_name.' = $'.$field_name.';'.PHP_EOL
				."\t}".PHP_EOL;
		return $code;
	}

	public function inCamelCase($string)
	{
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}
}
