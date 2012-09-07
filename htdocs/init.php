<?php
//lianghonghao@baixing.com
include __DIR__ . "/graph/init.php";

define('CONTROLLER_DIR', __DIR__ . "/controller");
define('VIEW_DIR', __DIR__ . "/view");

spl_autoload_register(function($name) {
	$name = strtr($name, '\\', DIRECTORY_SEPARATOR);
	if (file_exists(__DIR__ . "/lib/{$name}.php")) {
		require __DIR__ . "/lib/{$name}.php";
	}
});

set_exception_handler(array('ErrorHandler', 'handleException'));

