<?php
/**
 * 顾名思义，是一个有很多孔可以插东西的类，恩恩……
 */
class Hive {
	const TYPE_CHANGE_ARGS = 'args';
	const TYPE_CHANGE_RESULT = 'result';
	const TYPE_BEFORE = 'before';
	const TYPE_AFTER = 'after';

	private static $advices = [];
	private static $registered = [];

	/**
	 * @param Plugin $plugin 需要注册的Plugin
	 * @return Plugin
	 */
	public static function register(Plugin $plugin) {
		$methods = $plugin->getPlugins();

		$allowed_methods = self::filterJoinPoint($methods);

		foreach ($allowed_methods as $each_method) {
			list($method, $type, $function) = self::explain($each_method);
			self::pushAdvice($plugin, $method, $type, $function);
			if (!self::registered($method, $type)) {
				self::registerPluginHandler($method, $type);
				self::markAsRegistered($method, $type);
			}
		}

		return $plugin;
	}

	//@todo privilege control
	private static function filterJoinPoint(array $methods) {
		return $methods;
	}

	//@todo deal with more information
	private static function explain($method) {
		return [$method['method'], $method['type'], $method['function']];
	}

	private static function registered($method, $type) {
		return isset(self::$registered[$method][$type]);
	}

	private static function markAsRegistered($method, $type) {
		self::$registered[$method][$type] = true;
	}

	private static function registerPluginHandler ($method, $type){
		switch ($type) {
			case self::TYPE_CHANGE_ARGS :
				aop_add_before($method, array('Hive', 'changeArg'));
				break;
			case self::TYPE_CHANGE_RESULT :
				aop_add_after($method, array('Hive', 'changeResult'));
				break;
			case self::TYPE_BEFORE :
				aop_add_before($method, array('Hive', 'execAfterAndBefore'));
				break;
			case self::TYPE_AFTER :
				aop_add_after($method, array('Hive', 'execAfterAndBefore'));
				break;
		}
	}

	private static function pushAdvice($plugin, $method, $type, $function) {
		self::$advices[$method][$type][] =  array($plugin, $function);
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
		foreach (self::$advices[$object->getPointcut()][self::TYPE_CHANGE_ARGS] as $each_method) {
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