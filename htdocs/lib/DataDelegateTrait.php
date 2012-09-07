<?
//zhaojun@baixing.com

trait DataDelegateTrait {
	protected $data;

	public function __construct($data = null) {
		if ($data) $this->bind($data);
	}
	
	public function bind($data) {
		$this->data = $data;
	}

	public function __get($name) {
		if (!$this->data) throw new Exception('Need bind data first!');
		return $this->data->$name;
	}

	public function __set($name, $value) {
		if (!$this->data) throw new Exception('Need bind data first!');
		return $this->data->$name = $value;
	}

	public function __call($name, $args) {
		if (!$this->data) throw new Exception('Need bind data first!');
		return call_user_func_array(array($this->data, $name), $args);
	}
}