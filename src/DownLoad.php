<?php
namespace Laofu\Tools;
class DownLoad
{
	/* 版本 */
	public $version = '1.0.0';
	/* 编码 */
	private $char_set = 'utf-8';
	/**
	* 下载文件名称
	*/
	public $download_name = '';
	/* 文件名称 */
	public $download_file = '';
	/* 下载速率 30kb/s 0为不限速*/
	public $download_rate = 30;
	
	// 每次最多输出文件长度 1kb
	public $download_length = 1024;
	/* 是否允许中文文件名 */
	public $iconv = true;
	public function __construct($download_file, $download_name = '',$rate=512)
	{
		// 中文名转换
		if ($this->iconv) {
			$download_file = iconv($this->char_set, 'gbk', $download_file);
		}
		//echo $download_file;exit();
		// 判断文件
		if (!is_file($download_file) || !is_readable($download_file)) {
			throw new \Exception('cannot open file or cannot access file');
		}
		// 判断文件名
		if (!empty($download_name)) {
			$this->download_name = $download_name;
		}else{
			// 获取当前文件名
			$this->download_name = basename($download_file);
		}
		if($rate){
			$this->download_rate=$rate;
		}
		$this->download_file = $download_file;
	}
	/**
	* 输入文件
	* @return file
	*/
	public function out()
	{
		//文件类型是二进制流。设置为编码（支持中文文件名称）
		header("Content-type:application/octet-stream; charset=".$this->char_set);
		header("Content-Transfer-Encoding: binary");
		header("Accept-Ranges: bytes");
		//文件大小
		header("Content-Length: ".filesize($this->download_file));
		//触发浏览器文件下载功能
		header('Content-Disposition:attachment;filename="'.urlencode($this->download_name).'"');
		// flush 内容
		flush();
		// 文件
		$file_handle = fopen($this->download_file, "rb");
		if ($file_handle === false) {
			throw new \Exception('cannot open file');
		}
		$file_size = filesize($this->download_file);
		// 判断是否限速
		if ($this->download_rate == 0) {
			// 不限速直接输出
			//循环读取文件内容，并输出
			while(!feof($file_handle)) {
			    //从文件指针 handle 读取最多 length 个字节（每次输出10k）
			    echo fread($file_handle, $this->download_length);
			}
		}else{
			// 限制速度
			$file_count = 0;
			$buffer_size = $this->download_length * $this->download_rate;
			while(!feof($file_handle) && ($file_size - $file_count>0) ) {
				// 设置文件最长执行时间
				set_time_limit(0);
			    //从文件指针 handle 读取最多 length 个字节（每次输出10k）
			    echo fread($file_handle, $buffer_size);
			    $file_count += $buffer_size;
			    // flush 内容输出到浏览器端
			    flush();
			    // 防止PHP或web服务器的缓存机制影响输出
			    ob_flush();
			    // 终端1秒后继续
			    sleep(1);
			}
		}
		//关闭文件流
		fclose($file_handle);
	}
}