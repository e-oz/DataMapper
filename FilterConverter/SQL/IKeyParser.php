<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
interface IKeyParser
{
	public function canParseIt($key);

	public function getSQL($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL);
}
