<?php
namespace my\bq\criterion;
final class Projections {

	public static function distinct($property){
		return new Distinct($property);
	}
	
	public static function rowCount($alias="row_count") {
		return new RowCountProjection($alias);
	}
	
	public static function count($propertyName,$alias="") {
		if(!$alias) $alias = "count_".$propertyName;
		return new CountProjection($propertyName,$alias);
	}
	
	public static function countDistinct($propertyName,$alias="") {
		if(!$alias) $alias = "count_dstinct_".$propertyName;
		return new CountDistinctProjection($propertyName,$alias);
	}
	
	public static function max($propertyName,$alias="") {
		if(!$alias) $alias = "max_".$propertyName;		
		return new AggregateProjection("max", $propertyName,$alias);
	}
	
	public static function min($propertyName,$alias="") {
		if(!$alias) $alias = "min_".$propertyName;
		return new AggregateProjection("min", $propertyName,$alias);
	}
	
	public static function sum($propertyName,$alias="") {
		if(!$alias) $alias = "sum_".$propertyName;
		return new AggregateProjection("sum", $propertyName,$alias);
	}	
	
	public static function avg($propertyName,$alias="") {
		if(!$alias) $alias = "avg_".$propertyName;
		return new AvgProjection($propertyName,$alias);
	}
	
	public static function sql($sql) {
		return new SQLCriterion($sql);
	}

}
