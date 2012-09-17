<?php
//yubing@baixing.com

class JobManager {

	private function jobs() {
		$dir = __DIR__;
		$jobs = [];
		$_handler = opendir($dir);
		while (false !== ($filename = readdir($_handler))) {
			if (preg_match("/^(.+Job)\.php$/", $filename, $matches) && is_file("{$dir}/$filename")) {
				$jobs[$matches[1]] = "{$dir}/$filename";
			}
		}
		return $jobs;
	}

	public function exec() {
		foreach($this->jobs() as $name => $file) {
			exec(PHP_CLI . " -l $file", $cmdout, $ret_var);
			if ($ret_var != 0) {
				continue;	//代码有问题的Job不执行
			}
			$pid = shell_exec(HAOJING_CLI . " {$file} > " . LOG_DIR . "/job_exec.log 2>&1 &");
			Logger::syslog('JobManager', "{$name} started, PID:" . str_replace('[1]', '', $pid));
		}
	}

}


