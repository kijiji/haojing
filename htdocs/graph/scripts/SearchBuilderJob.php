<?php
//yubing@baixing.com
include( '/home/data/init.php');	//多层init的结构还是有问题，先写死路径。

if( count($argv) < 2 ) {
	usage();
}

switch ($argv[1]) {
	case 'build_all':
		buildAll();
		break;
	case 'update_all':
		updateAll();
		break;
	case 'build':
		if (count($argv) < 3) {
			usage();
		}
		build($argv[2]);
		break;
	case 'update':
		if (count($argv) < 3) {
			usage();
		}
		$since = isset($argv[3]) ? $argv[3] : 0;
		update($argv[2], $since);
		break;
	default:
		echo 'Unknown cmd';
		break;
}

function usage() {
	echo ("Usage: php SearchBuilderJob.php { build_all | build | update | update_all } [type_name] [update_since]\n");
	exit(1);
}

function buildAll() {
	$types = array_keys(Config::get('routing'));
	foreach ($types as $name) {
		echo "start building {$name}\n";
		shell_exec("/home/php/bin/php ". __FILE__ ." build {$name} > /dev/null 2>&1 &");
		sleep(120);	//避免db上太多dump出现资源竞争
	}
}

function updateAll() {
	$types = array_keys(Config::get('routing'));
	foreach ($types as $name) {
		echo "start updating {$name}\n";
		shell_exec("/home/php/bin/php ". __FILE__ ." update {$name} > /dev/null 2>&1 &");
		sleep(5);	//避免db上太多dump出现资源竞争
	}
}

function build($type) {
	return (new SearchBuilder($type))->buildAll();
}

function update($type, $since) {
	return (new SearchBuilder($type))->buildModified($since);
}
