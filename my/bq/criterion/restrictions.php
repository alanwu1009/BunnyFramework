<?php
namespace my\bq\criterion;
class Restrictions {

	public static function eq($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "=");
	}
	
	public static function ne($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "<>");
	}
	
	public static function like($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, " like ");
	}
	
	public static function gt($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, ">");
	}

	public static function lt($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "<");
	}

	public static function le($propertyName, $value) {
		return new SimpleExpression($propertyName,$value, "<=");
	}

	public static function ge($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, ">=");
	}

	public static function between($propertyName, $lo, $hi) {
		return new BetweenExpression($propertyName, $lo, $hi);
	}

	public static function in($propertyName ,$values) {
		return new InExpression($propertyName,$values);
	}
	

	public static function isNull($propertyName) {
		return new NullExpression($propertyName,"is null");
	}
	
	public static function isNotNull($propertyName) {
		return new NullExpression($propertyName,"is not null");
	}	

	public static function eqProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "=");
	}

	public static function neProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<>");
	}
	
	public static function ltProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<");
	}

	public static function leProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<=");
	}
	
	public static function gtProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, ">");
	}
	
	public static function geProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, ">=");
	}
	
	public static function exists($sql){
		return new Exists($sql, true);
	}
	
	public static function notExists($sql){
		return new Exists($sql, false);
	}
	
	
	
	public static function _and($lhs, $rhs,$ahs = null) {
		return new LogicalExpression($lhs, $rhs,$ahs,"and");
	}

	public static function _or($lhs, $rhs,$ahs = null){
		return new LogicalExpression($lhs, $rhs,$ahs,"or");
	}
	
	public static function append($ahs, $T){
		return new AppendExpression($ahs, $T);
	}
	
	public static function sql($sql){
		return new SQLCriterion($sql);
	}
}
