<?php
/**
 * 顾名思义，是一个有很多孔可以插东西的类，恩恩……
 */
class Hive {
	const TYPE_CHANGE_ARG = 0x01;
	const TYPE_CHANGE_RESULT = 0x02;
	const TYPE_BEFORE = 0x04;
	const TYPE_AFTER = 0x08;

	private static $advices = [];

	/**
	 * @param Plugin $plugin 需要注册的Plugin
	 * @return Plugin
	 */
	public static function register(Plugin $plugin) {
		$methods = $plugin->getPlugins();

		$allowed_methods = self::filterJoinPoint($methods);

		foreach ($allowed_methods as $each_method) {
			$meaning = self::explain($each_method);
			if (!self::registered($meaning)) {
				self::registerAdvice($plugin, $meaning['method'], $meaning['type'], $meaning['function']);
				self::markAsRegistered($meaning);
			}
		}

		return $plugin;
	}

	private static function filterJoinPoint(array $methods) {
		return $methods;
	}

	private static function registered($meaning) {
		return false;
	}

	private static function markAsRegistered($meaning) {
		return false;
	}

	private static function registerAdvice (Plugin $plugin, $method, $type, $function){
		self::pushAdvice($plugin, $method, $type, $function);
		switch ($type) {
			case self::TYPE_CHANGE_ARG :
				aop_add_before($method, array('Plugin', 'changeArg'));
				break;
			case self::TYPE_CHANGE_RESULT :
				aop_add_after($method, array('Plugin', 'changeResult'));
				break;
			case self::TYPE_BEFORE :
				aop_add_before($method, array('Plugin', 'execAfterAndBefore'));
				break;
			case self::TYPE_AFTER :
				aop_add_after($method, array('Plugin', 'execAfterAndBefore'));
				break;
		}
	}

	private static function pushAdvice($plugin, $method, $type, $function) {
		self::$advices[$method][$type][] =  array($plugin, $function);
	}

	private static function explain($method) {
		return $method;
	}

	public static function execAfterAndBefore(AopTriggeredJoinpoint $object) {
		$ori_arg = $object->getArguments();
		$type = ($object->getKindOfAdvice() & AOP_KIND_BEFORE) ? self::TYPE_BEFORE : self::TYPE_AFTER;
		foreach (self::$advices[$object->getPointcut()][$type] as $each_method) {
			call_user_func_array($each_method, $ori_arg);
		}
	}

	public static function changeArg(AopTriggeredJoinpoint $object) {
		$ori_arg = $object->getArguments();
		$new_arg = $ori_arg;
		foreach (self::$advices[$object->getPointcut()][self::TYPE_CHANGE_ARG] as $each_method) {
			$new_arg = call_user_func($each_method, $ori_arg);
			if (count($new_arg) != count($ori_arg)) {
				$new_arg = $ori_arg;
			}
		}
		$object->setArguments($new_arg);
	}

	public static function changeResult(AopTriggeredJoinpoint $object) {
		$return_value = $object->getReturnedValue();
		foreach (self::$advices[$object->getPointcut()][self::TYPE_CHANGE_RESULT] as $each_method) {
			$return_value = call_user_func($each_method, $return_value);
		}
		$object->setReturnedValue($return_value);
	}

	public static function execBefore(AopTriggeredJoinpoint $object){
		$ori_arg = $object->getArguments();
		foreach (self::$advices[$object->getPointcut()][self::TYPE_BEFORE] as $each_method) {
			call_user_func_array($each_method, $ori_arg);
		}
	}
}