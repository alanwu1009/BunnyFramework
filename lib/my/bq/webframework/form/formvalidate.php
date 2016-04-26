<?php
namespace my\bq\webframework\form;

//表单验证器.
interface FormValidate{
	
	//对表单属性值进行验证;
	public function validate($validator);

}
