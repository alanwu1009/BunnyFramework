<?php
namespace my\bq\criterion;

class InExpression implements Criterion,MongoCriterion{
	private $propertyName;
	private $values;
	public function __construct($propertyName, $values) {
		$this->propertyName = $propertyName;
		$this->values = $values;
	}

    public function toSqlString($criteria,$placeholder=true){
		if($placeholder){
            return $criteria->getAlias().'.'.$this->propertyName . " in(" . self::buildInParam($this->values, CriteriaQuery::$parameters). ")";
        }
        $in = '';
        foreach($this->values as $v){
            if($in != ''){
                $in.= ',';
            }
            if(is_numeric($v)){
                $in.="'$v'";
            }else{
                $in.="$v";
            }
        }
        return $this->propertyName . " in(" .$in. ")";
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


    public function toMongoParam($criteria){
        return array($this->propertyName=>array('$in'=>$this->values));
    }

	
	
}