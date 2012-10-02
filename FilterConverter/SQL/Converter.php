<?php
namespace Jamm\DataMapper\FilterConverter\SQL;
class Converter
{
	/** @var IKeyParser[] */
	protected $key_parsers;
	/** @var IKeyParser|NULL */
	protected $key_parser_default;

	public function __construct()
	{
		$this->addParsers();
	}

	protected function addParsers()
	{
		$this->key_parsers[]      = new KeyParserOR();
		$this->key_parser_default = new KeyParserAND();
		$this->key_parsers[]      = new KeyParserGT();
		$this->key_parsers[]      = new KeyParserGTE();
		$this->key_parsers[]      = new KeyParserLT();
		$this->key_parsers[]      = new KeyParserLTE();
		$this->key_parsers[]      = new KeyParserIN();
		$this->key_parsers[]      = new KeyParserNotIN();
		$this->key_parsers[]      = new KeyParserNotEqual();
        $this->key_parsers[]      = new KeyParserREGEX();
	}

	/**
	 * @param array $filter_array
	 * @param IPrepareValues|null $PrepareValues
	 * @return bool|string
	 */
	public function getSQLStringFromFilterArray(array $filter_array, IPrepareValues $PrepareValues = NULL)
	{
		if (empty($filter_array) || !is_array($filter_array)) return false;
		$SQL = $this->getConcatenatedLines($filter_array, $PrepareValues);
		return $SQL;
	}

	protected function getConcatenatedLines($filter_array, IPrepareValues $PrepareValues = NULL)
	{
		$SQL_lines = array();
		foreach ($filter_array as $key=> $value)
		{
			if (!$this->isFilteredKey($key))
			{
				continue;
			}
			$SQL_lines[] = $this->getStringForKeyValue($key, $value, '', $PrepareValues);
		}
		return implode(' AND ', $SQL_lines);
	}

	protected function getStringForKeyValue($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL)
	{
		if (!$this->isFilteredKey($key))
		{
			return false;
		}
		$KeyParser = $this->getKeyParser($key);
		if (empty($KeyParser))
		{
			trigger_error('Can not parse key '.$key, E_USER_NOTICE);
			return false;
		}
		$parsed_value = $this->getParsedValue($value, $key, $PrepareValues);
		$SQL          = $KeyParser->getSQL($key, $parsed_value, $parent_key, $PrepareValues);
		return $SQL;
	}

	protected function getParsedValue($value, $parent_key, IPrepareValues $PrepareValues = NULL)
	{
		if (!is_array($value))
		{
			return $value;
		}
		$parsed_value = array();
		$i            = 0;
		foreach ($value as $k=> $v)
		{
			if ($k===$i && !is_array($v))
			{
				$parsed_value[] = $v;
			}
			else
			{
				if (!$this->isFilteredKey($k))
				{
					continue;
				}
				$parsed_value[] = $this->getStringForKeyValue($k, $v, $parent_key, $PrepareValues);
			}
			$i++;
		}
		return $parsed_value;
	}

	protected function getKeyParser($key)
	{
		if (empty($this->key_parsers))
		{
			return $this->key_parser_default;
		}
		foreach ($this->key_parsers as $KeyParser)
		{
			if ($KeyParser->canParseIt($key))
			{
				return $KeyParser;
			}
		}
		return $this->key_parser_default;
	}

	protected function isFilteredKey($key)
	{
		if (!is_scalar($key)) return true;
		$filtered_key = preg_replace('/[^a-zA-Z0-9_\$]/', '', $key);
		return $filtered_key===((string)$key);
	}
}
