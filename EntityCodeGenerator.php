<?php
namespace Jamm\DataMapper;

class EntityCodeGenerator
{
	public function getEntityClassCode(IMetaTable $Table, $namespace = '', $parent_class_name = '')
	{
		$fields = $Table->getFields();
		if (empty($fields))
		{
			trigger_error('Set fields first', E_USER_WARNING);
			return false;
		}

		$existing_fields = array();
		if (!class_exists($parent_class_name))
		{
			$parent_class_name = '';
		}
		else
		{
			$existing_fields = $this->getExistingFields($parent_class_name);
		}

		$code = $this->getClassDeclarationCode($Table, $namespace, $parent_class_name);

		foreach ($fields as $Field)
		{
			if (in_array($Field->getName(), $existing_fields)) continue;
			$code .= $this->getFieldDeclarationCode($Field);
		}

		foreach ($fields as $Field)
		{
			if (in_array($Field->getName(), $existing_fields)) continue;
			$code .= PHP_EOL.$this->getFieldGetterCode($Field->getName()).PHP_EOL;
			if (!$Field->isReadOnly()) $code .= PHP_EOL.$this->getFieldSetterCode($Field->getName()).PHP_EOL;
		}

		$code .= '}'.PHP_EOL;
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