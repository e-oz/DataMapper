<?php
namespace Jamm\DataMapper;

interface IEntityConverter
{
	public function mapObjectFromArray($object, $data_array);

	/**
	 * @param $object
	 * @return array
	 */
	public function mapObjectToArray($object);

	public function setFieldValue($object, $field, $value);
}
