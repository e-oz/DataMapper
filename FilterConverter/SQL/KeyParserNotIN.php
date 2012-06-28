<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserNotIN extends KeyParserIN
{
	public function __construct()
	{
		$this->key = '$nin';
	}

	protected function getExpression($key, $value)
	{
		return $key.' NOT IN('.implode(', ', $value).')';
	}
}
