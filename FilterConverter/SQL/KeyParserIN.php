<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserIN implements IKeyParser
{
	protected $key = '$in';

	public function canParseIt($key)
	{
		return $this->key==$key;
	}

	public function getSQL($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL)
	{
		if (!is_array($value))
		{
			trigger_error('Wrong filter syntax', E_USER_NOTICE);
			return false;
		}
		$value = array_values($value);
		if (!empty($PrepareValues))
		{
			$value = $PrepareValues->getPreparedValue($parent_key, $value);
		}
		else
		{
			foreach ($value as $k=> $v)
			{
				if (!is_numeric($v))
				{
					$value[$k] = "'$v'";
				}
			}
		}
		$parent_key = '`'.addslashes($parent_key).'`';
		return $this->getExpression($parent_key, $value);
	}

	protected function getExpression($key, $value)
	{
		return $key.' IN('.implode(', ', $value).')';
	}
}
