<?php
/**
 * 云服务代理文件，跳转至云服务上
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cloud');
load()->func('communication');

$dos = array('profile', 'callback', 'appstore', 'buybranch', 'sms');
$do = in_array($do, $dos) ? $do : 'profile';

if ('profile' == $do) {
	define('ACTIVE_FRAME_URL', url('cloud/profile'));
	$iframe = cloud_auth_url('profile');
	$title = '注册站点';
}

if ('sms' == $do) {
	define('ACTIVE_FRAME_URL', url('cloud/sms'));
	permission_check_account_user('system_cloud_sms');
	$iframe = cloud_auth_url('sms');
	$title = '云短信';
}

if ('appstore' == $do) {
	$iframe = cloud_auth_url('appstore');
	$title = '应用商城';
	header("Location: $iframe");
	exit;
}

if ('promotion' == $do) {
	if (empty($_W['setting']['site']['key']) || empty($_W['setting']['site']['token'])) {
		itoast('你的程序需要在微擎云服务平台注册你的站点资料, 来接入云平台服务后才能使用推广功能.', url('cloud/profile'), 'error');
	}
	$iframe = cloud_auth_url('promotion');
	$title = '我要推广';
}

if ('buybranch' == $do) {
	$auth = array();
	$auth['name'] = safe_gpc_string($_GPC['m']);
	$auth['branch'] = intval($_GPC['branch']);
	$url = cloud_auth_url('buybranch', $auth);

	$response = ihttp_request($url);
	$response = json_decode($response['content'], true);

	if (is_error($response['message'])) {
		itoast($response['message']['message'], url('module/manage-system'), 'error');
	}

	$params = array(
		'is_upgrade' => 1,
		'is_buy' => 1,
	);
	if ('theme' == safe_gpc_string($_GPC['type'])) {
		$params['t'] = $auth['name'];
	} else {
		$params['m'] = $auth['name'];
	}

	itoast($response['message']['message'], url('cloud/process', $params), 'success');
}

if ('callback' == $do) {
	$secret = safe_gpc_string($_GPC['token']);
	if (32 == strlen($secret)) {
		$cache = cache_read(cache_system_key('cloud_auth_transfer'));
		//兼容处理一下更新时，旧缓存名获取数据
		if (empty($cache) || empty($cache['secret'])) {
			$cache = cache_read('cloud:auth:transfer');
		}
		cache_delete(cache_system_key('cloud_auth_transfer'));
		if (!empty($cache) && $cache['secret'] == $secret) {
			$site = $cache;
			unset($site['secret']);
			setting_save($site, 'site');
			$auth = array();
			$auth['key'] = $site['key'];
			$auth['password'] = md5($site['key'] . $site['token']);
			$url = cloud_auth_url('profile', $auth);
			header('Location: ' . $url);
			exit();
		}
	}
	itoast('访问错误.', '', '');
}

template('cloud/frame');
