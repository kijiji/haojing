<?
//zhaojun@baixing.com

trait ValidatorTrait {
	//todo: implement errors and message.

	/*
	 * $exp can be bool and array:
	 *   BOOL (0:none-required/1:required)
	 *   Array ('function name', ['params']), eg:
	 *      ['validNumber', 10, 100] should be valid for number between 10 and 100
	 *      ['validMobile'] should be a mobile number
	 */
	protected function validate($exp, $value) {
		if (is_array($exp) && !call_user_func_array(['self', $exp[0]], array_merge([$value], array_slice($exp, 1)))) {
			return false;
		} elseif ($exp && is_null($this->$key)) {
			return false;
		}
		return true;
	}

	protected static function validNumber($value, $min = null, $max = null) {
		return is_numeric($value) && (is_null($min) ? true : $value >= $min) && (is_null($max) ? true : $value <= $max);
	}

	protected static function validMobile($value) {
		return preg_match('/^1(3|4|5|8)\d{9}$/', $value);
	}

	protected static function validStringLength($value, $min = null, $max = null) {
		return self::validNumber(mb_strlen($value, 'utf8'), $min, $max);
	}
}