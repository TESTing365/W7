<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('article');
load()->model('module');

if (!empty($_W['uid'])) {
	header('Location: ' . $_W['siteroot'] . 'web/home.php');
	exit;
}

/*获取站点配置信息*/
$settings = $_W['setting'];

$copyright = $settings['copyright'];
$copyright['slides'] = iunserializer($copyright['slides']);
if (isset($copyright['showhomepage']) && empty($copyright['showhomepage'])) {
	header('Location: ' . url('user/login'));
	exit;
}
$notices = article_notice_home();
$news = article_news_home();
template('account/welcome');
