<?php
//lianghonghao@baixing.com
class Params {
	public function __get($name) {
		return null;
	}

	# debug only
	public function __toString() {
		return var_export(get_object_vars($this), true);
	}
}
