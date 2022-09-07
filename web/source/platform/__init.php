<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

if (!('material' == $action && 'delete' == $do) && empty($_GPC['version_id'])) {
	$account_api = WeAccount::createByUniacid();
	if (is_error($account_api)) {
		itoast('', $_W['siteroot'] . 'web/home.php');
	}
	$check_manange = $account_api->checkIntoManage();
	if (is_error($check_manange)) {
		itoast('', $account_api->displayUrl);
	}
	if ('detail' == $do) {
		define('FRAME', '');
	} else {
		define('FRAME', 'account');
	}
}

if ('material-post' != $action && (empty($_GPC['uniacid']) || FILE_NO_UNIACID != $_GPC['uniacid'])) {
	!defined('FRAME') && define('FRAME', 'account');
} else {
	define('FRAME', '');
}
