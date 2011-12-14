<?php
namespace Jamm\DataMapper;

class Mapper implements IMapper
{
	/** @var IEntityFactory */
	protected $model_factory;
	/** @var IStorageGateway */
	protected $storage_gateway;
	/** @var EntityConverter */
	protected $EntityConverter;

	public function __construct(IEntityFactory $ModelFactory, IStorageGateway $StorageGateway)
	{
		$this->model_factory = $ModelFactory;
		$this->storage_gateway = $StorageGateway;
	}

	public function setModelFactory(IEntityFactory $ModelFactory)
	{
		$this->model_factory = $ModelFactory;
	}

	/**
	 * @return \IEntityFactory\DataMapper\IModelFactory
	 */
	public function getModelFactory()
	{
		return $this->model_factory;
	}

	/**
	 * @return IStorageGateway
	 */
	public function getStorageGateway()
	{
		return $this->storage_gateway;
	}

	/**
	 * @param  $storage_gateway
	 */
	public function setStorageGateway($storage_gateway)
	{
		$this->storage_gateway = $storage_gateway;
	}

	public function insert($object)
	{
		$values = $this->MapToArray($object);
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

	public function update($object)
	{
		$values = $this->MapToArray($object);
		return $this->storage_gateway->update($values);
	}

	public function delete($id)
	{
		return $this->storage_gateway->delete($id);
	}

	public function MapFromArray($array)
	{
		return $this->model_factory->getNewInstance($array);
	}

	protected function getEntityConverter()
	{
		if (empty($this->EntityConverter)) $this->EntityConverter = new EntityConverter();
		return $this->EntityConverter;
	}

	protected function MapToArray($object)
	{
		return $this->getEntityConverter()->mapObjectToArray($object);
	}

	protected function setPrimaryFieldValue($object, $field, $value)
	{
		$this->getEntityConverter()->setFieldvalue($object, $field, $value);
	}

	public function fetchNext()
	{
		$data_array = $this->storage_gateway->fetchNext();
		if (empty($data_array)) return false;
		return $this->model_factory->getNewInstance($data_array);
	}

	public function fetchByID($id)
	{
		$data_array = $this->storage_gateway->fetchByID($id);
		if (empty($data_array)) return false;
		return $this->model_factory->getNewInstance($data_array);
	}
}
