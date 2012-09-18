<?php
//lianghonghao@baixing.com
class Plugin {
	public function getPlugins() {
		return array_filter(array_map(array($this, 'filterMethodName'), get_class_methods($this)));
	}

	private function filterMethodName($methodName) {
		$meaning = null;
		if (preg_match('#^(?<type>before|after)(?<method>.*?)$#', $methodName, $match) ||
			preg_match('#^change(?<method>.*?)(?<type>Args|Result)$#', $methodName, $match)) {
			$meaning = [
				'method' => str_replace('__', '->', $match['method']) . "()",
				'function' => $methodName,
				'type' => strtolower($match['type']),
			];
		}
		return $meaning;
	}
}
