<?php

namespace my\bq\entity;

use my\bq\criterion\Projections;

use my\bq\criterion\Limit;
use my\bq\criterion\CriteriaImpl;
use my\bq\criterion\MongoCriteriaImpl;
use my\bq\webframework\form\ValidateException;
use my\bq\webframework\form\Validator;

/**
 * 该类提供一个抽象方法，用于获取实体对应的基本配置及特殊的魔术方法,用于实现关联表的延迟加载.
 */
abstract class EntityTemplate
{
    protected $config;
    protected $relationPro = array();
    protected $criteria;
    protected $fileds = array("*");
    protected $set = array();
    protected $limit = null;
    protected $dataPager;
    protected $errors = array();
    public $hasError = false;

    protected $runValidation = false;

    #++++++++++++++++++++
    private $exceptions = array();

    private $isNewRecord = false;

    public function setIsNewRecord($boolean){
        $this->isNewRecord = $boolean;
    }
    public function checkIsNewRecord(){
        return $this->isNewRecord;
    }

    protected $validateRules = array();

    public function __construct()
    {
        if (!$this->config) {
            $this->config(); //init config
        }


        if (isset($this->config['relations'])) {
            $relations = $this->config['relations'];

            if (is_array($relations)) {
                $this->relationPro = array();
                foreach ($relations as $relation) {

                    $this->relationPro[$relation['name']] = $relation;
                }

            }
        }
    }

    /**
     * return array[];
     * config[] = array();
     * config['name'] = '数据库名称';
     * config['alias'] = '数据库别名';
     * config['columns'] = array('filed1','filed2','filed3'...);
     * config['relations'][0] = array('relation'=>'one-to-many','class'=>EntityClass,'name'=>'object','key'=>'filed1','column'=>'column1','lazy'=>true,'criteria'=>null);
     * config['relations'][1] = array('relation'=>'one-to-one','class'=>EntityClassList,'name'=>'object','key'=>'filed1','column'=>'column1','lazy'=>false);
     * .....
     * 注意, 除关系映射外其余配置必须被指定.
     */
    abstract function config();

    protected function output($var)
    {

        if (key_exists($var, $this->relationPro) && $this->criteria != null) {
            $rs = $this->criteria->fetchRelationData($this, $this->relationPro[$var]);
            $this->$var = $rs;
            return $rs;
        } else {
            if (isset($this->set[$var])) {
                return $this->set[$var];
            }
            return;
        }
    }

    //将数据解析并填充实体;
    public function load($data)
    {
        if (!$data)
            return;
        $config = $this->getConfig();
        $columns = $config['columns'];

        $this->validateRules = array_reverse($this->validateRules, true);
        if (is_array($this->validateRules)) {
            foreach ($this->validateRules as $column => $rule) {
                $kIdx = array_search($column, $columns);
                if ($kIdx !== false) {
                    unset($columns[$kIdx]);
                    array_unshift($columns, $column);
                }
            }
        }

        $exceptions = array();
        foreach ($columns as $column) {
            //使用验证器;
            if (isset($this->validateRules[$column])) {
                $rulesColl = $this->validateRules[$column];
                if (is_array($rulesColl)) {
                    foreach ($rulesColl as $k=> $rules) {
                        try {
                            $rules->validate($data[$column]);
                        } catch (ValidateException $ve) {
                            $ve->setMessage("[$column]" . $ve->getMessage());
                            array_push($this->exceptions, $ve);
                            unset($this->validateRules[$column]);
                            $this->load($data);
                        }
                    }
                }
            }

            if($data[$column]!=='')
                $this->$column = $data[$column];
        }

        if($this->exceptions!=null && is_array($this->exceptions)){
            $exceptionMessage = "";
            foreach ($this->exceptions as $exception) {
                $exceptionMessage .= $exception->getMessage() . "<br/>";
            }
            throw new ValidateException(ValidateException::PARAMS_MISSING_EXCEPTION,$exceptionMessage);
        }

    }

    //将数据数据解析并填充为实体;
    protected function loadPropertyValues($values)
    {
        self::__construct(); //执行当前够造函数;
        if (!$values) {
            return;
        }
        foreach ($values as $k => $value) {
            $this->$k = $value;
        }
    }


