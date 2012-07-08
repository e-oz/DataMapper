<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
interface IPrepareValues
{
	public function getStatements();

	public function getPreparedValue($key, $value);

	public function getStatementSuffix();

	public function setStatementSuffix($statement_suffix);
}
