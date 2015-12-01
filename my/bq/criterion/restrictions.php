<?php
namespace my\bq\criterion;
class Restrictions {

    /**
     *  等于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpressiond
     */
	public static function eq($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "=");
	}

    /**
     * 不等于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */
	public static function ne($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "<>");
	}

    /**
     * 模糊匹配 (仅限于关系型数据库标准SQL)
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */

    public static function like($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, " like ");
	}

    /**
     * 大于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */
	public static function gt($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, ">");
	}

    /**
     * 小于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */
	public static function lt($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, "<");
	}

    /**
     * 小于等于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */

	public static function le($propertyName, $value) {
		return new SimpleExpression($propertyName,$value, "<=");
	}

    /**
     * 大于等于
     * @static
     * @param String $propertyName
     * @param String $value
     * @return SimpleExpression
     */
	public static function ge($propertyName, $value) {
		return new SimpleExpression($propertyName, $value, ">=");
	}

    /**
     * 取两者之间
     * @static
     * @param String $propertyName
     * @param String $lo  bettween 前边的值
     * @param String $hi bettween 后边的值
     * @return BetweenExpression
     */
	public static function between($propertyName, $lo, $hi) {
		return new BetweenExpression($propertyName, $lo, $hi);
	}

    /**
     * 包含多项
     * @static
     * @param String  $propertyName
     * @param $values
     * @return InExpression
     */
	public static function in($propertyName ,$values) {
		return new InExpression($propertyName,$values);
	}
	

    /**
     * 是否为空,注意: 在mongodb中 会将不存在的字段也查询出来;
     * @static
     * @param String $propertyName
     * @return NullExpression
     */
	public static function isNull($propertyName) {
		return new NullExpression($propertyName,"is null");
	}

    /**
     * 查询为空,注意: 在mongodb中 会将不存在的字段也查询出来;
     * @static
     * @param String $propertyName
     * @return NullExpression
     */
	public static function isNotNull($propertyName) {
		return new NullExpression($propertyName,"is not null");
	}	

    /**
     * 数据库两个字段相比较 (仅限于关系型数据库标准SQL)
     * @static
     * @param Property $property
     * @param Property $otherProperty
     * @return PropertyExpression
     */
	public static function eqProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "=");
	}

    /**
     * 数据库两个字段不相等 (仅限于关系型数据库标准SQL)
     * @static
     * @param Property $property
     * @param Property $otherProperty
     * @return PropertyExpression
     */
	public static function neProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<>");
	}

    /**
     * 数据库两个字段相比较 (仅限于关系型数据库标准SQL)
     * @static
     * @param Property $property
     * @param Property $otherProperty
     * @return PropertyExpression
     */
	public static function ltProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<");
	}

    /**
     * 数据库两个字段相比较(小于等于) (仅限于关系型数据库标准SQL)
     * @static
     * @param Property $property
     * @param  Property $otherProperty
     * @return PropertyExpression
     */
	public static function leProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, "<=");
	}

    /**
     * 数据库两个字段相比较(大于指定属性) (仅限于关系型数据库标准SQL)
     * @static
     * @param  Property $property
     * @param Property $otherProperty
     * @return PropertyExpression
     */
	public static function gtProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, ">");
	}


    /**
     * 数据库两个字段相比较(大于等于指定属性) (仅限于关系型数据库标准SQL)
     * @static
     * @param Property $property
     * @param Property $otherProperty
     * @return PropertyExpression
     */
	public static function geProperty($property, $otherProperty) {
		return new PropertyExpression($property, $otherProperty, ">=");
	}

    /**
     * 指定sql子查询 用于判断子查询是否有返回值, 匹配有返回值(仅限于关系型数据库标准SQL)
     * @static
     * @param $sql 原生 SQL 字串
     * @return SqlExists
     */
    public static function sqlExists($sql){
		return new SqlExists($sql, true);
	}


    /**
     * 指定sql子查询 用于判断子查询是否有返回值, 匹配没有返回值(仅限于关系型数据库标准SQL)
     * @static
     * @param $sql 原生 SQL 字串
     * @return SqlExists
     */

    public static function notSqlExists($sql){
		return new SqlExists($sql, false);
	}

    /**
     * 匹配字段存在的结果
     * @static
     * @param $propertyName 属性名称
     * @return Exists
     */

    public static function exists($propertyName){
        return new Exists($propertyName, true);
    }

    /**
     * 匹配字段不存在的结果
     * @static
     * @param String $propertyName 属性名称
     * @return Exists
     */

    public static function notExists($propertyName){
        return new Exists($propertyName, false);
    }

    /**
     *  多条件逻辑或查询  (仅限于关系型数据库标准SQL)
     * @static
     * @param Criterion $lhs
     * @param Criterion $rhs
     * @param Criterion $ahs
     * @return LogicalExpression
     */
    public static function _and($lhs, $rhs,$ahs = null) {
		return new LogicalExpression($lhs, $rhs,$ahs,"and");
	}

    /**
     * 多条件逻辑与查询  (仅限于关系型数据库标准SQL)
     * @static
     * @param Criterion $criterions
     * @return LogicalExpression
     */

    public static function _or($criterions){
		return new LogicalExpression($criterions,"or");
	}

    /**
     * 附加条件  (仅限于关系型数据库标准SQL)
     * @static
     * @param Criterion $ahs 需要附加的条件
     * $T 与左向的关系 Criteria::_AND 或 Criteria::_OR
     */

    public static function append($ahs, $T){
		return new AppendExpression($ahs, $T);
	}


    /**
     * 使用原生sql查询 (仅限于关系型数据库标准SQL)
     * @static
     * @sql 原生sql语句
     */

	public static function sql($sql){
		return new SQLCriterion($sql);
	}

    /**
     * 使用 mongo原生查询;
     * @static
     * @param $query
     * @return MongoExpression
     *
     *  #######################################
     * 条件操作符
        $gt : >
        $lt : <
        $gte: >=
        $lte: <=
        $ne : !=、<>
        $in : in
        $nin: not in
        $all: all
        $not: 反匹配(1.3.3及以上版本)
     *
     * ########################################
     */
    public static function mongoQuery($query){
        return new MongoExpression($query);
    }


}
