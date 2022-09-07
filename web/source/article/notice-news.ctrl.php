<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('article');

$dos = array('list');
$do = in_array($do, $dos) ? $do : 'list';

if ('list' == $do) {
	$page = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$page_size = 20;
	$news = article_news_all(array(), $page, $page_size);
	$notices = article_notice_all(array(), $page, $page_size);
	$list = array();
	if (!empty($news['news'])) {
		foreach ($news['news'] as $new) {
			$new['type'] = 'news';
			$new['link'] = url('article/news-show/detail', array('id' => $new['id']));
			$list[] = $new;
		}
	}
	if (!empty($notices['notice'])) {
		foreach ($notices['notice'] as $notice) {
			$notice['type'] = 'notice';
			$notice['link'] = url('article/notice-show/detail', array('id' => $notice['id']));
			$list[] = $notice;
		}
	}
	
	$total = intval($notices['total']) + intval($news['total']);
	$pager = pagination($total, $page, $page_size);
	
}
template('article/notice-news');
