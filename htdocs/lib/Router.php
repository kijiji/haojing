<?php
//lianghonghao@baixing.com
class Router {
	public static function urlDispatch(Url $url) {
		if (strpos($url->getCurrentHost(), 'graph') !== false) return 'Graph';
		if (count($url->segments()) == 0) return 'Home';
		$id = $url->segments(0);
		$base_object = graph($id);
		$controller_name = $base_object->type() ?: 'error';
		return ucfirst($controller_name);
	}

}
