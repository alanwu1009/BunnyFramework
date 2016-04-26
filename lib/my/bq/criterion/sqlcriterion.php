<?php
namespace my\bq\criterion;

class SQLCriterion implements Criterion{

	private $sql;
	function __construct($sql){
		$this->sql = $sql;
	}

	public function toSqlString($criteria){
		return $this->sql;
	}


}