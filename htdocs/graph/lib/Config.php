<?php
//yubing@baixing.com

if (!defined('CONFIG_DIR')) {
	trigger_error('CONFIG_DIR is undefined !', E_USER_ERROR);
}

class Config {
	private static $configs = [];

	public static function get($path) {
		return (new self)->match($path);
	}

	public static function search($path) {
		try {
			return self::get($path);
		} catch (Exception $exc) {
			return null;
		}
	}

	protected function match($path) {
		$nodes = explode('.', $path);
		$root = array_shift($nodes);
		$m = $this->find($nodes, $this->load($root));
		if (is_null($m)) {
			throw new Exception("No config available for {$path}");
		} else {
			return $m;
		}
	}
	
	private function find($nodes, $config){
		if ( count($nodes) == 0 ) {
			return $config;
		} else {
			$node = array_shift($nodes);
			if ( isset($config[$node]) ) {
				return $this->find($nodes, $config[$node]);
			} else {
				return null;
			}
		}
	}

	private function load($root) {
		if(!isset(self::$configs[$root])) {
			if($root == 'type') {
				$config = TypeConfig::load();
			} else {
				$config = include(CONFIG_DIR . "/{$root}.php");
			}
			self::$configs[$root] = $config;
		}
		return self::$configs[$root];
	}

}


// Type Config Decorator， For Graph Project
// 这种形式还是比较丑陋
class TypeConfig {
	public static function load() {
		$config = Config::get('routing');
		$id_prefix_reverse = [];
		foreach ($config as $type => &$_conf) {
			if(!isset($_conf['conn'])) {
				$_conf['conn'] = [];
			}
			if(!isset($_conf['ref'])) {
				$_conf['ref'] = [];
			}
			$id_prefix_reverse[$_conf['id_prefix']] = $type;	//@todo testcase保证配置不重复
		}
		$config['id_prefix_reverse'] = $id_prefix_reverse;
		return $config;
	}
}

?>