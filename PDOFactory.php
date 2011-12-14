<?php
namespace Jamm\DataMapper;

class PDOFactory implements IPDOFactory
{
	/** @var \PDO */
	protected $connection;
	protected $dsn;
	protected $username;
	protected $password;
	protected $options;

	public function __construct($dsn, $username, $password, $options)
	{
		$this->dsn      = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->options  = $options;
	}

	/**
	 * @return \PDO
	 */
	public function getConnection()
	{
		if (empty($this->connection))
		{
			$this->connection = new \PDO($this->dsn, $this->username, $this->password, $this->options);
		}
		return $this->connection;
	}

	public function setConnection(\PDO $PDO_connection)
	{
		$this->connection = $PDO_connection;
	}
}
