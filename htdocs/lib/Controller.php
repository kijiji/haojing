<?php
//lianghonghao@baixing.com
class Controller {
	public static function delegate($url_router_result) {
		require CONTROLLER_DIR . "/{$url_router_result}.php";
		$class_name = "{$url_router_result}_Controller";
		$controller = new $class_name;
		return $controller->handle(new Url);
	}
}
