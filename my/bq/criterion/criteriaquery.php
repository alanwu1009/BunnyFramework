<?php
namespace my\bq\criterion;
abstract class CriteriaQuery {

	public static $parameters = array();
	
	public abstract function getColumn($criteria, $propertyPath);
	
	public abstract function getEntityName($criteria);
	
	public abstract function getSQLAlias($subcriteria);

	public abstract function getPropertyName($propertyName);
	
	public abstract function getIdentifierColumns($subcriteria);
	
	public abstract function generateSQLAlias();
	
	public static function getDumpSQL($sql,$params){
		if($sql){
			if(!$params) return $sql;
			reset($params);
			$qSize = count($params);
			for($i=0; $i < $qSize; $i++){
				$pos = strpos($sql, "?");
				if($pos!==false){
					$p = current($params);
					$sql = substr($sql, 0, $pos).(is_int($p)?$p:"'".$p."'").substr($sql, $pos+1);
					array_shift($params);
				}
			}
		}
		return $sql;
	}

	

	
	
}