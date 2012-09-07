<?php
//lianghonghao@baixing.com
class Error_Controller {
	public function handle(Url $url) {
		echo new View('header');

		echo new View('error/404');

		echo new View('footer');
	}
}