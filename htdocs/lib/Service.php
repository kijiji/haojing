<?
//zhaojun@baixing.com

abstract class Service {
	use DataDelegateTrait, AccrualBasedAccountingTrait, ValidatorTrait {
		DataDelegateTrait::__contruct				as __contruct;
		DataDelegateTrait::bind						as bind;
		AccrualBasedAccountingTrait::occuredRevenue as occuredRevenue;
        ValidatorTrait::validate 					as validate;
	}

	public static function factory($type, $data = null) {
		$className = $type . 'Service';
		return new $className($data);
	}

	public function init($params) {
		if ($this->id) throw new Exception('Can not init an exist service');
		foreach ($this->allowedFields() as $key => $required) {
			$this->$key = $params->$key;
			if ($required && !$this->validate($required, $this->$key)) throw new Exception('need required field:' . $key);
		}
		$this->save();
	}

	public function pay($price) {
		$this->price = $price;
		$this->activate(time());
		$this->save();
	}

	public function cancel($time) {
		if ($time < time() - 10) throw new Exception('Can not cancel service history.');// make 10sec buffer.
		if ($time > $this->endTime || $this->cancelTime) throw new Exception('Can not be canceled!');
		$this->cancelTime = $time;
		$this->save();
	}

	protected function allowedFields() {
		return ['userId' => true, 'validDays' => ['validInt', 1, 365], 'listPrice' => ['validInt', 1]];
	}

	protected function activate($time) {
		if ($this->startTime) throw new Exception('Already start!');
		$this->startTime = $time;
		$this->endTime = $this->validDays * 86400 + $time;
	}

	protected static function activeQuery($time = null) {
		if (!$time) $time = time();
		return new AndQuery(
				new DateRangeQuery('startTime', null, $time + 1),
				new DateRangeQuery('endTime', $time, null),
				new NotQuery(new DateRangeQuery('cancelTime', null, $time + 1))
			);
	}
}

abstract class TimebasedService extends Service {
	protected function shippedPercentage($time) {
		$time = min($time, $this->cancelTime ?: $this->endTime);
		return ceil(max(($time - $this->startTime), 0) / 86400) / $this->vaildDays;
	}

	abstract protected function subjectQuery();

