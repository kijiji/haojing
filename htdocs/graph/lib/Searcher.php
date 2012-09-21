<?php
//yubing@baixing.com

class SearchResult implements IteratorAggregate {
	protected  $ids = [];
	protected $totalCount, $size, $from;
	public function __construct($response, $params = []) {
		if ($response != null && !isset($response->error) && $response->hits->total != 0) {
			$this->totalCount = $response->hits->total;
			foreach ($response->hits->hits as $doc) {
				$this->ids[$doc->_id] = $doc->_id;
			}
		}
		if (isset($params['size'])) $this->size = $params['size'];
		if (isset($params['from'])) $this->from = $params['from'];
	}

	public function ids() {
		return $this->ids;
	}

	public function objs() {
		return Node::multiLoad($this->ids);
	}

	public function page() {
		return ['size' => $this->size, 'from' => $this->from];
	}

	public function prevPage() {
		return $this->from ? ['size' => $this->size, 'from' => max($this->from - $this->size, 0)] : [];
	}

	public function nextPage() {
		return $this->totalCount > ($this->size + $this->from) ? ['size' => $this->size, 'from' => $this->size + $this->from] : [];
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
		'terms' => [
			'params' => ['field' => true, 'size' => false],
			'response' => ['key' => 'terms', 'term' => 'term', 'count' => 'count']],
		'range' => [
			'params' => ['field' => true, 'ranges' => true],
			'response' => ['key' => 'ranges']],
		'histogram' => [
			'params' => ['field' => true, 'interval' => true],
			'response' => ['key' => 'entries', 'term' => 'key', 'count' => 'count']],
		'date_histogram' => [
			'params' => ['field' => true, 'interval' => true],
			'response' => ['key' => 'entries', 'term' => 'key', 'count' => 'count']],
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
		$allowParams = self::$facetTypes[$facetType]['params'];

		$params['facets']['facet'][$facetType] = [];
		foreach ($allowParams as $key => $required) {
			if (isset($options[$key])){
				$params['facets']['facet'][$facetType][$key] = $options[$key];
			} elseif ($required) {
				throw new Exception("Need option:'{$key}' to get a {$facetType} facet");
			}
		}

		$response = self::read(self::locate($type) . "/_search/", $params);
		$responseConf = self::$facetTypes[$facetType]['response'];
		$result = [];
		if (isset($response->facets->facet->$responseConf['key']) && is_array($response->facets->facet->$responseConf['key'])) {
			foreach ($response->facets->facet->$responseConf['key'] as $item) {
				if (!isset($responseConf['term'])) $result[] = $item;
				else $result[$item->$responseConf['term']] = $item->$responseConf['count'];
			}
		}
		return $result;
	}

	public static function index($type, $doc = []) {
		if(!$doc) return false;	//过滤空数组，有些数据无需build或者数据有问题的，支持在NodeDoc::build()的时候返回空数组
		return self::write(self::locate($type), $doc);
	}

	private static function locate($type){
		$mapping = Config::get("env.searcher.mapping");
		if (isset($mapping[$type])) {
			return "{$mapping[$type]}/{$type}/";
		} else {
			return "default/{$type}/";
		}
	}

	private static function read($uri, $params) {
		$uri .= (strpos($uri, '?') === false) ? '?' : '&';
		$uri .= 'timeout=' . self::READ_TIMEOUT . 's';
		return self::request($uri, $params, 'read');
	}

	private static function write($uri, $params) {
		$uri .= (strpos($uri, '?') === false) ? '?' : '&';
		$uri .= 'replication=async&timeout=' . self::WRITE_TIMEOUT . 's';
		return self::request($uri, $params, 'write');
	}

	private static function request($uri, $params = [], $type = 'read') {
		$url = Config::get("env.searcher.cluster") . $uri;
		$body = Http::postUrl($url, json_encode($params, JSON_UNESCAPED_UNICODE), ($type = 'read' ? self::READ_TIMEOUT : self::WRITE_TIMEOUT) + 1);
		return json_decode($body);
	}
}

