<?php
namespace my\bq\criterion;
use my\bq\common\Configuration;
use my\bq\common\Log;
use my\bq\dao\PdoManager;
use my\bq\mdbao\MongoManager;
use \MongoConnectionException;
class MongoCriteriaImpl implements Criteria{

	private $mongoDatabase = null;
    private $tableIns = null;

	private $criterionEntries =array();
	private $projectionEntries = array();
	private $joinEntries = array();
	private $orderEntries = array();
	private $groupEntries = array();
	private $relationEntries = array();

	private $criterias = array();
	private $table = "";
	private $tableEntity = "";
	private $alias = "";
	private $fileds = array();
	private $columns = array(); 

	const  ROOT_ALIAS = "this_";
	private $firstResult = 0;
	private $fetchSize = 0;

	private $fullQuery = "";
	private $fullGroupQuery = "";
	private $fullOrderQuery = "";
	private $fullJoinQuery = "";
	private $fullProjectionQuery = "";
	private $parameters = array();
    private $translaters = array();
    private $command;
    private $count = false; //是否查询记录条数;
	
	private $dataPager; //DataPager

    private $modifier; //修改器;

	private $sqlString;

	function __construct($entityClass = null){
		$entityConf = $entityClass->getConfig();
		$this->table = $entityConf['name'];
		$this->columns = $entityConf['columns'];
		$this->tableEntity = $entityClass;
		$this->fileds =  $entityClass->getFileds();

        //add Restrictions;
        foreach($this->columns as $column){
            if($entityClass->$column != null){
                $this->add(Criteria::_AND, Restrictions::eq($column, $entityClass->$column));
            }
        }

		$entityClass->setCriteria($this);

		//interator
		if(isset($entityConf['relations'])&& is_array($entityConf['relations'])){
			foreach ($entityConf['relations'] as $k => $relation){
				// = current($relationItem);
				$rEntity = $relation['class'];
				if(is_object($rEntity)){
					$crit = $this->createCriteria($rEntity);
					$cache = CacheManager::getInstance();
					$cacheTabEntity = $cache->get('tableEntity');
					if(!isset($cacheTabEntity)){
						$cacheTabEntity = array();
					}

					$cacheTabEntity[$entityConf['alias']] = $rEntity;
					$cache->set('tableEntity',$cacheTabEntity);
				}
			}
		}
	}

	public function add($T,$criterion){
		array_push($this->criterionEntries,array($T,$criterion));
		return $this;
	}
	public function addOrder($order){
		array_push($this->orderEntries,$order);
		return $this;
	}
	public function addGroup($group){
		array_push($this->groupEntries, $group);
		return $this;
	}

	public function addProjection($projection){
		array_push($this->projectionEntries, $projection);
		return $this;
	}
	public function setProjectionEntitys($projectionEntitys){
		$this->projectionEntries = $projectionEntitys;
	}

	public function addJoin($join){
		array_push($this->joinEntries, $join);

		$cache = CacheManager::getInstance();
		$join_table = $cache->get('join_table');
		if(!$join_table)$join_table = array();
		array_push($join_table, $join->getJoinCriteria()->getAlias());
		$cache->set('join_table',$join_table);
		return $this;
	}

	public function getJoinEntries(){
		return $this->joinEntries;
	}

	public function addRelation($relation){
		array_push($this->relationEntries,$relation);
	}

	public function getRelation(){
		return  $this->relationEntries;
	}

	public function getOrderEntries(){
		return $this->orderEntries;
	}

	public function getGroupEntries(){
		return $this->groupEntries;
	}

	public function getDataPager(){
		return $this->dataPager;
	}

	public function setDataPager($dataPager){
		$this->dataPager = $dataPager;
	}

    /*
     * 查询记录条数
     */
    public function rowCount($isCount){
        $this->count = true;

    }


	public function setFetchSize($fetchSize){
		$this->fetchSize = $fetchSize;
		return $this;
	}
	public function setFirstResult($firstResult){
		$this->firstResult = $firstResult;
		return $this;
	}

	public function getFetchSize(){
		return $this->fetchSize;
	}

	public function getFirstResult(){
		return $this->firstResult;
	}



	public function getQuery(){
		$this->build();
		return $this->fullQuery;
	}

	public function getOrderQuery(){
		if($this->orderEntries){
             $this->fullOrderQuery = array();
            foreach($this->orderEntries as $orerEntiry){
                $this->fullOrderQuery = array_merge($this->fullOrderQuery,$orerEntiry->toMongoParam($this));
            }
        }
		return $this->fullOrderQuery;
	}

	public function getGrounpQuery(){
		$this->buildGroupQuery();
		return $this->fullGroupQuery;
	}

