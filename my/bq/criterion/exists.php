<?php
namespace my\bq\criterion;
class Exists implements Criterion{
	private $sql;
	private $exists;
	public function __construct($sql,$exists) {
		$this->sql = $sql;
		$this->exists = $exists;
	}

    public function toSqlString($criteria){
		return $this->exists?"exists(".$this->sql.")":"not exists"."(".$this->sql.")";		
    }
	
}