    //将数据解析并填充实体;
    protected function loadValue($proptery, $value)
    {
        //TODO这里可以设置一系列过滤器;

        //使用验证器;
        if (isset($this->validateRules[$proptery])) {
            $rulesColl = $this->validateRules[$proptery];
            if (is_array($rulesColl)) {
                foreach ($rulesColl as $rules) {
                    try {
                        $rules->validate($value);
                    } catch (ValidateException $e) {
                        throw $e;
                    }
                }
            }
        }

        return $value;
    }


    public function fullSet($item)
    {
        if (is_array($item)) {
            $this->set = array_merge($this->set, $item);
        }
    }

    /**
     * 添加属性验证规则;
     * $property 实体属性名
     * $validateRule 验证规则 可以为单个validate 或 validate 集合
     */
    public function addValidateRule($property, $validateRule)
    {

        if (!is_array($validateRule)) {
            $validateRule = array($validateRule);
        }
        foreach ($validateRule as $rule) {
            if (isset($this->validateRules[$property])) {
                array_push($this->validateRules[$property], $rule);
            } else {
                $this->validateRules[$property] = array($rule);
            }
        }
    }

    /**
     * 设置是否需要验证
     * @param boolean $run
     */
    public function setRunValidation($run){

    }

    public function getConfig()
    {
        $this->__construct();
        return $this->config;
    }

    public function setConfig($conf)
    {
        $this->config = $conf;
        $this->__construct();
    }

    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getRelationPro()
    {
        return $this->relationPro;
    }

    public function setFileds($fileds, $relationEntiyName = null)
    {

        if ($relationEntiyName) {
            $cfg = $this->getConfig();

            foreach ($cfg['relations'] as $k => $relation) {
                if ($relation['name'] == $relationEntiyName) {
                    $crit = null;
                    if (isset($cfg['relations'][$k]['criteria'])) {
                        $crit = $cfg['relations'][$k]['criteria'];
                    } else {
                        if ('my\bq\criterion\MongoCriteriaImpl' == get_class($this->criteria)) {
                            $crit = new MongoCriteriaImpl(new $relation['class']);
                        } else {
                            $crit = new CriteriaImpl(new $relation['class']);
                        }
                    }
                    if (!is_array($fileds)) {
                        $fileds = explode(",", $fileds);
                    }
                    $crit->setFileds($fileds);
                    $cfg['relations'][$k]['criteria'] = $crit;
                    break;
                }
            }
            $this->setConfig($cfg);
        } else {
            $this->fileds = $fileds;
        }

    }

    public function getFileds()
    {
        return $this->fileds;
    }


    public function setLimit($firstResult, $size, $relationEntiyName = null, $countAll = false)
    {
        if ($relationEntiyName) {
            $cfg = $this->getConfig();
            foreach ($cfg['relations'] as $k => $relation) {
                if ($relation['name'] == $relationEntiyName) {
                    $crit = null;
                    if (isset($cfg['relations'][$k]['criteria'])) {
                        $crit = $cfg['relations'][$k]['criteria'];
                    } else {
                        if ('my\bq\criterion\MongoCriteriaImpl' == get_class($this->criteria)) {
                            $crit = new MongoCriteriaImpl(new $relation['class']);
                        } else {
                            $crit = new CriteriaImpl(new $relation['class']);
                        }
                    }

                    $crit->setFirstResult($firstResult);
                    $crit->setFetchSize($size);
                    if ($countAll) {
                        $crit->addProjection(Projections::rowCount('total'));
                    }
                    $cfg['relations'][$k]['criteria'] = $crit;
                    break;
                }
            }
            $this->setConfig($cfg);
        }
    }

    /**
     * 设定分页器;
     * @param DataPager $dataPager 分页器
     * @param String $relationEntiyName //指定关联对象名称;
     */
    public function setDataPager($dataPager, $relationEntiyName = null)
    {

        if ($relationEntiyName) {
            $cfg = $this->getConfig();
            foreach ($cfg['relations'] as $k => $relation) {
                if ($relation['name'] == $relationEntiyName) {
                    $crit = null;
                    if (isset($cfg['relations'][$k]['criteria'])) {
                        $crit = $cfg['relations'][$k]['criteria'];
                    } else {
                        if ('my\bq\criterion\MongoCriteriaImpl' == get_class($this->criteria)) {
                            $crit = new MongoCriteriaImpl(new $relation['class']);
                        } else {
                            $crit = new CriteriaImpl(new $relation['class']);
                        }
                    }
                    $crit->setDataPager($dataPager); //设定分页器
                    $crit->addProjection(Projections::rowCount('total'));

                    $cfg['relations'][$k]['criteria'] = $crit;
                    break;
                }
            }
            $this->setConfig($cfg);
        }else{
            $this->dataPager = $dataPager;
        }

    }

