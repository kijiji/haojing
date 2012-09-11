<?

class Listing {
	public static function ding($category, $area, $args) {
		return Service::factory('Ding')->ads($category, $area, $args);
	}

	public static function search($category, $area, $args, $appendQuery = null) {
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
			//todo: may figure out a better way of "_i"
			if (preg_match('/^\[(\d*),(\d*)\]$/', $value, $m)) {
				$query->add(new RangeQuery($field . '_i', $m[1] ?: null, $m[2] ?: null));
			} else {
				try {
					$node = new Node($value);
					if ($node->type() == 'Entity') {
						$query->add(new Query('Entities', $value));
						continue;
					}
				} catch (Exception $e) {}
				$query->add(new Query($field, $value));
			}
		}
		return Searcher::query('Ad', $query, $opts);
	}

}