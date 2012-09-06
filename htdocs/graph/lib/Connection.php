<?php
//yubing@baixing.com

class Connection {
	protected $config;

	public static function create($conf) {
		$conn = new $conf['type'];
		$conn->config = $conf['conf'];
		return $conn;
	}
	
	public function find($node, $args){
		return [];
	}
}

class SearchConnection extends Connection {
	public function find($node, $args) {
		$query = new AndQuery(
				new Query($this->config['col'], $node->id)
				);

		$allowedOptions = array(
			'size' => true,
			'from' => true,
		);
		$opts = array_intersect_key($args, $allowedOptions);
		$args = array_diff_key($args, $allowedOptions);
		$args = array_merge($args, $this->config['query']);
		foreach ($args as $field => $value) {
			$query->add( new Query($field, $value));
		}
		return Searcher::query($this->config['type'], $query, $opts);
	}
}

class PathConnection extends Connection {

	public function find($node, $null) {
		$parent = $this->config;
		if(!isset($node->$parent)) {
			return array($node);
		}
		//path成为了一个强制的Conn Name，感觉不太好。
		return array_merge($node->$parent->path(), array($node));
	}
}
