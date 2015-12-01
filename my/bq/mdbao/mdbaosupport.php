<?php

namespace my\bq\mdbao;
use my\bq\common\Log;
use my\bq\criterion\CacheManager;
use my\bq\common\Configuration;
use \Exception;
use \my\bq\mdbao\MongoManager;

class MdbaoSupport{


    /**
     * 支持 mongodb command
     * @static
     * @param $command
     * @return mixed
     */
    public static function runCommand($command){
        $mongoManager = MongoManager::getInstance();
        $mongoDatabase = $mongoManager->getDatabase(null,true);

        return $mongoDatabase->command($command);
    }


	//persistant object
	public static function saveEntity($entity){

		if($entity){

			$entityCfg = $entity->getConfig();
			$vars = array();
			foreach ($entityCfg['columns'] as $key){
				$vars[$key] =  $entity->$key;
			}

            $mongoTable = null;
            $keyId = "";
            try{
                $mongoManager = MongoManager::getInstance();
                $mongoDatabase = $mongoManager->getDatabase($entity,true);
                $mongoTable = $mongoDatabase->selectCollection($entityCfg['name']);
                if($vars["_id"] == null){
                    $keyId =  $vars["_id"] = \my\bq\mdbao\IdGenerator::nextMongoId();
                }
            }catch (MongoConnectionException $e){
                throw $e;
            }

            try{
               $mongoTable->insert($vars);
            }catch (MongoCursorException $me){
                throw $me;
            }

			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");

			if(!is_array($entiList)){
                $entiList = array();
            }
			array_push($entiList, @get_class($entity));

			$cache->set("inserted_or_deleted_or_updated_list",$entiList);				
			
			$relationCfg  = $entity->getRelationPro();
			
			foreach ($relationCfg as $var => $conf){
				if(isset($conf['cascade'])){
					$cascade = explode(",", $conf['cascade']);
					if(in_array("save", $cascade)){
						
						$relationObject = $entity->$conf['name'];

						//单个实体
						if(is_object($relationObject)){
							$relationObject->$conf['column'] = $vars[$conf['key']];
							self::saveEntity($relationObject);
						}
						//实体数组
						if(is_array($relationObject)){
							foreach($relationObject as $objectItem){
								if(is_object($objectItem)){
                                    $objectItem->$conf['column'] = $vars[$conf['key']];
                                    if($conf['column'])
									self::saveEntity($objectItem);
								}
							}
						}
					}
				}
			}
			
			return $keyId;
		}
		return false;
	}
	
	
	

