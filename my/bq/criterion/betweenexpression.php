<?php
namespace my\bq\criterion;


class  BetweenExpression implements Criterion{
	
	private $property;
	private $lo;
	private $hi;
	public function __construct($property,$lo,$hi){
		$this->property = $property;
		$this->lo = $lo;
		$this->hi = $hi;
	}
	
	public function toSqlString($criteria){
		array_push(CriteriaQuery::$parameters, $this->lo);
		array_push(CriteriaQuery::$parameters, $this->hi);
		return $criteria->getAlias().".".$this->property." between ? and ?";
	}
	
}