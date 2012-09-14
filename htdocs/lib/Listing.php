<?

class Listing {
	public static function ding($category, $area, $args) {
		return Service::factory('Ding')->ads($category, $area, $args);
	}

	public static function search($category, $area, $args, $appendQuery = null) {
		$query = new AndQuery(new RawQuery("category:{$category->id} areas:{$area->id} status:0"));
		if ($appendQuery) $query->add($appendQuery);

		$allowedOptions = ['size' => true, 'from' => true];
		$opts = array_intersect_key($args, $allowedOptions);
		$args = array_diff_key($args, $allowedOptions);
		foreach ($args as $field => $value) {
			if (Node::getType($value) == 'Entity') $field = 'entities';
			$query->add(new RawQuery("{$field}:{$value}"));
		}
		return Searcher::query('Ad', $query, $opts);
	}

}