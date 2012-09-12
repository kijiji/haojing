<?
//zhaojun@baixing.com

abstract class Service {
	use DataDelegateTrait, AccrualBasedAccountingTrait, ValidatorTrait;

	public static function factory($type, $data = null) {
		$className = $type . 'Service';
		if (!class_exists($className)) throw new Exception('None implement of service type : ' . $type);
		return new $className($data);
	}

	public function init($params) {
		if ($this->id) throw new Exception('Can not init an exist service');
		foreach ($this->allowedFields() as $key => $validateRule) {
			$this->$key = $params->$key;
			if (!$this->validate($this->$key, $validateRule)) throw new Exception('need required field:' . $key);
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
		return ['userId' => true, 'days' => ['validNumber', 1, 365], 'listPrice' => ['validNumber', 0]];
	}

	protected function activate($time) {
		if ($this->startTime) throw new Exception('Already start!');
		$this->startTime = $time;
		$this->endTime = $this->days * 86400 + $time;
	}

	protected static function activeQuery($time = null) {
		if (!$time) $time = time();
		$_time = $time + 1;
		return "startTime:[,{$_time}] endTime:[{$time},] -cancelTime:[,{$_time}]";
	}
}

abstract class TimebasedService extends Service {
	protected function shippedPercentage($time) {
		if (!$this->vaildDays) throw new Exception("Service {$this->id} without days!");
		$time = min($time, $this->cancelTime ?: $this->endTime);
		return ceil(max(($time - $this->startTime), 0) / 86400) / $this->vaildDays;
	}

	abstract protected function subjectQuery();

	protected function activate($time) {
		$s = Searcher::query('Service', $this->subjectQuery() . " type:{$this->type} endTime:[{$time},]");
		$lastEndTime = 0;
		foreach ($s as $service) {
			$lastEndTime = max($lastEndTime, $service->cancelTime ?: $service->endTime);
		}
		$this->startTime = $lastEndTime;
		$this->endTime = $this->days * 86400 + $lastEndTime;
	}
}

class PortService extends TimebasedService {

	public function categoryMapping() {
		return [
			'port' => new Query('parent', 'fang'),
			'carStore' => new Query('parent', 'cheliang'),
			'jobPort' => new InQuery('parent', ['gongzuo', 'jianzhi']),
			'fuwuStore' => new InQuery('parent', ['fuwu']),
		];
	}

	public function isPortAd($ad) {
		$portType = null;
		foreach (self::categoryMapping() as $type => $filter) {
			if ($filter->accept($ad->category)) {
				$portType = $type;
				break;
			}
		}
		if (!$portType) return false;
		return $this->activeService($ad->user->id, $portType, $ad->city->englishName);
	}

	public function activeService($userId, $type = null, $cityEnglishName = null) {
		$q = self::activeQuery() . " user:{$userId}";
		if ($type) $q .= " type:{$type}";
		//todo: replace cityEnglishName with area when refactor.
		if ($cityEnglishName) $q .= " cityEnglishName:{$cityEnglishName}";
		$s = Searcher::query('Service', $q);
		return $s->totalCount() > 0 ? $s->objs()[0] : null;
	}

	protected function allowedFields() {
		return ['area' => true] + parent::allowfields();
	}

	protected function subjectQuery() {
		return "userId:{$this->userId}";
	}
}

class DingService extends TimebasedService {
	use BiddingPriceTrait;

	private static $types = ['ding', 'dingKeyword', 'dingAll', 'dingProvince'];

	public function ads($category, $area, $args) {
		$areas = Util::object_map($area->path(), 'id');
		$q = self::activeQuery() . " category:{$category->id} type:{" . join(',', self::$types) . "} area:{" . join(',', $areas) . "}";
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

		$ads += Listing::search($category, $area, $args, "id:{" . join(',', $adIds) . "}")->objs();//use Listing::search to re-check
		return $ads;
	}

	public function activeService($ad) {
		$s = Searcher::query('Service', self::activeQuery() . " ad:{$ad->id} type:{" . join(',', self::$types) . "}");
		return $s->totalCount() > 0 ? $s->objs()[0] : null;
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

trait AccrualBasedAccountingTrait {
	public function occuredRevenue($time) {
		return intval($this->price * $this->shippedPercentage($time)) / 100;
	}

	abstract protected function shippedPercentage($time);
}

class CompanyAccount {
	public static function occuredRevenue($startTime, $endTime) {
		$money = 0;

		if ($endTime > time())
			throw new Exception("Refuse to tell you future, because that may be inaccurate");

		$s = Searcher::query('Service', "startTime:[,{$endTime}] endTime:[" . ($startTime - 1) . ",]");

		foreach ($s->objs() as $serviceData) {
			$service = Service::factory(ucfirst($serviceData->type), $serviceData);
			$money += ($service->occuredRevenue($endTime) - $service->occuredRevenue($startTime));
		}

		return $money;
	}
}