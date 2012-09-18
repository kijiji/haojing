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
			self::pushPlugin($plugin, $method, $type, $function);
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
				aop_add_before($method, array('Hive', 'execBefore'));
				break;
			case self::TYPE_AFTER :
				aop_add_after($method, array('Hive', 'execAfter'));
				break;
		}
	}

	private static function pushPlugin($plugin, $method, $type, $function) {
		self::$advices[$method][$type][] =  array($plugin, $function);
	}

	private static function exec (AopTriggeredJoinpoint $object, $type, $args) {
		foreach (self::$advices[$object->getPointcut()][$type] as $each_method) {
			call_user_func_array($each_method, $args);
		}
	}

	private static function iterExec(AopTriggeredJoinpoint $object, $type, $args) {
		foreach (self::$advices[$object->getPointcut()][$type] as $each_method) {
			$args = call_user_func_array($each_method, $args);
		}
		return $args;
	}
	public static function execAfter(AopTriggeredJoinpoint $object) {
		self::exec($object, self::TYPE_AFTER, $object->getReturnedValue());
	}

	public static function execBefore(AopTriggeredJoinpoint $object) {
		self::exec($object, self::TYPE_BEFORE, $object->getArguments());
	}

	public static function changeArg(AopTriggeredJoinpoint $object) {
		$object->setArguments(self::iterExec($object, self::TYPE_CHANGE_ARGS, $object->getArguments()));
	}

	public static function changeResult(AopTriggeredJoinpoint $object) {
		$object->setReturnedValue(self::iterExec($object, self::TYPE_CHANGE_RESULT, $object->getReturnedValue()));
	}
}