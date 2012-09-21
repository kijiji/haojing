<?php
//zhaojun@baixing.com

class ListingPlugin extends Plugin {
	private $category;
	private $args;
	private $query;
	private $opts;

	public function getMethods() {
		return [
			[
				'method' => 'ListingConnection->find()',
				'function' => 'buildQuery',
				'type' => Hive::TYPE_BEFORE,
			], [
				'method' => 'ListingConnection->find()',
				'function' => 'ding',
				'type' => Hive::TYPE_CHANGE_RESULT,
			], [
				'method' => 'ListingConnection->find()',
				'function' => 'extend',
				'type' => Hive::TYPE_CHANGE_RESULT,
			]
		];
	}

	public function buildQuery($args) {
		list($this->category, $this->args) = $args;
		list($this->query, $this->opts) = Listing::buildQuery($this->category, $this->args);
	}

	public function ding($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		$dingResult = Service::factory('Ding')->ads($this->category, $this->args);
		$listingResult->prepend($dingResult->ids());
		return $listingResult;
	}

	public function extend($listingResult) {
		if (isset($this->opts['from']) && $this->opts['from'] > 0) return $listingResult;
		//	$ids = CollabrationFilter::getRecommendAds($this->category, $this->args);
		//	$extendResult = Searcher::query('Ad', new InQuery('id', $ids));
		//	$listingResult->append($extendResult->ids());
		return $listingResult;
	}
}