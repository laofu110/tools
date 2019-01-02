<?php
namespace Laofu\Tools;
class Page
{
    // 总共有多少条数据
    private $count;
    // 每页显示多少条，limit 语句用的第二个数 limit 2,5
    public $page_size;
    // 当前url,已经去掉  &page=1 参数
    private $url;
    // 用户分页时，limit 语句用的第一个数 limit 2,5
    public $limit_page;
    // 当前页
    private $now_page;
    // 上一页
    private $pre_page;
    // 下一页
    private $next_page;
    // 总共多少页
    private $totol_page;
    // limit 从多少行开始搜索
    public $start_row;
    // 数子开始页
    private $number_start;
    // 数字结尾页
    private $number_end;
    // 显示多少个数字分页 如$number_show=2那么页面上显示就是[首页] [上页] 1 2 3 4 5 [下页] [尾页]
    private $number_show=2;
	//简介模式显示，只显示上一页下一页
	private $simple;
	private $show_total;

    /**
     * 构造方法
     * Page constructor.
     * @param integer $count [数据总条数]
     * @param integer $page_size [每页多少条]
     */
    public function __construct($count, $page_size,$simple=false,$show_total=true)
    {
        if(!$count){
            $this->count = 0;
            $this->start_row = 0;
            $this->page_size = 0;
            return ;
        }
        if($count<=$page_size){
            $page_size=$count;
        }
        $this->count      = $count;
        $this->page_size  = $page_size;
		$this->simple=$simple;
		$this->show_total=$show_total;

        $this->totol_page = ceil($this->count / $this->page_size);
        $this->now_page   = isset($_GET['page']) ? $_GET['page'] : 1;
        $this->pre_page   = $this->now_page - 1;
        $this->start_row  = ($this->now_page - 1) * $page_size;
        // 用于分页sql用  M('article')->limit($Page->now_page-1.','.$Page->listRows)->select();
        $this->limit_page = $this->now_page - 1;
        $this->next_page  = $this->now_page + 1;
        $this->url        = $this->create_url();
        $this->number_start = $this->now_page - $this->number_show;
        $this->number_end = $this->now_page + $this->number_show;
        if($this->number_start<1){
            $this->number_end = $this->now_page + 3;
            if($this->number_end>$this->totol_page){
                $this->number_end=$this->totol_page;
            }
            $this->number_start=1;
        }

        if($this->number_end>$this->totol_page){
            $this->number_start = $this->now_page - 3;
            $this->number_end=$this->totol_page;

            if($this->number_start<1){
                $this->number_start=1;
            }
        }

    }

    /**
     * 获取分页html代码
     * @return string [分页html代码]
     */
    public function show()
    {
		$simple=$this->simple;
		$show_total=$this->show_total;
        if(!$this->count){
            return ;
        }
        // 自带分页样式，仿造猪八戒网站分页
        $page_html = '<div class="page-box">';
		if(!$simple){
			// 首页
			if ($this->now_page > 1) {
				$page_html .= "<a class='page' href='" . $this->create_url() . "'>首页</a>";
			}else{
				$page_html .= "<a class='page-disable'>首页</a>";
			}			
		}

        // 上一页
        if ($this->pre_page > 0) {
            $pre_page_url = $this->create_url(['page'=>$this->pre_page]);
            $page_html .= "<a class='page' href='" . $pre_page_url . "'>«</a>";
        }else{
            $page_html .= "<a class='page-disable'>«</a>";
        }
		if(!$simple){
			//分页
			for($i=$this->number_start;$i<=$this->number_end;$i++){
				if($i==$this->now_page){
					$pre_page_url = $this->create_url(['page'=>$i]);
					$page_html .= "<a class='page now-page' href='" . $pre_page_url . "'>$i</a>";
				}else{
					$pre_page_url = $this->create_url(['page'=>$i]);
					$page_html .= "<a class='page' href='" . $pre_page_url . "'>$i</a>";
				}
			}			
		}
        // 下一页
        if ($this->next_page <= $this->totol_page) {
            $next_page_url = $this->create_url(['page'=>$this->next_page]);
            $page_html .= "<a class='page' href='" . $next_page_url . "'>»</a>";
        }else{
            $page_html .= "<a class='page-disable'>»</a>";
        }
		if(!$simple){
			// 尾页
			if ($this->now_page < $this->totol_page) {
				$last_page = $this->create_url(['page'=>$this->totol_page]);
				$page_html .= "<a class='page last-page' href='" . $last_page . "'>尾页</a>";
			}else{
				$page_html .= "<a class='last-page page-disable' >尾页</a>";
			}			
		}
        if($show_total){
			$page_html .= " <span class='page'>共" . $this->count . "条数据</span>";
		}
		$page_html .='</div>';
        return $page_html;
    }

    public function create_url($param=[]){
        $url= $_SERVER['REQUEST_URI'];
        $url_info=parse_url($url);
        $query_tem=explode('&',(isset($url_info['query'])?$url_info['query']:''));
        $query=[];
        foreach ($query_tem as $li) {
            if($li!=null){
                $li_tem=explode('=',$li);
                $query[$li_tem[0]]=urldecode($li_tem[1]);
            }
        }
        if(empty($param)){
            unset($query['page']);
        }else{
           $query= array_merge($query,$param);
        }
        return (!empty($query))?$url_info['path'].'?'.http_build_query($query):$url_info['path'];      
    }
}