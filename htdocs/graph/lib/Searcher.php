<?php
//yubing@baixing.com

class SearchResult implements IteratorAggregate {
	private $ids = [];
	private $totalCount, $size, $from;
	public function __construct($response, $params) {
		if ($response != null && $response->hits->total != 0) {
			$this->totalCount = $response->hits->total;
			foreach ($response->hits->hits as $doc) {
				$this->ids[$doc->_id] = $doc->_id;
			}
		}
		$this->size = $params['size'];
		$this->from = $params['from'];
	}

	public function ids() {
		return $this->ids;
	}

	public function objs() {
		return Node::multiLoad($this->ids);
	}

	public function prevPage() {
		return $this->from ? ['size' => $this->size, 'from' => max($this->size - $this->from, 0)] : null;
	}

	public function nextPage() {
		return $this->totalCount > ($this->size + $this->from) ? ['size' => $this->size, 'from' => $this->size + $this->from] : null;
	}

	public function totalCount() {
		return $this->totalCount;
	}

	public function getIterator() {
		return new ArrayIterator($this->objs());
	}
}

class Searcher {
	const READ_TIMEOUT = 2;
	const WRITE_TIMEOUT = 30;

	private static $facetTypes = [
		'terms' => ['field' => true, 'size' => false],
		'range' => ['field' => true, 'ranges' => true],
		'histogram' => ['field' => true, 'interval' => true],
		'date_histogram' => ['field' => true, 'interval' => true],
		//'geo_distance' => ['pin.location' => true, 'ranges' => true],
	];

	/**
	 * @return @type SearchResult
	 */
	public static function query($type, $query, $options = []) {
		$params = array(
			'from'	=>	0,
			'size'	=>	10,
			'query' =>	$query->esQuery(),
			'sort'	=>	array('id' => array('order' => 'desc')),
		);
		$params = array_merge($params, $options);
		$params['size'] = min(max($params['size'], 1), 1000);
		$params['from'] = min(max($params['from'], 0), 10000);
		$response = self::read(self::locate($type) . "/_search/", $params);
		return new SearchResult($response, $params);
	}

	public static function facet($type, $query, $options) {
		$params = ['query' => $query->esQuery(), 'size' => 0];

		$facetType = isset($options['type']) && isset(self::$facetTypes[$options['type']]) ? $options['type'] : 'terms';
		$allowParams = self::$facetTypes[$facetType];

		$params['facets']['facet'][$facetType] = [];
		foreach ($allowParams as $key => $required) {
			if (isset($options[$key])){
				$params['facets']['facet'][$facetType][$key] = $options[$key];
			} elseif ($required) {
				throw new Exception("Need option:'{$key}' to get a {$facetType} facet");
			}
		}

		$response = self::read(self::locate($type) . "/_search/", $params);
		$result = [];
		if (isset($response->facets->facet->$facetType) && is_array($response->facets->facet->$facetType)) {
			foreach ($response->facets->facet->$facetType as $term) {
				$result[$term->term] = $term->count;
			}
		}
		return $result;
	}

	public static function index($type, $doc) {
		return self::write(self::locate($type), $doc);
	}

	private static function locate($type){
		$mapping = Config::get("env.searcher.mapping");
		if (isset($mapping[$type])) {
			return "{$mapping[$type]}/{$type}";
		} else {
			return "default/{$type}";
		}
	}

	private static function read($uri, $params) {
		$params['timeout']	= self::READ_TIMEOUT . 's';
		return self::request($uri, $params, 'read');
	}

	private static function write($uri, $params) {
		$params['timeout']	= self::WRITE_TIMEOUT . 's';
		$params['replication ']	= 'async';
		return self::request($uri, $params, 'write');
	}

	private static function request($uri, $params = [], $type = 'read') {
		$url = Config::get("env.searcher.cluster") . $uri;
		$body = Http::postUrl($url, json_encode($params, JSON_UNESCAPED_UNICODE), ($type = 'read' ? self::READ_TIMEOUT : self::WRITE_TIMEOUT) + 1);
		return json_decode($body);
	}
}

