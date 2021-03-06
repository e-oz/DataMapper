<?php
namespace Jamm\DataMapper;
interface IStorageGateway
{
	/**
	 * @param array $values
	 * @return bool|string true, false, or last inserted ID
	 */
	public function insert($values = array());

	public function update($values);

	public function delete($id);

	/** @return string */
	public function getPrimaryField();

	/** @return array|bool */
	public function fetchNext();

	/**
	 * @param int $offset
	 * @param int $limit
	 * @param array $filter_keys
	 * @param array $filter_key_values
	 * @return boolean
	 */
	public function startFetchAll($offset = 0, $limit = 0, $filter_keys = array(), $filter_key_values = array());

	/**
	 * @param int|string $id
	 * @return array|bool
	 */
	public function fetchByID($id);

	public function truncateTable();

	public function getTableName();
}