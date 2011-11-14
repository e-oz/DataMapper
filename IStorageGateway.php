<?php
namespace Jamm\DataMapper;

interface IStorageGateway
{
	/**
	 * @param array $values
	 * @return bool|string true, false, or last inserted ID
	 */
	public function insert($values);

	public function update($values);

	public function delete($values);

	/** @return string */
	public function getPrimaryField();

	/** @return array|bool */
	public function fetchNext();

	/**
	 * @param int|string $id
	 * @return array|bool
	 */
	public function fetchByID($id);

	public function truncateTable();
}