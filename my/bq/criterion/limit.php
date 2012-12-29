<?php
namespace my\bq\criterion;
class Limit implements Criterion{

	private $firstResult;
	private $size;
	
	
	public function __construct($firstResult,$size){
		$this->firstResult = $firstResult;
		$this->size = $size;
	}

	public function toSqlString($lCrit){
		return "limit ".$this->firstResult.",".$this->size;
	}
	
	
	public function __get($var){
		return $this->$var;
	}
	public function __set($var,$value){
		$this->$var = $value;
	}

}
