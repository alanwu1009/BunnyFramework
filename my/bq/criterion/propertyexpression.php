<?php
namespace my\bq\criterion;

class PropertyExpression implements Criterion{
	
	private $property;
	private $otherProperty;
	private $op;
	public function __construct($property,$otherProperty,$op){
		$this->property = $property;
		$this->otherProperty = $otherProperty;
		$this->op = $op;
	}

	public function toSqlString($criteria){
		return $this->property->getAlias().'.'.$this->property->getPropertyName().$this->op .$this->otherProperty->getAlias().'.'.$this->otherProperty->getPropertyName();
	}	
	
	
}