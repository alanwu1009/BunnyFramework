<?php
namespace my\bq\criterion;
class SimpleExpression implements Criterion {

	private $propertyName;
	private $value;
	private $op;

	public function __construct($propertyName, $value, $op) {
		$this->propertyName = $propertyName;
		$this->value = $value;
		$this->op = $op;
	}

	public function toSqlString($criteria){
		array_push(CriteriaQuery::$parameters, $this->value);
		return $criteria->getAlias().'.'.$this->propertyName.$this->op .'?';

	}


	protected final function getOp() {
		return $this->op;
	}

}