<?php
namespace my\bq\criterion;

class MongoExpression implements MongoCriterion{

	private $expressions;
	public function __construct($expressions){
		$this->expressions = $expressions;
	}

    public function toMongoParam($criteria){
        return $this->expressions;
    }
}

