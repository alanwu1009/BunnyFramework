<?php
namespace my\bq\criterion;

use my\bq\criterion\MongoCriterion;

class  BetweenExpression implements Criterion,MongoCriterion{
	
	private $property;
	private $lo;
	private $hi;
	public function __construct($property,$lo,$hi){
		$this->property = $property;
		$this->lo = $lo;
		$this->hi = $hi;
	}
	
	public function toSqlString($criteria,$placeholder = true){
        if($placeholder){
            array_push(CriteriaQuery::$parameters, $this->lo);
            array_push(CriteriaQuery::$parameters, $this->hi);
            return $criteria->getAlias().".".$this->property." between ? and ?";
        }
        return $criteria->getAlias().".".$this->property." between '".$this->lo."' and '".$this->hi."'";
	}

    public function toMongoParam($criteria){
        $param_op=array();
        if($this->lo){//开始时间
            $param_op[]=array($this->property=>array('$gt'=>$this->lo));
        }
        if($this->hi){//结束时间
            $param_op[] = array($this->property=>array('$lt'=>$this->hi));
        }
        if(empty($param_op))
            return $param_op;
        $param = array('$and'=>$param_op);
        return $param;
    }

	
}