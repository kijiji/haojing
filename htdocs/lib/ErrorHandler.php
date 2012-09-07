<?php
//lianghonghao@baixing.com
class ErrorHandler {
	public static function handleException(Exception $exception) {
		#@todo 现在只是简单的显示一下错误 by lianghonghao@baixing.com
		if ($exception instanceof Exception\Viewable) {
			echo $exception;
		} else {
			echo new View('error/404');
		}
	}
}
