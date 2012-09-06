<?php
//yubing@baixing.com

class Query {
	protected $field;
	protected $value;

	public function __construct($field, $value) {
		$this->field = $field;
		$this->value = $value;
	}
	
	function esQuery() {
		if (in_array($this->field, array('description', 'title', 'content')) ) {
			return array('text' => array($this->field => array('query' => $this->value, 'operator' => 'and')));
		} else {
			return array('term' => array($this->field => $this->value));
		}
	}
}


class AndQuery extends Query {
	protected $children = [];

	public function  __construct() {
		foreach (func_get_args() as $q) $this->add($q);
	}

	function esQuery() {
		$arr = array('bool' => array('must' => []));
		foreach($this->children as $child) {
			$arr['bool']['must'] []= $child->esQuery();
		}
		return $arr;
	}

	function add($q) {
		if(get_class($this) == get_class($q)) {
			$this->children = array_merge($this->children, $q->children);
		} else {
			$this->children[] = $q;
		}
	}
}

class TrueQuery {
	function esQuery() {
		return array('match_all' => new stdClass());
	}
}

class RangeQuery extends Query {
	protected $lower;
	protected $upper;

	function __construct($field, $lower = null, $upper = null) {
		$this->field = $field;
		$this->lower = $lower;
		if (!(is_numeric($upper) && $upper > 2147483647)) {
			$this->upper = $upper;
		}
	}

	protected function format($val) {
		return trim($val);
	}
	
	function esQuery() {
		$arr = array('range' => array($this->field => []));
		if (!is_null($this->lower)) {
			$arr['range'][$this->field]['from'] = $this->format($this->lower);
		}
		if (!is_null($this->upper)) {
			$arr['range'][$this->field]['to'] = $this->format($this->upper);
		}
		return $arr;
	}

}

class DateRangeQuery extends RangeQuery {
	protected function format($val) {
		return date('Ymd\THis\Z', $val);
	}
}
