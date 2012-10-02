<?php
namespace Jamm\DataMapper;
trait EntityConverter
{
	public function mapObjectFromArray($object, $data_array)
	{
		try
		{
			$reflection = new \ReflectionClass($object);
			$properties = $reflection->getProperties();
			foreach ($properties as $property)
			{
				$name = $property->getName();
				//if value is set and not NULL
				if (isset($data_array[$name]))
				{
					$property->setAccessible(true);
					$property->setValue($object, $data_array[$name]);
					if (!$property->isPublic()) $property->setAccessible(false);
				}
			}
			return true;
		}
		catch (\ReflectionException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
			return false;
		}
	}

	public function mapObjectsArrayFromArraysList($object_instance, $arrays_list)
	{
		if (empty($arrays_list)) return false;
		$objects = array();
		foreach ($arrays_list as $data)
		{
			if (!is_array($data)) continue;
			$object = clone $object_instance;
			$this->mapObjectFromArray($object, $data);
			$objects[] = $object;
		}
		return $objects;
	}

	public function mapObjectToArray($object, $with_empty_fields = false)
	{
		$result_array = array();
		if (empty($object)) return $result_array;
		try
		{
			$reflection = new \ReflectionClass($object);
			$properties = $reflection->getProperties();
			foreach ($properties as $property)
			{
				$property->setAccessible(true);
				$value = $property->getValue($object);
				if (!isset($value) || is_null($value))
				{
					if (!$with_empty_fields) continue;
				}
				if (is_object($value))
				{
					$value = $this->mapObjectToArray($value);
				}
				$result_array[$property->getName()] = $value;
				if (!$property->isPublic()) $property->setAccessible(false);
			}
		}
		catch (\ReflectionException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
			return false;
		}
		return $result_array;
	}

	public function mapObjectsArrayToArray(array $objects)
	{
		$array = [];
		if (empty($objects)) return [];
		foreach ($objects as $object)
		{
			$array[] = $this->mapObjectToArray($object);
		}
		return $array;
	}

	public function setFieldValue($object, $field, $value)
	{
		try
		{
			$reflection = new \ReflectionClass($object);
			$Property   = $reflection->getProperty($field);
			$Property->setAccessible(true);
			$Property->setValue($object, $value);
			if (!$Property->isPublic()) $Property->setAccessible(false);
		}
		catch (\ReflectionException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);
			return false;
		}
		return true;
	}

	public function getFieldsListOfObject($object)
	{
		$array = $this->mapObjectToArray($object, true);
		return array_keys($array);
	}
}
