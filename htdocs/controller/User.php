<?php
//lianghonghao@baixing.com
class User_Controller {
	public function handle(Url $url) {
		echo new View('header');
		$user_id = "u" . $url->segments(1);

		$user = graph($user_id);

		Service::factory('Port');
		if (($port_type = PortService::isOnService($user->id))) {
			$portFilter = PortService::categoryMapping()[$port_type];
			$content = new View('user/port', compact('user', 'portFilter'));
		} else {
			$content = new View('user/normal', compact('user'));
		}

		echo $content;

		echo new View('footer');

	}
}
