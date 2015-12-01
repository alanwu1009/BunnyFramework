<?php
namespace my\bq\webframework\form;

use my\bq\webframework\form\DefaultValidateRule;

//表单验证器.
class Validator{
	
	/**
	 * 检测该值是否为合法email;
	 */
	public static function checkEmail($message = null){
		return new DefaultValidateRule(DefaultValidateRule::CHECK_EMAIL,$message);
	}
	/**
	 * 是否为一个有效数字;
	 */
	public static function checkNumber($message = null){
		return new DefaultValidateRule(DefaultValidateRule::CHECK_NUMBER,$message);
	}
	/**
	 * 是否为一个有效的手机号
	 */
	public static function  checkMobilePhone($message = null){
		return new DefaultValidateRule(DefaultValidateRule::CHECK_MOBILE_PHONE,$message);
	}
	
	/**
	 * 是否为空值; 
	 */
	public static function checkNull($message = null){
		return new DefaultValidateRule(DefaultValidateRule::CHECK_NULL,$message);
	}


    public static function checkLength($value,$max,$min,$message = null){
        $validateRule = new DefaultValidateRule(null,$message);
        $validateRule->checkLength($value,$max,$min);
    }

    public static function checkIdentityCard($value,$message = null){
        $validateRule = new DefaultValidateRule(null,$message);
        $validateRule->checkIdentityCard($value);
    }

    public static function checkError($entity,$attr){

        if(!empty($entity)){
            $exception = $entity->getError($attr);
            if(!empty($exception)){
                if(is_object($exception)){
                    $message = $exception->getMessage();
                    $field = trim(substr($message,0,strpos($message," ")),'\'\"');
                }else{
                    $message = $exception;
                }
                $config = $entity->getConfig();
                $fieldsDescription = $config['fields_description'];
                if($des = $fieldsDescription[$field]){
                    $message = str_replace($field,$des,$message);
                }

                echo "<div class='errorMessage'> <span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span> ".$message."</div>";
            }
        }

    }

}
