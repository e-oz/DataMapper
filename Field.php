<?php
namespace Jamm\DataMapper;
class Field implements IField
{
	private $name;
	private $read_only = false;
	private $primary_index = false;
	private $autoincrement = false;
	private $unique = false;
	private $type;
	private $not_null = true;
	/** @var \Jamm\DataMapper\IRandomKeyGenerator */
	private $RandomKeyGenerator = null;
	private $indexed = false;
	private $RandomKeyGenerator_field = 'RandomKeyGenerator';
	private $commentary;
	private $length;

	public function __construct($name)
	{
		if (empty($name))
		{
			trigger_error('name of field should not be empty', E_USER_WARNING);
		}
		$this->name = $name;
	}

	public function setAutoincrement($autoincrement = true)
	{
		$this->autoincrement = $autoincrement;
		if ($autoincrement)
		{
			$this->setReadOnly(true);
			$this->setUnique(true);
		}
	}

	public function isAutoincrement()
	{
		return $this->autoincrement;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setPrimaryIndex($primary_index = true)
	{
		$this->primary_index = $primary_index;
		if ($primary_index)
		{
			$this->setUnique(true);
		}
	}

	public function isPrimaryIndex()
	{
		return $this->primary_index;
	}

	public function setReadOnly($read_only = true)
	{
		$this->read_only = $read_only;
	}

	public function isReadOnly()
	{
		return $this->read_only;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function isNotNull()
	{
		return $this->not_null;
	}

	public function setNotNull($not_null = false)
	{
		$this->not_null = $not_null;
	}

	public function isValueAcceptable($value)
	{
		if ($this->read_only) return false;
		if ($this->not_null && $value===NULL) return false;
		return true;
	}

	public function isUnique()
	{
		return $this->unique;
	}

	public function setUnique($unique = true)
	{
		$this->unique = $unique;
	}

	/**
	 * @return \Jamm\DataMapper\IRandomKeyGenerator
	 */
	public function getRandomKeyGenerator()
	{
		if (empty($this->RandomKeyGenerator))
		{
			$this->setRandomKeyGenerator(new RandomKeyGenerator());
		}
		return $this->RandomKeyGenerator;
	}

	public function setRandomKeyGenerator(\Jamm\DataMapper\IRandomKeyGenerator $RandomKeyGenerator)
	{
		$this->RandomKeyGenerator = $RandomKeyGenerator;
		if (!empty($this->length))
		{
			$this->RandomKeyGenerator->setKeyLength($this->length);
		}
	}

	public function isIndexed()
	{
		return $this->indexed;
	}

	public function setIndexed($indexed = true)
	{
		$this->indexed = $indexed;
	}

	public function getSchemeArray()
	{
		$data = get_object_vars($this);
		if (!empty($this->RandomKeyGenerator)) $data[$this->RandomKeyGenerator_field] = get_class($this->RandomKeyGenerator);
		return $data;
	}

	public function mapSchemeArray(array $data)
	{
		$fields = get_object_vars($this);
		foreach ($fields as $field)
		{
			if (!array_key_exists($field, $data)) continue;
			$value = $data[$field];
			if ($field===$this->RandomKeyGenerator_field && !empty($value))
			{
				if (!class_exists($value))
				{
					trigger_error("Class $value does not exist", E_USER_WARNING);
					return false;
				}
				$value = new $value;
			}
			$this->$field = $value;
		}
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

	public function getLength()
	{
		return $this->length;
	}

	public function setLength($length)
	{
		$this->length = $length;
		if (!empty($this->RandomKeyGenerator))
		{
			$this->RandomKeyGenerator->setKeyLength($this->length);
		}
	}
}
