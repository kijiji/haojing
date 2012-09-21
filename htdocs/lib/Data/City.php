<?php
//lianghonghao@baixing.com
namespace Data;

use \Query;

class City {
	use \DataDelegateTrait;

	public static function loadByName($city_english_name) {
		$city = new self();
		$cities = \Searcher::query('City', new Query('englishName', $city_english_name), [
			'size' => 1
		])->objs();
		$city->bind(reset($cities));
		return $city;
	}
}
