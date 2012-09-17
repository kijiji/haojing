<?php
//yubing@baixing.com

class SearchJob extends CronJob {
	protected function update() {
		shell_exec(HAOJING_CLI . " " . __DIR__ . "/SearchBuilderScript.php update_all");
	}
}

$job = (new SearchJob())
		->onMinute(0, 5)
		->doJob('update');
