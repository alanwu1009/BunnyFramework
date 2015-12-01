<?php
namespace my\bq\criterion;
class Exists implements Criterion,MongoCriterion{
	private $propertyName;
	private $exists;
	public function __construct($propertyName,$exists) {
		$this->propertyName = $propertyName;
		$this->exists = $exists;
	}

    public function toSqlString($criteria, $placeholder = true){
           return $this->exists? $this->propertyName." exists(".$this->sql.")": $this->propertyName." not exists";
    }

    public function toMongoParam($criteria){
        return $this->exists? array($this->propertyName => array('$exists'=>true)): array($this->propertyName => array('$exists'=>false));
    }
	
}