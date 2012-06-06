<?php
namespace Jamm\DataMapper;

interface IMapper
{
	public function insert($object = null);

	public function update($object);

	public function delete($id);

	public function fetchNext();

	/**
	 * @param int $offset
	 * @param int $limit
	 * @param array $filter_keys
	 * @param array $filter_key_values
	 * @return boolean
	 */
	public function startFetchAll($offset = 0, $limit = 0, $filter_keys = array(), $filter_key_values = array());

	public function fetchByID($id);

	public function truncateStorage();
}
