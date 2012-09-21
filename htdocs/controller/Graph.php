<?php
//zhaojun@baixing.com

include_once(__DIR__ . '/../plugin/graph/ListingPlugin.php');
Hive::register(new ListingPlugin());

class Graph_Controller {
	public function handle(Url $url) {
		if (!isset($url->segments()[1])) {
			echo 'please use url to explore haojing graph like: <br /><a href="./ershouqiche/ad">ershouqiche/ad</a>';
			return;
		}

		$cmd = $url->segments()[1] . (isset($url->segments()[2]) ? '/' . $url->segments()[2] : '');
		$arg = $_SERVER['QUERY_STRING'];
		try {
			$node = graph($cmd . ($arg ? '?' . $arg : ''));
			$args = [];
			if ($arg) parse_str($arg, $args);

			$graphHost = "http://{$_SERVER['HTTP_HOST']}/g/";//"http://graph.baixing.com/";

			if ($node instanceof SearchResult) {
				$arr = [
					'data' => $this->format($node->objs()),
					'total' => $node->totalCount(),
				];

				$nextArgs = array_merge($args, $node->nextPage());
				if (isset($nextArgs['from']) && $node->totalCount() > $nextArgs['from']) {
					$arr['next'] = $graphHost . $cmd . "?" . http_build_query($nextArgs);
				}

				$prevArgs = array_merge($args, $node->prevPage());
				if (isset($args['from']) && $args['from'] > 0) {
					$arr['prev'] = $graphHost . $cmd . "?" . http_build_query($prevArgs);
				}

			} elseif ($node instanceof Node) {
				$arr = $this->format($node);
				if(isset($args['metadata'])) {
					$connections = array();
					foreach ($node->connections() as $name => $conn) {
						$connections[$name] = $graphHost . $cmd . "/$name";
					}
					$arr['metadata'] = array(
						'connections' => $connections,
						'type'	=> $node->type(),
					);
				}
			} elseif(is_array($node)) {
				$arr = array();
				foreach($node as $n) {
					$arr[] = $n->load();
				}
			} else {
				$arr['error'] = 'Unknown error';
			}
		} catch (Exception $e) {
			//throw $e;
			echo json_encode($e->getMessage());
		}

		header('Content-Type: text/html;charset=UTF-8');
		echo json_encode($arr, JSON_UNESCAPED_UNICODE);
	}

	private function format($data) {
		$arr = array();
		foreach($data as $col => $value) {
			if ($value instanceof Node) {			//ref
				$arr[$col] = get_object_vars($value);
			} elseif (is_array($value)) {			//array
				$arr[$col] = $this->format($value);
			} elseif ($value && preg_match('/Time$/', $col)) {	//time
				$arr[$col] = date('c', $value);
			} elseif (is_scalar($value) && strlen($value)) {	//scalar
				$arr[$col] = $value;
			} else {}	//不认识的不输出
		}
		return $arr;
	}

}