<?php
/**
 * 初始化web端数据
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->web('common');
load()->web('template');
load()->func('file');
load()->func('tpl');
load()->model('cloud');
load()->model('user');
load()->model('permission');
load()->model('attachment');
load()->classs('oauth2/oauth2client');
load()->model('switch');
load()->model('system');

$_W['isw7_request'] = !empty($_SERVER['HTTP_W7_OAUTHTOKEN']) || isset($_GPC['w7_request_secret']) && isset($_GPC['w7_accesstoken']);
$session = !empty($_GPC['__session']) ? json_decode(authcode($_GPC['__session']), true) : '';
if (is_array($session)) {
	$user = user_single(array('uid' => $session['uid']));
	if (is_array($user) && $session['hash'] === $user['hash']) {
		$_W['uid'] = $user['uid'];
		$_W['username'] = $user['username'];
		$user['currentvisit'] = $user['lastvisit'];
		$user['currentip'] = $user['lastip'];
		$user['lastvisit'] = empty($session['lastvisit']) ? '' : $session['lastvisit'];
		$user['lastip'] = empty($session['lastip']) ? '--' : $session['lastip'];
		$_W['user'] = $user;
		$_W['isfounder'] = user_is_founder($_W['uid']);
		$_W['isadmin'] = user_is_founder($_W['uid'], true);
	} else {
		isetcookie('__session', '', -100);
	}
	unset($user);
}
unset($session);
if (IMS_FAMILY == 'c' && ((!empty($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'console.w7.cc'))) && $controller != 'cloud' && $action != 'touch') {
	if ($_W['isajax']) {
		iajax(-1, '社区版不支持控制台');
	} else {
		echo ierror_page('社区版不支持控制台');
		exit;
	}
}
if (empty($_GPC['w7i']) && !empty($_GPC['uniacid']) && 0 < $_GPC['uniacid']) {
	$_GPC['w7i'] = $_GPC['uniacid'];
}
$_W['uniacid'] = !empty($_GPC['w7i']) ? $_GPC['w7i'] : igetcookie('__uniacid');
if (empty($_W['uniacid'])) {
	$_W['uniacid'] = switch_get_account_display();
}
$_W['uniacid'] = $_GPC['w7i'] = intval($_W['uniacid']);
if (!empty($_GPC['w7i']) && !empty(igetcookie('__uniacid')) && $_GPC['w7i'] != igetcookie('__uniacid')) {
	isetcookie('__uniacid', $_W['uniacid'], 7 * 86400);
}

if (!empty($_W['uid'])) {
	$_W['highest_role'] = permission_account_user_role($_W['uid']);
	$_W['role'] = permission_account_user_role($_W['uid'], $_W['uniacid']);
}

$_W['template'] = '2.0';

$_W['token'] = token();
$_W['attachurl'] = attachment_set_attach_url();
