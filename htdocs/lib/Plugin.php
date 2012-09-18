<?php
//lianghonghao@baixing.com
class Plugin {
	public function getPlugins() {
		return array_filter(array_map(array($this, 'filterMethodName'), get_class_methods($this)));
	}

	private function filterMethodName($method_name) {
		$plugin_info = null;
		if (preg_match('#^(?<type>before|after)(?<method>.*?)$#', $method_name, $match) ||
			preg_match('#^change(?<method>.*?)(?<type>Args|Result)$#', $method_name, $match)) {
			$plugin_info = [
				'method' => str_replace('__', '->', $match['method']) . "()",
				'function' => $method_name,
				'type' => strtolower($match['type']),
			];
		}
		return $plugin_info;
	}
}
