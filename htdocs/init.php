<?php
//lianghonghao@baixing.com

define('PHP_CLI', '/home/php/bin/php');
define('HTDOCS_DIR', __DIR__);
define('TEMP_DIR', '/tmp');
define('LOG_DIR', '/home/logs');

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

