<?php
//zhaojun@baixing.com
class Category_Controller {
	public function handle(Url $url) {
		echo new View('header');

		$category = (new Node($url->segments()[0]))->load();
		$args = $_GET;
		if ($url->get('area')) $args['area'] = $url->get('area');

		$p = new Params();

		foreach ($category->path() as $c) {
			echo " > <a href='/{$c->id}/'>{$c->name}</a>";
		}
		echo '<br />';

		$p->filters = $category->filters($args);
		foreach ($p->filters['category'] as $node) {
			echo "<a href='/{$node->id}/'>{$node->name}</a> ";
		}
		echo '<br /><br />';

		if (isset($args['area'])) {
			$area = new Node($args['area']);
			foreach ($area->path() as $c) {
				echo " > <a href='/{$category->id}/?area={$c->id}'>{$c->name}</a>";
			}
			echo '<br />';
		}

		foreach ($p->filters['area'] as $node) {
			echo "<a href='/{$category->id}/?area={$node->id}'>{$node->name}</a> ";
		}

		$p->ads = $category->ad($args);
		foreach ($p->ads as $ad) {
			echo "<li><a href='/{$category->id}/a{$ad->id}.html'>{$ad->title}</a></li>";
		}

		echo new View('footer');
	}
}
