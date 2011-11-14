<?php
namespace Jamm\DataMapper;

interface IEntityFactory
{
	public function getNewInstance(array $map_from_array = NULL);
}
