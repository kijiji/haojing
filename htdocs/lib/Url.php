<?php
//lianghonghao@baixing.com
class Url {
	private static 
		$current_get,
		$current_host;
	
	private 
		$get_data,
		$host,
		$path,
		$segments;
	
	#Todo 这样写起来虽然好看，但不是效率最高的方法，
	#如果遇到超大规模的Url Render，这里还有空间
	#by lianghonghao@baixing.com
	private function stripallslashes(array $data) {
		array_walk_recursive($data, function(&$value) {
			return trim(stripslashes($value));
		});
		return $data;
	}
	
	public function __construct() {
		if (!isset(self::$current_get)) {
			self::$current_get = $this->stripallslashes($_GET);
		}
		$this->get_data = self::$current_get;
		$this->path = isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : '/';
	}
	
	public function get($field, $default_value = null) {
		return isset($this->get_data[$field]) ? $this->get_data[$field] : $default_value;
	}
	
	public function set($field, $value) {
		$this->get_data[$field] = $value;
		return $this;
	}

	#应该返回$value 还是 $this，这是一个问题
	public function delete($field) {
		unset($this->get_data[$field]);
		return $this;
	}
	
	public function setHost($host) {
		$this->host = $host;
		return $this;
	}
	
	#还没想明白什么地方需要getHost^_^
	public static function getCurrentHost() {
		#Todo HTTP_HOST 也要过一次Filter来保证安全 by lianghonghao@baixing.com
		return isset(self::$current_host) ?
			self::$current_host :
			self::$current_host = $_SERVER['HTTP_HOST'];
	}

	public function setPath($path) {
		$this->path = $path;
		$this->segments = null;
		return $this;
	}

	public function getPath() {
		return $this->path;
	}

	public function segments($offset = -1) {
		if (is_null($this->segments)) {
			$this->segments = array_filter(explode('/', trim($this->path, '/')));
		}

		if ($offset >= 0) {
			return isset($this->segments[$offset]) ? $this->segments[$offset] : null;
		}
		return $this->segments;
	}

	#不支持?a=&b=1的形式
	public function __toString() {
		$this->get_data = array_filter($this->get_data, function($value){
			return $value !== 0;
		});
		
		$return = ($this->host && $this->host != self::getCurrentHost() ? 'http://' . $this->host : '');
		$return .= $this->path;
		if ($this->get_data) {
			$return .= '?' . http_build_query($this->get_data);
		}
		return $return;
	}
}
?>