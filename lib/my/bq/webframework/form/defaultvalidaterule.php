<?php
namespace my\bq\webframework\form;

use my\bq\utils\StrLen;
//表单验证器.
class DefaultValidateRule implements FormValidate{
	const CHECK_NUMBER = 0x01; //是否为数字
	const CHECK_MOBILE_PHONE = 0x02; //手机号
	const CHECK_EMAIL = 0x03; //邮箱
	const CHECK_NULL = 0x04; //为空判断
    const CHECK_LENGTH = 0x05; //判断长度

	private $checkingType;
	private $errorMessage;

	public function __construct($checkingType = 0x00,$onCorrectMessage = ""){
		$this->checkingType = $checkingType;
		$this->errorMessage = $onCorrectMessage;
	}

	//对表单属性值进行验证;
	public function validate($value){
		try{
			switch ($this->checkingType){
				case self::CHECK_EMAIL :
					$this->checkEmail($value);
					break;
				case self::CHECK_MOBILE_PHONE :
					$this->checkMobilePhone($value);
					break;
				case self::CHECK_NULL :
					$this->checkNull($value);
					break;
				case self::CHECK_NUMBER :
					$this->checkNumber($value);
					break;
				default:
			}
		}catch(ValidateException $e){
			throw $e;
		}
	}

	//CHECK_EMAIL 0x03
	private function checkEmail($value){
		$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,5}\$/i";
		if (strpos($value, '@') !== false && strpos($value, '.') !== false){
			if (preg_match($chars, $value)){
				return true;
			}
		}

		if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_MISSING_EXCEPTION,$this->errorMessage);

		throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"不是一个合法的邮箱");
	}

	//CHECK_NUMBER 0x01
	private function checkNumber($value){
		if(is_numeric($value)){
			return true;
		}

		if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_MISSING_EXCEPTION,$this->errorMessage);
		throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"不是有效数字");
	}

	//CHECK_MOBILE_PHONE 0x02
	private function checkMobilePhone($value){
		$pattern = '/1[\d]{10}$/';
		if(preg_match($pattern, $value)){
			return true;
		}

		if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,$this->errorMessage);
		throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"不是有效手机号");
	}

	//CHECK_NULL 0x04
	private function checkNull($value){
		if(!empty($value)){
			return true;
		}

		if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_MISSING_EXCEPTION,$this->errorMessage);
		throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"不可为空");
	}

    public function checkLength($value,$max,$min){

        if(!empty($value) && $max > 0 && $min > 0 ){
            $strLen = new StrLen();
            $length = $strLen->_strlen($value);
            if($length >= $min  &&  $length<= $max){
                return true;
            }
        }

        if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,$this->errorMessage);
        throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"长度应在 $min 和 $max 之间");
    }

    public function checkIdentityCard($value){

        if($this->isCreditNo($value)){
            return;
        }else{
            if($this->errorMessage!="") throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,$this->errorMessage);
            throw new ValidateException(ValidateException::PARAMS_CHECKING_EXCEPTION,"无效的证件号码");
        }
    }


    private function isCreditNo($value){
        $vStr = $value;
        $vCity = array(
            '11','12','13','14','15','21','22',
            '23','31','32','33','34','35','36',
            '37','41','42','43','44','45','46',
            '50','51','52','53','54','61','62',
            '63','64','65','71','81','82','91'
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $vStr)) return false;

        if (!in_array(substr($vStr, 0, 2), $vCity)) return false;

        $vStr = preg_replace('/[xX]$/i', 'a', $vStr);
        $vLength = strlen($vStr);

        if ($vLength == 18)
        {
            $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
        } else {
            $vBirthday = '19' . substr($vStr, 6, 2) . '-' . substr($vStr, 8, 2) . '-' . substr($vStr, 10, 2);
        }

        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18)
        {
            $vSum = 0;

            for ($i = 17 ; $i >= 0 ; $i--)
            {
                $vSubStr = substr($vStr, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
            }

            if($vSum % 11 != 1) return false;
        }

        return true;


    }

}
