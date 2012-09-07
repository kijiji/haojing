<?php
//yubing@baixing.com

class SearchResult implements IteratorAggregate {
	private $ids = [];
	private $totalCount;
	public function __construct($response) {
		if ($response != null && $response->hits->total != 0) {
			$this->totalCount = $response->hits->total;
			foreach ($response->hits->hits as $doc) {
				$this->ids[$doc->_id] = $doc->_id;
			}
		}
	}
	
	public function ids() {
		return $this->ids;
	}

	public function objs() {
		return Node::multiLoad($this->ids);
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
		return new SearchResult($response);
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
		$cUrl = curl_init();
		$url = Config::get("env.searcher.cluster") . $uri;
		curl_setopt($cUrl, CURLOPT_URL, $url);
		curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($cUrl, CURLOPT_HEADER, FALSE);
		curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($cUrl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($cUrl, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
		curl_setopt($cUrl, CURLOPT_TIMEOUT, ($type = 'read' ? self::READ_TIMEOUT : self::WRITE_TIMEOUT) + 1);
		curl_setopt($cUrl, CURLOPT_CONNECTTIMEOUT, 0);
		$body = curl_exec($cUrl);
		$info = curl_getinfo($cUrl);
		if ( !in_array($info['http_code'], array(200, 201)) || (curl_error($cUrl) != '') ) {
			$body = FALSE;
		}
		curl_close($cUrl);
		return json_decode($body);
	}
}

