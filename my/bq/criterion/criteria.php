<?php
/**
 * Bunny Query v2.0
 * @author Alan Wu
 */
namespace my\bq\criterion;
interface Criteria{

	const _AND = " and ";
	const _OR = " or ";
	const JOIN = " inner join ";
	const LEFT_JOIN = " left join ";
	const RIGHT_JOIN = " right join ";

	/**
	 * 向 Criteria 添加条件约束;
	 * @param String $T 与左项逻辑关系，"AND" 或者 "OR";
	 * @param Criterion $criterion 条件约束
	 */
	public function add($T,$criterion);
	
	/**
	 * 向 Criteria 添加 group by 子句 条件;
	 * @param Group $Group 条件约束
	 */
	public function addGroup($group);
	
	/**
	 * 向Criteria添加 Order by 子句条件。
	 * @param Order $order
	 */
	public function addOrder($order);
	
	/**
	 * 设定关联条件功能映射，及聚合函数.
	 * @param Projection $projection
	 */
	public function addProjection($projection);
	
	/**
	 * 设置Criteria表关联。
	 * @param Join $join 
	 */
	public function addJoin($join);
	
	
	/**
	 * 设置Criteria业务关系映射。
	 * @param Relation $relation 
	 */	
	public function addRelation($relation);
	
	/**
	 * 设置开始行号.
	 * @param int $firstResult
	 */
	public function setFirstResult($firstResult);
	
	/**
	 * 设置返回的记录条数.
	 * @param int $fetchSize
	 */
	public function setFetchSize($fetchSize);
	
	/**
	 * 返回SQL语句.
	 */
	public function sql();
	
	/**
	 * 获取执行结果
	 */
	public function _array();
	
	
}
