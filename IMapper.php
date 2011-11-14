<?php
namespace Jamm\DataMapper;

interface IMapper
{
	public function insert($object);

	public function update($object);

	public function delete($object);

	public function fetchNext();

	public function fetchByID($id);
}
