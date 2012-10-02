<?php
namespace Jamm\DataMapper;
class RandomKeyGenerator implements IRandomKeyGenerator
{
	private $symbols = 'qwertyuiopasdfghjklzxcvbnm1234567890';
	private $key_length = 32;

	public function getKey()
	{
		$len = strlen($this->symbols)-1;
		$key = '';
		for ($i = 0; $i < $this->key_length; $i++)
		{
			$key .= $this->symbols[mt_rand(0, $len)];
		}
		return $key;
	}

	/**
	 * Set string, containing all allowed symbols in key
	 * @param $symbols
	 */
	public function setSymbols($symbols)
	{
		$this->symbols = $symbols;
	}

	public function setKeyLength($key_length = 32)
	{
		$this->key_length = $key_length;
	}
}
