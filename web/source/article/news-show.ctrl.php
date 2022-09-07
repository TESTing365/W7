<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
$dos = array('detail', 'list');
$do = in_array($do, $dos) ? $do : 'list';
load()->model('article');
$_W['breadcrumb'] = '新闻';
if ('detail' == $do) {
	$id = intval($_GPC['id']);
	$news = article_news_info($id);
	if (is_error($news)) {
		if ($_W['isw7_request']) {
			iajax(-1, '新闻不存在或已删除');
		}
		itoast('新闻不存在或已删除', referer(), 'error');
	}
	if ($_W['isw7_request']) {
		iajax(0, $news);
	}
}

if ('list' == $do) {
	$categroys = article_categorys('news');
	$categroys[0] = array('title' => '所有新闻');
	$cateid = empty($_GPC['cateid']) ? 0 : intval($_GPC['cateid']);

	$filter = array('cateid' => $cateid);
	$pindex = empty($_GPC['page']) ? 1 : max(1, $_GPC['page']);
	$psize = 20;
	$newss = article_news_all($filter, $pindex, $psize);
	$total = intval($newss['total']);
	$data = $newss['news'];
	$pager = pagination($total, $pindex, $psize);
}

template('article/news-show');
