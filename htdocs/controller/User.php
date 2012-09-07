<?php
//lianghonghao@baixing.com
class User_Controller {
	public function handle(Url $url) {
		echo new View('header');
		$user_id = "u" . $url->segments(1);

		$user = graph($user_id);

		#@todo 和赵赵的要配合起来
		#$port_type = Service\Port::getPortType($user);

		if ($user->jobPort || $user->port || $user->carStore) {
			if ($user->jobPort) {
				$port_category = array('gongzuo', 'jianzhi');
			} elseif ($user->port) {
				$port_category = array('fang');
			} elseif ($user->carStore) {
				$port_category = array('cheliang');
			}

			$content = new View('user/port', compact('user', 'port_category'));
		} else {
			$content = new View('user/normal', compact('user'));
		}

		echo $content;

		echo new View('footer');

	}
}
