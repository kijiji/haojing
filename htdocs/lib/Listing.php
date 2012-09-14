<?

class Listing {
	public static function ads($category, $args, $opts) {
		return Searcher::query('Ad', self::getQuery($category, $args), $opts)->objs();
	}

	public static function getQuery($category, $args) {
		$query = new AndQuery(new RawQuery("category:{$category->id} status:0"));
		//$query = new AndQuery(new RawQuery("entities:{$category->objectId} status:0"));

		foreach ($args as $field => $value) {
			if (Node::getType($value) == 'Entity') $field = 'entities';
			if ($field == 'query') $field = 'content'; //todo: check if "content" can be searched by split words
			$query->add(new RawQuery("{$field}:'{$value}'"));
		}
		return $query;
	}

	public static function addDingAds($ads, $category, $args, $opts) {
		if ($opts['from'] > 0) return $ads;
		$dingAds = Service::factory('Ding')->ads($category, $args, $opts);
		$dingIds = Util::object_map($dingAds, 'id');
		$ads = array_filter($ads, function ($ad) use ($dingIds) { return !in_array($ad->id, $dingIds); });
		return array_merge($dingAds, $ads);
	}

	public static function addExtendAds($ads, $category, $args, $opts) {
		if (count($ads) >= $opts['size']) return $ads;
		// use collaboration filter
		/*
		$key = $category->id . '/' . urldecode(http_build_query($args));
		$cfLstSet = new \Redis\Set('CF_' . $key . '_' . date('md'), 86400 * 3);
		$cfLstSet->add(Visitor::trackId());
		$ms = $cfLstSet->members();
		$setNames = array();
		foreach (array_slice($ms, 0, 100) as $vtrId) {
			$k = 'CF_' . $vtrId . '_' . $categoryEnglishName . date('md', time() - 86400);
			$zs = new Redis\ZSet($k, 86400 * 3);
			$setNames[] = $zs->RedisId;
		}
		$adIds = Redis\ZSet::union($setNames)->range(0, 100, Redis\ZSet::ORDER_REV);
		foreach (Ad::loader()->loads(array_keys($adIds)) as $ad) {
			echo "<a href='{$ad->link()}'>{$ad->title}</a> ({$adIds[$ad->id]})<br />";
		}

		$existIds = Util::object_map($ads, 'id');
		return array_merge($ads, Node::multiLoad(array_diff(array_keys($adIds), $existIds)));
		*/
		return $ads;
	}

	public static function tags($category, $args) {
		$facetEntities = Searcher::facet('Ad', self::getQuery($category, $args), ['field' => 'entities', 'size' => 200]);
		$tagSet = [];
		foreach ($facetEntities as $entity => $count) {
			if ($count < 20) continue;
			$node = new Node($entity);
			$tagSet[$node->type][] = $node;
		}
		return array_filter($tagSet, function ($tags) { return count($tags) > 1; });
	}
}