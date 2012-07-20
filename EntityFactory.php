<?php
namespace Jamm\DataMapper;
abstract class EntityFactory implements IEntityFactory
{
	use \Jamm\DataMapper\EntityConverter;

	public function getNewInstance(array $map_from_array = NULL)
	{
		$object = $this->getObjectInstance();
		if (!empty($map_from_array)) $this->mapObjectFromArray($object, $map_from_array);
		return $object;
	}

	abstract protected function getObjectInstance();
}
