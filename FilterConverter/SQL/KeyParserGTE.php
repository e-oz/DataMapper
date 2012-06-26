<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserGTE extends KeyParserGT
{
	public function __construct()
	{
		$this->key = '$gte';
	}

	protected function getExpression($key, $value)
	{
		return $key.'>='.$value;
	}
}
