<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class PrepareValues implements IPrepareValues
{
	private $prefix = ':';
	private $prefixed_statements = array();
	private $statement_suffix = '_s';

	public function setPrefix($prefix)
	{
		return $this->prefix = $prefix;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}

	public function getStatements()
	{
		return $this->prefixed_statements;
	}

	public function getPreparedValue($key, $value)
	{
		if (!is_array($value))
		{
			$prefixed_key                             = $this->prefix.$key;
			$this->prefixed_statements[$prefixed_key] = $value;
			return $prefixed_key;
		}
		else
		{
			$i         = 1;
			$new_value = array();
			foreach ($value as $v)
			{
				$next_key                             = $this->prefix.$key.$this->statement_suffix.$i;
				$this->prefixed_statements[$next_key] = $v;
				$new_value[]                          = $next_key;
				$i++;
			}
			return $new_value;
		}
	}

	public function getStatementSuffix()
	{
		return $this->statement_suffix;
	}

	public function setStatementSuffix($statement_suffix)
	{
		$this->statement_suffix = $statement_suffix;
	}
}
