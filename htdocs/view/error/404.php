<?php
//lianghonghao@baixing.com
?><style type="text/css">
	.page-content {
		background: url(http://file.baixing.net/month_1207/20120712513757-840980.png) no-repeat 650px 15px;
	}

	.page-center {
		margin: 50px 0 10px;
	}

	.page-center a {
		margin-right: 30px;
	}

	.page-bottom, #secleft {
		display: inline-block;
		*display: inline;
		*zoom: 1
	}

	.page-bottom {
		margin: 50px 0 100px;
		text-align: center;
	}

	#secleft {
		width: 23px;
		color: #f30;
		text-align: center;
	}
</style>
<div class="grid grid-20 clearfix page-content">
	<h2 class="typo-h2">对不起，您所访问的页面暂时无法访问！</h2>

	<p class="typo-p">
		系统<span id="secleft">10</span>秒之后将自动跳转，您也可以点此 <a href="/">返回首页</a>。
	</p>

	<div class="page-center">
		<h3 class="typo-h3">下列信息对您有帮助吗？</h3>
		<a href="/zhengzu/">免费租房</a>
		<a href="/jianzhi/">兼职工作</a>
		<a href="/ershou/">跳蚤市场</a><br/>
		<a href="/jiaoyupeixun/">教育培训</a>
		<a href="/fuwu/">优质服务</a>
		<a href="/juhui/">同城聚会</a>
	</div>

	<ul class="page-bottom grid grid-20 clearfix">
		<li class="grid grid-4 first">
			<a href="/nanzhaonv/">
				<img src="http://file.baixing.net/month_1207/20120712668792-272502.jpg"/>
				找女朋友
			</a>
		</li>
		<li class="grid grid-4">
			<a href="/chongwulingyang/">
				<img src="http://file.baixing.net/month_1207/20120712101082-162732.jpg"/>
				免费领养宠物
			</a>
		</li>
		<li class="grid grid-4">
			<a href="/ershouqiche/">
				<img src="http://file.baixing.net/month_1207/20120712399116-548208.jpg"/>
				二手车
			</a>
		</li>
		<li class="grid grid-4">
			<a href="/shouji/">
				<img src="http://file.baixing.net/month_1207/20120712856346-641960.jpg"/>
				二手手机
			</a>
		</li>
		<li class="grid grid-4 last">
			<a href="/gongzuo/">
				<img src="http://file.baixing.net/month_1207/20120712741537-947339.jpg"/>
				高薪工作
			</a>
		</li>
	</ul>
</div>

<script>
	var waitSecond = 10,
		InterValObj = window.setInterval(SetRemainTime, 1000);

	function SetRemainTime() {
		if (waitSecond > 0) {
			waitSecond--;
			document.getElementById('secleft').innerHTML = waitSecond;
		} else {
			window.clearInterval(InterValObj);
			window.location = 'http://www.baixing.com/';
		}
	}
</script>