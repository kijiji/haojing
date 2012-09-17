<?php
//yubing@baixing.com

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
	case 'build_from_file':
		if (count($argv) < 3) {
			usage();
		}
		buildFile($argv[2]);
		break;
	case 'update':
		if (count($argv) < 3) {
			usage();
		}
		$since = isset($argv[3]) ? $argv[3] : 0;
		update($argv[2], $since);
		break;
	default:
		echo "Unknown cmd: {$argv[1]}\n";
		break;
}

function usage() {
	echo ("Usage: php SearchBuilderJob.php { build_all | build | update | update_all | build_from_file } [type_name] [update_since]\n");
	exit(1);
}

function buildAll() {
	$types = array_keys(Config::get('routing'));
	foreach ($types as $name) {
		if($name == 'User') continue;	//ES内存不足，暂时不build User
		echo "start building {$name}\n";
		shell_exec(HAOJING_CLI . " " . __FILE__ ." build {$name} > /dev/null 2>&1 &");
		sleep(120);	//避免db上太多dump出现资源竞争
	}
}

function updateAll() {
	$types = array_keys(Config::get('routing'));
	foreach ($types as $name) {
		if($name == 'User') continue;	//ES内存不足，暂时不build User
		echo "start updating {$name}\n";
		shell_exec(HAOJING_CLI . " " . __FILE__ ." update {$name} > /dev/null 2>&1 &");
		sleep(5);	//避免db上太多dump出现资源竞争
	}
}

function build($type) {
	return (new SearchBuilder($type))->buildAll();
}

function update($type, $since) {
	return (new SearchBuilder($type))->buildModified($since);
}

function buildFile($fileDir) {
	if (!file_exists($fileDir)) {
		echo "$fileDir not found !";
		return;
	}

	$handle = @fopen($fileDir, "r");
	if ($handle) {
		while (!feof($handle)) {
			$id = trim(fgets($handle));
			SearchBuilder::buildOne($id);
			echo "$id\n";
		}
		fclose($handle);
	}
}