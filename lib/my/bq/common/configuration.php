<?php
namespace my\bq\common;
class Configuration{
	public static $SHOW_SQL = false;
	public static $SHOW_CACHE = false;
	public static $DEBUG = false;
    public static $SHOW_CORE_EXCEPTION = false;
    public static $SHOW_MONGO_QUERY = false;

    public static $CACHE_QUERY_RESULT = false; //如果设置为true，需要配置缓存实例$_SERVICE;
    public static $CACHE_INSTANCE = null; //如果 $CACHE_QUERY_RESULT 设置为true, 系统会尝试使用该实例缓存数据;
	private $log4p = array();
	
	############################
	public static function initCfg(){
		
	}
	
	
}