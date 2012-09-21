<?php
//zhaojun@baixing.com
class Ad_Controller {
	public function handle(Url $url) {
		echo new View('header');

		$ad = new Node($url->segments(0));
		foreach ($ad->category->path() as $c) {
			echo " > <a href='/{$c->id}/'>{$c->name}</a>";
		}

		echo "<h1>{$ad->title}</h1>";
		echo $ad->description;
		var_dump($ad);

		echo new View('footer');
	}
}
