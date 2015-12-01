<?php
namespace my\bq\webframework;

//分页器接口定义.
interface DataPager{
	
	//取得当前页数;
	public function getCurrentPage();

	//取得当前单页纪录条数；
	public function getPageSize();
	
	//设定总纪录条数;
	public function setTotalNum($totalNum);
	
	//获取纪录起始行数;
	public function getFirstResult();
	

}
