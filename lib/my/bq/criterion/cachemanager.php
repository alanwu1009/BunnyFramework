<?php
namespace my\bq\criterion;
use my\bq\common\Log;
use my\bq\common\Configuration;

class CacheManager{

	private static $cacheStack = array();
	private static $cache = null;

	public static function  getInstance(){
		if(self::$cache == null){
			self::$cache = new CacheManager();
			return self::$cache;
		}

		return self::$cache;
	}


	public function set($key,$value){
		if(!$key)return;
		self::$cacheStack[md5($key)] = $value;
	}

	public function get($key){
		if(!$key)return;
		if(isset(self::$cacheStack[md5($key)])){
			return self::$cacheStack[md5($key)];
		}

	}
	
	public function clean($filter = null){
		//filter中的数据不需要过滤，为全局缓存
		$filter = array("inserted_or_deleted_or_updated_list");
		foreach (self::$cacheStack as $k => $item){
			if(!in_array($k, $filter))
				unset(self::$cacheStack[$k]);
		}
	}
	
	
	
	

}