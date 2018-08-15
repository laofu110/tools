<?php
namespace Laofu\Tools;
class Pinyin{
	/**
       *返回转换后的拼音
	   *$split为分割符
	   *retrun string
	*/
	public function getPinyin($string,$split = '')
	{
		if(!$string){
			return '';
		}
		$arr=$this->getResult($string);
		$rs=[];
		foreach($arr as $li){
			$rs[]=$li[0];
		}
		return implode($split,$rs);
	}
	/** 
	  return array 字符串首字母
	*/
	public function firstWord($string){
		if(!$string){
			return '';
		}
		$arr=$this->getResult($string);
		$rs=[];
		foreach($arr as $li){
			$rs[]=mb_substr($li[0],0,1,'utf8');
		}
		return $rs;		
	}
	/**
	 * 把字符串转为拼音数组结果
	 * @param string $string
	 * @return array
	 */
	public function getResult($string)
	{
		$len = mb_strlen($string,'UTF-8');
		$list = array();
		require_once(dirname(__FILE__).'/data/pinyin.php');
		for($i = 0; $i < $len; ++$i)
		{
			$word = mb_substr($string,$i,1,'UTF-8');
	        $list[]=(isset($pinyin_dict[$word]))?$pinyin_dict[$word]:[$word];
		}
		unset($pinyin_dict);
		return $list;
	}	
}