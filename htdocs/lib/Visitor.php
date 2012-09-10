<?php
class Visitor {
	private static $user = null;
	public static function instance() {
		if (is_null(self::$user)) {
			if (($user_id = Cookie::get('__u')) && Auth::userToken($user_id, Cookie::get('__c'))) {
				self::$user = graph("u{$user_id}");
			} else {
				self::$user = new Node();
			}
		}
		return self::$user;
	}

}
