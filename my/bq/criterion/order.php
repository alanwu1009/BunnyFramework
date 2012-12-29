<?php
namespace my\bq\criterion;
class Order implements Criterion{

	private $ascending;
	private $ignoreCase;
	private $propertyName;
	
	public function toString() {
		return $propertyName + ' ' + ($ascending?"asc":"desc");
	}
	
	public function ignoreCase() {
		$ignoreCase = true;
		return $this;
	}

	/**
	 * Constructor for Order.
	 */
	protected function __construct($propertyName, $ascending){
		$this->propertyName = $propertyName;
		$this->ascending = $ascending;
	}

	public function toSqlString($criteria){
		return $criteria->getAlias().'.'.$this->propertyName . ' ' . ($this->ascending?"asc":"desc");
	}

	public static function asc($propertyName) {
		return new Order($propertyName, true);
	}

	public static function desc($propertyName) {
		return new Order($propertyName, false);
	}

}
