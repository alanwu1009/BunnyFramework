<?php
namespace my\bq\criterion;
class Distinct implements Criterion{

	private $property;
	public function __construct($property){
		$this->property = $property;
	}


	public function toSqlString($criteria){

		return "distinct " . $this->property;

	}

}

