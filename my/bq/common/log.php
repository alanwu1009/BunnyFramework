<?php
namespace my\bq\common;

abstract class Log{
	const ERROR = "[ERROR]";
	const WARNING = "[WARNING]";
	const NOTICE = "[NOTICE]";
	static $opHandle = null;

	static function writeMsg($level,$msg){
        if(Log::$opHandle != null && is_object(Log::$opHandle)){
            try{
                self::$opHandle->saveLog($level,$msg);
            }catch (\Exception $e){}
        }
        if(Configuration::$DEBUG){
            echo "\r\n".'<span style="display:none">'.$level.': '.date('Y-m-d H:m:s',time()).' '.$msg.'</span>';
        }

	}

    abstract function saveLog($level,$msg);

}
