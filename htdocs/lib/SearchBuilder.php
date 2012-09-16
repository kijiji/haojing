<?php

if (!defined('LOG_DIR')) trigger_error('LOG_DIR is undefined !', E_USER_ERROR);
if (!defined('TEMP_DIR')) trigger_error('TEMP_DIR is undefined !', E_USER_ERROR);

class SearchBuilder extends Data {
	private $type;

	public function __construct($type) {
		$this->type = $type;
	}

	private function getIds($func_name, $arg = null) {
		$storage = $this->getStorage($this->type);
		if (!$storage instanceof SearchableStorage) {
			return [];
		}
		$sids = $storage->$func_name($arg);
		$gids = new BigArray();
		foreach($sids as $sid) {
			$gids->push($this->gid($sid, $this->type));
		}
		unset($sids);
		return $gids;
	}

	public function buildAll(){
		$this->build($this->getIds('getAllIds'));
	}

	public function buildModified($since){
		$since = $since ?: $this->readLastModified();
		if($since) {
			$this->build($this->getIds('getModifiedIds', $since));
		} else {
			trigger_error("You Need do a full build for `{$this->type}` first", E_USER_ERROR);
		}
	}

	private function build($id_list) {
		$last_modified = 0;
		foreach($id_list as $id) {
			$node = new Node($id);
			try {
				$node->load();
			} catch (Exception $exc) {
				file_put_contents(LOG_DIR . '/es_build_error.log', $exc->getMessage() . "\n", FILE_APPEND);
				continue;
			}
			Searcher::index($node->type(), $this->buildDoc($node));
			file_put_contents(LOG_DIR . "/build_{$node->type()}.log", date("Y-m-d H:i:s") . ": {$id}\n", FILE_APPEND);

			//这部分是buildModified()的逻辑，兼容没有modifiedTime表的更新。和MysqlStorage::getModifiedIds()的逻辑对应。
			if (isset($node->createdTime)) {
				$col = isset($node->modifiedTime) ? 'modifiedTime' : 'createdTime' ;
				if ( $node->$col > $last_modified ) {
					$last_modified = $node->$col;
					$this->saveLastModified($last_modified);
				}
			}
		}
	}
	
	public static function buildOne($id) {
		$node = new Node($id);
		try {
			$node->load();
			$builder = new self($node->type());
			Searcher::index($node->type(), $builder->buildDoc($node));
		} catch (Exception $exc) {
			file_put_contents(LOG_DIR . '/es_build_error.log', $exc->getMessage() . "\n", FILE_APPEND);
		}
	}

	private function logFile() {
		return LOG_DIR . "/last_{$this->type}.log";
	}

	private function saveLastModified($time) {
		if($this->readLastModified() < $time) {
			file_put_contents($this->logFile(), $time);
		}
	}

	private function readLastModified() {
		$file = $this->logFile();
		if(file_exists($file)) {
			return file_get_contents($file);
		} else {
			return 0;
		}
	}

	private function buildDoc($node) {
		$className = $node->type() . 'Doc';
		if (class_exists($className)) return $className::build($node);
		else return NodeDoc::build($node);
	}

}

class NodeDoc {
	public static function build($node) {
		$doc = [];
		foreach ($node as $name => $value) {
			if ($value instanceof Node) {
				$doc[$name] = $value->id;
			} elseif (!is_scalar($value) || strlen($value) == 0 || is_array($value)) {
				continue;
			} else {
				$doc[$name] = $value;
			}
		}
		return $doc;
	}
}

class AdDoc extends NodeDoc {
	public static function build($ad) {
		$doc = parent::build($ad);
		$tags = $entities = [];
		$doc['categoryEntity'] = $ad->category->objectId;
		$meta = self::parseMeta($ad->category);
		foreach ($doc as $key => $value) {
			$type = Node::getType($value);
			if ($type == 'Entity') {
				try {
					$path = (new Node($value))->load()->path();
				} catch (Exception $e) {
					$path = false;
				}
			}
			if ($type == 'Entity' && $path) {
				$tags = array_merge($tags, Util::object_map($path, 'name'));
				$entities = array_merge($entities, Util::object_map($path, 'id'));
			} elseif (!$type && mb_strlen($key) != strlen($key)) {//only chinese attribute
				$tags[] = $value;
			}
		}
		$doc['tags'] = join(' ', array_map(function($v){return str_replace(' ', '', $v);}, $tags));
		$doc['entities'] = join(' ', $entities);

		$area = $ad->area ?: graph($ad->city->objectId);
		if($area) $doc['areas'] = join(' ', Util::object_map($area->path(), 'id'));
		$doc['content'] = $doc['content'] . PHP_EOL . $doc['tags'];	//content include all
		return $doc;
	}

	private static $meta;
	private static function parseMeta($category) {
		if (isset(self::$meta[$category->id])) return self::$meta[$category->id];
		libxml_use_internal_errors(TRUE);
		$metaData = simplexml_load_string($category->metaData);
		if (!$metaData) return [];
		$metaArray = [];
		foreach ($metaData->meta as $meta) {
			$metaArray[strval($meta->name)] = $meta;
		}
		return self::$meta[$category->id] = $metaArray;
	}
}

class UserDoc extends NodeDoc {
	public static function buildDoc($node) {
		$doc = parent::buildDoc($node);
		unset($doc['password']);	//不能索引密码字段
		return $doc;
	}
}
