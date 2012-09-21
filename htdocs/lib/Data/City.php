<?php
//lianghonghao@baixing.com
namespace Data;

use \Query, \Searcher;

class City {
	use \DataDelegateTrait;

	public static function loadByName($city_english_name) {
		$cities = Searcher::query('City', new Query('englishName', $city_english_name), ['size' => 1])->objs();
		return $cities ? new self($cities[0]) : null;
	}
}
