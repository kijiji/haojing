<?php
//yubing@baixing.com

/**
 * @return Node|Array
 */
function graph($cmd) {
	if (!preg_match('/^(?<id>[^\/\s\?]+)(|\/(?<conn>[a-zA-Z]+))(|\?(?<arg>.+))$/', $cmd, $m)) {
		throw new Exception("Illegal format when calling graph() : {$cmd}");
	}
	
	$args = [];
	if(isset($m['arg'])) {
		parse_str($m['arg'], $args);
	}

	$node = new Node();
	$id = $m['id'];
	if(isset($m['conn']) && $m['conn']) {
		return $node->load($id)->$m['conn']($args);
	} else {
		return $node->load($id);
	}
}


class Node extends Data {
	public function __call($name, $arguments) {
		return $this->conn($name, current($arguments) ?: []);
	}

	public static function multiLoad($ids) {
		$nodes = [];
		foreach($ids as  $id) {
			$n = new Node();
			$nodes[] = $n->load($id);	//$nodes数组不像以前一样用$id做键值,内部做好去重以后，外边其实不需要
		}
		return $nodes;
	}

	private function conn($connName, $args) {
		$conns = $this->connections();
		if(isset($conns[$connName])) {
			return Connection::create($conns[$connName])->find($this->load(), $args);
		} else {
			throw new Exception("Illegal connection: '{$connName}' for type '{$this->type()}'");
		}
	}
	
	public function connections() {
		return Config::get("type.{$this->type()}.conn");
	}
}

