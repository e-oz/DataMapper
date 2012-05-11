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
	 * @return boolean
	 */
	public function startFetchAll($offset = 0, $limit = 0);

	public function fetchByID($id);

	public function truncateStorage();
}
