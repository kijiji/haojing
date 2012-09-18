<?php
//yubing@baixing.com

if( !defined('CONFIG_DIR')) {
	define('CONFIG_DIR', __DIR__ . '/config');
}

mb_internal_encoding('UTF-8');

spl_autoload_register(function($name) {
	if (file_exists(__DIR__ . "/lib/{$name}.php")) {
		require __DIR__ . "/lib/{$name}.php";
	}
});

require __DIR__ . '/lib/Graph.php';
require __DIR__ . '/lib/Query.php';
