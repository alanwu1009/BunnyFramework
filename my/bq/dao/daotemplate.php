<?php
namespace my\bq\dao;

use my\bq\dao\DaoSupport;
use my\bq\dao\PdoManager; 
use my\bq\criterion\CriteriaImpl;
use my\bq\criterion\Criteria;
use my\bq\criterion\Restrictions;
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
			foreach($columns as $column){
				if($entity->$column != null){
					$criteria->add(Criteria::_AND, Restrictions::eq($column, $entity->$column));
				}
			}
			return @current($this->findAll($criteria));
		}
	}	
	
	
	/**
	 * 指定Criteria 查询数据; SQL语句查询数组,返回为数据数组,不提供实体绑定;
	 * @param $criteria 
	 * @return 绑定实体后的数组对象;
	 */	
	
	public function findAll($criteria){
		return $criteria->_array();
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
	
	
}