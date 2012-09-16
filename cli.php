#!/usr/bin/env php
<?php
//yubing@baixing.com
// recommend setup cmd : 
// file='/home/haojing/cli.php' && chmod +x $file && ln -s $file /usr/bin/haojing

include(__DIR__ . '/htdocs/init.php');

class Cli {
	private function usage() {
		echo "# Run php with haojing libs #\n";
		echo "Usage: \n";
		echo "\thaojing -r <php_code> \t run a piece of code \n";
		echo "\thaojing [a.php] \t run a single php\n";
		echo "\thaojing ['a.php?foo=bar&ping=pong'] \t \$_GET['foo'] is ready for use !\n";
		echo "\t\t\t\t\t don't forget the '' for escape when using multi args\n";
		exit(1);
	}

	private function runCode($code) {
		return eval($code);
	}

	private function runFile($argv) {
		array_shift($argv);	//确保用php file, haojing file的效果是一样的
		$file = $argv[0];
		$pos = strpos($file, '?');
		if ($pos !== false) {
			parse_str(substr($file, $pos + 1), $_GET);
			$file = substr($file, 0, $pos);
		}
		include $file;
	}

	public function __construct($argv) {
		if (count($argv) < 2) {
			$this->usage();
		}
		if ($argv[1] == '-r') {
			if (isset($argv[2])) {
				$this->runCode($argv[2]);
			} else {
				$this->usage();
			}
		} else {
			$this->runFile($argv);
		}
	}
}

new Cli($argv);
