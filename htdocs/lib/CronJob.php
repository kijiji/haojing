<?php
//yubing@baixing.com

abstract class CronJob {
	private $enabled = true;
	
	private $shutdown_functions = array();
	
	private function disable($condition) {
		if($condition) $this->enabled = false;
		return $this;
	}

	public function onMinute($start_minute, $repeat_period = 60) {
		if($repeat_period > 60) throw new Exception("repeat_period should below 60, use onHour() instead !");
		return $this->disable( (date('i') - $start_minute) % $repeat_period != 0 );
	}
	

	public function onHour($start_hour, $repeat_period = 24) {
		if($repeat_period > 24) throw new Exception("repeat_period should below 24, you may need a new function!");
		return $this->disable( (date('H') - $start_hour) % $repeat_period != 0 );
	}
	
	public function isUnique(){
		$key = get_class($this);
		if(Locker::lock($key)) {
			$this->shutdown_functions[] = function() use($key) { Locker::unlock($key); };
		} else {
			$this->disable(true);
		}
		return $this;
	}
	
	public function doJob($name) {
		if($this->enabled) {
			$this->log('Start to run...');
			$this->$name();
			foreach($this->shutdown_functions as $func) {
				$func();
			}
			$this->log('Finished !');
		}
	}
	
	public function log($msg){
		Logger::syslog('JobLog_' . get_class($this), $msg);
	}
}

// 单机锁
class Locker {
	private static $file_handlers = [];

	public static function lock($key) {
		if(isset(self::$file_handlers[$key])) return false;
		self::$file_handlers[$key] = fopen(TEMP_DIR . '/' . $key . 'locker', 'w+');
		return flock(self::$file_handlers[$key], LOCK_EX | LOCK_NB);	// 独占锁、非阻塞
	}
	
	public static function unlock($key) {
		if (isset(self::$file_handlers[$key])) {
			fclose(self::$file_handlers[$key]);
			@unlink(TEMP_DIR . '/' . $key . 'locker');
			unset(self::$file_handlers[$key]);
		}
	}
}
