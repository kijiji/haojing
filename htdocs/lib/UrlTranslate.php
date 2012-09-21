<?php
//lianghonghao@baixing.com
use Data\City;
class UrlTranslate {
	public static function toGraph(Url $url) {
		if ($url->segments(0) == 'u') {
			$url->setPath('/u' . $url->segments(1));
		} elseif (count($url->segments()) == 1 && ctype_alpha($url->segments(0))){
			$city = City::loadByName(explode('.', $url->getCurrentHost())[0]);
			$url->setPath($url->segments(0));
			$url->set('area', $city->objectId);
		} elseif (preg_match('#a(\d+)\.html#', $url->segments(1), $matches)) {
			$url->setPath($matches[1]);
		}
		return $url;
	}
}
