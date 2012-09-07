<?
//zhaojun@baixing.com

class Util {
	public static function object_map($arrayOfObjects, $key) {
		return array_map(function($o) use ($key) {return $o->$key;}, $arrayOfObjects);
	}
}
