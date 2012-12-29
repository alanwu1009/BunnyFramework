<?php
namespace my\bq\criterion;
class AvgProjection implements Criterion{

	private $alias;
	private $property;
	public function __construct($property,$alias){
		$this->alias = $alias;
		$this->property = $property;
	}

	public function toSqlString($criteria){
		return "avg(".$criteria->getAlias().".".$this->property.") as ".$this->alias;
	}

}