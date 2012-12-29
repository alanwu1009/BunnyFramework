<?php
namespace my\bq\criterion;
class LogicalExpression implements Criterion {

	private $lhs;
	private $rhs;
	private $ahs;
	private $op;

	public  function __construct($lhs, $rhs,$ahs=null, $op) {
		$this->lhs = $lhs;
		$this->rhs = $rhs;
		$this->ahs = $ahs;
		$this->op = $op;
	}
	
	public function toSqlString($criteria){
		$ls = $this->lhs->toSqlString($criteria);
		$rs = $this->rhs->toSqlString($criteria);
		$as = "";
		if($this->ahs)
		$as = $this->ahs->toSqlString($criteria);

		return '(' .$ls.' ' .$this->getOp() .' ' .$rs.' ' .$as.')';
	}

	public function getOp() {
		return $this->op;
	}

}