<?php
namespace my\bq\common;

class Log{
	const ERROR = "\r\n<BR>[ERROR]";
	const WARNING = "\r\n<BR>[WARNING]";
	const NOTICE = "\r\n<BR>[NOTICE]";
	
	static function writeMsg($level,$msg){
		echo '<span style="display:none">'.$level.': '.date('Y-m-d H:m:s',time()).' '.$msg.'</span>';
	}

}
