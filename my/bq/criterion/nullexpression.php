<?php
namespace my\bq\criterion;

class NullExpression implements Criterion{

	private $property;
	private $op;
	public function __construct($property,$op){
		$this->property = $property;
		$this->op = $op;
	}

	public function toSqlString($criteria){
		return $criteria->getAlias().".".$this->property." ".$this->op;
	}

}

