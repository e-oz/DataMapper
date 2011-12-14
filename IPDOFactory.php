<?php
namespace Jamm\DataMapper;

interface IPDOFactory
{
	/** @return \PDO */
	public function getConnection();
	public function setConnection(\PDO $PDO_connection);
}
