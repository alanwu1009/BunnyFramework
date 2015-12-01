<?php
namespace my\bq\mdbao;
use my\bq\criterion\CacheManager;
use my\bq\common\Log;
use \MongoLog;
use \Mongo;


class MongoManager{
	
    private $mongoConnect = null;
	private static $selfInstance = null;
	
	function __construct(){
        //初始化数据库连接
       // MongoLog::setLevel(MongoLog::WARNING); // all log levels
        //设置连接池大小（与php进程数一致) DEPRECATED in driver: 1.2.11
        //Mongo::setPoolSize(256);
        try{
            $this->mongoConnect = new Mongo("mongodb://".$_SERVER['mongodb']['host'].":".$_SERVER['mongodb']['port']);
        }catch (MongoConnectionException $e){
            throw $e;
        }
	}
	
	public static function getInstance(){
		if(self::$selfInstance == null){
			self::$selfInstance = new self(); 
		}
		return self::$selfInstance;
	}

	/**
     * get database instance
     * @param null $entity
     * @param bool $master
     * @return null
     */
	public function getDatabase($entity=null,$master = false){

        $mongodb = $this->mongoConnect->selectDB($_SERVER['mongodb']['database']);
		
        $cache = CacheManager::getInstance();
        $entiList = $cache->get("inserted_or_deleted_or_updated_list");

        $class = @get_class($entity);
        //启用从库读
        if($master || (is_array($entiList) && in_array($class, $entiList))){
            $this->mongoConnect->setSlaveOkay(true);
        }else{
            $this->mongoConnect->setSlaveOkay(false);
        }
        return $mongodb;
	}

    /**
     * 获取 MongoGridFS 对象;
     * @param null $entity
     * @param bool $masger 启用主从;
     * @return \MongoGridFS
     */
    public function getGridFS($entity = null, $masger = false){
        $mongodb = $this->mongoConnect->selectDB($_SERVER['mongodb']['gridfs_db']);

        $cache = CacheManager::getInstance();
        $entiList = $cache->get("inserted_or_deleted_or_updated_list");

        $class = @get_class($entity);
        //启用从库读
        if($master || (is_array($entiList) && in_array($class, $entiList))){
            $this->mongoConnect->setSlaveOkay(true);
        }else{
            $this->mongoConnect->setSlaveOkay(false);
        }
        $grid = $mongodb->getGridFS();
        return $grid;
    }

	
}