	//update
    /**
     * @static
     * @param $entity
     * @param null $where 可以为 restrictions 构建的适应于 MongoDb的标准 Criteria; 或者 mongodb query;
     * @return bool|string
     * @throws MongoConnectionException
     * @throws MongoCursorException;
     */
	public static function updateEntity($entity,$where = null,$upsert = false){
        if($entity){

            $entityCfg = $entity->getConfig();
            $vars = array();
            foreach ($entityCfg['columns'] as $key){
                $vars[$key] =  $entity->$key;
                //为空的数据不再次刷到库中;
                if($vars[$key] === null ){
                   unset($vars[$key]);
                }
            }

            //如果指定where 则使用where条件,未指定则使用id; 更新不可以不指定条件;
            $parseWhere = array();
            if($where != null){
                if(is_object($where) && method_exists($where,'toMongoParam')){
                   $parseWhere = array_merge($parseWhere,$where->toMongoParam($entity->getCriteria()));
                }else{
                    if(is_array($where)){
                        $parseWhere = array_merge($parseWhere,$where);
                    }
                }
            }else{
                $parseWhere = array("_id"=> $entity->_id);
            }

            $mongoTable = null;
            $keyId = "";
            try{
                $mongoManager = MongoManager::getInstance();
                $mongoDatabase = $mongoManager->getDatabase($entity,true);
                $mongoTable = $mongoDatabase->selectCollection($entityCfg['name']);
            }catch (MongoConnectionException $e){
                throw $e;
            }
            try{
                if(isset($vars['_id'])) //mongodb不支持更新_id key
                    unset($vars['_id']);
                if(Configuration::$SHOW_MONGO_QUERY){
                    Log::writeMsg(Log::NOTICE,var_export(array('$set'=>$vars,'criteria'=>$parseWhere),true));
                }
                $mongoTable->update($parseWhere,array('$set'=>$vars),array('upsert'=> $upsert, 'multiple' => true,'safe' => true,'fsync' =>true, 'timeout' => 20000 ));
            }catch (MongoCursorException $me){
                throw $me;
            }

            $cache = CacheManager::getInstance();
            $entiList = $cache->get("inserted_or_deleted_or_updated_list");

            if(!is_array($entiList)){
                $entiList = array();
            }
            array_push($entiList, @get_class($entity));

            $cache->set("inserted_or_deleted_or_updated_list",$entiList);

            $relationCfg  = $entity->getRelationPro();

            foreach ($relationCfg as $var => $conf){

                if(isset($conf['cascade'])){
                    $cascade = explode(",", $conf['cascade']);
                    if(in_array("update", $cascade)){

                        $relationObject = $entity->$conf['name'];

                        //单个实体
                        if(is_object($relationObject)){

                            $relationObject->$conf['column'] = $vars[$conf['key']];
                            self::updateEntity($relationObject);
                        }

                        //实体数组
                        if(is_array($relationObject)){
                            foreach($relationObject as $objectItem){
                                if(is_object($objectItem)){
                                    self::updateEntity($objectItem);
                                }
                            }
                        }
                    }
                }
            }

            return $keyId;
        }
        return false;
	} 	
	
	
	//delete
	public static function deleteEntity($entity,$where=null){
        if($entity){

            $entityCfg = $entity->getConfig();
            $vars = array();
            foreach ($entityCfg['columns'] as $key){
                $vars[$key] =  $entity->$key;
            }

            //如果指定where 则使用where条件,未指定则使用id; 更新不可以不指定条件;
            $parseWhere = array();
            if($where != null){
                if(is_object($where) && method_exists($where,'toMongoParam')){
                    array_push($parseWhere,$where->toMongoParam($entity->getCriteria()));
                }else{
                    if(is_array($where)){
                        $parseWhere = $where;
                    }
                }
            }else{
                $parseWhere = array("_id"=> $entity->_id);
            }

            $mongoTable = null;
            $keyId = "";
            try{
                $mongoManager = MongoManager::getInstance();
                $mongoDatabase = $mongoManager->getDatabase($entity,true);
                $mongoTable = $mongoDatabase->selectCollection($entityCfg['name']);
            }catch (MongoConnectionException $e){
                throw $e;
            }

            try{
               $rs =  $mongoTable->remove($parseWhere,array('safe' => true,'fsync' =>false, 'timeout' => 20000 ));
               $keyId = $rs['n'];
               if($rs['err']!=null){
                   throw new Exception($rs['err'],\my\bq\dao\DAOException::DB_EXEC_EXCEPTION);
               }
            }catch (MongoCursorException $me){
                throw $me;
            }

            $cache = CacheManager::getInstance();
            $entiList = $cache->get("inserted_or_deleted_or_updated_list");

            if(!is_array($entiList)){
                $entiList = array();
            }
            array_push($entiList, @get_class($entity));

            $cache->set("inserted_or_deleted_or_updated_list",$entiList);

            $relationCfg  = $entity->getRelationPro();

            foreach ($relationCfg as $var => $conf){

                if(isset($conf['cascade'])){
                    $cascade = explode(",", $conf['cascade']);
                    if(in_array("update", $cascade)){

                        $relationObject = $entity->$conf['name'];

                        //单个实体
                        if(is_object($relationObject)){

                            $relationObject->$conf['column'] = $vars[$conf['key']];
                            self::deleteEntity($relationObject);
                        }

                        //实体数组
                        if(is_array($relationObject)){
                            foreach($relationObject as $objectItem){
                                if(is_object($objectItem)){
                                    self::deleteEntity($objectItem);
                                }
                            }
                        }
                    }
                }
            }

            return $keyId;
        }
        return false;
	}
		
}