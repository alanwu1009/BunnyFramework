<?php

namespace my\bq\dao;

/**
 * 普通认证异常类定义;
 * Alan 20120912 
 */

use \Exception;

class DAOException extends Exception{

    const DB_EXEC_EXCEPTION = 500;  const DB_EXEC_EXCEPTION_MESSAGE = "数据库处理异常";

	public function __construct ($code = null,$message = null) {
		$this->code = $code;
		$this->message = $message;
	}
	 
	
}
