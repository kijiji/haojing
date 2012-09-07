<?php
//lianghonghao@baixing.com
?>
<style>
	.aside {
		padding: 10px;
		position: relative;
	}

	.userinfo-des {
		display: block;
	}

	#userImg {
		width: 160px;
		height: 160px;
		background-color: #F2F2F2;
		margin-bottom: 10px;
	}

	#userImg img {
		width: 160px;
		height: 160px;
	}

	.outter {
		position: absolute;
		top: 80px;
		left: 40px;
	}

	.typo-h4 {
		position: relative;
	}

	.typo-h4 strong {
		word-wrap: break-word;
	}

	.icon-phone {
		position: absolute;
		right: -2px;
		_right: 0;
		top: 0;
		_top: 5px;
	}

	.tab {
		margin-bottom: 15px;
	}
</style>
<div id="gridlist" class="grid grid-20">
	<div id="aside-left" class="grid grid-4 first">
		<div class="aside">
			<div id="userImg">
				<?php if (count($user->images)) { ?>
				<img src=<?=$user->images[0]->square_180?>/>
				<? } else { ?>
				<div class="outter">TA 还没有头像</div>
				<? } ?>
			</div>

			<h4 class="typo-h4 user-nick">
					<a href="http://www.baixing.com/u/<?=trim($user->id, 'u')?>/" target="_blank">
						<strong><?=$user->name?></strong>
					</a>
					<span class="icon icon-phone <?=$user->MobileVerified ? 'icon-phone-done' : ''?>"></span>
				</h4>
				<small class="userinfo-des">
					<?=$user->area ? $user->area->name . ' • ' : ''?>已注册<?=ceil((time() - $user->createdTime) / 86400)?>天
					<p><?=$user->description?></p>

				</small>
			</div>
		</div>
		&nbsp;</div>
	<div id="content" class="grid grid-16 last clearfix">
		<div class="tab">
			<ul class="tab-title clearfix">
				<li class="tab-title-item tab-title-item-last active"><a href="javascript:void(0);">TA
					发布的信息</a></li>
			</ul>
		</div>

		<div class="images clearfix">
			<?php foreach($user->ad() as $ad) {
				$img_url = count($ad->images)
					? $ad->images[0]->square_180
					: "http://static.baixing.net/images/nopic_small.png";
			?>
			<a target="_blank" href="http://<?=$ad->cityEnglishName?>.baixing.com/<?=$ad->category->id?>/a<?=$ad->id?>.html"
			   class="images-item">
				<img class="images-item-img"  src="<?=$img_url?>">

				<div class="images-item-caption">
					<strong><?=$ad->title?></strong>

					<p class="images-item-caption-meta">
						<em><?=date('m月d日', $ad->createdTime)?></em> / <?=count($ad->images)?>图
						<cite><?=$ad->{"价格"} ?: $ad->{'工资'}?></cite>
					</p></div>
			</a>
			<? } ?>
		</div>
		&nbsp;</div>
</div>