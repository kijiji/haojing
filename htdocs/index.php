<?php
include __DIR__ . "/init.php";

# init graph plugin
Hive::autoRegister('graph');

# 不要try catch 这个函数！，如果他挂了，我们应该知道
$graph_url = UrlTranslate::toGraph(new Url);

$dispatch_result = Router::urlDispatch($graph_url);

Controller::delegate($dispatch_result);

# @todo 代码应该是这样的！先写成上面那样让大家可以跑起来
# $response = Controller::handle($dispatch_result);
# $response->render();
