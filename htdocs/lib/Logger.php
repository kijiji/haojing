<?php
//yubing@baixing.com

class Logger {
	/* 默认在 tail -f /var/logs/messages 可以读到log */
	public static function syslog($channel, $msg) {
		if(!$msg) return false;
		openlog("haojing", LOG_PID | LOG_PERROR, LOG_USER);
		return syslog(LOG_INFO, $channel . ' | ' . $msg);
	}
}
