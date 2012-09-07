<?php
//lianghonghao@baixing.com
class Cookie {
	public static function set($name, $value, $ttl = 0, $http_only = false) {
		if (PHP_SAPI == 'cli') return false;
		$expire = $ttl ? $ttl + time() : 0;
		return setcookie($name, $value, $expire, '/', 'baixing.com', false, $http_only);
	}

	public static function get($name) {
		return isset($_COOKIE[$name]) ? trim($_COOKIE[$name]) : null;
	}

	public static function delete($name) {
		return self::set($name, null, -8640000);
	}
}
