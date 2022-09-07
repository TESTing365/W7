<?php
/**
 * 用户登录
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');
if (!empty($_POST['username']) && !empty($_POST['password'])) {
	_login($_GPC['referer']);
}
$setting = $_W['setting'];
template('cloud/login');

function _login($forward = '') {
	global $_GPC, $_W;
	$member = array();
	$username = trim($_GPC['username']);
	pdo_delete('users_failed_login', array('lastupdate <' => TIMESTAMP-300));
	
	$failed = pdo_get('users_failed_login', array('username' => $username, 'ip' => CLIENT_IP));
	if ($failed['count'] >= 5) {
		message('输入密码错误次数超过5次，请在5分钟后再登录',referer(), 'info');
	}
	if (!empty($_W['setting']['copyright']['verifycode'])) {
		$verify = trim($_GPC['verify']);
		if (empty($verify)) {
			message('请输入验证码', '', '');
		}
		$result = checkcaptcha($verify);
		if (empty($result)) {
			message('输入验证码错误', '', '');
		}
	}
	if (empty($username)) {
		message('请输入要登录的用户名', '', '');
	}
	if (empty($_GPC['password'])) {
		message('请输入密码', '', '');
	}
	$record = pdo_get('users', array('username' => $username));
	$password = sha1("{$_GPC['password']}-{$record['salt']}-{$_W['config']['setting']['authkey']}");
	if ($password !== $record['password']) {
		return false;
	}
	if (!empty($record)) {
		$_W['uid'] = $record['uid'];
		$_W['user'] = $record;

		$cookie = array();
		$cookie['uid'] = $record['uid'];
		$cookie['lastvisit'] = $record['lastvisit'];
		$cookie['lastip'] = $record['lastip'];
		$cookie['hash'] = md5($record['password'] . $record['salt']);
		$session = authcode(json_encode($cookie), 'encode');
		isetcookie('__session', $session, !empty($_GPC['rember']) ? 7 * 86400 : 0, true);

		if ($record['uid'] != $_GPC['__uid']) {
			isetcookie('__uniacid', '', -7 * 86400);
			isetcookie('__uid', '', -7 * 86400);
		}
		pdo_delete('users_failed_login', array('id' => $failed['id']));
		message("欢迎回来，{$record['username']}。", 'cloud.php', 'success');
	} else {
		if (empty($failed)) {
			pdo_insert('users_failed_login', array('ip' => CLIENT_IP, 'username' => $username, 'count' => '1', 'lastupdate' => TIMESTAMP));
		} else {
			pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
		}
		message('登录失败，请检查您输入的用户名和密码！', '', '');
	}
}