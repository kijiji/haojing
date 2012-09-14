<?php
//panjie@baixing.com

class Http {
	private $instance;
	private $times = 1;

	public static function getUrl($url, $timeout = 1){
		$ch = new self($timeout);
		return $ch->get($url);
	}

	public static function postUrl($url, $params, $timeout = 1, $cookie = ''){
		$ch = new self($timeout);
		if ($cookie)	$ch->setOpt(array(CURLOPT_COOKIE => $cookie));
		return $ch->post($url, $params);
	}

	/**
	 * @param int|float $timeout 超时设置
	 * @param int $times 连续请求的次数，请准确设定。如果大于1则keepalive，直到全部请求完成才关闭连接
	 */
	public function __construct($timeout = 1, $times = 1) {
		$this->instance = curl_init();
		if ($timeout < 1) {
			curl_setopt($this->instance, CURLOPT_TIMEOUT_MS, intval($timeout * 1000));
		} else {
			curl_setopt($this->instance, CURLOPT_TIMEOUT, intval($timeout));
		}
		curl_setopt($this->instance, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->instance, CURLOPT_FOLLOWLOCATION, true);    //支持抓取302/301跳转后的页面内容
		$this->times = $times;
	}

	public function get($url) {
		if (!$this->instance)	return;
		curl_setopt($this->instance, CURLOPT_URL, $url);
		curl_setopt($this->instance, CURLOPT_HTTPGET, true);
		return $this->excute();
	}

	/**
	 * @param string $url
	 * @param mixed $params	 可以为"p1=val1&p2=val2&..."的字符串或一个数组，请求的Content-Type分别为 application/x-www-form-urlencoded 和 multipart/form-data
	 */
	public function post($url, $params) {
		if (!$this->instance)	return;
		curl_setopt($this->instance, CURLOPT_URL, $url);
		curl_setopt($this->instance, CURLOPT_POST, true);
		curl_setopt($this->instance, CURLOPT_POSTFIELDS, $params);
		return $this->excute();
	}

	private function excute() {
		$result = curl_exec($this->instance);
		$this->times --;
		if (curl_errno($this->instance))	$result = false;
		if ($this->times <= 0) {
			curl_close($this->instance);
			$this->instance = null;
		}
		return $result;
	}

	/**
	 * 设定自定义参数
	 * @param array $optArray
	 */
	public function setOpt($optArray) {
		if (!$this->instance)	return;
		if (!is_array($optArray))	throw new Exception("Argument is not an array!");
		curl_setopt_array($this->instance, $optArray);
	}
}
