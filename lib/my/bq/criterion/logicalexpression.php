<?php
namespace my\bq\criterion;
class LogicalExpression implements Criterion {

	private $criterions;
	private $op;

	public  function __construct($criterions, $op) {
		$this->criterions = $criterions;
		$this->op = $op;
	}
	
	public function toSqlString($criteria,$placeholder = true){
        $sqlString = "";

        if($this->criterions && is_array($this->criterions)){
            foreach($this->criterions as $criterion){
                if($sqlString == "")$sqlString.="(";
                if($sqlString != "(")$sqlString.=" or ";

                $sqlString .= $criterion->toSqlString($criteria);
            }
            if($sqlString!=""){
                $sqlString.=")";
            }
        }

        return $sqlString;
	}


    public function toMongoParam($criteria){
    }


	public function getOp() {
		return $this->op;
	}

}