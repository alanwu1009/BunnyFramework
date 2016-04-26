<?php
namespace my\bq\mdbao;
use my\bq\criterion\CacheManager;
use my\bq\common\Log;
use \MongoLog;
use \Mongo;


/**
 * ID 生成器;
 */
class IdGenerator{

    const  COLLECTION_NAME = "id_generator";
    private static $mongoDatabase = null;


    /**
     * 返回集合 int 类型的自增ID;
     * @static
     * @param String $collection 集合名称 或者 实现 EntityTemplate 的 实体对象;
     * @return int;
     * @throws MongoConnectionException;
     */
    public static function nextIncId($collection){
        if(self::$mongoDatabase == null){
            $mongMgr = null;
            try{
                $mongMgr = MongoManager::getInstance();
                self::$mongoDatabase = $mongMgr->getDatabase(null,true);
            }catch (MongoConnectionException $e){
                throw $e;
            }
        }
        $tableName = null;
        if(is_string($collection)){
            $tableName = $collection;
        }
        if(is_object($collection)){
             $config = $collection->getConfig();
            $tableName = $config['name'];
        }
        if($tableName == null){
            throw new \Exception("无法正常得到指定集合名称");
        }

        $next_id = self::genId($tableName);
        if(!$next_id){
           $next_id =  self::genId($tableName); //确保第一次生产ID从 1 开始;
        }

        return $next_id;

    }


    /**
     * 生成MongodvId mongoId;
     * @static
     * @return mixed
     * @throws
     */
    public static function nextMongoId($id = null){
        return new \MongoId($id);
    }


    private static function genId($tableName){
        $cour =  self::$mongoDatabase->command(array( 'findandmodify' => COLLECTION_NAME,
            'query'=> array('name'=>$tableName),
            'limit'=> 1,
            'update'=> array('$inc' => array('next_id' => 1)),'upsert'=>true));
        return $cour['value']['next_id'];

    }




	

}