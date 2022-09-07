<?php

/**
 * 切换pc.
 *
 * @var AccountTable
 *                   [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

$do = safe_gpc_belong($do, array('switch', 'display'), 'display');

if ('display' == $do) {
	$modulelist = uni_modules();
	if (!empty($modulelist)) {
		foreach ($modulelist as $name => &$row) {
			if (!empty($row['issystem']) || (!empty($_GPC['keyword']) && !strexists($row['title'], $_GPC['keyword'])) || (!empty($_GPC['letter']) && $row['title_initial'] != $_GPC['letter'])) {
				unset($modulelist[$name]);
				continue;
			}
		}
		$modules = $modulelist;
	}
	//PC没有欢迎页，故先放在此重建账号模块。后期有欢迎页后需异步请求
	cache_build_account_modules($_W['uniacid']);
	template('webapp/home');
}
