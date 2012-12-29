<?php
namespace my\bq\criterion;

class InExpression implements Criterion{
	private $propertyName;
	private $values;
	public function __construct($propertyName, $values) {
		$this->propertyName = $propertyName;
		$this->values = $values;
	}

    public function toSqlString($criteria){
		    	
		return $criteria->getAlias().'.'.$this->propertyName . " in(" . self::buildInParam($this->values, CriteriaQuery::$parameters). ")";		
    }
	
	public static function buildInParam($value,&$params){
		if(!is_array($value)) $value = array($value);
		$qStr = "";
		foreach ($value as $k => $item){
			$qStr .= ("?".(count($value)==($k+1)?"":","));
			array_push($params, $item);
		}
		return $qStr;
	}	
	
	
}