	protected function activate($time) {
		$s = Searcher::query('Service', new AndQuery(
				$this->subjectQuery(),
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

class PortService extends TimebasedService {

	public static function categoryMapping() {
		return [
			'port' => new Query('parent', 'fang'),
			'carStore' => new Query('parent', 'cheliang'),
			'jobPort' => new InQuery('parent', ['gongzuo', 'jianzhi']),
			'fuwuStore' => new InQuery('parent', ['fuwu']),
		];
	}

    public static function isPortAd($ad) {
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

    public static function isOnService($userId, $type = null, $cityId = null) {
		$q = new AndQuery(
				self::activeQuery()
				,new Query('user', $userId)
			);
		if ($type) $q->add(new Query('type', $type));
		if ($cityId) $q->add(new Query('city', $cityId));
		$s = Searcher::query('Service', $q);
		return $s->totalCount() > 0;
    }

	protected function allowedFields() {
		return ['area' => true] + parent::allowfields();
	}
	
	protected function subjectQuery() {
		return new Query('userId', $this->userId);
	}

}

class DingService extends TimebasedService {
	use BiddingPriceTrait;

	private static $types = ['ding', 'dingKeyword', 'dingAll', 'dingProvince'];

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

		//todo: need refactor when switch write.
		$adIds = $ads = [];
		foreach ($s->objs() as $service) {
			if (in_array($service->type, ['dingAll', 'dingProvince'])) { // need not use Listing::search to re-check
				$ads[] = $service->ad;
				continue;
			} elseif ($service->type == 'dingKeyword') {
				if (!in_array($service->tag->type, ['area2', 'area3']) && !in_array($service->tag->id, $args) && 
					(
						!$service->tag->children || 
						!array_intersect($service->tag->children, $args) //only support 2 level tags
					)
				)
					continue;
			}
			$adIds[] = $service->ad->id;
		}
		$ads += Listing::search($category, $area, $args, new InQuery('id', $adIds))->objs();//use Listing::search to re-check
		return $ads;
	}

    public static function isOnService($ad) {
		$q = new AndQuery(
				self::activeQuery()
				,new InQuery('type', self::$types)
				,new Query('ad', $ad->id)
			);
		$s = Searcher::query('Service', $q);
		return $s->totalCount() > 0;
    }

	protected function allowedFields() {
		return ['adId' => true, 'category' => true, 'area' => true, 'tag' => false] + parent::allowfields();
	}

	protected function subjectQuery() {
		return new Query('adId', $this->adId);
	}
}

trait BiddingPriceTrait {
	public static function price() {
		//todo: implement when switch write
	}
}

trait UserAccountTrait {
	private $balance, $ratio;
	
	public function in($money, $credit) {
		$this->ratio = $money + ($this->balance * $this->ratio) / ($this->balance + $money + $credit);
		$this->balance += $money + $credit;
		$this->save();
	}

	public function out($mondit) {
		$this->balance -= $mondit;
		$this->save();
	}

	public function pay($service) {
		if ($this->balance < $service->listPrice) throw new Exception('Not enough money to pay.');
		$service->pay($this->ratio * $service->listPrice);
		$this->out($service->listPrice);
	}

	public function cancel($service) {
		$time = time();
		$service->cancel($time);
		$refund = $service->price - $service->occuredRevenue($time);
		$ratio = $service->price / $service->listPrice;
		$this->in($refund, ($refund / $ratio) * (1 - $ratio));
	}
}

trait DataDelegateTrait {
	protected $data;

	public function __construct($data = null) {
		if ($data) $this->bind($data);
	}
	
	public function bind($data) {
		$this->data = $data;
	}

	public function __get($name) {
		return $this->data->$name;
	}

	public function __set($name, $value) {
		return $this->data->$name = $value;
	}

	public function __call($name, $args) {
		return call_user_func_array(array($this->data, $name), $args);
	}
}

trait ValidatorTrait {
	//todo: implement errors and message.

	protected function validate($exp, $value) {
		if (is_array($exp) && !call_user_func_array(['self', $exp[0]], array_merge([$value], array_slice($exp, 1))))
			return false;
		elseif ($exp && is_null($this->$key))
			return false;
		return true;
	}

	protected static function validInt($value, $min = null, $max = null) {
		return is_numeric($value) && (is_null($min) ? true : $value >= $min) && (is_null($max) ? true : $value <= $max);
	}

	protected static function validMobile($value) {
		return preg_match('/^1(3|4|5|8)\d{9}$/', $value);
	}

	protected static function validStringLength($value, $min = null, $max = null) {
		return self::validInt(mb_strlen($value, 'utf8'), $min, $max);
	}
}

trait AccrualBasedAccountingTrait {
	public function occuredRevenue($time) {
		return intval($this->price * $this->shippedPercentage($time)) / 100;
	}

	abstract protected function shippedPercentage($time);
}

class CompanyAccount {
	function occuredRevenue($starTime, $endTime) {
		$money = 0;

		if ($endTime > time()) 
			throw new Exception("Refuse to tell you future, because that may be inaccurate");

		$q = new AndQuery(
				new DateRangeQuery('startTime', null, $endTime),
				,new DateRangeQuery('endTime', $startTime - 1, null)
			);
		$s = Searcher::query('Service', $q);

		foreach ($s->objs() as $service)
			$money += ($service->occuredRevenue($endTime) - $service->occuredRevenue($startTime));

		return $money;
	}
}

//todo: put in Haojing' util dir
class Util {
	public static function object_map($arrayOfObjects, $key) {
		return array_map(function($o) use ($key) {return $o->$key;}, $arrayOfObjects);
	}
}