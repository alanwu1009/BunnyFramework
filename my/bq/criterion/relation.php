<?php
namespace my\bq\criterion;
abstract class Relation {
	public static $hasRelation = false;
	/**
	 * 根据关联关系条件获取数据
	 * @param Pdo connection $pdo
	 * @param Criteria $crit;.
	 */
	public abstract function fetchData($pdo,$crit);
	
	/**
	 *  one to one
	 */
	public static function oneToOne($crit,$lp,$rp){
		self::$hasRelation = true;
		return new SimpleRelation($crit,$lp,$rp,SimpleRelation::ONE_TO_ONE);
	}
	
	public static function oneToMany($crit,$lp,$rp){
		self::$hasRelation = true;
		return new SimpleRelation($crit,$lp, $rp, SimpleRelation::ONE_TO_MANY);
	}
	
	public static function manyToMany($lcrit,$lp,$rcrit,$rp,$rlp,$rrp){
		self::$hasRelation = true;
		return new manyToManyRelation($lcrit,$lp,$rcrit,$rp,$rlp,$rrp);		
	}


}
