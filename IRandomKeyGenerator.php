<?php
namespace Jamm\DataMapper;
interface IRandomKeyGenerator
{
	public function getKey();

	public function setSymbols($symbols);

	public function setKeyLength($key_length = 32);
}
