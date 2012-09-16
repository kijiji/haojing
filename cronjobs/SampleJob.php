<?php
//yubing@baixing.com

class TestCliJob extends CronJob {
	protected function testFilePermissions() {
		//如果真的有脚本需要定义生效or结束时间，如下，在方法里面直接写吧，这种需求太少见了。
		if (time() < strtotime('2008-8-8')) {
			return false;
		}
		$perms = fileperms(dirname(HTDOCS_DIR) . '/cli.php');
		$perm_str = substr(sprintf('%o', $perms), -4);
		if ($perm_str != '0755') {
			$this->log("cli permission is wrong: {$perm_str}");
		}
	}
}

//每天的9点开始，每5分钟执行一次
$job = (new TestCliJob())
		->onHour(9, 1)
		->onMinute(0, 5)
		->isUnique()	//这个在这里其实没用，只是告诉大家可以这么写，防止同个脚本并行跑。
		->doJob('testFilePermissions');
