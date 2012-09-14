<?php
//lianghonghao@baixing.com
if(!defined('TEMPLATE_DIR')) {
	trigger_error('You should define TEMPLATE_DIR first', E_USER_ERROR);
}

define ('COMPILED_FOLDER_NAME', 'compiledTpl');
define ('COMPILED_DIR', VIEW_DIR . DIRECTORY_SEPARATOR . COMPILED_FOLDER_NAME);

include HTDOCS_DIR . '/include/Everzet/init.php';

use Everzet\Jade\Dumper\PHPDumper;
use Everzet\Jade\Visitor\AutotagsVisitor;
use Everzet\Jade\Filter\JavaScriptFilter;
use Everzet\Jade\Filter\PHPFilter;
use Everzet\Jade\Filter\CSSFilter;
use Everzet\Jade\Parser;
use Everzet\Jade\Jade;
use Everzet\Jade\Lexer\Lexer;


class Template {
	private static $jade = false;
	private $file_name = false;
	private $compiled_filename;
	private $base_name;

	public static function jade() {
		if (!self::$jade) {
			$dumper = new PHPDumper();
			$dumper->registerVisitor('tag', new AutotagsVisitor());
			$dumper->registerFilter('javascript', new JavaScriptFilter());
			$dumper->registerFilter('php', new PHPFilter());
			$dumper->registerFilter('style', new CSSFilter());

			// Initialize parser & Jade
			$parser = new Parser(new Lexer());
			self::$jade = new Jade($parser, $dumper);
		}
		return self::$jade;
	}

	public function __construct($file_name, $data) {
		if (is_file(TEMPLATE_DIR . "/{$file_name}.tpl")) {
			$this->file_name = TEMPLATE_DIR . "/{$file_name}.tpl";
		} elseif (is_file(HTDOCS_DIR . "/{$file_name}.tpl")) {
			# if the template is not in TEMPLATE_DIR, you should use the complete path.
			$this->file_name = HTDOCS_DIR . "/{$file_name}.tpl";
		}

		$this->base_name = $file_name;

		// for staging debug, append the timestamp after compiled php filename.
		if (ENV != 'PRODUCTION' && $this->file_name) {
			$this->base_name .= "." . filemtime($this->file_name);
		}

		$this->data = $data;
	}

	private function getCompiledFilename() {
		if (is_null($this->compiled_filename)) {
			$this->compiled_filename = COMPILED_DIR . DIRECTORY_SEPARATOR . $this->base_name;
			$this->compiled_filename .= ".php";
		}
		return $this->compiled_filename;
	}

	private function compile() {
		// mkdir if the dir is not exist
		if (!is_dir(dirname($this->getCompiledFilename()))) {
			mkdir(dirname($this->getCompiledFilename()), 0644);
		}
		file_put_contents($this->getCompiledFilename(), self::jade()->render($this->file_name));
	}
	public function render($return = false) {
		if (!$this->file_name) {
			# @todo should trigger a USER_WARING level ERROR after modified the php.ini
			# by lianghonghao@baixing.com
			return false;
		}

		# @todo this work should be done during deploy. by lianghonghao@baixing.com
		if (!is_file($this->getCompiledFilename())) {
			$this->compile();
		}
		return  (new View(COMPILED_FOLDER_NAME . DIRECTORY_SEPARATOR . $this->base_name, $this->data))->render($return);

	}

	public function __destruct() {
		// when you need to debug the compile of template, you should change the "true" to "false".
		if (true && EVN != 'PRODUCTION') {
			unlink($this->getCompiledFilename());
		}
	}

}
