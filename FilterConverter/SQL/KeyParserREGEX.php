<?php
namespace Jamm\DataMapper\FilterConverter\SQL;

class KeyParserREGEX implements IKeyParser
{
    protected $key = '$regex';

    public function canParseIt($key)
   	{
   		return $this->key==$key;
   	}


    public function getSQL($key, $value, $parent_key, IPrepareValues $PrepareValues = NULL)
    {
        if (is_array($value))
        {
            trigger_error('Value should not be array, wrong filter', E_USER_NOTICE);
            return false;
        }
        if (!empty($PrepareValues))
        {
            $value = $PrepareValues->getPreparedValue($parent_key, $value);
        }
        $parent_key = '`'.addslashes($parent_key).'`';
        return $this->getExpression($parent_key, $value);
    }

    protected function getExpression($key, $value)
   	{
   		return $key.' REGEXP '.$value;
   	}
}
