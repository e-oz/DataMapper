<?php
namespace Jamm\DataMapper;
class ConnectedPDOFactory implements IPDOFactory
{
	/** @var \PDO */
	protected $connection;

	public function __construct(\PDO $PDO_connection)
	{
		$this->connection = $PDO_connection;
	}

	/** @return \PDO */
	public function getConnection()
	{
		return $this->connection;
	}

	public function setConnection(\PDO $PDO_connection)
	{
		$this->connection = $PDO_connection;
	}
}