	public function getProjectionQuery(){
		$this->buildProjectionQuery();
		return $this->fullProjectionQuery;
	}

	public function getJoinQuery(){
		$this->buildJoinQuery();
		return $this->fullJoinQuery;
	}


	public function addCriteria($criteria){
		array_push($this->criterias, $criteria);
		return $this;
	}

	public function getCriterias(){
		return $this->criterias;
	}

	public function getAlias(){
		return $this->alias?$this->alias:self::ROOT_ALIAS;
	}

	public function getTable(){
		return $this->table;
	}

	public function setFileds($fileds){
		$this->fileds = (is_string($fileds)||is_object($fileds))?array($fileds):$fileds;
		return $this;
	}

	public function setColumns($columns){
		$this->columns = $columns;
		return $this;
	}
    public function getColumns(){
        return $this->columns;
    }

	public function getFileds(){
		return $this->fileds;
	}
    public function cleanFileds(){
        $this->fileds = array();
    }

    public function getEntity(){
        return $this->tableEntity;
    }

	public function getProjectionEntitys(){
		return $this->projectionEntries;
	}





    /**
     * 批量设置过滤条件;
     * @param array $example("a"=>1, new Criterion());
     * 数组中可以为 两种数据，如果是普通类型的值 将会转换为  Restrictions::eq($k, $v), 也可以直接传递 Criterion接口类型的值;
     */
    public function setExample($example){
        if(isset($example) && is_array($example)){
            foreach ($example as $k => $v){
                    if(is_object($v) && $v instanceof \my\bq\criterion\MongoCriterion){
                        $this->add(Criteria::_AND, $v);
                    }else{
                        $this->add(Criteria::_AND, Restrictions::eq($k, $v));
                    }
            }
        }
        return $this;
    }







/*	public function getSelectQuery($isSubQuery = false,$isAppend = false){

		$selectQuery = "";
		$projectionQuery = null;
		$aheadString="";
		if(!$isSubQuery){
			$projectionQuery = $this->getProjectionQuery();
			$aheadString = $projectionQuery?("select ".$projectionQuery." "):"select ";
		}

		$cache = CacheManager::getInstance();
		$join_table = $cache->get('join_table');
		if($this->fileds && ($this->getAlias() == self::ROOT_ALIAS ||(is_array($join_table) && in_array($this->alias, $join_table)))){

			if($this->fileds[0] == '*') $this->fileds = $this->columns;
			foreach ($this->fileds as $filed){

				$selectQuery .= ((($selectQuery || ($isSubQuery && $isAppend)) ?', ':$aheadString).(($projectionQuery && !$isSubQuery)?', ':'').$this->getAlias().'.');
				if(is_object($filed)){
					$selectQuery .= ($filed->getPropertyName().' as '.$this->getAlias().'__'.trim($filed->getAlias(),'`'));
				}else{
					$selectQuery .= $filed.' as '.$this->getAlias().'__'.trim($filed,'`');
				}
			}
		}else{
			$selectQuery .= $aheadString;
			//$selectQuery = ($isSubQuery)?", ".$this->getAlias().".*": $aheadString.$this->getAlias().".* ";
		}

		if($this->criterias)
			foreach ($this->criterias as $criteria){
			$selectQuery .= ((($selectQuery || $isSubQuery)?'':$aheadString).$criteria->getSelectQuery(true,($projectionQuery||$this->fileds)));
		}

		return $selectQuery;
	}*/

	public function createProperty($property){
		return new property($property,$this->getAlias());
	}

    /**
     * 清除limit;
     */
	public function cleanLimit(){
		$this->fetchSize = 0;
		$this->firstResult = 0;
	}

    /**
     * 清除 Criterion 所有的实现类;
     */
    public function cleanCriterion(){
        $this->criterionEntries = array();
    }

    /**
     * 清除所有 Order;
     */
    public function cleanOrder(){
        $this->orderEntries = array();
    }


