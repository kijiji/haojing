<?php
	$old_version_user_id = trim($user->id, 'u');
?>
<style>
	#userImg {
		width: 160px;
		height: 160px;
		background-color: #F2F2F2;
		margin-bottom: 10px;
	}
	#userImg img{
		width: 160px;
		height: 160px;
		margin-top: 10px;
	}

	.userinfo-des {
		display: block;
	}

	price {
		display: block;
		color: #ff5500;
	}

	.qrcode {
		text-align: center;
		padding-top: 10px;
	}

</style>
<div id="gridlist" class="grid grid-20">
	<div id="aside-left" class="grid grid-4 first">
		<div class="aside">
			<div class="aside-box aside-box-noborder"><p></p>
				<?php if (count($user->images)) { ?>
					<div id="userImg">
						<img src=<?=$user->images[0]->square_180?> />
					</div>
				<? } ?>

				<h4 class="typo-h4">
					<a href="http://www.baixing.com/u/<?=$old_version_user_id?>/" target="_blank">
						<strong><?=$user->{"公司名称"} ?: $user->name?></strong>
					</a>
					<span class="icon icon-phone <?=$user->MobileVerified ? 'icon-phone-done' : ''?>"></span>
				</h4>
				<p>用户编号：<?=$old_version_user_id?></p>
				<p>注册时间：<?=date('Y年m月d日', $user->createdTime)?></p>
				<p class="qrcode">
					<img src="http://www.baixing.com/ajax/qr/user/?id=<?=$old_version_user_id?>"/>
					<br /><small>百姓名片</small>
				</p>
			</div>
		</div>
		&nbsp;</div>
	<div id="content" class="grid grid-16 last clearfix">
		<h1 class="typo-h3"><?=$user->{"公司名称"} ?: $user->name?></h1>
		<h2 class="spar">公司介绍</h2>
		<div class="bd-bt">
			<p class="typo-p">
				<em><?=nl2br($user->{"公司介绍"} ?: $user->description)?></em>
			</p>
		</div><div class="blank10"></div>
		<h2 class="spar">发布的信息</h2>
		<ul class="list" id="listingHead">
			<?php
			foreach ($user->ad() as $ad) {
				if (in_array($ad->categoryFirstLevelEnglishName, $port_category)) {
			?>
			<li class="list-item clearfix">
				<span class="list-item-block"><time><?=date('m月d日', $ad->createdTime)?></time></span>
				<div class="list-item-content">
					<a href="http://<?=$ad->cityEnglishName?>.baixing.com/<?=$ad->category->id?>/a<?=$ad->id?>.html"
					   target="_blank" class="topicLink"><?=$ad->title?></a>
				</div>
				<span class="list-item-block"><price><?=$ad->{"价格"} ?: $ad->{'工资'} ?: '面议'?></price></span>
			</li>
			<?
				}
			}
			?>
		</ul>
		<div class="clearfix"></div>
	&nbsp;</div>
</div>