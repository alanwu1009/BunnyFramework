<?php
namespace my\bq\criterion;
use my\bq\common\Configuration;
use my\bq\common\Log;
use my\bq\dao\PdoManager;
class CriteriaImpl implements Criteria{

	private $pdo;

	private $criterionEntries =array();
	private $projectionEntries = array();
	private $joinEntries = array();
	private $orderEntries = array();
	private $groupEntries = array();
	private $relationEntries = array();
    private $translaters = array();
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
	
	private $dataPager; //DataPager

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
    public function setOrders($orders){
        $this->orderEntries = $orders;
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


	private function build(){
		$query = "";
		foreach ($this->criterionEntries as $criterion){
			$singleUseingQuery = $criterion[1]->toSqlString($this);
			$query.= ($query || $this->getAlias() != self::ROOT_ALIAS)?($criterion[0].$singleUseingQuery):$singleUseingQuery;
		}
		$this->fullQuery = $query;
		if($this->criterias)
			foreach ($this->criterias as $criteria){
			$this->fullQuery .= $criteria->getQuery();
		}

	}

	public function buildOrderQuery(){

		$orderQuery = "";
		//get current orderEntries;
		$orderEntries = $this->getOrderEntries();
		foreach ($orderEntries as $order){
			$singleUseingQuery = $order->toSqlString($this);
			$orderQuery.= ($orderQuery || $this->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
		}
		unset($orderEntries);
		unset($singleUseingQuery);
		unset($order);

		//get orderEntries from criterias;
		foreach ($this->criterias as $criteria) {
			$criterias = $criteria->getCriterias();
			foreach ($criterias as $cri){
				$orderEntries = $cri->getOrderEntries();
				foreach ($orderEntries as $order){
					$singleUseingQuery = $order->toSqlString($cri);
					$orderQuery.= ($orderQuery || $cri->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
				}
			}
			unset($orderEntries);
			unset($singleUseingQuery);


			$orderEntries = $criteria->getOrderEntries();
			foreach ($orderEntries as $order){
				$singleUseingQuery = $order->toSqlString($criteria);
				$orderQuery.= ($orderQuery || $criteria->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
			}
		}
		$this->fullOrderQuery .= $orderQuery;

	}

	public function buildJoinQuery(){

		$joinQuery = "";
		//get current orderEntries;
		$joinEntries = $this->getJoinEntries();
		foreach ($joinEntries as $join){
			$singleUseingQuery = $join->toSqlString($this);
			$joinQuery.= $singleUseingQuery;
		}
		unset($joinEntries);
		unset($singleUseingQuery);
		unset($join);

		//get orderEntries from criterias;
		foreach ($this->criterias as $criteria) {
			$criterias = $criteria->getCriterias();
			foreach ($criterias as $cri){
				$joinEntries = $cri->getJoinEntries();
				foreach ($joinEntries as $join){
					$singleUseingQuery = $join->toSqlString($cri);
					$joinQuery.= $singleUseingQuery;
				}
			}
			unset($orderEntries);
			unset($singleUseingQuery);


			$joinEntries = $criteria->getjoinEntries();
			foreach ($joinEntries as $join){
				$singleUseingQuery = $join->toSqlString($criteria);
				$joinQuery.= $singleUseingQuery;
			}
		}
		$this->fullJoinQuery .= $joinQuery;
	}

	public function buildGroupQuery(){

		$groupQuery = "";
		$groupEntries = $this->getGroupEntries();

		foreach ($groupEntries as $group){
			$singleUseingQuery = $group->toSqlString($this);
			$groupQuery.= ($groupQuery || $this->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
		}
		unset($groupEntries);
		unset($singleUseingQuery);

		//get orderEntries from criterias;
		foreach ($this->criterias as $criteria) {
			$criterias = $criteria->getCriterias();
			foreach ($criterias as $cri){
				$groupEntries = $cri->getGroupEntries();
				foreach ($groupEntries as $group){
					$singleUseingQuery = $group->toSqlString($cri);
					$groupQuery.= ($groupQuery || $cri->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
				}
			}
			unset($groupEntries);
			unset($singleUseingQuery);

			$groupEntries = $criteria->getGroupEntries();
			foreach ($groupEntries as $group){
				$singleUseingQuery = $group->toSqlString($criteria);
				$groupQuery.= ($groupQuery || $criteria->getAlias() != self::ROOT_ALIAS)?(', '.$singleUseingQuery):$singleUseingQuery;
			}
		}
		$this->fullGroupQuery .= $groupQuery;
	}

	private function buildProjectionQuery(){
		$query = "";
		foreach ($this->projectionEntries as $projection){
			$singleUseingQuery = $projection->toSqlString($this);
			$query.= ($query || $this->getAlias() != self::ROOT_ALIAS)?(", ".$singleUseingQuery):$singleUseingQuery;
		}
		$this->fullProjectionQuery = $query;
		if($this->criterias)
			foreach ($this->criterias as $criteria){
			$this->fullProjectionQuery .= $criteria->getProjectionQuery();
		}
	}

	/**
	 * 批量设置过滤条件;
	 * @param array $example("a"=>1, new Criterion());
	 * 数组中可以为 两种数据，如果是普通类型的值 将会转换为  Restrictions::eq($k, $v), 也可以直接传递 Criterion接口类型的值;
	 */
	public function setExample($example){

		if(isset($example) && is_array($example)){

			foreach ($example as $k => $v){
				if($v instanceof \my\bq\criterion\Criterion){
					$this->add(Criteria::_AND, $v);
				}else{
					$this->add(Criteria::_AND, Restrictions::eq($k, $v));
				}
			}
		}
		return $this;
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
		$this->buildOrderQuery();
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

	public function createCriteria($entityClass,$join = null){
		$criteria = new CriteriaImpl(new $entityClass());
		$entity_confg = $entityClass->getConfig();
		$criteria->alias = $entity_confg['alias'];
		array_push($this->criterias, $criteria);

        if(is_object($join)){
            $join->setCriteria($criteria);
            $this->addJoin($join);
        }
		return $criteria;
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
        $this->tableEntity->setFileds($this->fileds);
		return $this;
	}

	public function setColumns($columns){
		$this->columns = $columns;
		return $this;
	}

	public function getFileds(){
		return $this->fileds;
	}


	public function getProjectionEntitys(){
		return $this->projectionEntries;
	}

	public function getSelectQuery($isSubQuery = false,$isAppend = false){

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
	}

	public function createProperty($property){
		return new property($property,$this->getAlias());
	}


    /**
     * 判断指定Crit是否被当前Crit建立Join链接
     * @param unknown_type $critObj
     */
    public function isHasJoin($critObj){
        if($this->joinEntries){
            foreach($this->joinEntries as $entrie){
                if($entrie->getJoinCriteria() === $critObj)
                    return true;
                else
                    return false;
            }
        }
    }

	//clean query;
	public function cleanFileds(){
		$this->fileds = array();
		$this->fullProjectionQuery = "";
		$this->fullJoinQuery = "";
		$this->fullGroupQuery = "";
		$this->fullOrderQuery = "";
		$this->fullQuery = "";
		$this->sqlString = "";
		CriteriaQuery::$parameters = array();
	}

	//clean query;
	public function cleanProjection(){
		$this->projectionEntries = array();
		$this->fullProjectionQuery = "";
		$this->fullJoinQuery = "";
		$this->fullGroupQuery = "";
		$this->fullOrderQuery = "";
		$this->fullQuery = "";
		$this->sqlString = "";
		CriteriaQuery::$parameters = array();
	}

    public function cleanOrder(){
        $this->orderEntries = array();
    }



	public function cleanQuery(){
		$this->criterionEntries = array();
		$this->fullJoinQuery = "";
		$this->fullGroupQuery = "";
		$this->fullOrderQuery = "";
		$this->fullQuery = "";
		$this->sqlString = "";
	}

	public function cleanLimit(){
		$this->fetchSize = 0;
		$this->firstResult = 0;
	}


    public function getTableEntity(){
        return $this->tableEntity;
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
			$crit = new CriteriaImpl($config['class']);
			$crit->setFileds($fileds);
		}
		$crit->add(Criteria::_AND, Restrictions::eq($config['column'], $key));

        $cache = CacheManager::getInstance();

		$totalNum = 0;
		$fileds = $crit->getFileds();

		$projectionEntitys = $crit->getProjectionEntitys();
		$dataPager = $crit->getDataPager(); //获取分页器

		if(isset($projectionEntitys) && count($projectionEntitys) > 0){
			$crit->cleanFileds();
			$crit->cleanLimit();


            $sql = $crit->sql();
            $parmas = $crit->getParameters();

            $rs1 = $cache->get(CriteriaQuery::getDumpSQL($sql, $parmas));
            if(!$rs1){
                $rs1 = $crit->_array(false);
                $cache->set(CriteriaQuery::getDumpSQL($sql, $parmas),$rs1);
            }

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

        $sql = $crit->sql();
        $parmas = $crit->getParameters();

        $data = $cache->get(CriteriaQuery::getDumpSQL($sql, $parmas));
        if($data){
            if($data ===true)
                return null;
            return $data;
        }

        $rs = $crit->_array(false);

        if(!$rs){ //如果为空表示数据库没有数据,但缓存还是需要写;
            $cache->set(CriteriaQuery::getDumpSQL($sql, $parmas),true);
        }

		if($rs){

			$class = get_class($config['class']);
			if($config['relation'] == 'one-to-one'){
				$rs = current($rs);
				$data = new $class();
                $data->setFileds($fileds);
				foreach($rs as $k => $v){
					$k = substr($k, strpos($k, "___")+3);
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
				$cache->set(CriteriaQuery::getDumpSQL($sql, $parmas),$data);

                $data->setIsNewRecord(false);
				return $data;

			}else{


				$dataList = array();
				foreach ($rs as $i => $item){
					$data = new $class();
                    $data->setFileds($fileds);
					foreach($item as $k => $v){
						$k = substr($k, strpos($k, "___")+3);
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
                    $data->setIsNewRecord(false);
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
			$n = 0;
			foreach ($data as $k =>$v){
				$_f = explode('__', $k);
				if($n==0){
					$class = null;
					if($_f[0] == 'this'){
						$class = get_class($this->tableEntity);
					}else{
						$class = get_class($tableEntitys[$_f[0]]);
					}

                    if($class == 'my\bq\criterion\CriteriaImpl'){
                        $class = $class = get_class($this->tableEntity);
                    }

					if($class){
						$tableEntity = new $class();
                        $tableEntity->setFileds($this->getFileds());
					}

				}
				$p = trim($_f[1],'_');
				if($_f[0] == 'this'){
					$tableEntity->$p = $v;
				}else{
					//将未知数据填入实体集合空间
					$tableEntity->fullSet(array($k=>$v));
				}

				$tableEntity->setCriteria($this);
				$n++;
			}


			//get relation data
			$relationCfg  = $tableEntity->getRelationPro();

			foreach ($relationCfg as $var => $conf){
				if(!isset($conf['lazy']) || !$conf['lazy'] == true){
					$tableEntity->$var = $this->fetchRelationData($tableEntity,$conf);
				}
			}

            $tableEntity->setIsNewRecord(false);

			return $tableEntity;
		}
		return null;
	}

	public function sql(&$returnParameters = ""){
		if($this->sqlString) return $this->sqlString;

		$query  = $this->getQuery();
		$groupQuery = $this->getGrounpQuery();
		$orderQuery = $this->getOrderQuery();
		$joinQuery = $this->getJoinQuery();

		$sql = $this->getSelectQuery();
		$sql .= ' from '.$this->table.' as '.$this->getAlias();

		if($joinQuery)
			$sql .= $this->fullJoinQuery;

		if($query){
            if(substr($query, 0,4) == " and"){
                $query = substr($query, 4);
            }
            $sql.=' where '.$query;
        }
		if($groupQuery){
			if(!$query) $sql.= ' where true ';
			$sql.= ' group by ' . $groupQuery;
		}
		if($orderQuery){
			if(!$query && !$groupQuery) $sql.= ' where true ';
			$sql.= ' order by ' . $orderQuery;
		}

		if($this->fetchSize > 0){
			$sql.= ' limit ' . $this->firstResult.','.$this->fetchSize;
		}

		$this->parameters = CriteriaQuery::$parameters;
		CriteriaQuery::$parameters = array();
		$returnParameters = $this->parameters;
		$this->sqlString=$sql;

		return ($this->sqlString);
	}

	public function getParameters(){
		return $this->parameters;
	}

	public function setPdo($pdo){
		$this->pdo = $pdo;
		return $this;
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

    /**
     * 取根CRITERIA相链接的所有Translater
     */
    public function getRootAndJoinsTranslater(){

        $transl = $this->translaters;

        if($this->joinEntries){
            foreach($this->joinEntries as $join){
                $tObj = $join->getJoinCriteria()->getRootAndJoinsTranslater();
                array_push($transl, $tObj);
            }
        }
        return $transl;
    }



	//需要PDO实现 getRows方法;
	public function _array($toObject = true){

		$pdoManager = PdoManager::getInstance();
		$pdo = $pdoManager->getPdo($this->tableEntity);
		$sql = $this->sql();
		$parmas = $this->getParameters();

		$dataArray = $pdo->getRows($sql,$parmas);

		if($toObject){

			$cache = CacheManager::getInstance();
			$tableEntitys = $cache->get('tableEntity');
			if(!$tableEntitys)$tableEntitys = array($this->tableEntity);


            $transData = $this->getRootAndJoinsTranslater();

            if(is_array($transData) && $transData[0]){
                foreach ($dataArray as $k =>$data){
                    foreach($transData as $dataTranslater){
                        if($dataTranslater){
                            $property = $dataTranslater->getProperty();

                            $dataKey = $this->getAlias()."__".$property->getPropertyName();
                            if(!array_key_exists($dataKey,$data)) continue;

                            $data[$this->getAlias()."__".$property->getAlias()] = $dataTranslater->translate($data[$dataKey]);
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

}