	public function fetchRelationData($tableEntity,$config){

		$entityCfg =  $tableEntity->getConfig();
		$key = $tableEntity->$config['key'];

        $config['class'] = new $config['class']();

		$fileds = $config['class']->getFileds();
		$relateCfg =  $config['class']->getConfig();
		if($fileds[0] == '*'){
			$fileds = $relateCfg['columns'];
		}

		$crit = null;
		if(isset($config['criteria']) && is_object($config['criteria'])){
			$crit = $config['criteria'];
		}else{
			$crit = new self($config['class']);
			$crit->setFileds($fileds);
		}


		$crit->add(Criteria::_AND, Restrictions::eq($config['column'], $key));

		$cache = CacheManager::getInstance();

/*
        缓存机制需要实现 by Alan 20130301
        $sql = $crit->sql();
		$parmas = $crit->getParameters();

		$data = $cache->get(CriteriaQuery::getDumpSQL($sql, $parmas));
		if($data){
			return $data;
		}*/

		$totalNum = 0;
		$fileds = $crit->getFileds();
		$projectionEntitys = $crit->getProjectionEntitys();
		$dataPager = $crit->getDataPager(); //获取分页器

		if(isset($projectionEntitys) && count($projectionEntitys) > 0){
			$crit->cleanFileds();
			$crit->cleanLimit();
			$rs1 = $crit->_array(false);
			$totalNum = @current(@current($rs1));

			if(is_object($dataPager)){
				$dataPager->setTotalNum($totalNum);
			}

			$crit->cleanProjection();
		}

		$crit->setFileds($fileds);
		if(is_object($dataPager)){
			$crit->setFirstResult($dataPager->getFirstResult());
			$crit->setFetchSize($dataPager->getPageSize());
		}

		$rs = $crit->_array(false);

		if($rs){

			$class = get_class($config['class']);

			if($config['relation'] == 'one-to-one'){

				$rs = current($rs);
				$data = new $class();
                $data->setFileds($fileds);
				foreach($rs as $k => $v){
					$data->$k = $v;
				}
				//检查多级关联
				$cfgl = $data->getRelationPro();
				if(isset($cfgl) && is_array($cfgl)){
					foreach ($cfgl as $pro =>$cfg){
						if(!isset($cfg['lazy']) || !$cfg['lazy'] == true){
							$data->$cfg['name'] = $this->fetchRelationData($data,$cfg);
						}
					}
				}

				$data->setCriteria($this);

				//$cache->set(CriteriaQuery::getDumpSQL($sql, $parmas),$data);

				return $data;

			}else{


				$dataList = array();
				foreach ($rs as $i => $item){
					$data = new $class();
                    $data->setFileds($fileds);
					foreach($item as $k => $v){
						$data->$k = $v;
					}
					$data->setCriteria($this);

					//检查多级关联
					$cfgl = $data->getRelationPro();
					if(isset($cfgl) && is_array($cfgl)){
						foreach ($cfgl as $pro =>$cfg){
							if(!isset($cfg['lazy']) || !$cfg['lazy'] == true){
								$data->$cfg['name'] = $this->fetchRelationData($data,$cfg);
							}
						}
					}
					array_push($dataList, $data);
				}

				return $dataList;

				}
			}
		//}

		return null;
	}

	// to object
	public function dataToObject($tableEntitys,$data){

		if($data && isset($tableEntitys) && is_array($tableEntitys)){

			$tableEntity = null;
            $class = get_class($this->tableEntity);
            $tableEntity = new $class();
            $tableEntity->setCriteria($this);
            $tableEntity->setFileds($this->getFileds());
            $columns = $this->getColumns();
            foreach ($data as $k =>$v){
                if(in_array($k,$columns)){
                    $tableEntity->$k = $v;
                }else{
                    $tableEntity->fullSet(array($k=>$v));
                }
			}
			//get relation data
			$relationCfg  = $tableEntity->getRelationPro();

			foreach ($relationCfg as $var => $conf){
				if(!isset($conf['lazy']) || !$conf['lazy'] == true){
					$tableEntity->$var = $this->fetchRelationData($tableEntity,$conf);
				}
			}


			return $tableEntity;
		}
		return null;
	}

	public function sql(&$returnParameters = ""){

	}

	public function getParameters(){


        $this->parameters = array();
        foreach ($this->criterionEntries as $k=> $criterion){
            $param  = $criterion[1]->toMongoParam($this);

            if($param && is_array($param)){

                $this->parameters = array_merge($this->parameters,$param);

            }

        }

        return $this->parameters;
	}

    /**
     * add Data Translater
     * @param Translater $translater
     */
    public function addTranslater($translater){
        $translater->setCriteria($this);
        array_push($this->translaters, $translater);
        return $this;
    }



    public function setModifier($modifier){
        $this->modifier = $modifier;
    }


    /**
     * dump 结果
     * @param bool $toObject 转换为实体;
     * @return array
     * @throws
     */

