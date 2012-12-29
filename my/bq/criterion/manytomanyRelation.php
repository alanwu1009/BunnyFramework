<?php
namespace my\bq\criterion;
class manyToManyRelation extends Relation {

	private $lcrit;
	private $lp;
	private $rcrit;
	private $rp;
	private $rlp;
	private $rrp;

	/**
	 * @param Criteria $lcrit 左侧关联的数据表
	 * @param String $lp 左侧关联的字段
	 * @param Criteria $rcrit 右侧关联的数据表
	 * @param Property $rp 右关联表中的字段.
	 @param $rrp 关联表中与左表匹配依赖的字段.
	 @param $rrp 关联表中与右表匹配关联的字段.
	 */
	public function __construct($lcrit,$lp,$rcrit,$rp,$rlp,$rrp){
		$this->lcrit = $lcrit;
		$this->lp = $lp;
		$this->rcrit = $rcrit;
		$this->rp = $rp;
		$this->rlp = $rlp;
		$this->rrp = $rrp;
	}


	/**
	 * 根据关联关系条件获取数据
	 * @param Pdo connection $pdo
	 * @param Criteria $crit;.
	 */
	public function fetchData($data,$pdo){
		$selectFiled = "";
		$rrs = array(); $lrs = array();
		if($data[$this->lp]){
			$filed = $this->lcrit->getFileds();
			if(is_object($filed)){
				$selectFiled = ($filed->getPropertyName().' as '.$filed->getAlias());
			}elseif(is_array($filed)){
				$selectFiled = implode(",", $filed);
			}else{
				$selectFiled = $filed;
			}
			
			$sql = "select ".$selectFiled." from ".$this->lcrit->getTable()." where ".$this->rlp->getPropertyName();
			
			//cache
			$cache = CacheManager::getInstance();
			if($rdata = $cache->get($sql." = ".$data[$this->lp])){
				$lrs = $rdata; 
			}else{
				//write sql;
				if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql." = ".$data[$this->lp]);
				$lrs = $pdo->getRows($sql." = ?",array($data[$this->lp]));
				
				//TRANS
				$transData = $cache->get('trans_data');
				if($lrs && $transData){
					foreach($transData as $dataTranslater){
						if($dataTranslater->getCriteria() === $this->lcrit){
							$property = $dataTranslater->getProperty();
							foreach ($lrs as $k => $item){
								$value = $item[$property->getPropertyName()];
								if($value){
									$item[$property->getAlias()] = $dataTranslater->translate($value);
									$lrs[$k] = $item;
								}
							}
						}
					}
				}				
				
				$cache->set($sql." = ".$data[$this->lp],$lrs);	
			}
				
		}

		if($data[$this->rp]){
			$filed = $this->rcrit->getFileds();
			if(is_object($filed)){
				$selectFiled = ($filed->getPropertyName().' as '.$filed->getAlias());
			}elseif(is_array($filed)){
				$selectFiled = implode(",", $filed);
			}else{
				$selectFiled = $filed;
			}
			$sql = "select ".$selectFiled." from ".$this->rcrit->getTable()." where ".$this->rrp->getPropertyName();
			
			//cache
			$cache = CacheManager::getInstance();
			if($rdata = $cache->get($sql." = ".$data[$this->rp])){
				$rrs = $rdata; 
			}else{
				//write sql;
				if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql." = ".$data[$this->rp]);
				$rrs = $pdo->getRows($sql." = ?",array($data[$this->rp]));
				
				//TRANS
				$transData = $cache->get('trans_data');
				if($rrs && $transData){
					foreach($transData as $dataTranslater){
						if($dataTranslater->getCriteria() === $this->rcrit){
							$property = $dataTranslater->getProperty();
							foreach ($rrs as $k => $item){
								$value = $item[$property->getPropertyName()];
								if($value){
									$item[$property->getAlias()] = $dataTranslater->translate($value);
									$rrs[$k] = $item;
								}
							}
						}
					}
				}
				
				$cache->set($sql." = ".$data[$this->rp],$rrs);	
			}
		}

		if($lrs || $rrs){
			return array("relation_data"=>array($this->rlp->getAlias()=>$lrs,$this->rrp->getAlias()=>$rrs));
		}
		return null;

	}

}
