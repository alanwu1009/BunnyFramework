<?php
namespace my\bq\criterion;

class NullExpression implements Criterion,MongoCriterion{

	private $property;
	private $op;
	public function __construct($property,$op){
		$this->property = $property;
		$this->op = $op;
	}

	public function toSqlString($criteria,$placeholder=true){
        if($placeholder){
            return $criteria->getAlias().".".$this->property." ".$this->op;
        }
		return $this->property." ".$this->op;
	}

    public function toMongoParam($criteria){
        if($this->op == "is null"){
            return array($this->property=>array('$exists'=>false));
        }else{
            return array($this->property=>array('$exists'=>true));
        }

    }
}

