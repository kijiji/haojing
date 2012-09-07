<?php
//lianghonghao@baixing.com
class Router {
	public static function urlDispatch(Url $url) {
		$segments = $url->segments();
		$controller_name = 'error';
		switch($segments[0]) {
			case 'u' :
				$controller_name = 'user';
				break;
		}
		return ucfirst($controller_name);
	}

}
