<?php
namespace mdbao;
use \Iterator as Iterator;
use \Countable as Countable;
class MongoRecordIterator implements Iterator, Countable{

	protected $current; // a PHP5.3 pointer hack to make current() work
	protected $cursor;
	protected $className;
  
	public function __construct($cursor, $className)
	{
		$this->cursor = $cursor;
		$this->className = $className;
		$this->cursor->rewind();
		$this->current = $this->current();
	}
  
	/**
	 * 当前记录对象
	 * @see Iterator::current()
	 */
	public function current()
	{
		$this->current = $this->instantiate($this->cursor->current());
		return $this->current;
	}

	/**
	 * 查询结果总数
	 * @see Countable::count()
	 */
	public function count()
	{
		return $this->cursor->count();
	}
  
	/**
	 * 获取当前结果的 _id字段
	 * @see Iterator::key()
	 */
	public function key()
	{
		return $this->cursor->key();
	}
  
	/**
	 * 移动游标到下一条结果
	 * @see Iterator::next()
	 */
	public function next()
	{
		$this->cursor->next();
	}
	
	/**
	 * 读取游标中的下一条数据
	 * @see http://www.php.net/manual/en/mongocursor.getnext.php
	 */
	public  function getNext(){
		
		$this->current = $this->instantiate( $this->cursor->getNext() );
		return $this->current;
	}
	
	public function hasNext(){
		
		return $this->cursor->hasNext();
	}
  
	public function explain(){
		return $this->cursor->explain();
	}
	
	public function rewind()
	{
		$this->cursor->rewind();
	}
  
	/**
	 * 获得当前游标是否有效
	 * @see Iterator::valid()
	 */
	public function valid()
	{
		return $this->cursor->valid();
	}
	
	/**
	 * 设置当前游标的过期时间
	 * @param unknown_type $ms
	 */
	public function timeout($ms){
		return $this->cursor->timeout($ms);
	}
	

	private function instantiate($document)
	{
		if ($document)
		{
			$className = $this->className;
			return new $className($document, false);
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * 获取下一条记录
	 */
	public function get_next()
	{
		if($this->cursor->hasNext())
            return $this->cursor->getNext();
		else
            return false;
	}
	
}

