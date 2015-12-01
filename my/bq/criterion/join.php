<?php
namespace my\bq\criterion;
class Join implements Criterion{

	private $criteria;
	private $lp;
	private $rp;
	private $joinOn;
	
	
	/**
	 * 设置Criteria对象关联。
	 * @param Criteria $criteria 关联的对象
	 * @param String $lp 关联对象的值。
	 * @param String $rp 当前对象的值。
	 * @param String $T 与左项逻辑关系，join \ left join \ right join;
	 */
	
	
	public static function add($criteria,$lp,$rp,$T){
		return new self($criteria, $lp, $rp, $T);
	}

    public function setCriteria($criteria){
        $this->criteria = $criteria;
    }
	
	protected function __construct($criteria,$lp,$rp,$T){
		$this->criteria = $criteria;
		$this->lp = $lp;
		$this->rp = $rp;
		$this->joinOn = $T;
	}

	public function toSqlString($lCrit){
		$joinString = $this->joinOn.$this->criteria->getTable().' as '.$this->criteria->getAlias() .' on '.$lCrit->getAlias() .'.'.$this->lp.' = '.$this->criteria->getAlias().'.'.$this->rp;
		return $joinString;
	}

	public function getJoinCriteria(){
		return $this->criteria;
	}
}
