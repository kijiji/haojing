<?php
//linjianfeng@baixing.com
?><!DOCTYPE html>
<html xmlns:h="http://www.w3.org/1999/xhtml">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1,requiresActiveX=true" />
	<title>百姓网</title>
	<link rel="apple-touch-icon" href="http://static.baixing.net/images/iphone_57x57_icon.png" />
	<script>
		var _bx = [], _uvq = [], _msg = [];
		function readCookie(name) {
			var nameEQ = name + "=";
			var ca = document.cookie.split(';');
			for(var i = 0; i < ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) == ' ') c = c.substring(1, c.length);
				if (c.indexOf(nameEQ) == 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
			}
			return null;
		}

		function writeCookie(name, value, days) {
			if (days) {
				var date = new Date();
				date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
				var expires = "; expires=" + date.toGMTString();
			}
			else var expires = "";
			document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; domain=.baixing.com";
		}
	</script>
	<link rel="stylesheet" href="http://www.baixing.com/media/css/puerh.css" />
	<link rel="stylesheet" href="http://www.baixing.com/media/css/puerh-page.css" />
</head>
<body>
<div id="bxTips" class="hide"><span></span></div>

<div id="topbar">
	<div class="container clearfix">
		<div class="grid grid-20">
			<div class="topbar-left">
			</div>

		<div class="topbar">
			<? if (isset($_COOKIE['__n'])) { ?>
			<span class="topbar-right-text">欢迎，<a href="http://www.baixing.com/wo/"><?=$_COOKIE['__n']?></a>
			[<a href="/auth/tuichu/?src=homeheader">退出</a>]</span>
			<? } else { ?>
			<a href="http://www.baixing.com/auth/zhuce/?src=headerHome" rel="nofollow">注册</a> <span class="sep">|</span>
			<a href="http://www.baixing.com/auth/denglu/?src=headerHome" rel="nofollow">登录</a>
			<? } ?>
			<span class="sep">|</span> <a href="http://www.baixing.com/wo/" rel="nofollow">我的百姓网</a>
			<span class="sep">|</span> <a href="http://www.baixing.com/wo/favorite/" rel="nofollow">我的收藏</a>

			<span class="sep">|</span> <a href="/pay/entrance/" rel="nofollow" target="_blank">付费推广</a>
		</div> <!-- topbar -->
		</div>
	</div>
</div> <!-- #topbar -->
<div class="container clearfix">
	<div id="header" class="grid grid-20">
		<div class="logo clearfix grid grid-6 first">
			<h1><a href="/"><img src="http://static.baixing.net/images/logo_v2_S.png" alt="百姓网" /></a></h1>
		</div> <!-- .logo -->
	</div> <!-- #header -->

<div class="grid grid-20 clearfix" style="height:5px;"></div>
