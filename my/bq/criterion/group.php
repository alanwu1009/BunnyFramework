<?php
namespace my\bq\criterion;
class Group implements Criterion{

	private $propertyName;
	

	/**
	 * Constructor for Order.
	 */
	protected function __construct($propertyName){
		$this->propertyName = $propertyName;
	}

	public function toSqlString($criteria){
		return $criteria->getAlias().'.'.$this->propertyName;
	}

	public static function add($propertyName) {
		return new Group($propertyName);
	}

}
