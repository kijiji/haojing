<?php
//zhaojun@baixing.com
class Home_Controller {
	public function handle(Url $url) {
		echo new View('header');

		echo "<ul class='typo-ul'>";
		foreach ((new Node('root'))->children() as $cate) {
			echo "<li style='float:left;width:150px'><a href='/{$cate->id}/'>{$cate->name}</a><ul>";
			foreach ($cate->children() as $cate) {
				if (!$cate->name) continue;
				echo "<li><a href='/{$cate->id}/'>{$cate->name}</a></li>";
			}
			echo "</li></ul>";
		}
		echo "</ul>";

		echo new View('footer');
	}
}
