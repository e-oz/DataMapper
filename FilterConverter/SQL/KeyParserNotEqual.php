<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserNotEqual extends KeyParserGT
{
	public function __construct()
	{
		$this->key = '$ne';
	}

	protected function getExpression($key, $value)
	{
		return $key.' != '.$value;
	}
}
