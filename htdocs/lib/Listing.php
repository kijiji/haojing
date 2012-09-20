<?

class ListingPlugin extends Plugin {
	private $category;
	private $args;
	private $query;
	private $opts;

	public function getMethods() {
		$plugins = [];
		$plugins[] = [
			'method' => 'ListingConnection->find()',
			'function' => 'ding',
			'type' => Hive::TYPE_CHANGE_RESULT,
		];
		$plugins[] = [
			'method' => 'ListingConnection->find()',
			'function' => 'extend',
			'type' => Hive::TYPE_CHANGE_RESULT,
		];
		return array_merge(parent::getMethods(), $plugins);
	}
	/*
	public function changeListingConnection__findResult ($res) {
		echo 'xx';
		try {
			throw new Exception('ww');
		} catch (Exception $e) {
			echo 'exp';
		}
		return $res;
	}
*/
	public function beforeListingConnection__find($args) {
		list($this->category, $this->args) = $args;
		list($this->query, $this->opts) = Listing::buildQuery($this->category, $this->args);
	}

	public function ding($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		$dingResult = Service::factory('Ding')->ads($this->category, $this->args);
		$listingResult->mergeIds($dingResult->ids(), true);
		return $listingResult;
	}

	public function extend($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		//	$ids = CollabrationFilter::getRecommendAds($this->category, $this->args);
		//	$extendResult = Searcher::query('Ad', new InQuery('id', $ids));
		//	$listingResult->mergeIds($extendResult->ids());
		return $listingResult;
	}
}

class Listing {

	public static function ads($category, $args) {
		list($query, $opts) = self::buildQuery($category, $args);
		return Searcher::query('Ad', $query, $opts);
	}

	public static function entities($category, $args) {
		list($query, $opts) = self::buildQuery($category, $args);
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
		//$query = new AndQuery(new RawQuery("entities:{$category->objectId} status:0"));
		$allowedOptions = array(
			'size' => true,
			'from' => true,
		);
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