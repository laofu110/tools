<?php
namespace Laofu\Tools;
class MyRedis
{
	protected $handler = null;
	protected $options = [
		'host'       => '127.0.0.1',
		'port'       => 6379,
		'password'   => '',
		'select'     => 0,
		'timeout'    => 0,
		'expire'     => 0,
		'persistent' => false,//持久连接，若同一服务器上有多个redis项目不建议开启，有可能会出现数据交叉的问题
		'prefix'     => '',
		'serialize'  => true,
	];	
	public function __construct($options=array()) 
	{
		if (!empty($options)) {
			$this->options = array_merge($this->options, $options);
		}
		if (extension_loaded('redis')) {
			$this->handler = new \Redis;
            try{
				if ($this->options['persistent']) {
					$this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
				} else {
					$this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
				}
			}catch(Exception $e){
				throw new \BadFunctionCallException("Redis server mybe not start!");
			}
			if ('' != $this->options['password']) {
				$this->handler->auth($this->options['password']);
			}

			if (0 != $this->options['select']) {
				$this->handler->select($this->options['select']);
			}
		} elseif (class_exists('\Predis\Client')) {
			$params = [];
			foreach ($this->options as $key => $val) {
				if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication'])) {
					$params[$key] = $val;
					unset($this->options[$key]);
				}
			}
			$this->handler = new \Predis\Client($this->options, $params);
		} else {
			throw new \BadFunctionCallException('not support: redis');
		}
	}
	
	/**
	 * 判断缓存
	 * @access public
	 * @param  string $name 缓存变量名
	 * @return bool
	 */
	public function has($name)
	{
		return $this->handler->exists($this->options['prefix'].$name);
	}		
	/**
	 * 读取缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @return mixed
	 */
	 
	 
	public function get($name) 
	{
		$value = $this->handler->get($this->options['prefix'].$name);
		$jsonData  = json_decode( $value, true );
		return ($jsonData === NULL) ? $value : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
	}

	/**
	 * 写入缓存
	 * @access public
	 * @param string $name 缓存变量名
	 * @param mixed $value  存储数据
	 * @param integer $expire  有效时间（秒）
	 * @return boolen
	 */
	 
	 
	public function set($name, $value, $expire = null) 
	{
		if(is_null($expire)) 
		{
			$expire  =  $this->options['expire'];
		}
		$name   =   $this->options['prefix'].$name;
		//对数组/对象数据进行缓存处理，保证数据完整性
		$value  =  (is_object($value) || is_array($value)) ? json_encode($value) : $value;
		//删除缓存操作支持
		if($value===null)
		{
			return $this->handler->delete($this->options['prefix'].$name);
		}
		if(is_int($expire) && $expire !=0) 
		{
			$result = $this->handler->setex($name, $expire, $value);
		}
		else
		{
			$result = $this->handler->set($name, $value);
		}
		return $result;
	}

	/**
	* 删除缓存
	* @access public
	* @param string $name 缓存变量名
	* @return boolen
	*/
	
	
	public function rm($name) 
	{
		return $this->handler->delete($this->options['prefix'].$name);
	}

	
	/**
	* 清除缓存
	* @access public
	* @return boolen
	*/
	
	
	public function clear() 
	{
		return $this->handler->flushDB();
	}
	/**
	 * redis 命令取法
	 * @param string $key redis自定义键值
	 * @param string $value redis
	 * @return mixed
	 */
	public function raw_command($key, $value)
	{
		return $this->handler->rawCommand($key, ...explode(' ', $value));
	}	

    /**
     * 添加地理位置
     * @param $key
     * @param $pos
     * @return mixed,添加成功的记录数
     */
    public function geoAdd($key, $pos)
    {

        $posToArray = [$key];

        foreach ($pos as $value) {
            $posToArray[] = $value['lon'];
            $posToArray[] = $value['lat'];
            $posToArray[] = $value['member'];
        }

        $posToString = implode(' ', $posToArray);
        return $this->raw_command('geoAdd', $posToString);
    }

    /**
     * 重置地理位置
     * @param $key
     * @param $pos
     * @return mixed
     */
    public function geoReset($key, $pos)
    {
        $this->handler->zRem($key, $pos['member']);

        return $this->geoAdd($key, [$pos]);
    }

    /**
     * 查看地理位置
     * @param string $key
     * @param array $member
     * @return mixed
     */
    public function geoPos($key, $member)
    {
        array_unshift($member, $key);

        $member = implode(' ', $member);

        return $this->raw_command('geoPos', $member);
    }

    /**
     * 地理位置 hash 码
     * @param string $key
     * @param array $member
     * @return mixed
     */
    public function geoHash($key, $member)
    {
        array_unshift($member, $key);

        $member = implode(' ', $member);

        return $this->raw_command('geoHash', $member);
    }

    /**
     * 计算两地距离
     * @param $key
     * @param $member
     * @param $unit
     * @return bool|mixed
     */
    public function geoDist($key, $member, $unit = 'm')
    {
        if (count($member) != 2) {
            return false;
        }

        array_unshift($member, $key);

        array_push($member, $unit);

        $member = implode(' ', $member);

        return $this->raw_command('geoDist', $member);
    }

    /**
     * 获取范围内的位置成员
     * @param $key
     * @param $config
     * @return mixed
     */
    public function geoRadius($key, $config)
    {
        $arrayToString = [$key];
        if (!isset($config['lon']) || !isset($config['lat']) || !isset($config['dist'])) {
            return false;
        }
        $config['withDist']=isset($config['withDist'])?$config['withDist']:1;
        $config['withCoord']=isset($config['withCoord'])?$config['withCoord']:0;
        $config['withHash']=isset($config['withHash'])?$config['withHash']:0;
        $config['count']=isset($config['count'])?$config['count']:0;
        $config['sort']=isset($config['sort'])?$config['sort']:'asc';
        $arrayToString[] = $config['lon'];
        $arrayToString[] = $config['lat'];
        $arrayToString[] = $config['dist'];
        $arrayToString[] = isset($config['unit'])?$config['unit']:'m';

        // 返回与给定成员之间的距离，单位与给定的单位保持一致
        if (isset($config['withDist']) && $config['withDist']) {
            $arrayToString[] = 'withDist';
        }

        // 将位置元素的经度和维度也一并返回
        if (isset($config['withCoord']) && $config['withCoord']) {
            $arrayToString[] = 'withCoord';
        }

        //  以 52 位有符号整数的形式（超过最大数了，可忽略）， 返回位置元素经过原始 geohash 编码的有序集合分值，
        if (isset($config['withHash']) && $config['withHash']) {
            $arrayToString[] = 'withHash';
        }

        // 选项去获取前 N 个匹配元素，无法直接分页，分页功能需自己实现
        if (isset($config['count']) && $config['count']) {
            $arrayToString[] = $config['count'];
        }

        // 排序 asc|desc
        $arrayToString[] = $config['sort'];

        return $this->raw_command('geoRadius', implode(' ', $arrayToString));
    }

    /**
     * 指定成员的位置被用作查询的中心
     * @param $key
     * @param $config
     * @return bool|mixed
     */
    public function geoRadiusByMember($key, $config)
    {
        $arrayToString = [$key];
        /**
         * member 存储的成员
         * dist 距离
         * unit 单位
         */
        if (!isset($config['member']) || !isset($config['dist'])) {
            return false;
        }
        $config['withDist']=isset($config['withDist'])?$config['withDist']:1;
        $config['withCoord']=isset($config['withCoord'])?$config['withCoord']:0;
        $config['withHash']=isset($config['withHash'])?$config['withHash']:0;
        $config['count']=isset($config['count'])?$config['count']:0;
        $config['sort']=isset($config['sort'])?$config['sort']:'asc';
        $arrayToString[] = $config['member'];
        $arrayToString[] = $config['dist'];
        $arrayToString[] = isset($config['unit'])?$config['unit']:'m';

        // 在返回位置元素的同时， 将位置元素与中心之间的距离也一并返回。 距离的单位和用户给定的范围单位保持一致
        if (isset($config['withDist']) &&$config['withDist']) {
            $arrayToString[] = 'withDist';
        }

        // 将位置元素的经度和维度也一并返回
        if (isset($config['withCoord']) &&$config['withCoord']) {
            $arrayToString[] = 'withCoord';
        }

        //  以 52 位有符号整数的形式， 返回位置元素经过原始 geohash 编码的有序集合分值
        if (isset($config['withHash']) && $config['withHash']) {
            $arrayToString[] = 'withHash';
        }

        // 选项去获取前 N 个匹配元素
        if (isset($config['count']) && $config['count']) {
            $arrayToString[] = $config['count'];
        }

        // 排序 asc|desc
        $arrayToString[] = $config['sort'];


        return $this->raw_command('geoRadiusByMember', implode(' ', $arrayToString));
    }		
		
		/**
		* 析构释放连接
		* @access public
		*/
		
		
		public function __destruct()
		{
			$this->handler->close();
		}
}
