<?php
namespace my\bq\criterion;
class FiledTranslater extends DataTranslater{
	
	private $property;
	private $transHandle;
	private $crit;
	
	function __construct($property,&$transHandle){
		$this->property = $property;
		$this->transHandle = $transHandle;
	}
	
	public function setCriteria($criteria){
		$this->crit = $criteria;
	}
	
	public function getCriteria(){
		return $this->crit;
	}
	
	public function getProperty(){
		return $this->property;
	}
	
	
		
	public function translate($value){
		
		return  call_user_func_array($this->transHandle, array($value));
		
	}
	
}