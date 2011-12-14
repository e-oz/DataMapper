<?php
namespace Jamm\DataMapper;

interface IMetaTable
{
	/** @return string */
	public function getName();

	public function setName($name);

	public function addField(IField $Field);

	/**
	 * @return IField[]
	 */
	public function getFields();

	/**
	 * @param string $name
	 * @return Field
	 */
	public function getFieldByName($name);

	/**
	 * @param IField[] $fields
	 * @return array
	 */
	public function getNamesOfFields(array $fields);

	/** @return string */
	public function getPrimaryFieldName();

	/** @return IField[] */
	public function getWritableFields();

	/** @return IField[] */
	public function getIndexedFields();

	/** @return array */
	public function getSchemeArray();

	public function mapSchemeArray(array $data);

	public function mapFromDB(\PDO $PDO_connection);

	/** @return string */
	public function getDbName();

	public function setDbName($db_name);
}
