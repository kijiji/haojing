<?php
//yubing@baixing.com

class Query {
	protected $field;
	protected $value;

	public function __construct($field, $value) {
		list($this->field, $this->value) = [$field, $value];
	}
	
	function esQuery() {
		if (in_array($this->field, ['description', 'title', 'content']))
			return ['text' => [$this->field => ['query' => $this->value, 'operator' => 'and']]];
		else
			return ['term' => [$this->field => $this->value]];
	}

	function accept($o) {
		return (is_string($o->{$this->field}) ?: $o->{$this->field}->id) == $this->value;
	}
}


class AndQuery extends Query {
	protected $children = [];

	public function  __construct() {
		foreach (func_get_args() as $q)
			$this->add($q);
	}

	function esQuery() {
		$arr = ['bool' => ['must' => []]];
		foreach($this->children as $child)
			$arr['bool']['must'][] = $child->esQuery();
		return $arr;
	}

	function add($q) {
		if (get_class($this) == get_class($q))
			$this->children = array_merge($this->children, $q->children);
		else
			$this->children[] = $q;
	}

	function accept($o) {
		foreach ($this->children as $q)
			if ($q->accept($o) == false)
				return false;
		return true;
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
		if (!(is_numeric($upper) && $upper > 2147483647))
			$this->upper = $upper;
	}

	protected function format($val) {
		return trim($val);
	}
	
	function esQuery() {
		$arr = ['range' => [$this->field => []]];
		if (!is_null($this->lower))
			$arr['range'][$this->field]['from'] = $this->format($this->lower);
		if (!is_null($this->upper))
			$arr['range'][$this->field]['to'] = $this->format($this->upper);
		return $arr;
	}

	function accept($o) {
		return ( (($this->upper === null) ? true : ($o->{$this->field} <= $this->upper))
					&& (($this->lower === null) ? true : ($o->{$this->field} >= $this->lower)) );
	}
}

class DateRangeQuery extends RangeQuery {
	protected function format($val) {
		return date('Ymd\THis\Z', $val);
	}
}


class NotQuery extends AndQuery {
	function esQuery() {
		$arr = ['bool' => ['must_not' => []]];
		foreach($this->children as $child)
			$arr['bool']['must_not'][]= $child->esQuery();
		return $arr;
	}
}

class OrQuery extends AndQuery {

	function esQuery() {
		$arr = ['bool' => ['should' => [], 'minimum_number_should_match' => 1]];
		foreach($this->children as $child) 
			$arr['bool']['should'][] = $child->esQuery();
		return $arr;
	}

	function accept($o) {
		foreach ($this->children as $q)
			if ($q->accept($o))
				return true;
		return false;
	}
}

class InQuery extends OrQuery {
	private $values;
	public function __construct($field, $values) {
		$this->field = $field;
		$this->values= $values;
	}

	function esQuery() {
		$arr = ['terms' => [$this->field => [], 'minimum_match' => 1]];
		foreach ($this->values as $val)
			$arr['terms'][$this->field][] = $val;
		return $arr;
	}
}

