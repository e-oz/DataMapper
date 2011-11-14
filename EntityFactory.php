<?php
namespace Jamm\DataMapper;

abstract class EntityFactory implements IEntityFactory
{
	/** @var EntityConverter */
	protected $EntityConverter;

	protected function mapObjectFromArray($object, $data_array)
	{
		if (empty($this->EntityConverter)) $this->EntityConverter = new EntityConverter();
		return $this->EntityConverter->mapObjectFromArray($object, $data_array);
	}

	public function getNewInstance(array $map_from_array = NULL)
	{
		$object = $this->getObjectInstance();
		if (!empty($map_from_array)) $this->mapObjectFromArray($object, $map_from_array);
		return $object;
	}

	abstract protected function getObjectInstance();
}