    /**
     * 获取当前查询实体使用的分页器;
     */
    public function getDataPager(){
        return $this->dataPager;
    }

    /**
     * 获取实体对应的表名;
     * @return String
     */
    public function getTableName()
    {
        return $this->config['name'];
    }

    /**
     *  将除关联对象之外的属性转换为关联组数组;
     */
    public function getColumnValToArray($loadRelation = false,$deep = 1)
    {
        $arr = array();

        $columns = $this->config['columns'];
        foreach ($columns as $column) {
            $arr[$column] = $this->$column;
        }

        if($loadRelation && $deep > 0){
            $relations = $this->config['relations'];
            if(!empty($relations)){
                foreach($relations as $relation){
                    $relationObj = $this->$relation['name'];
                    if(!empty($relationObj)){
                        $arr[$relation['name']] = $relationObj->getColumnValToArray($loadRelation,$deep-1);
                    }
                }
            }
        }

        $arr = array_merge($arr,$this->set);
        return $arr;
    }

    /**
     *  将除关联对象之外的属性转换为关联组数组;
     */
    public function getFieldsValToArray()
    {

       if($this->criteria){
           $fields = $this->getFileds();
           if($fields && $fields[0] == "*"){
               return $this->getColumnValToArray();
           }
           $arr = array();
           foreach ($fields as $field) {
               if(is_object($field)) continue;
               $arr[$field] = $this->$field;
           }
           $arr = array_merge($arr,$this->set);
           return $arr;
       }else{
           throw new \Exception("no session criteria");
       }
    }

    public function validate($on = null){
        $rules = $this->config['rules'];
        if(!empty($rules)){
            foreach($rules as $ruleItem){

                $columns = explode(",",$ruleItem['columns']);
                $rule = $ruleItem['rule'];
                $cOn =  $ruleItem['on'];

                if(empty($cOn) || $on == $cOn){

                    if(!empty($columns)){

                        switch($rule){
                            case "required":
                                foreach($columns as $column){
                                    try{
                                        Validator::checkNull()->validate($this->$column);
                                    }catch (ValidateException $e){
                                        if(empty($this->errors[$column])){
                                            $this->errors[$column] = $e;

                                        }
                                    }
                                }

                                break;
                            case "length":
                                foreach($columns as $column){
                                    try{
                                        Validator::checkLength($this->$column,$ruleItem['max'],$ruleItem['min']);
                                    }catch (ValidateException $e){
                                        if(empty($this->errors[$column])){
                                            $this->errors[$column] = $e;
                                        }
                                    }
                                }

                                break;
                            case "email":
                                foreach($columns as $column){
                                    try{
                                        Validator::checkEmail()->validate($this->$column);
                                    }catch (ValidateException $e){
                                        if(empty($this->errors[$column])){
                                            $this->errors[$column] = $e;
                                        }
                                    }
                                }

                                break;
                            case "identityCode":

                                foreach($columns as $column){
                                    try{
                                        Validator::checkIdentityCard($this->$column);
                                    }catch (ValidateException $e){
                                        if(empty($this->errors[$column])){
                                            $this->errors[$column] = $e;
                                        }
                                    }
                                }

                                break;

                            case "mobilePhone":

                                foreach($columns as $column){
                                    try{
                                        Validator::checkMobilePhone()->validate($this->$column);
                                    }catch (ValidateException $e){
                                        if(empty($this->errors[$column])){
                                            $this->errors[$column] = $e;
                                        }
                                    }
                                }
                                break;
                            default :
                                if(method_exists($this,$rule)){
                                    $this->$rule();
                                }
                                break;
                        }
                    }

                }

            }
        }


        if(!empty($this->errors)){
            $this->hasError = true;
            return false;
        }else{
            $this->hasError = false;
        }
        return true;

    }

    public function getErrors(){
        return $this->errors;
    }

    public function getErrorsToArray(){
        $errors = [];
        if($this->getErrors()){
            foreach($this->getErrors() as $attr => $error){
                if(is_object($error)){
                    $message = $error->getMessage();
                }else{
                    $message = $error;
                }
                $errors[trim($attr)] = $message;
            }
        }
        return $errors;
    }

    public function getError($attr){
        return $this->errors[$attr];
    }

    public function checkError($attr){
        Validator::checkError($this,$attr);
    }

    public function setError($attr,$message){
        $this->errors[$attr] = $message;
    }

}