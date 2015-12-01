<?php
namespace my\bq\criterion;
class AppendExpression implements Criterion {
	
	private $ahs = array();
	private $op;

	
	public  function __construct($ahs, $op){
		$this->ahs = $ahs;
		$this->op = $op;
	}	
	
	
	
	public function toSqlString($criteria,$placeholder = true){
		$rs = "";
		if(is_array($this->ahs)){
			foreach ($this->ahs as $r)
			$rs .= $this->getOp()." ".$r->toSqlString($criteria);;
			
		}else{
			$rs = $this->getOp()." ".$this->ahs->toSqlString($criteria);
		}
		
		return $rs;
	}


	public function getOp() {
		return $this->op;
	}

}