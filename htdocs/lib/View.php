<?php
//lianghonghao@baixing.com
if(!defined('VIEW_DIR')) {
	trigger_error('You should define VIEW_DIR first', E_USER_ERROR);
}

class View {
	private
		$data,
		$file_name;

	public function __construct ($view_name, Params $data) {
		$this->data = $data ?: new Params();
		$this->file_name = VIEW_DIR . DIRECTORY_SEPARATOR . $view_name . '.php';
	}

	public function __get($name) {
		return $this->data->$name;
	}

	public function __set($name, $value) {
		$this->data->{$name} = $value;
		return $this;
	}

	public function render($return = false) {
		ob_start();

		$data = $this->data;
		if (file_exists($this->file_name)) {
			try {
				include $this->file_name;
			} catch (Exception $e) {
				ErrorHandler::handleException($e);
			}
		}

		if ($return) {
			return ob_get_clean();
		} else {
			echo ob_get_clean();
		}
	}

	public function __toString() {
		return $this->render(true);
	}

}
