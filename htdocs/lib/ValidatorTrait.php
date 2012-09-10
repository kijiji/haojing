<?
//zhaojun@baixing.com

trait ValidatorTrait {
	//todo: implement errors and message.

	/*
	 * $rule can be bool and array:
	 *   BOOL (0:none-required/1:required)
	 *   Array ('rule_func', ['params']), eg:
	 *      ['validNumber', 10, 100] should be valid for number between 10 and 100
	 *      ['validMobile'] should be a mobile number
	 */
	protected function validate($value, $rule) {
		if (is_array($rule) && !call_user_func_array(['self', $rule[0]], array_merge([$value], array_slice($rule, 1)))) {
			return false;
		} elseif ($rule && is_null($this->$key)) {
			return false;
		}
		return true;
	}

	protected static function validNumber($value, $lower = null, $upper = null) {
		return is_numeric($value) && (is_null($lower) ? true : $value >= $lower) && (is_null($upper) ? true : $value <= $upper);
	}

	protected static function validMobile($value) {
		return preg_match('/^1(3|4|5|8)\d{9}$/', $value);
	}

	protected static function validStringLength($value, $lower = null, $upper = null) {
		return self::validNumber(mb_strlen($value, 'utf8'), $lower, $upper);
	}
}