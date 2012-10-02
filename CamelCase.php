<?php
namespace Jamm\DataMapper;
trait CamelCase
{
	public function inCamelCase($string)
	{
		if (is_numeric($string[0]))
		{
			$prefix = $this->getWordFromDigit($string[0]);
			$string = $prefix.'_'.$string;
		}
		$string = str_replace('_', ' ', $string);
		$string = ucwords(strtolower($string));
		$string = str_replace(' ', '', $string);
		return $string;
	}

	protected function getWordFromDigit($digit)
	{
		$digit = intval($digit);
		if ($digit < 0 || $digit > 9) $digit = 0;
		$map = [
			0                   => 'zero',
			1                   => 'one',
			2                   => 'two',
			3                   => 'three',
			4                   => 'four',
			5                   => 'five',
			6                   => 'six',
			7                   => 'seven',
			8                   => 'eight',
			9                   => 'nine'];
		return $map[$digit];
	}
}
