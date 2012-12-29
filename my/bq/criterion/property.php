<?php
namespace my\bq\criterion;
class property{
	private $propertyName;
	private $alias;
	

	/**
	 * @param String $propertyName 
	 * @param String $alias 参数别名
	 */
	public function __construct($propertyName,$alias){
		$this->propertyName = $propertyName;
		$this->alias = $alias;
	}
	
	public function getPropertyName(){
		return $this->propertyName;
	}
	

	public function getAlias(){
		return $this->alias;
	}
	
	
	
}