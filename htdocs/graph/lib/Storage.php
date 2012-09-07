<?php
//yubing@baixing.com

interface ReadableStorage {
	public function load($id);
}

interface SearchableStorage {
	public function dumpIds();
}

class Storage {
	protected $config;

	public static function create($type) {
		$config = Config::get("routing.{$type}.storage");
		return new $config['type']($config);
	}
	
	public function __construct($config) {
		$this->config = $config;
	}
}

class MysqlStorage extends Storage implements ReadableStorage, SearchableStorage {
	private $result;
	private static $connections = array();
	
	private static function get_connection($db, $type = '.read', $reConnect = false) {
		if($reConnect || !isset(self::$connections[$db.$type])) {
			$c = Config::get( "env.mysql.{$db}{$type}");
			$conn = mysql_connect(
						$c['host'],
						$c['user'],
						$c['password'],
						true,
						MYSQL_CLIENT_COMPRESS
				);
			
			if ($conn === false) {
				throw new Exception("Can't connect to Db: {$c['user']}@{$c['host']}");
			}
			
			mysql_select_db($c['database'], $conn);
			mysql_set_charset('utf8', $conn);
			self::$connections[$db.$type] = $conn;
		}
		return self::$connections[$db.$type];
	}
	
	public function load($id) {
		$sql = 'SELECT ' . $this->get_col_alias_name() 
				. ' FROM `' . $this->config['table']
				. '` WHERE `' . $this->config['columns']['id'] . '` = "' . $id 
				. '" LIMIT 1';
//		var_dump($sql);
		$this->result = $this->query($sql);
		return $this->parseFromResult();
	}
	
	private function query($sql) {
		$conn = self::get_connection($this->config['db']);
		$result = mysql_query($sql, $conn);
		if (!$result) {
			$errno = mysql_errno($conn);
			if ($errno == '2006' || $errno == '2013') {	// skip 'MySQL server has gone away' error
				$conn = self::get_connection($this->config['db'], '.read', true);
				$result = mysql_query($sql, $conn);
				if ($result === false) {
					throw new Exception("Fail to query in mysql! " . mysql_error($conn));
				}
			} else {
				throw new Exception("There's something wrong with the sql! {$sql}");
			}
		}
		return $result;
	}


	private function parseFromResult() {
		$row = mysql_fetch_assoc($this->result);
		if(!$row) return array();
		
		if(isset($row['attributeData'])) {
			preg_match_all("/([^:]+):(.*)\n/", $row['attributeData'] . "\n", $matches);
			foreach ($matches[1] as $matchKey => $dbName) {
					$row[$dbName] = str_replace(array('%%', '%n'), array( "%", "\n"), $matches[2][$matchKey]);
			}
			unset($matches, $row['attributeData']);
		}
		return $row;
	}

	//为了性能考虑，parse的时候可以少很多次的数据copy
	private function get_col_alias_name() {
		$string = '';
		foreach($this->config['columns'] as $obj => $db) {
			$string .= ", `$db` as `$obj`";
		}
		return trim($string, ',');
	}

	public function dumpIds() {
		$conn = self::get_connection($this->config['db']);
		$sql = "SELECT `{$this->config['columns']['id']}` FROM `{$this->config['table']}` ORDER BY `{$this->config['columns']['id']}` DESC" ;
		$this->result = mysql_unbuffered_query($sql, $conn);
		$bigArray = new BigArray();
		while($row = mysql_fetch_row($this->result)) {
			$bigArray->push($row[0]);
		}
		return $bigArray;
	}
}

class MongoStorage extends Storage implements ReadableStorage, SearchableStorage {
	private static $connections = array();

	private static function get_connection($db, $type = 'read') {
			if(!isset(self::$connections[$db.$type])) {
			$conn = new Mongo(Config::get("env.mongo.{$db}.{$type}.server"), Config::get("env.mongo.{$db}.{$type}.option"));
			self::$connections[$db.$type] = $conn->selectDB($db);
		}
		return self::$connections[$db.$type];
	}

	function load($id) {
		$result = self::get_connection($this->config['db'])
				->selectCollection($this->config['table'])
				->findOne(array(
					'_id' => preg_match('/^[0-9a-f]{24}$/', $id) ? new MongoId($id) : $id
					)
				);
		
		if(!$result) return array();
		
		array_walk_recursive($result, function(&$val) {
			if (is_string($val)) {
				$val = str_replace(array('ref:'), '', $val);
			}
		});

		$result['id'] = $result['_id'];
		unset($result['_id']);
		return $result;
	}

	public function dumpIds() {
		$cursor = self::get_connection($this->config['db'])
				->selectCollection($this->config['table'])
				->find(array(), array('_id' => true));

		$bigArray = new BigArray();
		foreach ($cursor as $row) {
			$bigArray->push($row['_id']);
		}
		return $bigArray;
	}
}

class IpStorage extends Storage implements ReadableStorage {
	public function load($ip) {
		if (ip2long($ip) === false) {
			return array();
		} else {
			return array( 'url' => "http://ip.taobao.com/service/getIpInfo.php?ip={$ip}" );
		} 
	}
}

class MobileNumberStorage extends Storage implements ReadableStorage {
	public function load($mobile) {
		if(preg_match('/^1[3458][0-9]{9}$/', $mobile)) {
			return array( 'url' => "http://www.ip138.com:8080/search.asp?action=mobile&mobile={$mobile}" );
		} else {
			return array();
		}
	}
}

class ImageStorage extends Storage implements ReadableStorage {
	public function load($img) {
		list($img_id, $type) = explode('.',  $img);
		if (strpos($type, '#up') !==  FALSE) {
			$type = explode('#', $type)[0];
			return $this->youpai($img_id, $type);
		} else {
			return $this->mongo($img_id, $type);
		}
	}
	private function mongo($img_id, $type) {
		$sizeList = [
			'big' => '',
			'square' => '_sq',
			'small' => '_sm',
			'square_180' => '/180x180',
		];
		$data = array();
		foreach ($sizeList as $name => $subfix) {
			$data[$name] = "http://img.baixing.net/m/{$img_id}{$subfix}.{$type}";
		}
		return $data;
	}

	private function youpai($img_id, $type) {
		$sizeList = array(
			'big'	=>	'bi',
			'square' => 'sq',
			'small' => 'sm',
			'square_180' => '180x180',
		);
		$data = array();
		foreach ($sizeList as $name => $subfix) {
			$data[$name] = "http://tu.baixing.net/{$img_id}.{$type}_{$subfix}";
		}
		return $data;
	}
}
