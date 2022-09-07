<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
if ('display' != $action) {
	define('FRAME', 'account_manage');
} else {
	if ('display' == $action) {
		define('FRAME', 'platform');
	} else {
		//高版本php引用未定义常量报错，此处定义空值兼容高版本
		define('FRAME', '');
	}
}

if ('account' == $controller && 'manage' == $action) {
	if (!empty($_GPC['account_type']) && ACCOUNT_TYPE_APP_NORMAL == safe_gpc_string($_GPC['account_type'])) {
		define('ACTIVE_FRAME_URL', url('account/manage/display', array('account_type' => ACCOUNT_TYPE_APP_NORMAL)));
	}
}

$account_all_type = uni_account_type();
$account_all_type_sign = uni_account_type_sign();
$account_param = WeAccount::create(array('type' => empty($_GPC['account_type']) ? '' : $_GPC['account_type']));
if (!is_error($account_param)) {
	define('ACCOUNT_TYPE', $account_param->type);
	define('TYPE_SIGN', $account_param->typeSign);
	define('ACCOUNT_TYPE_NAME', $account_param->typeName);
	define('ACCOUNT_TYPE_TEMPLATE', $account_param->typeTempalte);
}
