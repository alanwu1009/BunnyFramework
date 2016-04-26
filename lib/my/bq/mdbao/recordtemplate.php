<?php
    namespace  my\bq\mdbao;

    use \Mongo as Mongo;
    use \MongoCode as MongoCode;
    use \MongoId as MongoId;
    use \MongoLog;
    use \MongoCursorException;
    use \Exception;
    use \Debug;
    use \my\bq\criterion\Criteria;
    use \my\bq\criterion\MongoCriteriaImpl;
    use \my\bq\mdbao\MdbaoSupport;
    use \my\bq\criterion\Projections;
    use \my\bq\criterion\Restrictions;



    class RecordTemplate{
        /**
         * 设置查询的超时间时间，默认是20000毫秒
         * @param unknown_type $timeout
         */
        public function setFindTimeout($timeout){

        }

        /**
         * 将实体持久化到数据库;
         * @param object $entity Or an objects;
         * @return ini 记录ID;
         */
        public function save($entity){
            $arrayId = array();
            if(is_array($entity)){
                foreach ($entity as $object){
                    $id = MdbaoSupport::saveEntity($object);
                    array_push($arrayId, $id);
                }

            }else{
                return MdbaoSupport::saveEntity($entity);
            }
            return $arrayId;
        }

        /**
         * 更新实体, 必须指定实体的主键值
         * @param object $entity
         */
        public function replace($entity){
            $arrayId = array();
            if(is_array($entity)){
                foreach ($entity as $object){
                    $id = MdbaoSupport::updateEntity($object,null,true);
                    array_push($arrayId, $id);
                }

            }else{
                return MdbaoSupport::updateEntity($entity,null,true);
            }
            return $arrayId;
        }


        /**
         * 按实体更新数据库，若不指定 where 则按给定主键删除;
         * @param object $entity
         * @param array $where
         */

        public function delete($entity, $where = null){
            return MdbaoSupport::deleteEntity($entity, $where);
        }

        /**
         * 按实体更新数据库，若不指定 where 则按给定主键更新;
         * @param object $entity
         * @param array $where
         */
        public function update($entity,$where = null){
            return MdbaoSupport::updateEntity($entity,$where);
        }

        public function find($criteria){
            $criteria->setFirstResult(0)->setFetchSize(1);
            $rs = $criteria->_array();
            if(is_array($rs)){
                return @current($rs);
            }
        }

        /**
         * 按实体查询数据库
         * @param object $entity
         * @param Order $order 指定排序规则, 可以为 Order对像的数组;
         * @reutrn 返回绑定实体后的数组对象;
         */
        public function findByEntity($entity,$order = null){

            if(is_object($entity)){
                $config = $entity->getConfig();
                $columns = $config['columns'];

                $criteria = new MongoCriteriaImpl($entity);
                $criteria->setFileds($entity->getFileds()); //要查询的字段

                //set order
                if(is_object($order)){
                    $criteria->addOrder($order);
                }else{
                    if(is_array($order))
                        foreach($order as $orderItem)
                            $criteria->addOrder($orderItem);
                }

                //add Restrictions;
                foreach($columns as $column){
                    if($entity->$column != null){
                        $criteria->add(Criteria::_AND, Restrictions::eq($column, $entity->$column));
                    }
                }
                return $this->findAll($criteria);
            }
        }


        /**
         * 按实体查询数据库返回单一数据
         * @param object $entity
         * @param Order $order 指定排序规则, 可以为 Order对像的数组;
         */
        public function findByEntityUnique($entity,$order = null){

            if(is_object($entity)){
                $config = $entity->getConfig();
                $columns = $config['columns'];

                $criteria = new MongoCriteriaImpl($entity);
                $criteria->setFileds($entity->getFileds()); //要查询的字段
                $criteria->setFirstResult(0)->setFetchSize(1); //set limit

                //set order
                if(is_object($order)){
                    $criteria->addOrder($order);
                }else{
                    if(is_array($order))
                        foreach($order as $orderItem)
                            $criteria->addOrder($orderItem);
                }

                //add Restrictions;
/*                foreach($columns as $column){
                    if($entity->$column != null){
                       // $criteria->add(Criteria::_AND, Restrictions::eq($column, $entity->$column));
                    }
                }*/
                return @current($this->findAll($criteria));
            }
        }


        /**
         * 指定Criteria 查询数据; SQL语句查询数组,返回为数据数组,不提供实体绑定;
         * @param $criteria
         * @return 绑定实体后的数组对象;
         */

        public function findAll($criteria){

            //是否使用分页器
            $dataPager = $criteria->getDataPager();

            if(is_object($dataPager)){

                $totalNum = 0;
                $fileds = $criteria->getFileds();

                $criteria->cleanFileds();
                $criteria->addProjection(Projections::rowCount());
                $totalNum = $criteria->_count();

                $dataPager->setTotalNum($totalNum);
                $criteria->setFileds($fileds);
                $criteria->setFirstResult($dataPager->getFirstResult());
                $criteria->setFetchSize($dataPager->getPageSize());
            }

            return $criteria->_array(true);
        }

        public function findAndModify($criteria,$modifier){
            //是否使用分页器
            $dataPager = $criteria->getDataPager();

            if(is_object($dataPager)){
                $totalNum = 0;
                $fileds = $criteria->getFileds();
                $criteria->cleanFileds();
                $criteria->addProjection(Projections::rowCount());
                $rs1 = $criteria->_array(false);
                $totalNum = @current(@current($rs1));
                $dataPager->setTotalNum($totalNum);

                $criteria->cleanProjection();
                $criteria->setFileds($fileds);
                $criteria->setFirstResult($dataPager->getFirstResult());
                $criteria->setFetchSize($dataPager->getPageSize());

            }
            if($modifier != null){
              $criteria->setModifier($modifier);
            }
            try{
                $rs = $criteria->_array(true);
            }catch (Exception $e){
                throw $e;
            }
            return $rs;
        }

        /**
         * 支持mongo命令
         * @param $command || $criteria;
         * @return mixed
         */
        public function runMongoCommand($command){

            if(is_object($command)){
                $command = $command->getCommand();
            }
            $cursor =  MdbaoSupport::runCommand($command);
            $values = $cursor['values'];
            if(!$values){
                return $cursor['retval'];
            }
            return $values;

            //$fileds = $criteria->getFileds();

            //$dataArray = array();
/*            if($values){
                foreach($values as $cur){
                    /*                continue;
                  if(!$cur['_id']) continue;
                  $d = array();
                  $d['_id'] = $cur['_id'];
                  if($fileds == "*" || $fileds[0] == "*"){
                      $cfg = $criteria->getEntity();
                      die("sdf");
                      var_dump($cfg);
                      $fileds = $cfg['columns'];
                      var_dump($fileds);
                  }
                  foreach($fileds as $filed){
                      $d[$filed] =  $cur[$filed];
                  }
                  $dataArray[] = $d;
                }
            }
**/

            //var_dump($dataArray);

        }




    }


