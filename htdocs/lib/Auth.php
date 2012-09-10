<?php
//lianghonghao@baixing.com
class Auth {
	public static function userToken($user_id, $user_token) {
		$user = self::silenceGetUser($user_id);
		return $user && sha1($user->password) === $user_token;
	}

	public static function login($user_id, $user_password) {
		$user = self::silenceGetUser($user_id);

		if ($user && $user->password == md5($user_password)) {
			Cookie::set('__u', ltrim($user->id, 'u'));
			Cookie::set('__c', sha1($user->password));
			return true;
		}
		return false;
	}

	private static function silenceGetUser($user_id) {
		try {
			$user = graph("u{$user_id}");
		} catch (Exception $e) {
			$user = false;
		}
		return $user;
	}
}
