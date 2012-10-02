<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserAND implements IKeyParser
{
	protected $key = '$and';

	public function canParseIt($key)
	{
		return true;
	}

	public function getSQL($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL)
	{
		if (!is_array($value))
		{
			if (!empty($PrepareValues))
			{
				$prepared_value = $PrepareValues->getPreparedValue($key, $value);
				if ($prepared_value===false)
				{
					return $value;
				}
				$value = $prepared_value;
			}
			elseif (!is_numeric($value))
			{
				$value = "'$value'";
			}
			return '`'.addslashes($key).'` = '.$value;
		}
		if (count($value) > 1)
		{
			if ($key!==$this->key)
			{
				return false;
			}
			else
			{
				return '('.implode(' AND ', $value).')';
			}
		}
		else
		{
			return current($value);
		}
	}
}
