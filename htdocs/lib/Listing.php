<?
//zhaojun@baixing.com
include_once(__DIR__ . '/../graph/lib/Searcher.php');

class ListingPlugin extends Plugin {
	private $category;
	private $args;
	private $query;
	private $opts;

	public function getMethods() {
		return [
			[
				'method' => 'ListingConnection->find()',
				'function' => 'buildQuery',
				'type' => Hive::TYPE_BEFORE,
			], [
				'method' => 'ListingConnection->find()',
				'function' => 'ding',
				'type' => Hive::TYPE_CHANGE_RESULT,
			], [
				'method' => 'ListingConnection->find()',
				'function' => 'extend',
				'type' => Hive::TYPE_CHANGE_RESULT,
			]
		];
	}

	public function buildQuery($args) {
		list($this->category, $this->args) = $args;
		list($this->query, $this->opts) = Listing::buildQuery($this->category, $this->args);
	}

	public function ding($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		$dingResult = Service::factory('Ding')->ads($this->category, $this->args);
		$listingResult->prepend($dingResult->ids());
		return $listingResult;
	}

	public function extend($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		//	$ids = CollabrationFilter::getRecommendAds($this->category, $this->args);
		//	$extendResult = Searcher::query('Ad', new InQuery('id', $ids));
		//	$listingResult->append($extendResult->ids());
		return $listingResult;
	}
}

class ListingSearchResult extends SearchResult {
	public function __construct(SearchResult $r) {
		$this->ids = $r->ids();
		list($this->size, $this->from) = array_values($r->page());
		$this->totalCount = $r->totalCount();
	}

	public function append(array $ids) {
		$this->ids += $ids;
	}

	public function prepend(array $ids) {
		$this->ids = $ids + $this->ids;
	}

	//todo: to be implement
	public function applyFilter($filter) {}
}

class Listing {

	public static function ads($category, $args) {
		list($query, $opts) = self::buildQuery($category, $args);
		return new ListingSearchResult(Searcher::query('Ad', $query, $opts));
	}

	public static function entities($category, $args) {
		$query = self::buildQuery($category, $args)[0];
		$facetEntities = Searcher::facet('Ad', $query, ['field' => 'entities', 'size' => 200]);
		$tagSet = [];
		foreach ($facetEntities as $entity => $count) {
			if ($count < 3) continue;
			$node = new Node($entity);
			$tagSet[$node->type][] = $node;
		}
		return array_filter($tagSet, function ($tags) { return count($tags) > 1; });
	}

	public static function buildQuery($category, $args) {
		$query = new AndQuery(new RawQuery("category:{$category->id} status:0"));
		if (isset($args['area'])) {
			$query->add(new Query('area', $args['area']));
			unset($args['area']);
		}
		$allowedOptions = ['size' => true, 'from' => true];
		$opts = array_intersect_key($args, $allowedOptions);
		$args = array_diff_key($args, $allowedOptions);
		foreach ($args as $field => $value) {
			if ('Entity' == Node::getType($value)) $field = 'entities';
			if (is_numeric($field) || $field == 'query') $field = 'content'; //todo: check if "content" can be searched by split words
			$query->add(new RawQuery("{$field}:'{$value}'"));
		}
		return [$query, $opts];
	}
}