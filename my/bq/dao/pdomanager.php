<?php
namespace my\bq\dao;
use my\bq\criterion\CacheManager;
use dao\OrderDao;
use my\bq\common\Log;



class PdoManager{

    private $pdoMaster = null;
    private $pdoSlave = array();
    private static $selfInstance = null;

    function __construct(){

        $masterCfg = $_SERVER["mdb"]; //master
        if(is_array($masterCfg)){
            $this->pdoMaster = new PDOext($masterCfg['dsn'], $masterCfg['user'], $masterCfg['password']);
        }
        $slaveCfg = $_SERVER["mdb"]; //slave

        if(is_array($slaveCfg)){
            if(isset($slaveCfg['dsn'])){
                array_push($this->pdoSlave, new PDOext($slaveCfg['dsn'], $slaveCfg['user'], $slaveCfg['password']));
            }else{
                for($i=0; $i<3;$i++){
                    array_push($this->pdoSlave, new PDOext($slaveCfg['dsn'], $slaveCfg['user'], $slaveCfg['password']));
                }
            }

        }



    }

    public static function getInstance(){
        if(self::$selfInstance == null){
            self::$selfInstance = new self();
        }
        return self::$selfInstance;
    }


    /**
	 * 简单定制一个规则，当传入的实体被用于更新或删除过,为了保险起见，直接返回pdoMaster;
	 * 如果该实体未被用于删除或更新，则在pdoSlave队列中切出一个PDO并返回.切出的PDO放至队列的末尾。
	 */
	public function getPdo($entity=null,$master = false){
		
			$cache = CacheManager::getInstance();
			$entiList = $cache->get("inserted_or_deleted_or_updated_list");
			$class = @get_class($entity);
			if($master || (is_array($entiList) && in_array($class, $entiList))){
				return $this->pdoMaster;
			}
			sort($this->pdoSlave);
			$pdo = @array_shift($this->pdoSlave);
			array_push($this->pdoSlave, $pdo);
			return $pdo; 
	}
	
}