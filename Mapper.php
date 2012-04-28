<?php
namespace Jamm\DataMapper;

class Mapper implements IMapper
{
	/** @var IEntityFactory */
	private $model_factory;
	/** @var IStorageGateway */
	protected $storage_gateway;
	/** @var EntityConverter */
	protected $EntityConverter;

	public function __construct(IEntityFactory $ModelFactory, IStorageGateway $StorageGateway)
	{
		$this->model_factory   = $ModelFactory;
		$this->storage_gateway = $StorageGateway;
	}

	public function truncateStorage()
	{
		return $this->storage_gateway->truncateTable();
	}

	public function insert($object)
	{
		$values = $this->mapToArray($object);
		$result = $this->storage_gateway->insert($values);
		if (!empty($result))
		{
			$primary_field = $this->storage_gateway->getPrimaryField();
			if (!empty($primary_field))
			{
				$this->setPrimaryFieldValue($object, $primary_field, $result);
			}
		}
		return $result;
	}

	public function fetchNext()
	{
		$data_array = $this->storage_gateway->fetchNext();
		if (empty($data_array)) return false;
		return $this->mapFromArray($data_array);
	}

	public function fetchByID($id)
	{
		$data_array = $this->storage_gateway->fetchByID($id);
		if (empty($data_array)) return false;
		return $this->mapFromArray($data_array);
	}

	public function update($object)
	{
		$values = $this->mapToArray($object);
		return $this->storage_gateway->update($values);
	}

	public function delete($id)
	{
		return $this->storage_gateway->delete($id);
	}

	protected function mapFromArray($array)
	{
		return $this->model_factory->getNewInstance($array);
	}

	protected function getEntityConverter()
	{
		if (empty($this->EntityConverter)) $this->EntityConverter = new EntityConverter();
		return $this->EntityConverter;
	}

	protected function mapToArray($object)
	{
		return $this->getEntityConverter()->mapObjectToArray($object);
	}

	protected function setPrimaryFieldValue($object, $field, $value)
	{
		$this->getEntityConverter()->setFieldValue($object, $field, $value);
	}

	/**
	 * @param int $offset
	 * @param int $limit
	 * @return boolean
	 */
	public function startFetchAll($offset = 0, $limit = 0)
	{
		return $this->storage_gateway->startFetchAll($offset, $limit);
	}

	/**
	 * @return \Jamm\DataMapper\IEntityFactory
	 */
	protected function getModelFactory()
	{
		return $this->model_factory;
	}
}
