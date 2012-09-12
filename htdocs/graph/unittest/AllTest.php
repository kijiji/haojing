<?
require_once('../init.php');

class AllTests extends PHPUnit_Framework_TestSuite {
	private static $testCases = [
		'Query',
	];

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite();

		foreach ($_SERVER['argv'] as $c) {
			if (file_exists('./' . $c . 'TestCase.php')) { //skip phpunit parameters(such as: --debug)
				require_once('./' . $c . 'TestCase.php');
				$suite->addTestSuite($c . 'TestCase');
			}
		}
		if ($suite->count() == 0) {
			foreach (self::$testCases as $c) {
				require_once('./' . $c . 'TestCase.php');
				$suite->addTestSuite($c . 'TestCase');
			}
		}
		return $suite;
	}
}