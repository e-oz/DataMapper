<?php
namespace Jamm\DataMapper;

trait CamelCase
{
	public function inCamelCase($string)
	{
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}
}
