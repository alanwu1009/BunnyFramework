<?php
namespace my\bq\mdbao;

interface MongoRecord
{
	/**
	 * 设置查询的超时间时间，默认是20000毫秒
	 * @param unknown_type $timeout
	 */
	public function setFindTimeout($timeout);
	
	/**
	 *  执行查找操作，返回符合条件的第一个结果的指定字段
	 * @param unknown_type $query
	 * @param unknown_type $fields
	 */
	public function findOne($criteria);
	
	/**
	 * 删除符合条件的数据
	 * @param unknown_type $criteria
	 * @param unknown_type $options
	 */
	public function remove($criteria = null,$options = array());
	
	/**
	 * 执行一个查询，返回一个查询结果的迭代器(游标)
	 * （可以当数组使用用for .. as  ...遍历，同时可以用count()方法获得记录总数)
	 * @param unknown_type $query
	 * @param unknown_type $fields
	 * @param unknown_type $options
	 */
	public function find($query = array(), $fields = array(), $options = array());
	
	/**
	 * 执行一个查询，并将符合条件的数据做为对象数组返回
	 * Enter description here ...
	 * @param unknown_type $query
	 * @param unknown_type $fields
	 * @param unknown_type $options
	 */
	public function findAll($query = array(), $fields = array(), $options = array());
	
	/**
	 * 获取一个查询符合条件的记录总数
	 * @param unknown_type $query
	 */
	public function count($query = array());
	
	
	/**
	* 批量更新符合query条件的数据，用fields中指定的字段值
	* @param unknown_type $query  mongo  查询字段 		   array( filed1=>condition1,field2=>condition2,...)
	* @param unknown_type $new_object  要更新的对象值必须继承自BaseMongoRecord，注：如果_id字段有值有可能导致更新失败
	*/
	public function updateAll($query = array() , $new_object , $options = null);
	
}

