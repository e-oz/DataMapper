<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserOR implements IKeyParser
{
	protected $key = '$or';

	public function getSQL($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL)
	{
		if (!is_array($value))
		{
			trigger_error('Value should be an array', E_USER_WARNING);
			return false;
		}
		return '('.implode(' OR ', $value).')';
	}

	public function canParseIt($key)
	{
		return $key==$this->key;
	}
}
