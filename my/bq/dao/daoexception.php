<?php

namespace my\bq\dao;

/**
 * 普通认证异常类定义;
 * Alan 20120912 
 */

use \Exception;

class DAOException extends Exception{
	
	const OAUTH_FAIL_EXCEPTION = 300; const OAUTH_FAIL_EXCEPTION_MESSAGE = "身份验证失败异常";
	const RUNTIME_EXCEPTION = 400;  const RUNTIME_EXCEPTION_MESSAGE = "API服务程序运行时异常";
	const PARAMS_MISSING_EXCEPTION = 401; const PARAMS_MISSING_EXCEPTION_MESSAGE = "参数缺失丢失异常";
	const PARAMS_CHECKING_EXCEPTION = 402; const PARAMS_CHECKING_EXCEPTION_MESSAGE = "参数校验异常";
	
	const PHONE_REGISTER_AUTH_CODE_INVALID = 10; const PHONE_REGISTER_AUTH_CODE_INVALID_MESSAGE = "验证码校验失败";
	const PHONE_REGISTER_MOBILE_BLOCKED = 11; const PHONE_REGISTER_MOBILE_BLOCKED_MESSAGE = "用户手机号已被列入黑名单";
	
	const REGISTER_USER_NAME_INVALID = -1; const REGISTER_USER_NAME_INVALID_MESSAGE = "用户名无效";
	const REGISTER_USER_NAME_BLOCKED = -2; const REGISTER_USER_NAME_BLOCKED_MESSAGE = "用户名称已被列入黑名单";
	const REGISTER_USER_NAME_EXISTS = -3;  const REGISTER_USER_NAME_EXISTS_MESSAGE = "用户名已经存在";
	const REGISTER_USER_EMAIL_ERROR = -4; const REGISTER_USER_EMAIL_ERROR_MESSAGE = "email有错误";
	const REGISTER_USER_EMAIL_BLOCKED = -5; const REGISTER_USER_EMAIL_BLOCKED_MESSAGE = "邮箱被列入黑名单";
	const REGISTER_USER_EMAIL_EXISTS = - 6; const REGISTER_USER_EMAIL_EXISTS_MESSAGE = "邮箱已经被注册";
	
	
	public function __construct ($code = null,$message = null) {
		$this->code = $code;
		$this->message = $message;
	}
	 
	
}
