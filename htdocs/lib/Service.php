<?
//zhaojun@baixing.com

abstract class DataDelegate {
	protected $data;

	function __construct($data = null) {
		if ($data) $this->data = $data;
	}

	function __get($name) {
		return $this->data->$name;
	}

	function __set($name, $value) {
		return $this->data->$name = $value;
	}

	function __call($name, $args) {
		return call_user_func_array(array($this->data, $name), $args);
	}
}

abstract class Service extends DataDelegate {
	function occuredRevenue($time) {
		return intval($this->price * $this->shippedPercentage($time)) / 100;
	}

	public static function factory($type, $data = null) {
		$className = $type . 'Service';
		return new $className($data);
	}

	function init($params) {
		if ($this->id) throw new Exception('Can not init an exist service');
		foreach ($this->allowfields() as $key => $required) {
			$this->$key = $params->$key;
			if ($required && is_null($this->$key)) throw new Exception('need required field:' . $key);
		}
		$this->save();
	}
	
	function allowfields() {
		return array('userId' => 1, 'validDays' => 1, 'listPrice' => 1);
	}

	function pay($price) {
		$this->price = $price;
		$this->setValidWindow(time());
		$this->save();
	}

	function setValidWindow($time) {
		if ($this->startTime) throw new Exception('Already start!');
		$this->startTime = $time;
		$this->endTime = $this->validDays * 86400 + $time;
	}

	function cancel($time) {
		if ($time > $this->endTime || $this->cancelTime) throw new Exception('Can not be canceled!');
		$this->cancelTime = $time;
		$this->save();
	}
	
	public static function activeQuery($time = null) {
		if (!$time) $time = time();
		return new AndQuery(
				new DateRangeQuery('startTime', null, $time + 1),
				new DateRangeQuery('endTime', $time, null),
				new NotQuery(new DateRangeQuery('cancelTime', null, $time + 1))
			);
	}
}

abstract class DaybasedService extends Service {
	function shippedPercentage($time) {
		$time = min($time, $this->cancelTime ?: $this->endTime);
		return ceil(max(($time - $this->startTime), 0) / 86400) / $this->vaildDays;
	}

	function setValidWindow($time) {
		$s = Searcher::query('Service', new AndQuery(
				$this->uniqleQuery(),
				new Query('type', $this->type),
				new RangeQuery('endTime', $time)
			));
		$lastEndTime = 0;
		foreach ($s as $service) {
			$lastEndTime = max($lastEndTime, $service->cancelTime ?: $service->endTime);
		}
		$this->startTime = $lastEndTime;
		$this->endTime = $this->validDays * 86400 + $lastEndTime;
	}
}

class PortService extends DaybasedService {

	static function categoryMapping() {
		return [
			'port' => new Query('parent', 'fang'),
			'carStore' => new Query('parent', 'cheliang'),
			'jobPort' => new InQuery('parent', ['gongzuo', 'jianzhi']),
			'fuwuStore' => new InQuery('parent', 'fuwu'),
		];
	}

	function allowfields() {
		return array('category' => 1, 'area' => 1) + parent::allowfields();
	}
	
	function uniqleQuery() {
		return new Query('userId', $this->userId);
	}

    static function isPortAd($ad) {
		$portType = null;
    	foreach (self::categoryMapping() as $type => $filter) {
    		if ($filter->accept($ad->category)) {
    			$portType = $type;
    			break;
    		}
    	}
    	if (!$portType) return false;
    	return self::isOnService($ad->user->id, $portType, $ad->city->id);
    }

    static function isOnService($userId, $type = null, $cityId = null) {
		$q = new AndQuery(
				self::activeQuery()
				,new Query('user', $userId)
			);
		if ($type) $q->add(new Query('type', $type));
		if ($cityId) $q->add(new Query('city', $cityId));
		$s = Searcher::query('Service', $q);
		return $s->totalCount() > 0;
    }
}

class DingService extends DaybasedService {
	private static $types = array('ding', 'dingKeyword', 'dingAll', 'dingProvince');
	function allowfields() {
		return array('adId' => 1, 'category' => 1, 'area' => 1, 'tag' => 0) + parent::allowfields();
	}

	function uniqleQuery() {
		return new Query('adId', $this->adId);
	}

	public static function ads($category, $area, $args) {
		include_once('./lib/Listing.php');
		
		$areas = Util::object_map($area->path(), 'id');
		$q = new AndQuery(
				self::activeQuery()
				,new InQuery('type', self::$types)
				,new Query('category', $category->id)
				,new InQuery('area', $areas)
			);
		$s = Searcher::query('Service', $q);
		$adIds = array();
		$ads = array();
		foreach ($s->objs() as $service) {
			if (in_array($service->type, array('dingAll', 'dingProvince'))) {
				$ads[] = $service->ad;
				continue;
			} elseif ($service->type == 'dingKeyword') {
				if (!in_array($service->tag->type, array('area2', 'area3')) && !in_array($service->tag->id, $args) && 
					(
						!$service->tag->children || 
						!array_intersect($service->tag->children, $args) //only support 2 level tags
					)
				)
					continue;
			}
			$adIds[] = $service->ad->id;
		}
		$ads += Listing::search($category, $area, $args, new InQuery('id', $adIds))->objs();
		return $ads;
	}

    static function isOnService($ad) {
		$q = new AndQuery(
				self::activeQuery()
				,new InQuery('type', self::$types)
				,new Query('ad', $ad->id)
			);
		$s = Searcher::query('Service', $q);
		return $s->totalCount() > 0;
    }
}

