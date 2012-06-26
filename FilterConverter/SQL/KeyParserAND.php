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
				$value = $PrepareValues->getPreparedValue($key, $value);
			}
			return $key.'='.$value;
		}
		if (count($value) > 1)
		{
			return '('.implode(' AND ', $value).')';
		}
		else
		{
			return current($value);
		}
	}
}
