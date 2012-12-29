<?php

namespace my\bq\entity;
use my\bq\criterion\Projections;

use my\bq\criterion\Limit;
use my\bq\criterion\CriteriaImpl;
/**
 * 该类提供一个抽象方法，用于获取实体对应的基本配置及特殊的魔术方法,用于实现关联表的延迟加载.
 */
abstract class EntityTemplate{
	protected $config;
	protected $relationPro = array();
	protected $criteria;
	protected $fileds = array("*");
	protected $set = array();
	protected $limit = null;
	public function __construct(){
		if(!$this->config){
			$this->config(); //init config
		}
		if(isset($this->config['relations'])){
			$relations = $this->config['relations'];
			if(is_array($relations)){
				$this->relationPro = array();
				foreach ($relations as $relation){
					$this->relationPro[$relation['name']] = $relation;
				}
			}
		}
	}
	
	/**
	 * return array[];
	 * config[] = array();
	 * config['name'] = '数据库名称';
	 * config['alias'] = '数据库别名';
	 * config['columns'] = array('filed1','filed2','filed3'...);
	 * config['relations'][0] = array('relation'=>'one-to-many','class'=>EntityClass,'name'=>'object','key'=>'filed1','column'=>'column1','lazy'=>true,'criteria'=>null);
	 * config['relations'][1] = array('relation'=>'one-to-one','class'=>EntityClassList,'name'=>'object','key'=>'filed1','column'=>'column1','lazy'=>false);
	 * .....
	 * 注意, 除关系映射外其余配置必须被指定.
	 */
	abstract function config();
	
	protected function output($var){
		if(key_exists($var, $this->relationPro) && $this->criteria != null){
			$rs  = $this->criteria->fetchRelationData($this,$this->relationPro[$var]);
			$this->$var = $rs;
			return $rs; 
		}else{
			if(isset($this->set[$var])){
				return $this->set[$var];
			}
			return;
		}
	}
	
	public function fullSet($item){
		if(is_array($item)){
			$this->set = array_merge($this->set,$item);
		}
	}
	
	
	public function getConfig(){
		$this->__construct();
		return $this->config;
	}
	public function setConfig($conf){
		$this->config = $conf;
		$this->__construct();
	}
	
	public function setCriteria($criteria){
		$this->criteria = $criteria;
	}
	
	public function getCriteria(){
		return $this->criteria; 
	}
	public function getRelationPro(){
		return $this->relationPro;
	}
	
	public function setFileds($fileds,$relationEntiyName = null){
		
		if($relationEntiyName){
			$cfg = $this->getConfig();
			
			foreach ($cfg['relations'] as $k => $relation){
				if($relation['name'] == $relationEntiyName){
					$crit = null;
					if(isset($cfg['relations'][$k]['criteria'])){
						$crit = $cfg['relations'][$k]['criteria'];
					}else{
						$crit = new CriteriaImpl(new $relation['class']);
					}
					
					$crit->setFileds(explode(",", $fileds));
					$cfg['relations'][$k]['criteria'] = $crit;
					break;  
				}
			}
			$this->setConfig($cfg);
		}else{
			$this->fileds = $fileds;
		}
		
	}
	public function getFileds(){
		return $this->fileds; 
	}
	
	
	public function setLimit($firstResult,$size,$relationEntiyName = null,$countAll = false){
		if($relationEntiyName){
			$cfg = $this->getConfig();
			foreach ($cfg['relations'] as $k => $relation){
				if($relation['name'] == $relationEntiyName){
					$crit = null;
					if(isset($cfg['relations'][$k]['criteria'])){
						$crit = $cfg['relations'][$k]['criteria'];
					}else{
						$crit = new CriteriaImpl(new $relation['class']);
					}
					
					$crit->setFirstResult($firstResult);
					$crit->setFetchSize($size);
					if($countAll){
						$crit->addProjection(Projections::rowCount('total'));
					}
					$cfg['relations'][$k]['criteria'] = $crit;
					break;
				}
			}
			$this->setConfig($cfg);
		}
	}
	
	
	
}