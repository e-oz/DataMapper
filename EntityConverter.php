<?php
namespace Jamm\DataMapper;

class EntityConverter
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
				if (array_key_exists($name, $data_array))
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

	public function mapObjectToArray($object)
	{
		$result_array = array();
		try
		{
			$reflection = new \ReflectionClass($object);
			$properties = $reflection->getProperties();
			foreach ($properties as $property)
			{
				$property->setAccessible(true);
				$result_array[$property->getName()] = $property->getValue($object);
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

	public function setFieldValue($object, $field, $value)
	{
		try
		{
			$reflection = new \ReflectionClass($object);
			$Property = $reflection->getProperty($field);
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
}
