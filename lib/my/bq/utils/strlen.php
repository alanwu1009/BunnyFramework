<?php
namespace my\bq\utils;

 class StrLen{
     private $str='';
     function __construct($str=''){
         $this->str=$str;
     }
     /**
      * 计算字符串长度
      * @param $str
      * @param string $charset
      * @return int
      */
     public function _strlen($str,$charset='UTF8'){
         if($charset!='UTF8') {
             return 'not support other code';
         }
         $num = strlen($str);
         if(empty($num)){
             return 0;
         }
         //系统自带
         if(function_exists('mb_strlen')){
             $mb=mb_strlen($str,'UTF8');
             $len=strlen($str);
             return ceil($mb-($mb-($len-$mb)/2)/2);
         }
         $cnNum = 0;
         for($i=0;$i<$num;$i++){
             if(ord(substr($str,$i+1,1))>0xa0){
                 $cnNum++;
                 $i++;
             }else{
                 $cnNum++;
             }
         }
         return ceil($cnNum);
     }
 }
 ?>
