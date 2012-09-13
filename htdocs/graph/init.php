<?php
//yubing@baixing.com
//任何地方应该只要include了此文件，就能调用haojing类库

if( !defined('CONFIG_DIR')) {
	define('CONFIG_DIR', __DIR__ . '/config');
}

define('LOG_DIR', '/home/logs');
define('TEMP_DIR', '/tmp');
define('PHP_CLI', '/home/php/bin/php');
define('HTDOCS_DIR', __DIR__);

spl_autoload_register(function($name) {
	if (file_exists(__DIR__ . "/lib/{$name}.php")) {
		require __DIR__ . "/lib/{$name}.php";
	}
});

require __DIR__ . '/lib/Graph.php';
require __DIR__ . '/lib/Query.php';
