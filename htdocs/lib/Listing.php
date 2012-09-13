<?

class Listing {
	public static function ding($category, $area, $args) {
		return Service::factory('Ding')->ads($category, $area, $args);
	}

	public static function search($category, $area, $args, string $appendQuery = null) {
		$query = "category:{$category->id} areas:{$area->id} status:0";
		if ($appendQuery) $query .= " {$appendQuery}";
		$query = QueryParser::parse($query);//todo: fix when merge the prev pull request.
		$query = new AndQuery(
			new Query('category', $category->id)
			,new Query('areas', $area->id)
			,new Query('status', 0)
		);

		if ($appendQuery) $query->add($appendQuery);

		$allowedOptions = array(
			'size' => true,
			'from' => true,
		);
		$opts = array_intersect_key($args, $allowedOptions);
		$args = array_diff_key($args, $allowedOptions);
		foreach ($args as $field => $value) {
			if (preg_match('/^\[(\d*),(\d*)\]$/', $value, $m)) {
				//todo: may figure out a better way of "_i"
				$query->add(new RangeQuery($field . '_i', $m[1] ?: null, $m[2] ?: null));
			} elseif (Node::getType($value) == 'Entity') {
				$query->add(new Query('Entities', $value));
			} else {
				$query->add(new Query($field, $value));
			}
		}
		return Searcher::query('Ad', $query, $opts);
	}

}