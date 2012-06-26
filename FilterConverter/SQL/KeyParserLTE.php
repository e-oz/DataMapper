<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class KeyParserLTE extends KeyParserGT
{
	public function __construct()
	{
		$this->key = '$lte';
	}

	protected function getExpression($key, $value)
	{
		return $key.'<='.$value;
	}
}
