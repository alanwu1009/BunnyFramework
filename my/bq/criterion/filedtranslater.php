<?php
namespace my\bq\criterion;
class FiledTranslater extends DataTranslater{
	
	private $property;
	private $transHandle;
	private $crit;
    private $attribute;

    /**
     * @param property $property
     * @param function($value,$crit){}  $transHandle
     * @param array $attribute; 附加属性;
     */
    function __construct($property,&$transHandle,$attribute = null){
		$this->property = $property;
		$this->transHandle = $transHandle;
        $this->attribute = $attribute;
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
	
	
	// do trans;
	public function translate($value){
        return  call_user_func_array($this->transHandle, array($value,$this->getCriteria(),$this->attribute));
	}

}