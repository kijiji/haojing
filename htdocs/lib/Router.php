<?php
//lianghonghao@baixing.com
class Router {
	public static function urlDispatch(Url $url) {
		$id = $url->segments(0);
		$base_object = graph($id);
		$controller_name = $base_object->type() ?: 'error';
		return ucfirst($controller_name);
	}

}
