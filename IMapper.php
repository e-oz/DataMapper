<?php
namespace Jamm\DataMapper;

interface IMapper
{
	public function insert($object);

	public function update($object);

	public function delete($id);

	public function fetchNext();

	public function fetchByID($id);
}
