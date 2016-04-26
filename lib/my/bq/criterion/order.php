<?php
namespace my\bq\criterion;
class Order implements Criterion,MongoCriterion{

	private $ascending;
	private $ignoreCase;
	private $propertyName;
	
	public function toString() {
		return $propertyName + ' ' + ($ascending?"asc":"desc");
	}
	
	public function ignoreCase() {
		$ignoreCase = true;
		return $this;
	}

	/**
	 * Constructor for Order.
	 */
	protected function __construct($propertyName, $ascending){
		$this->propertyName = $propertyName;
		$this->ascending = $ascending;
	}

	public function toSqlString($criteria){
        $fileds = $criteria->getFileds();
        if($fileds == "*" || $fileds[0] == "*"){
            $entity = $criteria->getTableEntity();
            $cfg = $entity->getConfig();
            $fileds = $cfg['columns'];
        }

        if($fileds && in_array($this->propertyName,$fileds)){
            return $criteria->getAlias().'__'.$this->propertyName . ' ' . ($this->ascending?"asc":"desc");
        }else{
            return $this->propertyName . ' ' . ($this->ascending?"asc":"desc");
        }
	}

    public function toMongoParam($criteria){
        return array($this->propertyName=>$this->ascending?1:-1);
    }



	public static function asc($propertyName) {
		return new Order($propertyName, true);
	}

	public static function desc($propertyName) {
		return new Order($propertyName, false);
	}

}
