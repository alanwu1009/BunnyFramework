<?php
namespace my\bq\dao;

use \PDO;
use \PDOStatement;
use \my\bq\criterion\CriteriaQuery;
use \my\bq\common\Configuration;
use \my\bq\common\Log;

/**
 * 扩展了的pdo
 *
 */
class PDOext extends PDO {
	private $_lastErrorInfo = '';
	private $_queryTime = '';
	private $_sth = '';
	private $_debug = false;
	
	public static $keys = array('key', 'type', 'condition', 'div', 'int1', 'int2', 'int3', 'int4', 'int8', 'status'); //mysql 常用关键字
	
	public function __construct($dsn, $userName, $passowrd, $charSet='utf8') {
		parent::__construct($dsn, $userName, $passowrd);
		$this->query("set names '$charSet'");
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	/**
	 * 取查询结果的一列
	 * @param string $sql
	 * @param array $binds
	 * @return multitype:string 
	 */
	public function getCol($sql, array $binds=array()) {
		$rows = array();
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		while ($row = $sth->fetchColumn())
			$rows[] = $row;
		$sth->closeCursor();
		return $rows;
	}
	
	
	/**
	 * 取查询结果一个元素 如 [select count(1) as cnt]的 cnt
	 * @param string $sql
	 * @param array $binds
	 * @return string
	 */
	public function getOne($sql, array $binds=array()) {
		return $this->getScaler($sql, $binds);
	}
	
	
	/**
	 * 同getOne
	 * @param string $sql
	 * @param array $binds
	 * @return string
	 */
	public function getScaler($sql, array $binds=array()) {
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetchColumn();
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 取查询结果中的一行
	 * @param unknown_type $sql
	 * @param array $binds
	 * @return mixed
	 */
	public function getRow($sql, array $binds=array()) {
		if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, CriteriaQuery::getDumpSQL($sql, $binds));
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetch();
		$sth->closeCursor();
		return $out;
	}
	

	/**
	 * 取查询结果集
	 * @param unknown_type $sql
	 * @param array $binds
	 * @return multitype:
	 */
	public function getRows($sql, array $binds=array()) {
        if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, CriteriaQuery::getDumpSQL($sql, $binds));
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetchAll();
		$sth->closeCursor();
		return $out;
	}
	
	public function insert($table, array $data) {
		
		$ks = array();
		foreach (array_keys($data) as $k) {
			if (in_array($k, self::$keys))
				$k = "`$k`";
			$ks[] = $k;
		}
		$sqlK = implode(', ', $ks);
		$sqlV = ':'.implode(', :', array_keys($data));
        $sqlV = str_replace("`","",$sqlV);
		$sql = "insert into $table ($sqlK) values ($sqlV)";
		
		if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql);
		
		
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth)?$this->lastInsertId():false;
		$sth->closeCursor();
		return $out;
	}
	
	public function update($table, array $data, $where) {
		if (strlen($where) == 0)
			return false;
			
		$sqlU = 'set ';
		foreach ($data as $v=>$v2) {
			if ($v[0] == ':')
				$v[0] = '';
			if (in_array($v, self::$keys))
				$k = "`$v`";
			else
				$k = $v;
			$sqlU .= "$k=:$v, ";
		}
		$sqlU = trim(trim($sqlU, ' '), ',');
		$sql = "update $table $sqlU where $where";
		if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql);
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth);
		$sth->closeCursor();
		return $out;
	}
	
	public function replace($table, array $data) {
		$ks = array();
		foreach (array_keys($data) as $k) {
			if (in_array($k, self::$keys))
				$k = "`$k`";
			$ks[] = $k;
		}
		$sqlK = implode(', ', $ks);
		$sqlV = ':'.implode(', :', array_keys($data));
        $sqlV = str_replace("`","",$sqlV);
		$sql = "replace into $table ($sqlK) values ($sqlV)";
		if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql);
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth)?$this->lastInsertId():false;
		$sth->closeCursor();
		return $out;
	}
	
	
	public function delete($table, $where) {
		$sql = "delete from $table where $where";
		if(Configuration::$SHOW_SQL)Log::writeMsg(Log::NOTICE, $sql);
		$sth = $this->prepare($sql);
		$out = $this->execute($sth);
		$sth->closeCursor();
		return $out;
	}
	
	public static function bindValue(PDOStatement &$sth, array $binds) {
		
		foreach ($binds as $k=>$v) {
			if (is_int($k)) {
				$sth->bindValue($k+1, $v);
				continue;
			}
			if ($k[0] != ':')
				$k = ':'.$k;
            $k = str_replace("`","",$k);
			$sth->bindValue($k, $v);
		}
	}
	
	public function execute(&$sth, $setFetchAssoc=true) {
		try{
			if ($setFetchAssoc)
				$sth->setFetchMode(PDO::FETCH_ASSOC);
			$this->debug($sth);
			$out = $sth->execute();
		}catch(\PDOException $e){
            if(Configuration::$SHOW_CORE_EXCEPTION){
                Log::writeMsg(Log::ERROR,$e->getMessage());
            }
			throw $e;
		}
		return $out;
	}

	public function lastErrorCode() {
		return $this->_lastErrorInfo? $this->_lastErrorInfo[0]:'';
	}
	
	public function lastError() {
		return $this->_lastErrorInfo? $this->_lastErrorInfo[2]:'';
	}
	
	public function prepare($sql, $driver_options=array()) {
		$this->debugTime();
		return parent::prepare($sql);
	}
	
	public function debugTime() {
		$this->_queryTime = microtime();
	}
	
	public function debug($sth) {
          return ;
		if (Configuration::$DEBUG) {
			//Auth::check('debug_db');
			$queryTime = (microtime() - $this->_queryTime);
			echo '<li>';
			$sth->debugDumpParams();
			printf("cost:[%.4f s]", $queryTime);
			echo '</li>';
			debug_print_backtrace();
		}
		
	}
	
	
}