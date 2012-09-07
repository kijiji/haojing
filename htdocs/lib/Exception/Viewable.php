<?php
//lianghonghao@baixing.com
namespace Exception;
class Viewable extends \Exception{
	public function __toString(){
		return strval(new \View('error/viewable', ['exception' => $this]));
	}
}
