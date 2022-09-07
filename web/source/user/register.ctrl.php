<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');
load()->model('setting');
load()->model('utility');
load()->classs('oauth2/oauth2client');

$dos = array('display', 'valid_mobile', 'register', 'check_username', 'get_extendfields', 'check_code', 'check_mobile_code', 'check_password_safe', 'check_failed_code');
$do = in_array($do, $dos) ? $do : 'register';

if (empty($_W['setting']['register']['open'])) {
	itoast('本站暂未开启注册功能，请联系管理员！');
}
$register_type = 'system';
$owner_uid = intval($_GPC['owner_uid']);
$setting = $_W['setting']['register'];
$user_type = empty($_GPC['type']) || USER_TYPE_COMMON == $_GPC['type'] ? USER_TYPE_COMMON : USER_TYPE_CLERK;

if ('register' == $do) {
	if (checksubmit() || $_W['ispost'] && $_W['isajax']) {
		$register_user = OAuth2Client::create($register_type)->setUserType($user_type)->register();
		if (is_error($register_user)) {
			if ('system' == $register_type) {
				itoast($register_user['message']);
			}
			if ('mobile' == $register_type) {
				iajax(-1, $register_user['message']);
			}
		}
		$redirect = url('user/login');
		
		$module_name = safe_gpc_string($_GPC['m']);
		$uniacid = intval($_GPC['uniacid']);
		if (USER_TYPE_CLERK == $user_type && $module_name && $uniacid) {
			//给注册的操作员分配模块权限
			$role = permission_account_user_role($_W['uid'], $uniacid);
			if (in_array($role, array(ACCOUNT_MANAGE_NAME_FOUNDER, ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_VICE_FOUNDER))) {
				$uni_modules = uni_modules_by_uniacid($uniacid);
				if (!empty($uni_modules[$module_name])) {
					pdo_insert('uni_account_users', array('uniacid' => $uniacid, 'uid' => $register_user['uid'], 'role' => 'clerk'));
					pdo_insert('users_permission', array('uniacid' => $uniacid, 'uid' => $register_user['uid'], 'type' => $module_name, 'permission' => 'all'));
				}
			}
			$redirect = url('module/permission/display', array('module_name' => $module_name));
		}
		
		itoast($register_user['message'], $redirect);
	}
}

if ('check_username' == $do) {
	$member['username'] = safe_gpc_string($_GPC['username']);
	if (user_check(array('username' => $member['username']))) {
		iajax(-1, '非常抱歉，此用户名已经被注册，你需要更换注册名称！');
	} else {
		iajax(0, '用户名未被注册');
	}
}

if ('check_code' == $do) {
	if (!checkcaptcha(intval($_GPC['code']))) {
		iajax(-1, '你输入的验证码不正确, 请重新输入.');
	} else {
		iajax(0, '验证码正确');
	}
}

template('user/register');
