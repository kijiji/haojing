#!/usr/bin/env php
<?php
//yubing@baixing.com
//recommend setup: chmod +x /path/cli.php && ln -s /path/cli.php /usr/bin/haojing

include(__DIR__ . '/htdocs/init.php');

function usage() {
	echo "# Run php with haojing libs #\n";
	echo "Usage: \n";
	echo "\thaojing -r <php_code> \t run a piece of code \n";
	echo "\thaojing [a.php] \t run a single php\n";
	echo "\thaojing ['a.php?foo=bar&ping=pong'] \t \$_GET['foo'] is ready for use !\n";
	echo "\t\t\t\t\t don't forget the '' for escape when using multi args\n";
	exit(1);
}

function runCode($code) {
	return eval($code);
}

function runFile($file) {
	$pos = strpos($file, '?');
	if ( $pos !== false ){
		parse_str(substr($file, $pos + 1), $_GET);
		$file = substr($file, 0, $pos);
	}
	include $file;
}

if ( count($argv) < 2 ) {
	usage();
}

if ($argv[1] == '-r'){
	if (isset($argv[2])) {
		runCode($argv[2]);
	} else {
		usage();
	}
} else {
	runFile($argv[1]);
}
