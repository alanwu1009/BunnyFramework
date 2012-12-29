<?php

namespace my\bq\dao;
use my\bq\common\Log;
use my\bq\criterion\CacheManager;
use my\bq\common\Configuration;
use \Exception;
class DaoSupport{
	
	//persistant object
	public static function saveEntity($entity){
		if($entity){
			
			$entityCfg = $entity->getConfig();
			$vars = array();
			foreach ($entityCfg['columns'] as $key){
				$vars[$key] =  $entity->$key;
			}
			
			if(isset($vars[$entityCfg['id']]))
				unset($vars[$entityCfg['id']]);
			
			$pdoManager = PdoManager::getInstance();
			$pdo = $pdoManager->getPdo($entity,true);
				
			$id = $pdo->insert($entityCfg['name'], $vars);
			
			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");
			if(!is_array($entiList)) $entiList = array();
			array_push($entiList, @get_class($entity));

			$cache->set("inserted_or_deleted_or_updated_list",$entiList);				
			
			if(Configuration::$SHOW_SQL)
				Log::writeMsg(Log::NOTICE, "{$entityCfg['name']}插入一条数据, ");
			
			if($id === false) throw new Exception("保存[{$entityCfg['name']}]失败");
			
			$relationCfg  = $entity->getRelationPro();
			
			foreach ($relationCfg as $var => $conf){
				if(isset($conf['cascade'])){
					$cascade = explode(",", $conf['cascade']);
					if(in_array("save", $cascade)){
						
						$relationObject = $entity->$conf['name'];
						
						//单个实体
						if(is_object($relationObject)){
							$relationObject->$conf['column'] = $id;
							self::saveEntity($relationObject);
						}
						//实体数组
						if(is_array($relationObject)){
							foreach($relationObject as $objectItem){
								if(is_object($relationObject)){
									$relationObject->$conf['column'] = $id;
									self::saveEntity($relationObject);
								}
							}
						}
					}
				}
			}
			
			return 	$id;
		}
		return false;
	}
	
	
	
	
	//persistant object
	public static function replaceEntity($entity){
		if($entity){
				
			$entityCfg = $entity->getConfig();
			$vars = array();
			foreach ($entityCfg['columns'] as $key){
				$vars[$key] =  $entity->$key;
			}
				
			$pdoManager = PdoManager::getInstance();
			$pdo = $pdoManager->getPdo($entity,true);
				
			$id = $pdo->replace($entityCfg['name'], $vars);
				
			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");
			if(!is_array($entiList)) $entiList = array();
			array_push($entiList, @get_class($entity));
	
			$cache->set("inserted_or_deleted_or_updated_list",$entiList);
				
			if(Configuration::$SHOW_SQL)
				Log::writeMsg(Log::NOTICE, "{$entityCfg['name']}插入一条数据, ");
				
			if($id === false) throw new Exception("保存[{$entityCfg['name']}]失败");
				
			$relationCfg  = $entity->getRelationPro();
				
			foreach ($relationCfg as $var => $conf){
				if(isset($conf['cascade'])){
					$cascade = explode(",", $conf['cascade']);
					if(in_array("save", $cascade)){
	
						$relationObject = $entity->$conf['name'];
	
						//单个实体
						if(is_object($relationObject)){
							$relationObject->$conf['column'] = $id;
							self::saveEntity($relationObject);
						}
						//实体数组
						if(is_array($relationObject)){
							foreach($relationObject as $objectItem){
								if(is_object($relationObject)){
									$relationObject->$conf['column'] = $id;
									self::saveEntity($relationObject);
								}
							}
						}
					}
				}
			}
				
			return 	$id;
		}
		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//update
	public static function updateEntity($entity,$where = null){
		if($entity){
			if(!is_object($entity)) throw Exception("runtime exception");
			$entityCfg = $entity->getConfig();
			$vars = array();
			$sql = "update {$entityCfg['name']} set ";
			$set = "";
			foreach ($entityCfg['columns'] as $key){
				$p = trim($key,"`"); 
				$value = $entity->$p;
				
				if($value){
					if(is_string($value)){
						$value = addslashes($value);
					}
					if($set)$set.=",";
					$set .= $key."='".$value."'";
				}
			}
			
			//如果指定where 则使用where条件,未指定则使用id; 更新不可以不指定条件;
			$parseWhereStr = "";
			if($where != null){
				foreach ($where as $k => $v){
					if($parseWhereStr!="")$parseWhereStr.=" and ";
					$parseWhereStr.=("`".$k."` = ".$v);
				}
			}else{
				$id = explode(",",$entityCfg['id']);
				foreach ($id as $k){
					if($parseWhereStr!="")$parseWhereStr.=" and ";
					$parseWhereStr.=("`".$k."` =".$entity->$k);
				}
			}
			
			$sql.= $set." where ".$parseWhereStr;
			
			$pdoManager = PdoManager::getInstance();
			$pdo = $pdoManager->getPdo($entity,true);  
			$bool = $pdo->exec($sql);
			
			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");
			if(!is_array($entiList)) $entiList = array();
			array_push($entiList, @get_class($entity));
			$cache->set("inserted_or_deleted_or_updated_list",$entiList);
			
			if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE,$sql);
				
			$relationCfg  = $entity->getRelationPro();
			foreach ($relationCfg as $var => $conf){
				if(isset($conf['cascade'])){
					$cascade = explode(",", $conf['cascade']);
					if(in_array("update", $cascade)){
						$relationObject = $entity->$conf['name'];
						if(is_object($relationObject)){
							$relationObject = array($relationObject);
						}
						if(is_array($relationObject)){
							foreach ($relationObject as $object){
								try{
									self::updateEntity($object);
								}catch(Exception $e){
									return $e;
								}
							}
						}
					}
				}
			}
				
			return 	$bool;
		}
		return false;
	} 	
	
	
	//delete
	public static function deleteEntity($entity,$where=null){
		if($entity){
			
			$entityCfg = $entity->getConfig();
			$vars = array();
			foreach ($entityCfg['columns'] as $key){
				$p = trim($key,"`");
				$vars[$key] =  $entity->$p;
			}
			
			
			
			
			//如果指定where 则使用where条件,未指定则使用id; 更新不可以不指定条件;
			$parseWhereStr = "";
			if($where != null){
				foreach ($where as $k => $v){
					if($parseWhereStr!="")$parseWhereStr.=" and ";
					$parseWhereStr.=("`".$k."` =".$v);
				}
			}else{
				$id = explode(",",$entityCfg['id']);
				foreach ($id as $k){
					if($parseWhereStr!="")$parseWhereStr.=" and ";
					$parseWhereStr.=("`".$k."` =".$entity->$k);
				}
			}
				
			$sql = 	"delete from {$entityCfg['name']} where ".$parseWhereStr;
			
			$pdoManager = PdoManager::getInstance();
			$pdo = $pdoManager->getPdo($entity,true);
			$bool = $pdo->exec($sql);
			
			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");
			if(!is_array($entiList)) $entiList = array();
			array_push($entiList, @get_class($entity));
			$cache->set("inserted_or_deleted_or_updated_list",$entiList);
			
			if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql);
	
			$relationCfg  = $entity->getRelationPro();
			foreach ($relationCfg as $var => $conf){
				if(isset($conf['cascade'])){
					$cascade = explode(",", $conf['cascade']);
					if(in_array("delete", $cascade)){
						
						$relationObject = $entity->$conf['name'];
						if(is_object($relationObject)){
							$relationObject = array($relationObject);
						}
						if(is_array($relationObject)){
							foreach ($relationObject as $object){
								try{
									$objConf = $object->getConfig();
									$objConf['id'] = $conf['column'];
									$object->setConfig($objConf);
									$object->$objConf['id'] = $entity->$conf['key'];
									self::deleteEntity($object);
								}catch(Exception $e){
									return $e;
								}
							}
						}
					}
				}
			}
	
			return 	$bool;
		}
		return false;
	}
		
}