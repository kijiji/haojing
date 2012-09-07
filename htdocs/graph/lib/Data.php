<?php
//yubing@baixing.com

class Data {
	public $id;
	private $loaded = false;

	public function __construct($id = null) {
		$this->id = $id;
	}

	//在__get()某个属性（id除外）的时候自动触发lazyload;
	//Loaded的对象所有属性都是public的，就不应该还有调这个方法的了。
	public function __get($name) {
		if (!$this->loaded) {
			$this->load();	
			return isset($this->$name) ? $this->$name : null;
		} else {
			return null;	//loaded的对象身上还没有的属性一定不存在
		}
	}
	
	public function load($id = null) {
		if ($id) {
			$this->id = $id;
		}

		if(!$this->id) {
			throw new Exception('Load stops, id not given');
		}

		//reload
		if ($this->loaded) {
			$_id = $this->id;
			$this->reset();
			return $this->load($_id);
		}

		$array = $this->getStorage()->load($this->sid());
		if(!$array) {
			throw new Exception("Load fails, id: `$this->id` not found");
		}
		$this->parseFromArray($array);
		$this->loaded = true;
		return $this;
	}

	private function parseFromArray($array) {
		foreach($array as $col => $value) {
			if ($col == 'id' || (is_scalar($value) && strlen($value) == 0)){
				continue;
			} else {
				$this->$col = $value;
			}
		}

		$refs = Config::get("type.{$this->type()}.ref");
		$class = get_class($this);
		foreach ($refs as $refName => $config) {
			if (isset($this->$config['col'])) {
				if (isset($config['id_delimiter'])) {
					$_nodes = [];
					foreach(explode($config['id_delimiter'], $this->$config['col']) as $_id) {
						$gid = $this->gid($_id, $config['type']);
						$_nodes[] = new $class($gid);
					}
					unset($this->$config['col']);
					$this->$refName = $_nodes;
				} else {
					$gid = $this->gid($this->$config['col'], $config['type']);
					unset($this->$config['col']);
					$this->$refName = new $class($gid);
				}
			}
		}
	}

	protected function gid($sid, $type) {
		return Config::get("type.{$type}.id_prefix") . $sid;
	}

	private function sid() {
		return substr( $this->id, strlen(Config::get("type.{$this->type()}.id_prefix")) );
	}

	protected function getStorage($type = null) {
		$type = $type ?: $this->type();	//insert or dumpid的时候没有id，所以没法用type()
		return Storage::create($type);
	}

	private function reset() {
		foreach($this as $col => $val) {
			if ($col == 'loaded') {
				$this->$col = false;	//unset掉再赋值就不是private了
			} else {
				unset($this->$col);
			}
		}
	}

	public function type() {
		$id = $this->id;
		if(!$id) throw new Exception('No id is set when calling type() function!');

		if (preg_match('/^[0-9]{8,9}$/', $id)) {
			return 'Ad';
		} elseif (preg_match('/^(m[0-9]+|china)$/', $id)) { //todo: compatible with china, and will remove when the data is clean.
			return 'Entity';
		} elseif (preg_match('/^c_[a-z]+$/', $id)) {
			return 'City';
		} elseif (preg_match('/^[a-z]+$/', $id)) {
			return 'Category';
		} elseif (preg_match('/^([a-z]+[:]?)/', substr($id, 0, 4), $matches)) {
			if( $type = Config::search('type.id_prefix_reverse.' . $matches[1]) ) {
				return $type;
			}
		}
		throw new Exception('Unknown type for ' . $id);
	}
}
