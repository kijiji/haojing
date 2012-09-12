<?php
//yubing@baixing.com

class Query {
	protected $field;
	protected $value;

	//todo: query need to support object as value;
	public function __construct($field, $value) {
		list($this->field, $this->value) = [$field, $value];
	}

	function esQuery() {
		if (in_array($this->field, ['title', 'content']) ) {	//ES的meta配置里面对title和content字段都做了分词
			return ['text' => [$this->field => ['query' => $this->value, 'operator' => 'and']]];
		} else {
			return ['term' => [$this->field => $this->value]];
		}
	}

	function accept($o) {
		if (is_null($o->{$this->field})) return false;
		return (is_scalar($o->{$this->field}) ? $o->{$this->field} : $o->{$this->field}->id) == $this->value;
	}
}


class AndQuery extends Query {
	protected $children = [];

	public function  __construct() {
		unset($this->field);
		unset($this->value);
		foreach (func_get_args() as $q) {
			$this->add($q);
		}
	}

	function esQuery() {
		$arr = ['bool' => ['must' => []]];
		foreach($this->children as $child) {
			$arr['bool']['must'][] = $child->esQuery();
		}
		return $arr;
	}

	function add($q) {
		if (get_class($this) == get_class($q)) {
			$this->children = array_merge($this->children, $q->children);
		} else {
			$this->children[] = $q;
		}
	}

	function accept($o) {
		foreach ($this->children as $q) {
			if ($q->accept($o) == false) {
				return false;
			}
		}
		return true;
	}
}

class TrueQuery {
	function esQuery() {
		return ['match_all' => new stdClass()];
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
		$arr = ['range' => [$this->field => []]];
		if (!is_null($this->lower)) {
			$arr['range'][$this->field]['from'] = $this->format($this->lower);
		}
		if (!is_null($this->upper)) {
			$arr['range'][$this->field]['to'] = $this->format($this->upper);
		}
		return $arr;
	}

	function accept($o) {
		return ( (($this->upper === null) ? true : ($o->{$this->field} <= $this->upper))
			&& (($this->lower === null) ? true : ($o->{$this->field} >= $this->lower)) );
	}
}

class NotQuery extends AndQuery {
	function esQuery() {
		$arr = ['bool' => ['must_not' => []]];
		foreach($this->children as $child) {
			$arr['bool']['must_not'][]= $child->esQuery();
		}
		return $arr;
	}

	function accept($o) {
		return !parent::accept($o);
	}
}

class OrQuery extends AndQuery {

	function esQuery() {
		$arr = ['bool' => ['should' => [], 'minimum_number_should_match' => 1]];
		foreach ($this->children as $child) {
			$arr['bool']['should'][] = $child->esQuery();
		}
		return $arr;
	}

	function accept($o) {
		foreach ($this->children as $q) {
			if ($q->accept($o)) {
				return true;
			}
		}
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
		foreach ($this->values as $val) {
			$arr['terms'][$this->field][] = $val;
		}
		return $arr;
	}

	function accept($o) {
		foreach ($this->values as $value) {
			$this->add(new Query($this->field, $value));
		}
		return parent::accept($o);
	}
}



/*
 * HJQuery is a format query string in Haojing
 * like: (a:b OR a:"c" OR (b:[1,10] AND e:[,100]))
 */

class QueryParser {
	public static function parse($HJQueryString) {
		$str = preg_replace('/\s+/', ' ', trim($HJQueryString));
		//var_dump($str);

		$RPNStack = self::buildRPNStack($str);
		//var_dump($RPNStack);

		$opStack = [];
		foreach ($RPNStack as $item) {
			if (is_array($item)) {
				if (!$item['key']) $item['key'] = 'title'; //todo: as the full content index field;
				if (preg_match('/^\[(?<lower>\d*),(?<upper>\d*)\]$/', $item['value'], $m)) {
					$query = new RangeQuery($item['key'], $m['lower'] == '' ? null : $m['lower'], $m['upper'] == '' ? null : $m['upper']);
				} elseif (preg_match('/^\{(([\"\']?[^\"\'\,]+[\"\'\,]*)+)\}$/', $item['value'], $m)) {
					$values = array_map(function ($o) {return trim($o, '"\'');}, explode(',', $m[1]));
					$query = new InQuery($item['key'], $values);
				} else {
					$query = new Query($item['key'], $item['value']);
				}
				array_push($opStack, $query);
				continue;
			}
			switch ($item) {
				case 'AND':
				case 'OR':
					$queryClass = ucfirst(strtolower($item)) . 'Query';
					array_push($opStack, new $queryClass(array_pop($opStack), array_pop($opStack)));
					break;
				case 'NOT':
					array_push($opStack, new NotQuery(array_pop($opStack)));
					break;
				default:
					throw new Exception('Invalid Expression!');
			}
		}

		$query = array_pop($opStack);
		if (count($opStack)) throw new Exception('Invalid Expression!');
		//var_dump($query);
		return $query;
	}

	private static function buildRPNStack($str) {
		//RPN = Reverse Polish Notation, refer: http://blog.kingsamchen.com/archives/637
		$RPNStack = $opStack = [];
		$opPriority = ['(' => 9, ')' => 9, 'NOT' => 3, 'AND' => 2, 'OR' => 1];
		for ($i = 0, $len = mb_strlen($str); $i < $len; $i++) {
			$currentStr = mb_substr($str, $i);
			if ($currentStr[0] == ' ') {
				if (preg_match('/^\s(AND\s?|&&\s?|OR\s?|\|\|\s?)/i', $currentStr, $m)) continue;
				$i -= 3;
				$currentStr = 'AND' . $currentStr;
			}

			if (preg_match('/^(?<op>AND\s?|&&\s?|OR\s?|\|\|\s?|NOT\s?|\-\s?|\(|\))/i', $currentStr, $m)) {
				$i += (mb_strlen($m[1]) - 1);
				//var_dump($m['op']);
				$m['op'] = strtoupper(str_replace(array('&&', '||', '-'), array('AND', 'OR', 'NOT'), trim($m['op'])));
				switch ($m['op']) {
					case 'NOT':
						array_push($opStack, $m['op']);
						break;
					case '(':
						array_push($opStack, $m['op']);
						break;
					case ')':
						do {
							$RPNStack[] = $op = array_pop($opStack);
							if ($op == '(') array_pop($RPNStack);
						} while ($op != '(');
						break;
					default:
						while (!(count($opStack) == 0 || end($opStack) == '(' || $opPriority[$m['op']] >= $opPriority[end($opStack)])) {
							$RPNStack[] = array_pop($opStack);
						}
						array_push($opStack, $m['op']);
				}
			} elseif (preg_match('/^(((?<key>[^\:\(\)]+)\s*\:)?\s*([\"\'](?<value>[^\"\'\(\)]+)[\"\']|(?<value2>[^\s\(\)]+))).*$/i', $currentStr, $m)) {
				//var_dump($m[1]);
				$RPNStack[] = ['key' => $m['key'], 'value' => $m['value'] ?: $m['value2']];
				$i += (mb_strlen($m[1]) - 1);
			}
		}

		if (count($opStack)) {
			do {
				$RPNStack[] = $op = array_pop($opStack);
				if (is_null($op)) array_pop($RPNStack);
			} while (!is_null($op));
		}
		unset($opStack);
		return $RPNStack;
	}
}