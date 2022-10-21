<?php
/**
 * 找回密码短信签名设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('setting');

$dos = array('save_expire', 'change_status', 'setting');
$do = in_array($do, $dos) ? $do : 'setting';

if (!$_W['isfounder']) {
	if ($_W['isajax']) {
		iajax(-1, '无权限操作！');
	}
	itoast('无权限操作！');
}

$user_expire = setting_load('user_expire');
$user_expire = !empty($user_expire['user_expire']) ? $user_expire['user_expire'] : array();

if ('save_expire' == $do) {
	$type = safe_gpc_string($_GPC['type']);

	if ('day' == $type) {
		$user_expire['day'] = empty($_GPC['day']) ? 1 : max(1, intval($_GPC['day']));
		$url = url('user/expire');
	} elseif ('notice' == $type) {
		$user_expire['notice'] = !empty($_GPC['notice']) ? safe_gpc_string($_GPC['notice']) : '';
		$url = url('user/expire/setting');
	}

	$result = setting_save($user_expire, 'user_expire');
	if (is_error($result)) {
		iajax(-1, '设置失败', $url);
	}
	iajax(0, '设置成功', $url);
}

if ('change_status' == $do && $_W['ispost']) {
	$type = safe_gpc_string($_GPC['type']);
	if (empty($type) || !in_array($type, array('status'))) {
		iajax(-1, '参数错误！');
	}
	if ('status' == $type) {
		$user_expire['status'] = empty($user_expire['status']) ? 1 : 0;
		$url = url('user/expire');
	}

	$result = setting_save($user_expire, 'user_expire');
	if (is_error($result)) {
		iajax(-1, '设置失败', $url);
	}
	iajax(0, '设置成功', $url);
}

if ('setting' == $do) {
	$user_expire['notice'] = !empty($user_expire['notice']) ? $user_expire['notice'] : '您的账号已到期，请联系管理员';
	unset($user_expire['status'], $user_expire['day']);
}
if ($_W['isajax']) {
	iajax(0, $user_expire);
}
template('user/expire');

