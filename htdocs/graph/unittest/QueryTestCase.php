<?
//zhaojun@baixing.com

class QueryTestCase extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider queryParserProvider
	 */
	public function testQueryParser($HJQuery, $o, $res) {
		$q = QueryParser::parse($HJQuery);
		$this->assertEquals($q->accept($o), $res, "fall: \n{$HJQuery} not accept : \n" . print_r($o, true) . "\n" . print_r($q, true));
	}

	public static function queryParserProvider() {
		$tests = [];
		$testObject = new stdClass();

		//test simple
		$testObject->a = 1;
		$testObject->b = null;
		$testObject->c = null;
		$tests[] = ['a:1', clone $testObject, true];
		$tests[] = ['a:2', clone $testObject, false];

		//test simple and/or/not
		$tests[] = ['a:1 b:1', clone $testObject, false];
		$tests[] = ['a:1 AND b:1', clone $testObject, false];
		$tests[] = ['a:1 OR b:1', clone $testObject, true];
		$testObject->b = 1;
		$tests[] = ['a:1 b:1', clone $testObject, true];
		$tests[] = ['a:2 b:1', clone $testObject, false];
		$tests[] = ['b:1 -a:2', clone $testObject, true];

		//test muti-blank and duplicate
		$tests[] = ['b:1   -a:2   ', clone $testObject, true];
		$tests[] = ['b:1    -a:2   b:1', clone $testObject, true];
		$tests[] = ['b:1    -a:2   b:2', clone $testObject, false];
		$tests[] = ['a:1   -(c:1     b:1)', clone $testObject, true];
		$tests[] = ['a:1   -(c:1   OR    b:1)', clone $testObject, false];
		$tests[] = ['a:1   -c:1     b:1', clone $testObject, true];

		//test combination and/or/not
		$tests[] = ['a:1 AND (b:1 OR b:2)', clone $testObject, true];
		$tests[] = ['a:1 AND (b:2 OR b:3)', clone $testObject, false];
		$tests[] = ['a:1 AND -(b:4 OR b:5)', clone $testObject, true];

		//test in
		$tests[] = ['a:1 AND b:{1,2,3}', clone $testObject, true];
		$tests[] = ['a:1 AND b:{3,4,5}', clone $testObject, false];

		//test quote
		$testObject->b = 'aaa aa asd a';
		$tests[] = ['a:1 AND b:"' . $testObject->b . '"', clone $testObject, true];
		$tests[] = ['a:1 AND b:"' . $testObject->b . ' n o t"', clone $testObject, false];

		//test range
		$testObject->b = 50;
		$testObject->c = 100;
		$tests[] = ['a:1 b:[0,60]', clone $testObject, true];
		$tests[] = ['a:1 AND b:[0,50]', clone $testObject, true];
		$tests[] = ['a:1 AND b:[50,60]', clone $testObject, true];
		$tests[] = ['a:1 AND b:[,100]', clone $testObject, true];
		$tests[] = ['a:1 AND b:[50,]', clone $testObject, true];
		$tests[] = ['a:1 AND b:[60,100]', clone $testObject, false];
		$tests[] = ['a:1 AND b:[60,]', clone $testObject, false];
		$tests[] = ['a:1 AND b:[60,] c:[60,]', clone $testObject, false];
		$tests[] = ['a:1 AND (b:[60,] || c:[60,])', clone $testObject, true];

		//test keywords
		$testObject->title = 'ww';
		$tests[] = ['kk ee yy', clone $testObject, false];
		$tests[] = ['kk || ee || yy || ww || oo || rr || dd', clone $testObject, true];
		return $tests;
	}
}