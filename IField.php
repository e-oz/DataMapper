<?php
namespace Jamm\DataMapper;
interface IField
{
	public function setAutoincrement($autoincrement = true);

	/** @return boolean */
	public function isAutoincrement();

	public function setName($name);

	/** @return string */
	public function getName();

	public function setPrimaryIndex($primary_index = true);

	/** @return boolean */
	public function isPrimaryIndex();

	public function setReadOnly($read_only = true);

	/** @return boolean */
	public function isReadOnly();

	/** @return string */
	public function getType();

	public function setType($type);

	/** @return boolean */
	public function isNotNull();

	public function setNotNull($not_null = false);

	/**
	 * @param mixed $value
	 * @return boolean
	 */
	public function isValueAcceptable($value);

	/** @return boolean */
	public function isUnique();

	public function setUnique($unique = true);

	/**
	 * @return \Jamm\DataMapper\IRandomKeyGenerator
	 */
	public function getRandomKeyGenerator();

	public function setRandomKeyGenerator(\Jamm\DataMapper\IRandomKeyGenerator $RandomKeyGenerator);

	/** @return boolean */
	public function isIndexed();

	public function setIndexed($indexed = true);

	/** @return array */
	public function getSchemeArray();

	public function mapSchemeArray(array $data);

	public function getCommentary();

	public function setCommentary($commentary);
}
