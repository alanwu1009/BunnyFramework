<?php
namespace my\bq\criterion;
class SimpleRelation extends Relation {

	const ONE_TO_ONE = 1;
	const ONE_TO_MANY = 2;
	private $crit;
	private $lp;
	private $rp;
	private $r;

	/**
	 * Criteria $crit 关联的数据表
	 * @param String $lp 依据字段名
	 * @param Property $rp 关联表中的字段.
	 * @param int $r 关联关系.
	 */
	public function __construct($crit,$lp,$rp,$r){
		$this->crit = $crit;
		$this->lp = $lp;
		$this->rp = $rp;
		$this->r = $r;
	}


	/**
	 * 根据关联关系条件获取数据
	 * @param Pdo connection $pdo
	 * @param Criteria $crit;.
	 */
	public function fetchData($data,$pdo){
		$selectFiled = "";
		$rs = array();
		if($data[$this->lp]){
			$filed = $this->crit->getFileds();
			if(is_object($filed)){
				$selectFiled = ($filed->getPropertyName().' as '.$filed->getAlias());
			}elseif(is_array($filed)){
				$selectFiled = implode(",", $filed);
			}else{
				$selectFiled = $filed;
			}
			$sql = "select ".$selectFiled." from ".$this->crit->getTable()." where ".$this->rp->getPropertyName();
			
			//write sql;
						//cache
			$cache = CacheManager::getInstance();
			/*
			if($data = $cache->get($sql." = ".$data[$this->lp])){
				$rs = $data; 
			}else{
				if(Configuration::SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql." = ?");
				$rs = $pdo->getRows($sql." = ?",array($data[$this->lp]));
				$cache->set($sql." = ".$data[$this->lp],$rs);
			}
			*/
			//修复查询无结果bug @xwarrior at 2012.3.24
			$rs = $cache->get($sql." = ".$data[$this->lp]);
			if( !$rs ){
				if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql." = ".$data[$this->lp]);
			
				$rs = $pdo->getRows($sql." = ?",array($data[$this->lp]));
				
				//TRANS
				$transData = $cache->get('trans_data');
				if($rs && $transData){
					foreach($transData as $dataTranslater){
						if($dataTranslater->getCriteria() === $this->crit){
							$property = $dataTranslater->getProperty();
							foreach ($rs as $k => $item){
								$value = $item[$property->getPropertyName()];
								if($value){
									$item[$property->getAlias()] = $dataTranslater->translate($value);
									$rs[$k] = $item; 
								}
							}
						}
					}
				}
				
				$cache->set($sql." = ".$data[$this->lp],$rs);
			}
			
			if(!$rs) return null;
			switch ($this->r){
				case self::ONE_TO_MANY:
					return array($this->rp->getAlias()=>$rs); 
				case self::ONE_TO_ONE:
					return array($this->rp->getAlias()=>current($rs));
			}
		}
	}

}
