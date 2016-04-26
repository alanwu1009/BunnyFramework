<?php
namespace my\bq\criterion;
class AggregateProjection implements Criterion{

	private $alias;
	private $property;
	private $op;
	public function __construct($op,$property,$alias){
		$this->alias = $alias;
		$this->property = $property;
		$this->op = $op;
	}

	public function toSqlString($criteria){
		return $this->op."(".$criteria->getAlias().".".$this->property.") as ".$this->alias;
	}

}