	public function _array($toObject = true){

        if($this->tableIns == null){
            try{
                $mongoManager = \my\bq\mdbao\MongoManager::getInstance();
                $this->mongoDatabase = $mongoManager->getDatabase($this->tableEntity);
            }catch (MongoConnectionException $e){
                throw $e;
            }
        }

		$parmas = $this->getParameters();

        $order = $this ->getOrderQuery();
        $cursor = null;

        //fields;
        $fields = $this->getFileds();
        $fetchFields = null;
        if(!($fields && $fields[0] == "*")){
            if(is_array($fields)){
                foreach($fields as $k => $field){
                    unset($fields[$k]);
                    if(is_object($field)){
                        $fields[$field->getPropertyName()] = true;
                    }else{
                        $fields[$field] = true;
                    }
                }
                $fetchFields = $fields;
            }
        }

        if(Configuration::$SHOW_MONGO_QUERY){
            $_Lginfo = array('query'=>$parmas);
            if($order){
                $_Lginfo['order'] = $order;
            }
            if($this->fetchSize > 0){
                $_Lginfo['skip'] = array($this->firstResult,$this->fetchSize);
            }
            if($fetchFields != null){
                $_Lginfo['fields'] = array($fetchFields);
            }
            Log::writeMsg(Log::NOTICE,"MongoQuery SEARCH :[".$this->table."]  ".var_export($_Lginfo,true));
        }

        if($this->modifier!=null){
            $cursor = $this->mongoDatabase->command(array( 'findandmodify' => $this->table,
                'query'=> $parmas,
                'update'=> $this->modifier));
            if($cursor['lastErrorObject']['err']!=null){
                Log::writeMsg(Log::ERROR,"MongoERR:[".$this->table."]".var_export($cursor['lastErrorObject'],true));
                throw new \Exception($cursor['lastErrorObject']['err'],$cursor['lastErrorObject']['code']);
            }

        }else{
            $this->tableIns = $this->mongoDatabase->selectCollection($this->table);
            $cursor =$this->tableIns->find($parmas);
        }


        //has order
        if(is_array($order) && count($order)>0){
            $cursor->sort($order);
        }

        //has limit
        if($this->fetchSize > 0){
            $cursor->skip($this->firstResult)->limit($this->fetchSize);
        }

        //fields;
        if($fetchFields != null){
            $cursor->fields($fetchFields);
        }

        $dataArray = array();
        foreach($cursor as $cur){
            if(!$cur['_id']) continue;
            $d = array();
            $d['_id'] = $cur['_id'];

            if(($fields && $fields[0] == "*")){
                $this->fileds = $this->columns;
            }

            foreach($this->fileds as $filed){
                if(is_object($filed)){
                    $f = explode('.',$filed->getPropertyName());
                    if(is_array($f)){
                        $v = $cur[$f[0]];
                        unset($f[0]);
                        foreach($f as $i){
                               $v = $v[$i];
                        }
                        $d[$filed->getAlias()] =  $v;
                    }
                }else{
                    $d[$filed] =  $cur[$filed];
                }
            }
            $dataArray[] = $d;
        }

		if($toObject){

                $cache = CacheManager::getInstance();
                $tableEntitys = $cache->get('tableEntity');
                if(!$tableEntitys)$tableEntitys = array($this->tableEntity);


                $transData = $this->translaters;

                if(is_array($transData) && $transData[0]){
                    foreach ($dataArray as $k =>$data){
                        foreach($transData as $dataTranslater){
                            $property = $dataTranslater->getProperty();
                            if(!isset($data[$property->getPropertyName()])) break;
                            $value = $data[$property->getPropertyName()];
                            if($value){
                                $data[$property->getAlias()] = $dataTranslater->translate($value);
                                $dataArray[$k] = $data;
                            }
                        }
                    }
                }

                if($tableEntitys){
					foreach ($dataArray as $k =>$data){

						//if(Relation::$hasRelation) //has relation
						//to object
						$dataArray[$k] = $this->dataToObject($tableEntitys,$data);
					}
				}
			}
			Relation::$hasRelation = false;
			CacheManager::getInstance()->clean();

        return $dataArray;
	}



    public function setCommand($command){
            $this->command = $command;
    }

    public function getCommand($command){
        $this->command['query'] = $this->getParameters();
        return $this->command;
    }



    /**
     * 统计记录行数;
     * @param bool $countAll
     * @return array
     * @throws
     */

    public function _count($countAll = true){

       if($this->tableIns == null){
           try{
               $mongoManager = \my\bq\mdbao\MongoManager::getInstance();
               $this->mongoDatabase = $mongoManager->getDatabase($this->tableEntity);
               $this->tableIns = $this->mongoDatabase->selectCollection($this->table);
           }catch (MongoConnectionException $e){
               throw $e;
           }
       }

        $parmas = $this->getParameters();

        //$order = $this ->getOrderQuery();

        Log::writeMsg(Log::NOTICE,"MongoQuery:[".$this->table."]".var_export($parmas,true));

        $cursor = $this->tableIns->find($parmas)->count(!$countAll);
/*        if(is_array($order) && count($order)>0){
            $cursor->sort($order);
        }*/

        if($this->fetchSize > 0){
            $cursor->skip($this->firstResult)->limit($this->fetchSize);
        }

        return $cursor;
    }



}