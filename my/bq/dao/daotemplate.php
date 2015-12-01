<?php
namespace my\bq\dao;

use my\bq\common\Configuration;
use my\bq\common\Log;
use my\bq\criterion\CriteriaQuery;
use my\bq\dao\DaoSupport;
use my\bq\dao\PdoManager; 
use my\bq\criterion\CriteriaImpl;
use my\bq\criterion\Criteria;
use my\bq\criterion\Restrictions;
use my\bq\criterion\Projections;
use dao\OrderDao;

class DaoTemplate{
	
	/**
	 * 将实体持久化到数据库;
	 * @param object $entity
	 * @return ini 记录ID;
	 */	
	public function save($entity){
		$arrayId = array();
		if(is_array($entity)){
			foreach ($entity as $object){
				$id = DaoSupport::saveEntity($object);
				array_push($arrayId, $id);
			}
			
		}else{
			return DaoSupport::saveEntity($entity);
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
				$id = DaoSupport::replaceEntity($object);
				array_push($arrayId, $id);
			}
				
		}else{
			return DaoSupport::replaceEntity($entity);
		}
		return $arrayId;
	}
	
	
	/**
	 * 按实体更新数据库，若不指定 where 则按给定主键删除;
	 * @param object $entity
	 * @param array $where
	 */	
	
	public function delete($entity, $where = null){
		return DaoSupport::deleteEntity($entity, $where);
	}
	
	/**
	 * 按实体更新数据库，若不指定 where 则按给定主键更新;
	 * @param object $entity
	 * @param array $where
	 */
	public function update($entity,$where = null){
		return DaoSupport::updateEntity($entity,$where);
	}
	
	public function find($criteria){
        $criteria->setFirstResult(0)->setFetchSize(1); //set limit
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
			
			$criteria = new CriteriaImpl($entity);
			$criteria->setFileds($entity->getFileds()); //要查询的字段

            //如果该实体关联了分页器，则使用分页器进行分页查询;
            $dataPager = $entity->getDataPager();
            if($dataPager != null){
                $criteria->setDataPager($entity->getDataPager());
            }
			//set order
			if(is_object($order)){
				$criteria->addOrder($order);
			}else{
				if(is_array($order))
					foreach($order as $orderItem)
					$criteria->addOrder($orderItem);
			}			


			//add Restrictions;
/*			foreach($columns as $column){
				if($entity->$column != null){
					$criteria->add(Criteria::_AND, Restrictions::eq($column, $entity->$column));
				}
			}*/
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
				
			$criteria = new CriteriaImpl($entity);
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
/*			foreach($columns as $column){
				if($entity->$column != null){
                    var_dump($column,$entity->$column);
					$criteria->add(Criteria::_AND, Restrictions::eq($column, $entity->$column));
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
            $orderEntries = $criteria->getOrderEntries();
            $criteria->cleanOrder();
			$criteria->cleanFileds();

            $entityConf = $criteria->getTableEntity()->getConfig();
            $criteria->setFileds(explode(",",$entityConf['id']));
			$criteria->addProjection(Projections::rowCount());

			$rs1 = $criteria->_array(false);
			$totalNum = @current(@current($rs1));
			$dataPager->setTotalNum($totalNum);
			$criteria->cleanProjection();
			$criteria->setFileds($fileds);
            $criteria->setOrders($orderEntries);
			$criteria->setFirstResult($dataPager->getFirstResult());
			$criteria->setFetchSize($dataPager->getPageSize());
			
		}
		
		
		return $criteria->_array();
	}
	
	
	
	
	
    /**
     * 可以指定条件筛选范本来查询实体;
     * @param Object $entity 需要查询的实体对象
     * @param $example 指定条件样本
     * @param array<Order> $orders  指定排序规则; 可以为 Order对像的数组;
     * @param DataPager $dataPager 数据分页器
     * @param array<Translater> $translaters 数据转义器
     * @return array<Entity>;
     */
    public function findByExample($entity,$example, $order = null, $dataPager = null,$translaters = null){

        if(is_object($entity)){
            $config = $entity->getConfig();
            $columns = $config['columns'];
			
            $criteria = new CriteriaImpl($entity);
            $criteria->setFileds($entity->getFileds()); //瑕佹煡璇㈢殑瀛楁
            $criteria->setExample($example);
            //set order
            if(is_object($order)){
                $criteria->addOrder($order);
            }else{
                if(is_array($order))
                    foreach($order as $orderItem)
                        $criteria->addOrder($orderItem);
            }

            if(is_object($translaters)){
                $criteria->addTranslater($translaters);
            }else{
                if(is_array($translaters))
                    foreach($translaters as $translater)
                        $criteria->addTranslater($translater);
            }

            if($dataPager){
                $criteria->setDataPager($dataPager);
            }
			
            return $this->findAll($criteria);
        }
    }
	
	
	/**
	 * 指定SQL语句查询数组
	 * @param String $sql
	 * @return 返回为数据数组,不提供实体绑定;
	 */
	public function findBySQL($sql){
		$pdoManager = PdoManager::getInstance();
		$pdo = $pdoManager->getPdo();
		return $pdo->query($sql);
	}

    public function getPdoTemplate($master = false){
        $pdoManager = PdoManager::getInstance();
        $pdo = $pdoManager->getPdo($master);
        return $pdo;
    }
	
	/**
	 * 执行指定SQL语句
	 * @param string $sql
	 * @param string $binds
	 */
	public function execBySql($sql,$binds){
        if(\my\bq\common\Configuration::$SHOW_SQL){
            \my\bq\common\Log::writeMsg(\my\bq\common\Log::NOTICE,$sql);
        }
        try{
            $pdoManager = PdoManager::getInstance();
            $pdo = $pdoManager->getPdo();
            $sth = $pdo->prepare($sql);
            $pdo::bindValue($sth, $binds);
            $out = $pdo->execute($sth);
            $sth->closeCursor();
        }catch (\PDOException $e){
            if(\my\bq\common\Configuration::$SHOW_CORE_EXCEPTION){
                \my\bq\common\Log::writeMsg(\my\bq\common\Log::ERROR,$e->getMessage());
            }
            throw new DAOException(DAOException::DB_EXEC_EXCEPTION,DAOException::DB_EXEC_EXCEPTION_MESSAGE);
        }
		return $out; 	
	}



    /**
     * 字段自增;
     */
    public function incHolder($entity,$field,$num = 1){

        $entityCfg = $entity->getConfig();
        $id = explode(",",$entityCfg['id']);
        $parseWhereStr = '';
        foreach ($id as $k){
            if($parseWhereStr!="")$parseWhereStr.=" and ";
            $parseWhereStr.=("`".$k."` =".$entity->$k);
        }
        if($parseWhereStr){
            $sql = "update ".$entity->getTableName()." set $field = $field + ? where ".$parseWhereStr;
            return $this->execBySql($sql,array($num));
        }
    }




}