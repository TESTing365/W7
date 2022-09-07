<?php
/**
 * 用户登录
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
define('IN_GW', true);

load()->model('message');
load()->model('utility');
if (!empty($_W['uid']) && 'bind' != $_GPC['handle_type']) {
	if ($_W['isajax']) {
		iajax(-1, '请先退出再登录！');
	}
	itoast('', $_W['siteroot'] . 'web/home.php');
}

$setting = $_W['setting'];
$_GPC['login_type'] = !empty($_GPC['login_type']) ? $_GPC['login_type'] : 'system';
$support_login_types = OAuth2Client::supportThirdLoginType();
if (checksubmit() || $_W['isajax'] || in_array($_GPC['login_type'], $support_login_types)) {
	_login($_GPC['referer']);
}
$login_urls = user_support_urls();
$login_template = !empty($_W['setting']['basic']['login_template']) ? $_W['setting']['basic']['login_template'] : 'base';
$login_template = 'base';
template('user/login-' . $login_template);

function _login($forward = '') {
	global $_GPC, $_W, $setting;
	if (empty($_GPC['login_type'])) {
		$_GPC['login_type'] = 'system';
	}

	if (empty($_GPC['handle_type'])) {
		$_GPC['handle_type'] = 'login';
	}

	$appid = empty($_W['setting']['thirdlogin']) ? '' : $_W['setting']['thirdlogin'][$_GPC['login_type']]['appid'];
	$appsecret = empty($_W['setting']['thirdlogin']) ? '' : $_W['setting']['thirdlogin'][$_GPC['login_type']]['appsecret'];
	if ('login' == $_GPC['handle_type']) {
		$member = OAuth2Client::create($_GPC['login_type'], $appid, $appsecret)->login();
	} else {
		$member = OAuth2Client::create($_GPC['login_type'], $appid, $appsecret)->bind();
	}

	if (!empty($_W['user']) && '' != $_GPC['handle_type'] && 'bind' == $_GPC['handle_type']) {
		if (is_error($member)) {
			if ($_W['isajax']) {
				iajax(-1, $member['message'], url('user/profile/bind'));
			}
			itoast($member['message'], url('user/profile/bind'), '');
		} else {
			if ($_W['isajax']) {
				iajax(1, '绑定成功', url('user/profile/bind'));
			}
			itoast('绑定成功', url('user/profile/bind'), '');
		}
	}

	if (is_error($member)) {
		if ($_W['isajax']) {
			iajax(-1, $member['message'], url('user/login'));
		}
		itoast($member['message'], url('user/login'), '');
	}

	$record = user_single($member);
	$failed = pdo_get('users_failed_login', array('username' => safe_gpc_string($_GPC['username'])));
	if (!empty($record)) {
		if (USER_STATUS_CHECK == $record['status'] || USER_STATUS_BAN == $record['status']) {
			if ($_W['isajax']) {
				iajax(-1, '您的账号正在审核或是已经被系统禁止，请联系网站管理员解决', url('user/login'));
			}
			itoast('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决', url('user/login'), '');
		}
		$_W['uid'] = $record['uid'];
		$_W['isfounder'] = user_is_founder($record['uid']);
		$_W['isadmin'] = user_is_founder($_W['uid'], true);
		$_W['user'] = $record;
		$_GPC['agreement'] = empty($_GPC['agreement']) ? 0 : (int)$_GPC['agreement'];
		if ($_W['isadmin'] && $_GPC['agreement'] === 1) {
			setting_save(1, 'community_agreement');
		}
		if ($_W['isadmin'] && true === cloud_prepare() && $_GPC['agreement'] != 1 && empty($setting['community_agreement'])) {
			$extend_buttons = array(
				'cancel' => array('url' => '', 'class' => 'btn btn-default', 'title' => '取消', ),
				'agree' => array('url' => '', 'class' => 'btn btn-primary', 'title' => '同意协议并继续'),
			);
			$message = array(
				'status' => -2,
				'message' => '微擎社区版更新协议',
				'extend_buttons' => $extend_buttons,
				'redirect' => '',
			);
			iajax(0, $message);
		}
		$support_login_bind_types = Oauth2CLient::supportThirdLoginBindType();
		if (in_array($_GPC['login_type'], $support_login_bind_types) && !empty($_W['setting']['copyright']['oauth_bind']) && !$record['is_bind'] && empty($_W['isfounder']) && (USER_REGISTER_TYPE_QQ == $record['register_type'] || USER_REGISTER_TYPE_WECHAT == $record['register_type'])) {
			if ($_W['isajax']) {
				iajax(-1, '您还没有注册账号，请前往注册');
			}
			message('您还没有注册账号，请前往注册', url('user/third-bind/bind_oauth', array('uid' => $record['uid'], 'openid' => $record['openid'], 'register_type' => $record['register_type'])));
			exit;
		}

		if (!empty($_W['siteclose']) && empty($_W['isfounder'])) {
			if ($_W['isajax']) {
				iajax(-1, '站点已关闭，关闭原因:' . $_W['setting']['copyright']['reason']);
			}
			itoast('站点已关闭，关闭原因:' . $_W['setting']['copyright']['reason'], '', '');
		}
		if (isset($_W['setting']['copyright']['log_status']) && $_W['setting']['copyright']['log_status'] == STATUS_ON) {
			$login_log = array(
				'uid' => $_W['uid'],
				'ip' => $_W['clientip'],
				'city' => isset($local['data']['city']) ? $local['data']['city'] : '',
				'createtime' => TIMESTAMP
			);
			table('users_login_logs')->fill($login_log)->save();
		}

		if ((empty($_W['isfounder']) || user_is_vice_founder()) && $_W['user']['is_expired']) {
			$user_expire = setting_load('user_expire');
			$user_expire = !empty($user_expire['user_expire']) ? $user_expire['user_expire'] : array();
			$notice = !empty($user_expire['notice']) ? $user_expire['notice'] : '您的账号已到期，请联系管理员!';
			$redirect = '';
			$extend_buttons = array();
			$extend_buttons['cancel'] = array(
				'url' => '',
				'class' => 'btn btn-default',
				'title' => '取消',
			);
			if ($_W['isajax']) {
				$message = array(
					'status' => -1,
					'message' => $notice,
					'extend_buttons' => $extend_buttons,
					'redirect' => $redirect,
				);
				iajax(0, $message);
			}
			message($notice, $redirect, 'expired', '', $extend_buttons);
		}

		$cookie = array();
		$cookie['uid'] = $record['uid'];
		$cookie['lastvisit'] = $record['lastvisit'];
		$cookie['lastip'] = $record['lastip'];
		$cookie['hash'] = !empty($record['hash']) ? $record['hash'] : md5($record['password'] . $record['salt']);
		$cookie['rember'] = empty($_GPC['rember']) ? 0 : safe_gpc_int($_GPC['rember']);
		$session = authcode(json_encode($cookie), 'encode');
		$autosignout = (int)$_W['setting']['copyright']['autosignout'] > 0 ? (int)$_W['setting']['copyright']['autosignout'] * 60 : 0;
		isetcookie('__session', $session, !empty($_GPC['rember']) ? 7 * 86400 : $autosignout, true);
		pdo_update('users', array('lastvisit' => TIMESTAMP, 'lastip' => $_W['clientip']), array('uid' => $record['uid']));

		if (empty($forward)) {
			$forward = user_login_forward(!empty($_GPC['forward']) ? $_GPC['forward'] : '');
		}
		// 只能跳到本域名下
		$forward = safe_gpc_url($forward);

		$_GPC['__uid'] = empty($_GPC['__uid']) ? 0 : $_GPC['__uid'];
		if ($record['uid'] != $_GPC['__uid']) {
			isetcookie('__uniacid', '', -7 * 86400);
			isetcookie('__uid', '', -7 * 86400);
		}
		if (!empty($failed)) {
			pdo_delete('users_failed_login', array('id' => $failed['id']));
		}
		cache_build_frame_menu();
		if ($_W['isajax']) {
			iajax(0, "欢迎回来，{$record['username']}", $forward);
		}
		itoast("欢迎回来，{$record['username']}", $forward, 'success');
	} else {
		if (empty($failed)) {
			pdo_insert('users_failed_login', array('ip' => $_W['clientip'], 'username' => safe_gpc_string($_GPC['username']), 'count' => '1', 'lastupdate' => TIMESTAMP));
		} else {
			pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
		}
		if ($_W['isajax']) {
			iajax(-1, '登录失败，请检查您输入的账号和密码');
		}
		itoast('登录失败，请检查您输入的账号和密码', '', '');
	}
}
