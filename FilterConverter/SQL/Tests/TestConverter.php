<?php
namespace Jamm\DataMapper\FilterConverter\SQL\Tests;
class TestConverter extends \Jamm\Tester\ClassTest
{
	/** @var \Jamm\DataMapper\FilterConverter\SQL\Converter */
	private $Converter;
	/** @var \Jamm\DataMapper\FilterConverter\SQL\IPrepareValues */
	private $PrepareValues;

	public function setUp()
	{
		$this->Converter     = new \Jamm\DataMapper\FilterConverter\SQL\Converter();
		$this->PrepareValues = new \Jamm\DataMapper\FilterConverter\SQL\PrepareValues();
	}

	public function test_getSQLStringFromFilterArray_OR()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['$or'=> ['a'=> 1, 'b'=> 2]], $this->PrepareValues);
		$this->assertEquals($SQL, '(`a` = :a OR `b` = :b)');
		$this->assertEquals($this->PrepareValues->getStatements(), [':a'=> 1, ':b'=> 2]);
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> 5, '$or'=> ['y'=> 7, 'z'=> 10]]);
		$this->assertEquals($SQL, '`x` = 5 AND (`y` = 7 OR `z` = 10)');
	}

	public function test_getSQLStringFromFilterArray_AND()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['$and'=> ['x'=> 5, 'y'=> 10]], $this->PrepareValues);
		$this->assertEquals($SQL, '(`x` = :x AND `y` = :y)');
		$this->assertEquals($this->PrepareValues->getStatements(), [':x'=> 5, ':y'=> 10]);
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['$and'=> ['x'  => 5,
					   '$or'=> ['y'=> 7, 'z'=> 10]]
			]);
		$this->assertEquals($SQL, '(`x` = 5 AND (`y` = 7 OR `z` = 10))');
	}

	public function test_getSQLStringFromFilterArray_3args()
	{
		$SQl = $this->Converter->getSQLStringFromFilterArray(
			['a'  => 1,
			 'b'  => 2,
			 '$or'=> ['c'   => 3,
					  '$and'=> ['d'=> 4, 'e'=> 5]],
			 'f'  => 6
			]);
		$this->assertEquals($SQl, '`a` = 1 AND `b` = 2 AND (`c` = 3 OR (`d` = 4 AND `e` = 5)) AND `f` = 6');
	}

	public function test_getSQLStringFromFilterArray_gt()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['a'=> 1, 'x'=> ['$gt'=> 5]], $this->PrepareValues);
		$this->assertEquals($SQL, '`a` = :a AND `x` > :x');
		$this->assertEquals($this->PrepareValues->getStatements(), [':a'=> 1, ':x'=> 5]);
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['z'  => ['$gt'=> 12],
			 '$or'=> ['x'=> ['$gt' => 17],
					  'y'=> ['$gt'=> 11]]]
		);
		$this->assertEquals($SQL, '`z` > 12 AND (`x` > 17 OR `y` > 11)');
	}

	public function test_getSQLStringFromFilterArray_lt()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'   => ['$lt'=> 5],
			 '$and'=> [
				 'a'=> ['$lt'=> 'b'],
				 'b'=> ['$gt'=> 'a']
			 ]
			], $this->PrepareValues
		);
		$this->assertEquals($SQL, '`x` < :x AND (`a` < :a AND `b` > :b)');
		$this->assertEquals($this->PrepareValues->getStatements(), [':x'=> 5, ':a'=> 'b', ':b'=> 'a']);
	}

	public function test_getSQLStringFromFilterArray_in()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$gt'=> 5],
			 'y'=> ['$in'=> [1, 2, 3, 4, 5]]
			]
		);
		$this->assertEquals($SQL, '`x` > 5 AND `y` IN(1, 2, 3, 4, 5)');
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$gt'=> 5],
			 'y'=> ['$in'=> [1, 2, 3, 3]]
			], $this->PrepareValues
		);
		$this->assertEquals($SQL, '`x` > :x AND `y` IN(:y, :y_s0, :y_s1, :y_s1)');
		$this->assertEquals($this->PrepareValues->getStatements(), [':x'=> 5, ':y'=> 1, ':y_s0'=> 2, ':y_s1'=> 3]);
	}

	public function test_getSQLStringFromFilterArray_nin()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$nin'=> [1, 2, 3]],
			 'y'=> ['$in'=> [4, 5]]
			]
		);
		$this->assertEquals($SQL, '`x` NOT IN(1, 2, 3) AND `y` IN(4, 5)');
	}

	public function test_getSQLStringFromFilterArray_gte()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$gte'=> 5]]
		);
		$this->assertEquals($SQL, '`x` >= 5');
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$gte', 5]]
		);
		$this->assertTrue($SQL!=='`x` >= 5')->addCommentary($SQL);
	}

	public function test_getSQLStringFromFilterArray_lte()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['y'=> 5,
			 'z'=> ['$lte'=> 25]
			]
		);
		$this->assertEquals($SQL, '`y` = 5 AND `z` <= 25');
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['y'=> ['$lte', 118]]
		);
		$this->assertTrue($SQL!=='`y` <= 118')->addCommentary($SQL);
	}

	public function test_getSQLStringFromFilterArray_ne()
	{
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			['x'=> ['$ne'=> 18]]
		);
		$this->assertEquals($SQL, '`x` != 18');
		$SQL = $this->Converter->getSQLStringFromFilterArray(
			[0=> ['$ne'=> 18]]
		);
		$this->assertTrue($SQL!=='`0` != 18')->addCommentary($SQL);
	}

	public function test_getSQLStringFromFilterArray_and_with_array()
	{
		$filter = ['$and'=>
				   [
					   ['order_date_time'=> ['$gte'=> 1333224000]],
					   ['order_date_time'=> ['$lte'=> 1334087999]],
					   ['order_delivery_address_id'=> ['$in'=> [1, 3, 5]]]
				   ]
		];
		$SQL    = $this->Converter->getSQLStringFromFilterArray($filter);
		$this->assertEquals($SQL, '((`order_date_time` >= 1333224000) AND `order_date_time` <= 1334087999 AND `order_delivery_address_id` IN(1, 3, 5))');
		$SQL = $this->Converter->getSQLStringFromFilterArray($filter, $this->PrepareValues);
		$this->assertEquals($SQL, '((`order_date_time` >= :order_date_time) AND `order_date_time` <= :order_date_time_s0 AND `order_delivery_address_id` IN(:order_delivery_address_id, :order_delivery_address_id_s0, :order_delivery_address_id_s1))');
	}
}
