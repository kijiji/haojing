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
		$methods = $plugin->getMethods();

		$allowed_methods = self::filterJoinPoint($methods);

		foreach ($allowed_methods as $each_method) {
			list($join_point, $type, $function) = self::explain($each_method);
			self::pushMethod($plugin, $join_point, $type, $function);
			if (!self::registered($join_point, $type)) {
				self::registerPluginHandler($join_point, $type);
				self::markAsRegistered($join_point, $type);
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

	private static function registered($join_point, $type) {
		return isset(self::$registered[$join_point][$type]);
	}

	private static function markAsRegistered($join_point, $type) {
		self::$registered[$join_point][$type] = true;
	}

	private static function registerPluginHandler ($join_point, $type){
		switch ($type) {
			case self::TYPE_CHANGE_ARGS :
				aop_add_before($join_point, array('Hive', 'changeArg'));
				break;
			case self::TYPE_CHANGE_RESULT :
				aop_add_after($join_point, array('Hive', 'changeResult'));
				break;
			case self::TYPE_BEFORE :
				aop_add_before($join_point, array('Hive', 'execBefore'));
				break;
			case self::TYPE_AFTER :
				aop_add_after($join_point, array('Hive', 'execAfter'));
				break;
		}
	}

	private static function pushMethod($plugin, $join_point, $type, $function) {
		self::$advices[$join_point][$type][] =  array($plugin, $function);
	}

	private static function exec (AopTriggeredJoinpoint $object, $type, $arg, $iterate) {
		foreach (self::$advices[$object->getPointcut()][$type] as $each_method) {
			$result = call_user_func($each_method, $arg);
			if ($iterate) {
				$args = $result;
			}
		}
	}

	public static function execAfter(AopTriggeredJoinpoint $object) {
		self::exec($object, self::TYPE_AFTER, $object->getReturnedValue(), false);
	}

	public static function execBefore(AopTriggeredJoinpoint $object) {
		self::exec($object, self::TYPE_BEFORE, $object->getArguments(), false);
	}

	public static function changeArg(AopTriggeredJoinpoint $object) {
		$object->setArguments(self::exec($object, self::TYPE_CHANGE_ARGS, $object->getArguments(), true));
	}

	public static function changeResult(AopTriggeredJoinpoint $object) {
		$object->setReturnedValue(self::exec($object, self::TYPE_CHANGE_RESULT, $object->getReturnedValue(), true));
	}
}