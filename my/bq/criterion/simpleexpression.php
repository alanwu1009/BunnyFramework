<?php
namespace my\bq\criterion;
class SimpleExpression implements Criterion,MongoCriterion {

	private $propertyName;
	private $value;
	private $op;


    public function __construct($propertyName, $value, $op) {
		$this->propertyName = $propertyName;
		$this->value = $value;
		$this->op = $op;
	}

	public function toSqlString($criteria,$placeholder = true){
        if($placeholder){
            array_push(CriteriaQuery::$parameters, $this->value);
            return $criteria->getAlias().'.'.$this->propertyName.$this->op .'?';
        }
        if(is_numeric($this->value)){
            return $this->propertyName.$this->op .$this->value;
        }
		return $this->propertyName.$this->op .'"'.$this->value.'"';
	}

    public function toMongoParam($criteria){

        $mongoOp = "";

        if($this->op == "<>") $mongoOp = '$ne';
        if($this->op == '>') $mongoOp = '$gt';
        if($this->op == "<") $mongoOp = '$lt';
        if($this->op == "<=") $mongoOp = '$lte';
        if($this->op == ">=") $mongoOp = '$gte';

       if($mongoOp != ""){

           return array($this->propertyName=>array($mongoOp=>$this->value));
       }
        if($this->op == "="){
            return array($this->propertyName=>$this->value);
        }
    }


	protected final function getOp() {
		return $this->op;
	}

}