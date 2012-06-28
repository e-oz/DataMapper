<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserLT extends KeyParserGT
{
	public function __construct()
	{
		$this->key = '$lt';
	}

	protected function getExpression($key, $value)
	{
		return $key.' < '.$value;
	}
}
