<?php
/**
 * 支付宝小程序入口
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */

defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');

if (!empty($_W['uniacid'])) {
	$version_info = miniapp_version_by_version(safe_gpc_string(trim($_GPC['v'])));
	if (!empty($version_info['modules'])) {
		foreach ($version_info['modules'] as $module) {
			if (!empty($module['account']) && intval($module['account']['uniacid']) > 0) {
				$_W['uniacid'] = $module['account']['uniacid'];
				$_W['account']['link_uniacid'] = $module['account']['uniacid'];
			}
		}
	}
}

$site = WeUtility::createModuleToutiaoapp($entry['module']);
$method = 'doPage' . ucfirst($entry['do']);
if(!is_error($site)) {
	exit($site->$method());
}
exit();