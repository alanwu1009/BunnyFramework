<?php
namespace my\bq\criterion;

class RowCountProjection implements Criterion{

	private $alias;
	public function __construct($alias){
		$this->alias = $alias;
	}

	public function toSqlString($criteria){
		
		return "count(*) as ".$criteria->getAlias().'__'.$this->alias;

	}

    public function toMongoParam($criteria){

    }

}

