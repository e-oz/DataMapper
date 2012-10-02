<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class PrepareValues implements IPrepareValues
{
	private $prefixed_statements = array();
	private $statement_suffix = '_s';

	public function getStatements()
	{
		return $this->prefixed_statements;
	}

	public function getPreparedValue($key, $value)
	{
		if (!$this->isFilteredKey($key))
		{
			return false;
		}
		if (!is_array($value))
		{
			return $this->getKeyOfInsertedStatementPair($key, $value);
		}
		else
		{
			$new_value = array();
			foreach ($value as $v)
			{
				$new_value[] = $this->getKeyOfInsertedStatementPair($key, $v);
			}
			return $new_value;
		}
	}

	protected function getKeyOfInsertedStatementPair($key, $value)
	{
		if (!$this->isFilteredKey($key))
		{
			return false;
		}
		if (is_numeric($key))
		{
			return false;
		}
		$key = ':'.$key;
		if (isset($this->prefixed_statements[$key]))
		{
			$next_key                             = $this->getNextStatementKey($key);
			$this->prefixed_statements[$next_key] = $value;
			return $next_key;
		}
		else
		{
			$this->prefixed_statements[$key] = $value;
			return $key;
		}
	}

	protected function getNextStatementKey($key)
	{
		for ($i = 0; $i < 1000; $i++)
		{
			$next_key = $key.$this->statement_suffix.$i;
			if (!isset($this->prefixed_statements[$next_key]))
			{
				return $next_key;
			}
		}
		return $key;
	}

	public function getStatementSuffix()
	{
		return $this->statement_suffix;
	}

	public function setStatementSuffix($statement_suffix)
	{
		$this->statement_suffix = $statement_suffix;
	}

	protected function isFilteredKey($key)
	{
		if (!is_scalar($key)) return true;
		$filtered_key = preg_replace('/[^a-zA-Z0-9_\$]/', '', $key);
		return $filtered_key===((string)$key);
	}
}
