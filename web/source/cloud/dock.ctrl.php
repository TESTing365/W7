<?php

/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('cloud');
load()->model('setting');
load()->model('statistics');

$dos = array(
	'auth',
	'build',
	'init',
	'schema',
	'download',
	'module.query',
	'module.bought',
	'module.info',
	'module.build',
	'module.setting.cloud',
	'theme.query',
	'theme.info',
	'theme.build',
	'application.build',
	'sms.send',
	'sms.info',
	'api.oauth',
	'if_un',
	'member_module',
	'module_support',
);
$do = in_array($do, $dos) ? $do : '';

if ('auth' != $do) {
	if (is_error(cloud_prepare())) {
		exit('cloud service is unavailable.');
	}
}

$post = file_get_contents('php://input');
if ('auth' == $do) {
	$auth = @json_decode(base64_decode($post), true);
	if (empty($auth)) {
		exit('推送的站点数据有误');
	}
	//对要写入的数据，再次发起请求，以防非法写入
	$_W['setting']['site'] = isset($_W['setting']['site']) && is_array($_W['setting']['site']) ? $_W['setting']['site'] : array();
	$_W['setting']['site']['url'] = $auth['url'];
	$_W['setting']['site']['key'] = $auth['key'];
	$_W['setting']['site']['token'] = $auth['token'];
	$site_info = cloud_site_info();
	if (is_error($site_info) || $site_info['key'] != $auth['key']) {
		exit('非法请求！');
	}
	$auth['username'] = $site_info['username'];
	setting_save($auth, 'site');
	setting_upgrade_version($auth['family'], $auth['version'], $auth['release']);
	exit('success');
}

$cache_random = cache_random();
if ('build' == $do) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = array();
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/application.build' . $cache_random, iserializer($ret));
		exit($secret);
	}
}

if ('schema' == $do) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = array();
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/application.schema' . $cache_random, iserializer($ret));
		exit($secret);
	}
}

if ('download' == $do) {
	$data = base64_decode($post);
	if (base64_encode($data) !== $post) {
		$data = $post;
	}
	$ret = iunserializer($data);
	$gz = function_exists('gzcompress') && function_exists('gzuncompress');
	$file = base64_decode($ret['file']);
	if ($gz) {
		$file = gzuncompress($file);
	}

	$_W['setting']['site']['token'] = authcode(cache_load(cache_system_key('cloud_transtoken')), 'DECODE');
	$string = (md5($file) . $ret['path'] . $_W['setting']['site']['token']);
	if (!empty($_W['setting']['site']['token']) && md5($string) === $ret['sign']) {
		//模块和微官网模板无需先放在data下，系统文件需放在data下以防升级时文件没有更新完而报错
		//$path = IA_ROOT . $ret['path'];
		if (0 === strpos($ret['path'], '/web/') || 0 === strpos($ret['path'], '/framework/')) {
			$patch_path = sprintf('%s/data/patch/upgrade/%s', IA_ROOT, date('Ymd'));
		} else {
			$patch_path = IA_ROOT;
		}
		$path = $patch_path . $ret['path'];
		load()->func('file');
		@mkdirs(dirname($path));
		file_put_contents($path, $file);
		$sign = md5(md5_file($path) . $ret['path'] . $_W['setting']['site']['token']);
		if ($ret['sign'] === $sign) {
			exit('success');
		}
	}
	exit('failed');
}

if (in_array($do, array('module.query', 'module.bought', 'module.info', 'module.build', 'theme.query', 'theme.info', 'theme.build', 'application.build'))) {
	$dat = __secure_decode($post);
	if (!empty($dat)) {
		$secret = random(32);
		$ret = array();
		$ret['data'] = $dat;
		$ret['secret'] = $secret;
		file_put_contents(IA_ROOT . '/data/' . $do . $cache_random, iserializer($ret));
		exit($secret);
	}
}

if ('module.setting.cloud' == $do) {
	$data = __secure_decode($post);
	$data = iunserializer($data);
	$setting = $data['setting'];
	$_W['uniacid'] = $data['acid'];
	$module = WeUtility::createModule($data['module']);
	$module->saveSettings($setting);
	cache_delete(cache_system_key('module_info', array('module_name' => $data['module'])));
	cache_delete(cache_system_key('module_setting', array('module_name' => $data['module'], 'uniacid' => $_W['uniacid'])));
	echo 'success';
	exit;
}
//检测模块是否已卸载
if ('if_un' == $do) {
	$module_name = safe_gpc_string($_GPC['m']);
	$module_support_type = module_support_type();
	$module_support_type['theme_support'] = array();
	$support_type = in_array($_GPC['support'] . '_support', array_keys($module_support_type)) ? ($_GPC['support'] . '_support') : '';
	if (empty($module_name) || (empty($support_type))) {
		exit('参数错误！');
	}
	$module_info = module_fetch($module_name);
	if (!empty($module_info) && $module_support_type[$support_type]['support'] == $module_info[$support_type]) {
		exit('0');
	}
	exit('1');
}
//返回当前应用已安装的支持
if ('module_support' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$result = array();
	$module_info = module_fetch($module_name);
	if (empty($module_info)) {
		exit(json_encode($result));
	}
	foreach (module_support_type() as $support => $support_info) {
		if ($support_info['support'] == $module_info[$support]) {
			$result[] = $support_info['type'];
		}
	}
	exit(json_encode($result));
}
//会员和模块统计
if ('member_module' == $do) {
	$result['member'] = stat_mc_member();
	$result['module'] = stat_module();
	exit(json_encode($result));
}

if ('sms.send' == $do) {
	$dat = __secure_decode($post);
	$dat = iunserializer($dat);
}

if ('sms.info' == $do) {
	$dat = __secure_decode($post);
	$dat = iunserializer($dat);
	if (!empty($dat) && is_array($dat)) {
		setting_save($dat, 'sms.info');
		cache_clean();
		die('success');
	}
	die('fail');
}

if ('api.oauth' == $do) {
	$dat = __secure_decode($post);
	$dat = iunserializer($dat);
	if (!empty($dat) && is_array($dat)) {
		if ('core' == $dat['module']) {
			$result = file_put_contents(IA_ROOT . '/framework/builtin/core/' . md5(complex_authkey()) . '.cer', $dat['access_token']);
		} else {
			$result = file_put_contents(IA_ROOT . "/addons/{$dat['module']}/" . md5('module' . complex_authkey()) . '.cer', $dat['access_token']);
		}
		if (false !== $result) {
			die('success');
		}
		die('获取到的访问云API的数字证书写入失败.');
	}
	die('获取云API授权失败: api oauth.');
}

function __secure_decode($post) {
	global $_W;
	$data = base64_decode($post);
	if (base64_encode($data) !== $post) {
		$data = $post;
	}
	$ret = iunserializer($data);
	$string = ($ret['data'] . $_W['setting']['site']['token']);
	if (md5($string) === $ret['sign']) {
		return $ret['data'];
	}

	return false;
}
