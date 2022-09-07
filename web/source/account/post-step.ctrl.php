<?php
/**
 * 手动添加公众号
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->func('file');
load()->model('module');
load()->model('user');
load()->model('account');
load()->classs('weixin.platform');

$uniacid = empty($_GPC['uniacid']) ? 0 : intval($_GPC['uniacid']);
$_W['role'] = permission_account_user_role($_W['uid'], $uniacid);
$step = !empty($_GPC['step']) ? intval($_GPC['step']) : 1;
//模版调用，显示该用户所在用户组可添加的主公号数量，已添加的数量，还可以添加的数量
$user_create_account_info = permission_user_account_num();
$dos = array('check_account_limit');
$do = in_array($do, $dos) ? $do : '';
$_W['breadcrumb'] = '新建平台账号';
if ('check_account_limit' == $do) {
	if ($user_create_account_info['account_limit'] <= 0 && !$_W['isfounder']) {
		iajax(-1, '创建公众号已达上限！');
	}
	if (!empty($_W['setting']['platform']['authstate'])) {
		$account_platform = new WeixinPlatform();
		$authurl = $account_platform->getAuthLoginUrl();
		iajax(0, array('url' => $authurl));
	}
	iajax(-1, '请先开启微信开放平台');
}

if (1 == $step) {

} elseif (2 == $step) {
	//新建平台基本信息. 新路由 account/create/base_info  &sign=account
} elseif (3 == $step) {
	//新建平台分配权限. 新路由 account/create/account_modules &uniacid=
} elseif (4 == $step) {
	$uniacid = intval($_GPC['uniacid']);
	$uni_account = pdo_get('uni_account', array('uniacid' => $uniacid));
	if (empty($uni_account)) {
		if ($_W['isajax']) {
			iajax(-1, '非法访问');
		}
		itoast('非法访问');
	}
	$owner_info = account_owner($uniacid);
	if (!($_W['isadmin'] || $_W['uid'] == $owner_info['uid'])) {
		if ($_W['isajax']) {
			iajax(-1, '非法访问');
		}
		itoast('非法访问');
	}
	$account = account_fetch($uni_account['default_acid']);
	if ($_W['isajax']) {
		$result = array(
			'isconnect' => $account['isconnect'],
			'name' => $account['name'],
			'uniacid' => $account['uniacid'],
			'access_url' => $_W['siteroot'] . 'api.php?id=' . $account['acid'],
			'token' => $account['token'],
			'encodingaeskey' => $account['encodingaeskey'],
		);
		iajax(0, $result);
	}
}
template('account/post-step');
