<?php
spl_autoload_register(function($name){
	if (strpos($name, 'Everzet') === 0 ) {
		$paths = explode("\\", $name);
		array_shift($paths);
		$file_name = join(DIRECTORY_SEPARATOR, $paths);
		require(__DIR__ . DIRECTORY_SEPARATOR . $file_name . ".php");
	}
});