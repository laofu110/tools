<?php
namespace Laofu\Tools;
use Laofu\Tools\Phpanalysis;
/* 
    *分词扩展，基于Phpanalysis.class.php
 */
class Fenci{
	/**
       *获取分词对象，用于接下来的调用
	*/
	private function get_obj($str){
		$str=strip_tags($str);
		PhpAnalysis::$loadInit = false;
		$Fenci=new PhpAnalysis();
		$Fenci->LoadDict();
		$Fenci->toLower=true;
		$Fenci->SetSource($str);
		$Fenci->SetResultType(2);
		$Fenci->differMax=true;
		$Fenci->differFreq=true;
		$Fenci->unitWord=true;
		$Fenci->StartAnalysis(true);
        return $Fenci;		
	}
	/**
       *获取分词
	   *$split为分割符
	   *retrun string
	*/
	public function fenci($str='',$split = ' ')
	{
        $Fenci=$this->get_obj($str);
		$rs=$Fenci->GetFinallyResult($split,false);
		return $rs;
	}
	/**
       *获取词频最高的$num个词
	*/
    public function hotWord($str='',$num=10){
		$Fenci=$this->get_obj($str);
		return $Fenci->GetFinallyKeywords($num);
	}
}