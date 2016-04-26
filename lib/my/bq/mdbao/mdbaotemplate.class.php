<?php
    namespace mdbao;

    use \Mongo as Mongo;
    use \MongoCode as MongoCode;
    use \MongoId as MongoId;
    use \MongoLog;
    use \MongoCursorException;
    use \Exception;
    use \Debug;

    class class MdbaoTemplate implements MongoRecord
    {
        public $attributes;
        protected $errors;
        private $new;

        public static $database = null;
        public static $connection = null;
        public static $findTimeout = 20000;
        /**
         * Collection name will be generated automaticaly if setted to null.
         * If overridden in child class, then new collection name uses.
         *
         * @var string
         */
        protected static $collectionName = null;

        /**
         * 定义schema信息，用来完善和约束字段
         * @var Array:
         */
        protected static $schema = null;

        public function __construct($attributes = array(), $new = true)
        {
            $this->new = $new;
            $this->attributes = $attributes;
            $this->errors = array();

            if ($new)
                $this->afterNew();
        }


        /**
         * 设置是否对当胆集合启用从库的查询，在复制集中默认为true,会自动将读操作路由到从库
         * @param unknown_type $ok
         */
        public  function setSlaveOk($ok  = true){
            /*if( $ok ){
                    self::$connection->setReadPreference(Mongo::RP_PRIMARY_PREFERRED,array());
               }else{
                    self::$connection->setReadPreference(Mongo::RP_SECONDARY_PREFERRED,array());
               }
               return true;
               */
            return self::$connection->setSlaveOkay($ok);
        }


        /*
       * 获取是否对当前集合启用从库查询
       */
        public  function getSlaveOkay(){
            return self::$connection->getSlaveOkay();
        }

        public function validate($update=false)
        {
            $this->beforeValidation();
            $retval = $this->isValid($update);
            $this->afterValidation();
            return $retval;
        }

        /**
         * 保存一个对象的变更到数据库中
         * 如果相同_id的对象已经存在则执行更新操作覆盖数据库的记录
         * 如果没有设置 _id，则会插入新记录，并将新插入自动生成的_id保存到当前对象上
         * @param array $options
        @param    $options 选项详情： 是否安全插入 safe:true,false
        是否同步到硬盘 fsync:true,false
        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
        @param insertonly     是否只执行插入操作: insertonly :  true,false  当设置为true时，如果当前数据的_id已经在数据库中，则会抛出异常 (仅当options的safe为true时才会获得异常)
         */
        public function save(array $options = array(),$insert_only = false)
        {


            try{
                if (!$this->validate())
                    return false;

                $begin_microtime = \Debug::getTime();
                $this->beforeSave();

                $collection = self::getCollection();
                if ( $insert_only ){
                    $ret = $collection->insert( $this->attributes,  $options );
                }else{
                    $ret = $collection->save(  $this->attributes,  $options );
                }
                //added by xwarrior to check insert update and logging at 2012/6/7
                if ( is_array($ret)  ){
                    if (isset($ret['err']) && $ret['err'] != null ){
                        \Debug::log( 'Error' , 'insert ' . self::$collectionName . ' fail,mongo error code:'  . $ret['code'] . ' mongo errormsg:' . $reg['errmsg'] );
                        return false;
                    }
                }else{
                    if ( $ret == false){
                        \Debug::log( 'Error' , 'insert doc to ' . self::$collectionName . ' fail!');
                        return false;
                    }
                }
                $this->new = false;
                $this->afterSave();

                //add performence log xwarrior  2012/8/8
                self::log_query('save',$this, $options, $collection, $begin_microtime,$ret);
            }catch(\Exception $err){
                //注意： 到这里通常都是因为传入非UTF8字符造成的！
                if(preg_match('/non-utf8/i',$err->getMessage())){
                    //处理非UTF8字符导致出错
                    foreach($this->attributes as $key=>$val){
                        if(mb_detect_encoding($str) == 'UTF-8')continue;
                        else $this->attributes[$key] = iconv('GBK','UTF-8//IGNORE',$val);
                    }
                    try{
                        $collection->save($this->attributes, $options);
                    }catch(\Exception $err){
                        \Debug::log('Error',$err->getMessage());
                        throw $err;
                        return false;
                    }
                }else{
                    \Debug::log('Error',$err->getMessage());
                    throw $err;
                    return false;
                }
            }
            return true;
        }

        /**
         * 把_id按递增数字存储
         * @param array $options  同save的选项
         *            是否安全插入 safe:true,false
        是否同步到硬盘 fsync:true,false
        超时时间设置timeout: If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response
         * @return 保存成功，返回true
         */
        public function numsave(array $options = array())
        {
            $max_error_limit = 100;

            $options['safe'] = true;

            $maxiddoc	=	self::findAll(	array(),
                array( '_id' ),
                array( 'sort' => array('_id' => -1) ,
                       'limit' => 1)
            );
            if( $maxiddoc && count($maxiddoc) > 0 ){

                $this->_id	=	intval( $maxiddoc[0]->_id ) + 1;
            }else{
                $this->_id	=	1;
            }

            $i = 0;
            while( $i < $max_error_limit){
                try {

                    $success = $this->save( $options,true );
                } catch (MongoCursorException $e) {
                    if ( $e->getCode() == 11000){ //continue when exception is :key duplicate
                        if ($i >= $max_error_limit){
                            throw new Exception('递增插入主键失败，超过最大冲突尝试次数');
                        }
                        $success = false;
                        $this->_id += 1;
                    }else{
                        throw $e;
                    }
                }
                if( $success ){
                    return true;
                }
                $i++;
            }

            return false;
        }

        /**
         * 获得当前对象的数组表示形式
         */
        public function toArray(){
            return $this->attributes;
        }

        /**
         * 从库中删除当前对象
         */
        public function destroy($options = array())
        {
            $this->beforeDestroy();

            if (!$this->new)
            {
                $collection = self::getCollection();
                return $collection->remove(array('_id' => $this->attributes['_id']),$options);
            }else{
                return false;
            }
        }

        /**
         * 删除当前集合符合查询条件的数据
         * @see http://cn.php.net/manual/en/mongocollection.remove.php
         * @param   $criteria 要删除的查询条件
         * @param   $options 删除选项
         */
        public static function remove($criteria = null,$options = array()){
            $begin_microtime = \Debug::getTime();
            if ( $criteria == null ){
                throw new Exception("$criteria 未提供");
            }

            $collection = self::getCollection();
            $ret =  $collection->remove( $criteria ,$options);

            //add performence log xwarrior  2012/8/8
            self::log_query('remove',$criteria, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }

        /**
         * 获取当前集合的名称
         */
        public function getName(){
            if (null  !== static::$collectionName)
            {
                $collectionName = static::$collectionName;
            }
            else
            {


                $className = get_called_class();
                //hack by jimmy.dong@gmail.com
                $real_className = array_pop(explode('\\',$className));

                $collectionName = self::tableize($real_className);
            }
            return $collectionName;
        }


        /**
         * 执行一个查询，并返回所有的文档结果数组
         * @param   $query   查询条件  array( field1=>condition,field2=>array($op=>condition),field3...)
         * @param   $fields    查询字段  array( 'field1','field2',... )
         * @param   $options  查找选项 array ( sort=>array(field1=>1,field2=>-1,...),skip=>int,limit=>int )
         */
        public static function findAll($query = array(), $fields = array(), $options = array())
        {
            $begin_microtime = \Debug::getTime();

            if ( null === $query ){
                $query == array();
            }
            if ( null === $fields){
                $fields = array();
            }
            if ( null === $options  ){
                $options = array();
            }

            $query = self::merge_in($query);

            $collection = self::getCollection();
            if( NULL !=$fields && count( $fields ) > 0){
                $documents = $collection->find($query,$fields);
            }else{
                $documents = $collection->find($query);
            }

            $className = get_called_class();

            if (isset($options['sort']))
                $documents->sort($options['sort']);

            if (isset($options['skip']))
                $documents->skip($options['skip']);

            if (isset($options['limit']))
                $documents->limit($options['limit']);

            if (isset($options['asArray']) && $options['asArray'] == 1){
                $flag_as_array = true;
            }else $flag_as_array = false;

            $ret = array();
            $documents->timeout($className::$findTimeout);

            //mongodb fetrue :when set batchsize,cusor will close after read batchsize rows
            if (isset($options['limit'])) {
                $documents->batchSize($options['limit']);  //optimize for read performence
            }

            while ( ($document = $documents->getNext()) != null)
            {
                if($flag_as_array)
                    $ret[] = $document;
                else
                    $ret[] = self::instantiate($document);
            }
            //add performence log xwarrior  2012/8/8
            self::log_query('findAll',$query, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }

        /**
         * 合并in查询中的重复条件
         * xwarrior 2012/8/8
         * @param unknown_type $query
         */
        private static function merge_in($query){
            foreach($query as $key => &$value){
                if ( is_array($value) && isset( $value['$in'] ) ){
                    $value['$in'] = array_unique(  $value['$in'] );
                }
            }
            return $query;
        }


        /**
         * 记录查询日志
         * xwarrior @ 2012/8/8
         * @param unknown_type $query
         * @param unknown_type $options
         * @param unknown_type $collection
         * @param unknown_type $begin_microtime
         */
        private  static function  log_query($method,$query,$options,$collection,$begin_microtime,$ret){
            $stacktrace = '';

            /* TODO: 根据日志级别决定是否显示调用堆栈 */
            $stack_list = debug_backtrace() ;
            $stacks = array();
            foreach($stack_list as $stack){
                $stacks[] = 'at file:'. $stack['file']  . ' line:' . $stack['line'] . ' function:' . $stack['function'];
            }
            $stacktrace = implode('\n',$stacks);
            $colname = '';
            if( $collection ){
                $colname = $collection->getName();
            }
            $logquery = 'invoke:' . $method . ' query:' . json_encode( $query ) . '  options:'.  json_encode($options)  . "\n$stacktrace";
            \Debug::db('mongodb://' . BaseMongoRecord::$connection ,  BaseMongoRecord::$database . ':' . $colname ,$logquery, Debug::getTime() - $begin_microtime, $ret);

        }



        /**
         * 给collection中的某个字段+1
         * @param   $query     查询条件  array( field1=>condition,field2=>array($op=>condition),field3...)
         * @param   $fields    需要增加数值的字段  string OR array( 'field1','field2',... )
         *
         * @return  bool
         */
        public static function inc( $query	=	array(),$fields	=	array()	,$incnum	=	1,	$upsert = false,	$safe = false){
            if ( null === $query ){
                return false;
            }
            if ( null === $fields){
                return false;
            }
            $begin_microtime = \Debug::getTime();
            $collection = self::getCollection();
            $options = array( 'upsert' => $upsert, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

            /* inc不需要执行先查询再更新，如果不想没数据的时候插入，设置$upsert=false即可    xwarrior 2012/8/8
           if( NULL !=$fields){
               $documents = $collection->findOne($query);
           }else{
               return false;
           }
           */
            if(is_array($fields)){
                foreach($fields	as $v){
                    $new_fields[$v]	=	$incnum;
                }
            }else{
                $new_fields[$fields] = $incnum;
            }

            $addok	=	$collection->update($query,  array( '$inc' => $new_fields),$options);

            //add performence log xwarrior  2012/8/8
            self::log_query('inc',$query, $options, $collection, $begin_microtime,$addok);
            return $addok;

        }
        /**
         * 求collection中某字段的和	相当于mysql 的 sum
         * @param   $group_by		用来group by 的字段数组  array(id => true,name => true)
         * @param   $where			查询条件  常规的where数组  array('user_id' => '1260858')
         * $param     $sub_columns		可选，默认1时求count，不为1就只能设为integer类型的字段名称的字符串或数组,
         *                                        如:  'field_name' or array('field_name_1','field_name_2')
         * @return  array			Array ( [retval] => Array ( [0] => Array ( [user_id] => 1260858 [count] => 6 ) ) [count] => 2 [keys] => 1 [ok] => 1 )
         */
        public static function sum($group_by = NULL,$where = NULL,$sub_columns=1){
            if ( $group_by == NULL && $sub_columns == 1 ){
                $ret =  self::count($where);

                return $ret;
            }

            $begin_microtime = \Debug::getTime();
            $collection = self::getCollection();
            //added by xwarrior at 2012/4/21 for suport no field to group by
            if( $group_by == NULL ){
                $group_by= new MongoCode('function(doc) { return {any:1}; }');
            }

            if ( is_array($sub_columns)){  //added by xwarrior for suport multi field sum
                $sum = 'prev.count +=1 ;';
                $initial = array('count' => 0);
                foreach( $sub_columns as $field_name  ){
                    $initial[ $field_name ] = 0;
                    $sum .= "prev.$field_name += doc.$field_name;";
                }
                $reduce = new MongoCode("function(doc, prev) { $sum }");
            }else if ($sub_columns==1){
                $initial =  array('count' => 0,'sum' => 0);
                $reduce = new MongoCode('function(doc, prev) { prev.count +=1; }');
            }else{
                $initial =  array('count' => 0,'sum' => 0);
                $reduce = new MongoCode(  'function(doc, prev) { prev.count +=1 ; prev.sum += doc.'.$sub_columns.'; }' );
            }

            $ret =	$collection->group(	$group_by,								// fields to group by
                $initial,								// initial value of the aggregation counter object.
                $reduce,								// a function that takes two arguments (the current document and the aggregation to this point) and does the aggregation
                array('condition'=>$where)				// condition for including a document in the aggregation
            );

            //add performence log xwarrior  2012/8/8
            if(!$where){
                $where = array();
            }
            if(!$group_by){
                $group_by = array();
            }
            self::log_query('sum',$where, array(), $collection, $begin_microtime,$ret);

            return $ret;

        }

        /**
         * 关联多个集合
         * @param   $coll_a	=	array(	'column',		array('a'=>'b')集合A中需要查出的列,key为数据库查询字段，val为查处数据字短
        'where',		集合A的查询条件
        'join_a',		向后关联时用的字段
        'join_type',	集合A关联后面数据的方法 left/inner/none
        )
         * @param   $coll_b	=	array(	'collection'	集合B的名称,key为数据库查询字段，val为查处数据字短
        'column',		array('a'=>'b')集合B中需要查出的列，前面为数据库字段名，后面为希望的名称
        'where',		集合B的查询条件
        'join_b',		向前关联时用的字短
        'join_a',		向后关联时用的字短
        'join_type',	集合B关联后面数据的方法 left/inner/none  左关联后面数据/inner关联后面数据/后面不关联数据
        'join_next'		下一个需要关联的集合
        )
        )
        注意　coll_b 的ｃｏｌｕｍｎ不能为＊，必须为数组array('a'=>'b')格式
        　　　ｃｏｌｌｅｃｔｉｏｎ　　需要带命名空间
        　多个集合关联时，集合ａ关联到集合Ｃ的字段时，集合Ｂ中的ｊｏｉｎ＿ｂ应写为ｊｏｉｎ＿ｃ中字段对应的新名称，不应为ｊｏｉｎ＿ｃ的数据库字段
        　

         * @return  和findAll返回相同
         */
        public static function ajoinb($coll_a,$coll_b){
            //递归调用多个集合
            if($coll_b['join_type']	==	'left' || $coll_b['join_type']	==	'inner'){
                $b_data	=	$coll_b['collection']::ajoinb($coll_b,$coll_b['join_next']);
                return	$b_data;
            }else{
                $b_data	=	$coll_b['collection']::findAll($coll_b['where'],array_keys($coll_b['column']));
            }

            if($coll_a['join_type'] == 'inner'){
                $in_array	=	array();
                $data_array	=	array();
                if($b_data){
                    foreach($b_data	as $val){

                        if($coll_b['join_b'] == '_id'){
                            $in_array[]	=	strval($val->attributes[$coll_b['join_b']]);
                            $b_data_array[strval($val->attributes[$coll_b['join_b']])]	=	$val->attributes;
                        }else{
                            $in_array[]	=	$val->attributes[$coll_b['join_b']];
                            $b_data_array[$val->attributes[$coll_b['join_b']]]	=	$val->attributes;
                        }
                    }
                }
                $coll_a['where'][$coll_a['join_a']]	=	array('$in'=>$in_array);

                if($coll_a['column'][0] == '*'){
                    $a_data	=	self::findAll($coll_a['where'],array());
                }else{
                    $a_data	=	self::findAll($coll_a['where'],array_keys($coll_a['column']));
                }

                if($a_data){
                    foreach($a_data as &$val){
                        foreach($coll_b['column'] as $k=>$v){
                            if($k == '_id'){
                                $val->attributes[$v]	=	strval($b_data_array[$val->attributes[$coll_a['join_a']]][$k]);
                            }else{
                                $val->attributes[$v]	=	$b_data_array[$val->attributes[$coll_a['join_a']]][$k];
                            }
                        }
                        if($coll_a['column'][0] != '*'){
                            foreach($coll_a['column'] as $k=>$v){
                                $val->attributes[$v]	=	$val->attributes[$k];
                            }
                        }
                    }
                }
            }elseif($coll_a['join_type'] == 'left'){
                if($coll_a['column'][0] == '*'){
                    $a_data	=	self::findAll($coll_a['where'],array());
                }else{
                    $a_data	=	self::findAll($coll_a['where'],array_keys($coll_a['column']));
                }
                if($b_data){
                    foreach($a_data as &$val){
                        if($coll_a['column'][0] != '*'){
                            foreach($coll_a['column'] as $kc=>$vc){
                                $val->attributes[$vc]	=	$val->attributes[$kc];
                            }
                        }
                        if($coll_b['join_b'] == '_id'){
                            foreach($b_data as $k=>$v){
                                if(isset($val->attributes[$coll_a['join_a']]) && isset($v->attributes[$coll_b['join_b']]) && $val->attributes[$coll_a['join_a']] == strval($v->attributes[$coll_b['join_b']])){
                                    foreach($coll_b['column'] as $ka=>$va){
                                        if($ka == '_id'){
                                            $val->attributes[$va]	=	strval($v->attributes[$ka]);
                                        }else{
                                            $val->attributes[$va]	=	$v->attributes[$ka];
                                        }
                                    }
                                    break;
                                }
                            }
                        }else{
                            foreach($b_data as $k=>$v){
                                if(isset($val->attributes[$coll_a['join_a']]) && isset($v->attributes[$coll_b['join_b']]) && $val->attributes[$coll_a['join_a']] == $v->attributes[$coll_b['join_b']]){
                                    foreach($coll_b['column'] as $ka=>$va){
                                        if($ka == '_id'){
                                            $val->attributes[$va]	=	strval($v->attributes[$ka]);
                                        }else{
                                            $val->attributes[$va]	=	$v->attributes[$ka];
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }else{
                    foreach($a_data as &$val){
                        if($coll_a['column'][0] != '*'){
                            foreach($coll_a['column'] as $kc=>$vc){
                                $val->attributes[$vc]	=	$val->attributes[$kc];
                            }
                        }
                    }
                }
            }
            if($a_data){
                return $a_data;
            }else{
                return false;
            }
        }

        /**
         * leftjoin 左关联
         * @param   $data_list		基数据  findAll返回结果
         * @param   $join_column	关联时的左右字段	目前只有一个	array('create_user_id' => array('column' => '_id','mongoid' => false))
         * @param   $columns		需关联进来的集合字段  array( '_id' => 'mag_id')
         * @param   $where			集合数据查询条件
         * @return  和findAll返回相同
         * 还需做的事情 join_column 需要能按 array('mag_id' => '_id','or'=>array(),'and'=>array()) 处理
        用法$products	=	ActiProd::findAll(array('product_code'=>array('$in' => $product_ids)),array());
        $products	=	Activity::leftjoin($products,array('activity_id'=>'_id'),array('_id' => 'act_id'),array());
         */
        public static function leftjoin($data_list,$join_column = array(),$columns = array(),$where = array()){
            if(!$data_list)
                return false;
            $in_array	=	array();
            $join_a		=	key($join_column);
            $join_b		=	current($join_column);
            if($join_b['mongoid']){
                foreach($data_list as $val){
                    $in_array[]	=	new MongoId($val->$join_a);
                }
            }else{
                foreach($data_list as $val){
                    $in_array[]	=	$val->$join_a;
                }
            }
            $where[$join_b['column']]	=	array('$in' => $in_array);
            $data_b		=	self::findAll($where,array_keys($columns));
            $in_data	=	array();
            if($data_b ){
                foreach($data_b as $v){
                    if(is_object($v->$join_b['column'])){
                        $in_data[$v->$join_b['column']->{'$id'}]	=	$v;
                    }else{
                        $in_data[$v->$join_b['column']]	=	$v;
                    }
                }
            }
            foreach($data_list as &$val){
                if($val->$join_a){
                    if(!is_object($val->$join_a)){
                        foreach($columns as $k=>$v){
                            if(isset($in_data[$val->$join_a])){
                                $val->attributes[$v]	=	$in_data[$val->$join_a]->$k;
                            }
                        }
                    }else{
                        foreach($columns as $k=>$v){
                            if(isset($in_data[strval($val->$join_a)])){
                                $val->attributes[$v]	=	$in_data[strval($val->$join_a)]->$k;
                            }
                        }
                    }
                }
            }
            return $data_list;
        }

        /**
         * innerjoin 关联
         * @param   $data_list		基数据  findAll返回结果
         * @param   $join_column	关联时的左右字段	目前只有一个
         * @param   $columns		需关联进来的集合字段  array( '_id' => 'mag_id')
         * @param   $where			集合数据查询条件
         * @param   $sort_limit		排序，分页等
         * @return  和findAll返回相同
         * 还需做的事情 join_column 需要能按 array('mag_id' => '_id','or'=>array(),'and'=>array()) 处理
        用法$products	=	ActiProd::findAll(array('product_code'=>array('$in' => $product_ids)),array());
        $products	=	Activity::innerjoin($products,array('activity_id'=>'_id'),array('_id' => 'act_id'),array());
         */
        public static function innerjoin($data_list,$join_column = array(),$columns = array(),$where = array(),$sort_limit = array()){
            if(!$data_list)
                return false;
            $in_array	=	array();
            $join_a		=	key($join_column);
            $join_b		=	current($join_column);
            if($join_b['mongoid']){
                foreach($data_list as $val){
                    $in_array[]	=	new MongoId($val->$join_a);
                }
            }else{
                foreach($data_list as $val){
                    $in_array[]	=	$val->$join_a;
                }
            }
            $where[$join_b['column']]	=	array('$in' => $in_array);
            $data_b		=	self::findAll($where,array_keys($columns),$sort_limit);
            $in_data	=	array();
            if($data_b ){
                foreach($data_b as $v){
                    if(is_object($v->$join_b['column'])){
                        $in_data[$v->$join_b['column']->{'$id'}]	=	$v;
                    }else{
                        $in_data[$v->$join_b['column']]	=	$v;
                    }
                }
            }
            foreach($data_list as $key=>&$val){
                if($val->$join_a){
                    if(!is_object($val->$join_a)){
                        foreach($columns as $k=>$v){
                            if(isset($in_data[$val->$join_a])){
                                $val->attributes[$v]	=	$in_data[$val->$join_a]->$k;
                            }else{
                                unset($data_list[$key]);
                            }
                        }
                    }else{
                        foreach($columns as $k=>$v){
                            if(isset($in_data[strval($val->$join_a)])){
                                $val->attributes[$v]	=	$in_data[strval($val->$join_a)]->$k;
                            }else{
                                unset($data_list[$key]);
                            }
                        }
                    }
                }else{
                    unset($data_list[$key]);
                }
            }
            return $data_list;
        }



        /**
         * 执行一个查询，返回查询游标
         * @param   $query    查询条件 array( field1=>condition,field2=>array($op=>condition),field3...)
         * @param   $fields     查询字段 array( 'field1','field2',... )
         * @param   $options   查找选项 array ( sort=>array,skip=>int,limit=>int )
         */
        public static function find($query = array(), $fields = array(), $options = array())
        {
            $begin_microtime = \Debug::getTime();
            $collection = self::getCollection();

            $query = self::merge_in($query);
            if( NULL !=$fields && count( $fields ) > 0){
                $documents = $collection->find($query,$fields);
            }else{
                $documents = $collection->find($query);
            }

            $className = get_called_class();

            if (isset($options['sort']))
                $documents->sort( $options['sort'] );

            if (isset($options['skip']))
                $documents->skip($options['skip']);

            if (isset($options['limit']))
                $documents->limit($options['limit']);

            $documents->timeout($className::$findTimeout);

            $ret = new MongoRecordIterator($documents, $className);

            //add performence log xwarrior  2012/8/8
            self::log_query('findAll',$query, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }

        /**
         * 查找一条单独的记录，如果有多条结果，只返回第一条
         * @param unknown_type $query   查询条件array( field1=>condition1,field2=>condition2);
         * @param unknown_type $fields　  查询字段列表 array('fild1','field2')
         * @RETURN 数据对象，未查找到返回null
         */
        public static function findOne($query = array(), $fields = array())
        {
            $begin_microtime = \Debug::getTime();

            $query = self::merge_in($query);
            $collection = self::getCollection();
            if( $fields != null && count( $fields) > 0 ){
                $document = $collection->findOne($query,$fields);
            }else{
                $document = $collection->findOne($query);
            }
            $ret =  self::instantiate($document);

            //add performence log xwarrior  2012/8/8
            self::log_query('findOne',$query, array(), $collection, $begin_microtime,$ret);

            return $ret;
        }

        /**
         * 查找指定id的数据
         * @param string or objectid or array $arr_ids  要查找的id数组(string or MongoId) 如: array('4f92a1768749160f74000001' ,  '4f92a1768749160f74000001' ,  '4f92a1768749160f74000001')
         * @param array $other_query_condtion   ids之外的其它查询条件  如 : array( 'visibility' => 0  , owner_id => '2233334' )
         * @param $conver_to_key_value 是否转换为array( _id=>array() ,_id=array())的关联数组
         * @param   $fields    查询字段  array( 'field1','field2',... )
         * @param   $options  查找选项 array ( sort=>array(field1=>1,field2=>-1,...),skip=>int,limit=>int )
         * @return 返回符合条件文档的数组结果
         */
        public static function findByIds($arr_ids = array(),$other_query_condtion = array() , $conver_to_key_value = false,
                                         $fields = array(), $options = array()){
            $begin_microtime = \Debug::getTime();
            if ( count($arr_ids) == 0 ){
                return array();
            }
            $or_ids = array();

            foreach($arr_ids as $id){

                if ( $id instanceof MongoId  ){
                    $_id = $id;
                }else{
                    $_id = new MongoId( strval($id) );  //try convert to mongoid
                    if (  strval($_id) !=  $id){
                        $_id = $id;
                    }
                }
                $or_ids[] =  $_id;
            }
            $query = array( '_id' => array( '$in' => $or_ids ) );
            if ( $other_query_condtion ){
                foreach($other_query_condtion as $key => $value ){
                    $query[$key] = $value;
                }
            }
            $ret = self::findAll($query,$fields,$options);
            //转换为key,value关联数组
            if ( $ret && $conver_to_key_value ){
                $newret = array();
                foreach($ret as $item){
                    $newret[ $item->getID() ] = $item;
                }
                $ret =  $newret;
            }

            //add performence log xwarrior  2012/8/8
            self::log_query('findByIds',$query, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }


        /**
         * 获取指定查询的记录总数
         * @param unknown_type $query
         */
        public static function count($query = array())
        {
            $begin_microtime = \Debug::getTime();
            $collection = self::getCollection();
            $documents = $collection->count($query);
            $ret =  $documents;

            //记录sql执行时间  xwarrior 2012/8/10
            self::log_query('count',$query, array(), $collection, $begin_microtime,$ret);
            return $ret;
        }

        private static function instantiate($document)
        {
            if ($document)
            {
                $className = get_called_class();
                return new $className($document, false);
            }
            else
            {
                return null;
            }
        }

        /**
         * 获取对象主键字符串形式的值
         */
        public function getID()
        {
            //if ( array_key_exists('_id', $this->attributes )){
            if ( isset( $this->attributes['_id'] ) ){
                return strval( $this->attributes['_id'] );
            }else{
                return NULL;
            }
        }

        /**
         * 获取对象主键的值 MongoId,当前没有主键则返回null
         * @param $_id  可以为MongoID或字符串，如果字符串代表的是MongoID,则必须初始化为MongoID
         */
        public function setID($_id)
        {

            $this->attributes['_id'] = $_id;
        }

        /**
         * 允许$->获取属性值
         * @param unknown_type $property_name
         */
        public function __get($property_name){

            $property = self::tableize($property_name );
            /*if (  static::$schema != null &&
                    array_key_exists($property, static::$schema) ) */  //changed by xwarrior@2012.5.2 for performence
            if ( isset( static::$schema[$property] ) )
            {
                /*if (   array_key_exists($property, $this->attributes) ){*/
                if  (isset( $this->attributes[$property]) ){
                    return $this->attributes[$property];
                }else{
                    return null;
                }
            }else{
                if ( isset(  $this->$property_name ) ){
                    return $this->$property_name ;
                }else{
                    return null;
                }
            }
        }

        public function __set($property_name,$property_value){

            $property = self::tableize( $property_name );
            /*if(  static::$schema != null &&
                    array_key_exists($property, static::$schema) ){ */  //changed by xwarrior@2012.5.2 for performence
            if ( isset(  static::$schema[$property] ) ){
                $this->attributes[$property] = $property_value;
                return $this;
            }else{
                $this->$property_name = $property_value;
                return $this;
            }
        }

        public function __call($method, $arguments)
        {
            //get or set must  len > 3
            if( strlen($method) <= 3 ){
                return ;
            }
            // Is this a get or a set
            $prefix = strtolower(substr($method, 0, 3));

            if ($prefix != 'get' && $prefix != 'set')
                return;

            if ( static::$schema == null){
                $className = get_called_class();
                throw new Exception("$className schema undefined!");
            }



            // What is the get/set class attribute
            $property = self::tableize(substr($method, 3));
            if ( empty($prefix) || empty($property) )
            {
                // Did not match a get/set call
                throw New Exception("Calling a non get/set method that does not exist: $method");
            }

            /*if ( !array_key_exists($property, static::$schema) ){ */
            if ( !isset( static::$schema[$property]  ) ){
                return;
            }


            // Get
            //if ($prefix == "get"  && array_key_exists($property, $this->attributes))
            if ($prefix == "get"  && isset( $this->attributes[$property] ) )
            {
                return $this->attributes[$property];
            }
            else if ($prefix == "get")
            {
                return null;
            }

            // Set
            //if ($prefix == "set" && array_key_exists(0, $arguments))
            if ($prefix == "set" && isset($arguments[0] ) )
            {
                $this->attributes[$property] = $arguments[0];
                return $this;
            }
            else
            {
                throw new \Exception("Calling a get/set method that does not exist: $property");
            }
        }


        // framework overrides/callbacks:
        public function beforeUpdate() {}
        public function beforeSave() {}
        public function afterSave() {}
        public function beforeValidation() {}
        public function afterValidation() {}
        public function beforeDestroy() {}
        public function afterNew() {}


        protected function isValid($update = false)
        {
            $className = get_called_class();
            $methods = get_class_methods($className);

            foreach ($methods as $method)
            {
                if (substr($method, 0, 9) == 'validates')
                {
                    $propertyCall = 'get' . substr($method, 9);
                    if(true == $update && !$this->$propertyCall())continue; //update操作时，空值的字段忽略检查。by jimmy, for update
                    if (!$className::$method($this->$propertyCall()))
                    {
                        return false;
                    }
                }
            }
            /**
             * Auto validate
             * @author jimmy.dong@gmail.com
             * 根据schema与 schema_ext对类型进行检查
             *
             * 【schema扩展】
             * by jimmy.dong@gmail.com
             * 通用扩展属性：
             *  default		缺省值
             * 	jugglin		是否允许自动类型转换
             *  essential	必须有值(非null)
             * 类型适用属性：
             * switch(type){
             *	case 'integer' 	:   min 最小值，max 最大值，break;
             *  case 'float'	:   min 最小值，max 最大值，break;
             *  case 'string'	:   min 长度最小  max 长度最大 break;
             *  case 'boolean'	:
             *  case 'object'	:
             *  case 'resouce'	:
             *	case 'NULL'		:
             *	case 'unknown type': break;
             * }
             * eg:
            protected static $schema = array(
            _id=>'objectid',
            name=>'string',
            key=>'integer',
            data=>'array'
            );
            protected static $schema_ext = array(
            key=>array('jugglin'=>1,'essential'=>1,'min'=>10,'max'=>100),

            );
             */
            $ext_schema = array();
            if(isset(static::$schema_ext) && is_array(static::$schema_ext))foreach(static::$schema_ext as $key=>$val){
                if(is_array($val))foreach($val as $key2=>$val2){
                    $ext_schema[$key][strtolower($key2)] = $val2;
                }
            }
            foreach( static::$schema as $k=> $v){
                if($k=='_id'){
                    //wait...
                    continue;
                }
                $current_var	= $this->$k;

                //if(true == $update && !$current_var)continue; //update操作时，不存在的字段忽略检查。by jimmy, for update
                //如果current_var=0|'' 会缺少类型检查
                if(true == $update && $current_var ===null)
                    continue;
                $ext = isset($ext_schema[$k])?$ext_schema[$k]:array();
                //检查是否必须非空值
                if(isset($ext['essential']) && $ext['essential'] && $current_var===null){
                    throw new \Exception("BaseMongoRecord: $k must have value!");
                    break;
                }

                switch($v){
                    //注意： 自定义类型请在此补充检查条件
                    case 'datetime':
                        //日期自动转换为整数（注：应该当为毫秒数)  xwarriro @ 2012.5.9
                        if ( $this->$k ){
                            $this->$k = intval( $this->$k );
                        }else{
                            $this->$k = 0;  //set default valueto 0 or throw exception?
                        }
                        break;
                    case 'boolean':
                    case 'integer':
                    case 'float':
                    case 'double':
                    case 'string':
                    case 'array':
                    case 'object':
                        if(isset($ext['default']) && $current_var===null){
                            //缺省值处理
                            $this->$k = $ext['default'];
                        }elseif(isset($ext['jugglin']) && $ext['jugglin'] && (gettype($current_var) != $v)){
                            //自动转义处理
                            $tmp = $current_var;
                            settype($tmp, $v);
                            $this->$k = $tmp;
                        }elseif(gettype($current_var) != $v && $current_var !== null) {
                            throw new \Exception("BaseMongoRecord: '$k' must be $v !");
                            return false;
                        }
                        //长度检查
                        if(isset($ext['min'])){
                            if('string' == $v) $l=strlen($current_var);
                            else $l=$current_var;
                            if($l < $ext['min']){
                                throw new \Exception("BaseMongoRecord: '$k' = $current_var length not enough!");
                                return false;
                            }
                        }
                        if(isset($ext['max'])){
                            if('string' == $v) $l=strlen($current_var);
                            else $l=$current_var;
                            if($l > $ext['max']){
                                throw new \Exception("BaseMongoRecord: '$k' => $current_var length over max!");
                                return false;
                            }
                        }
                        break;
                    case 'unkonw':
                    default:
                        break;
                }
            }

            return true;
        }

        /**
         * 标准化表名
         * @static
         * @param $camelCasedWord
         * @return string
         */
        protected static function tableize($camelCasedWord) {
            return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
        }

        // core conventions
        protected static function getCollection()
        {
            $className = get_called_class();

            if (null !== static::$collectionName)
            {
                $collectionName = static::$collectionName;
            }
            else
            {
                //hack by jimmy.dong@gmail.com
                $t = explode('\\',$className);
                $real_className = array_pop($t);

                $collectionName = self::tableize($real_className);
            }

            if ($className::$database == null)
                throw new \Exception("BaseMongoRecord::database must be initialized to a proper database string");

            if ($className::$connection == null)
                throw new \Exception("BaseMongoRecord::connection must be initialized to a valid Mongo object");

            if (!($className::$connection->connected))
                $className::$connection->connect();

            return $className::$connection->selectCollection($className::$database, $collectionName);
        }

        public static function setFindTimeout($timeout)
        {
            $className = get_called_class();
            $className::$findTimeout = $timeout;
        }

        /**
         * 建立当前集合上的索引，或确保索引建立,如果已经有索引则忽略
         * 警告：在繁忙的生产系统上对大集合数据调用ensureIndex会导致系统阴塞
         * @param array $keys
         * @param array $options
         */
        public static function ensureIndex(array $keys, array $options = array())
        {
            return self::getCollection()->ensureIndex($keys, $options);
        }

        /**
         * 删除集合上的索引
         * @param unknown_type $keys
         */
        public static function deleteIndex($keys)
        {
            return self::getCollection()->deleteIndex($keys);
        }

        /**
         * 将当前对象的变更保存到数据库中
         * @param boolean $overite_all   是否用当前对象完全替换数据库记录，
         *                                           例如数据库中为: array(a=>1,b=>2),对象的值为array(c=>1)
         *                                           当$overite_all=true,则保存后数据库中为 array(c=>1),a和b字段的值将丢失
         *                                           当$overite_all=false,则保存后数据库中为array(a=>1,b=>2,c=>1),新增了一个c字段
         * @param boolean $upsert  如果不存在，是否插入
         * @return 如果设置了safe=true,返回了包含 status的数组，如果safe=false,只要$new_object的值不是空就返回true
         * @see http://www.php.net/manual/en/mongocollection.update.php
         * 	@author xwarrior 2012/3/8
         */
        public function Update($overite_all = false,$upsert = false,$safe = false){
            /**
             * 执行数据验证规则
             */
            if ( !$this->validate(true) ){
                throw new \Exception( get_called_class() . '对象schema验证失败');
            }

            if( !isset($this->attributes['_id'])  ){
                throw new \Exception(get_called_class() .'对象没有指定 _id 属性，无法执行更新');
            }

            $begin_microtime = \Debug::getTime();
            $this->beforeUpdate();
            //added by Kit: for trace update
            //throw new \Exception('this is:'.$this->attributes['visibility']);

            $options = array( 'upsert' => $upsert, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

            $critera = array('_id' => $this->attributes['_id'] );

            //added by Kit: bug report:mod on _id not allowed, _id should be removed from attributes before doing update
            $_id = $this->attributes['_id']; /* 保存_id属性后边再恢复  */
            unset( $this->attributes['_id'] );

            //覆盖替换所有字段
            $ret = null;
            $collection = $this->getCollection();
            if ( $overite_all ){
                $ret = $collection->update($critera,  $this->attributes, $options);
                $this->_id = $_id;
            }else{
                //只执行几个字段变更
                $ret = $collection->update($critera,  array( '$set' => $this->attributes),$options);
                $this->_id = $_id;
            }
            //add performence log xwarrior  2012/8/8
            self::log_query('Update',$critera, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }

        /**
         * 往对象的array类型字段中追加一条数据
         * @param $field_values   array( filed => values, field => value ...)
         *
         */
        public function Push($field_values,$safe = false){
            $begin_microtime = \Debug::getTime();
            $options = array( 'upsert' => false, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

            $critera = array('_id' => $this->attributes['_id'] );
            $ret = null;
            $collection = $this->getCollection();

            $ret = $collection->update($critera,  array('$push' => $field_values), $options);

            self::log_query('Push',$critera, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }

        /**
         * 在内嵌文档中删除指定条件的数据
         * $remove_query  要移除的内嵌文档的数据条件 如 array( comment_id: ’xxxxxxxxxxxxxxxx' );
         */
        public function Pull($remove_query,$safe = false){
            $begin_microtime = \Debug::getTime();
            $options = array( 'upsert' => false, 'multiple' => false,'safe' => $safe,'fsync' =>false, 'timeout' => static::$findTimeout  );

            $critera = array('_id' => $this->attributes['_id'] );
            $ret = null;
            $collection = $this->getCollection();

            $ret = $collection->update($critera,  array('$pull' => $remove_query), $options);

            $query = array_merge($critera,$remove_query);
            self::log_query('Pull',$query, $options, $collection, $begin_microtime,$ret);
            return $ret;
        }


        /**
         * 批量更新符合query条件的一批数据，用fields中指定的字段值
         * @param array   $query  mongo  查询字段 		   array( filed1=>condition1,field2=>condition2,...)
         * @param object $new_object  要更新的对象值必须继承自BaseMongoRecord (为了数据验证需要)
         * @param  array  $options array("upsert" => <boolean>,"multiple" => <boolean>,"safe" => <boolean|int>,"fsync" => <boolean>, "timeout" => <milliseconds>)
         * // @return 如果设置了safe=true,返回了包含 status的数组，如果safe=false,只要$new_object的值不是空就返回true
         * @return 默认返回更新条数 //by jimmy
         *  @author  xwarrior at 2012/3/8
         * @see http://www.php.net/manual/en/mongocollection.update.php
         */
        public static function updateAll($query = array() , $new_object ,
                                         $options = array( 'upsert' => false, 'multiple' => true,'safe' => true,'fsync' =>false, 'timeout' => 20000  )){
            $attrs = $new_object->toArray();
            unset($attrs['_id']);  //防止传入_id

            if ( !$attrs || count($attrs) == 0 ){
                throw new Exception( gettype($new_object). '要保存的对象值不能为空');
            }

            if ( !$query || count($query) == 0 ){
                throw new Exception(gettype($new_object).'更新的查询条件不能为空');
            }

            /**
             * 执行数据验证规则
             */
            if ( !$new_object->validate(true) ){
                throw new \Exception(gettype($new_object).'对象schema验证失败');
            }

            $ret =  self::getCollection()->update($query,array('$set'=>$attrs),$options);

            if($options['safe']) return $ret['n'];
            else return $ret;

        }


        public function getAttributes()
        {
            return $this->attributes;
        }

        /**
         * 检查一个给定的mongoid是否是有效的格式
         * @param unknown_type $mongoid
         */
        public static function isMongoIDValid($mongoid){
            if ( !$mongoid ){
                return false;
            }

            if ( $mongoid instanceof MongoId  ){
                return true;
            }

            $oid = new MongoId( strval($mongoid) );  //try convert to mongoid
            if (  strval($oid) !=  $mongoid){
                return false;
            }
            return True;
        }

        /**
         * 读取当前时间的整数millsecond表示形式
         * @see 只支持64位系统下的php
         */
        public function Millseconds(){

            list($mills,$seconds )  =   explode( ' ',microtime()) ;
            $microtime =  $seconds .  substr(  $mills ,2,3);

            return intval($microtime);
        }

        /**
         * 字符串或字符串id数组,转换到,mongoId或mongoId数组
         * @param $ids 值格式：'id' 或 'id1,id2,idN' 或  array(id1,id2,id3,idN)
         * @return new MongoId() 或  array(new MongoId,new MongoId,....);
         */
        public static function toMongoId($ids){
            if(!is_array($ids)){
                if(strstr($ids,',')){
                    $ids=explode(",",$ids);
                }else{
                    return new MongoId($ids);
                }
            }
            $c=count($ids);
            if($c<1)
                return null;
            else if($ids[$c-1]==''){
                array_pop($ids);
            }
            foreach ($ids as &$id){
                $id=new MongoId(trim($id));
            }
            return $ids;
        }
    }


