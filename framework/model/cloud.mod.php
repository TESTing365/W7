<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

function cloud_client_define() {
	return array(
		'/framework/function/communication.func.php',
		'/framework/model/cloud.mod.php',
		'/web/source/cloud/upgrade.ctrl.php',
		'/web/source/cloud/process.ctrl.php',
		'/web/source/cloud/dock.ctrl.php',
		'/web/themes/default/cloud/upgrade.html',
		'/web/themes/default/cloud/process.html'
	);
}

function cloud_not_must_authorization_method() {
	return array(
		'module/setting/index',
		'module/setting/save',
		'sms/info',
		'sms/sign',
		'wxapp/info',
		'wxapp/login/qr-code',
		'wxapp/login/qr-scan',
		'wxapp/publish',
		'wxapp/publish/download',
		'module/query',
		'theme/query',
		'we7/oauth/user-bind/mobile-bind-info',
		'we7/oauth/user-bind/mobile-code',
		'we7/oauth/user-bind/complete',
		'we7/oauth/user-bind/info',
		'we7/oauth/user-bind/complete-with-accesstoken',
		'we7/site/console/visible',
		'we7/site/console/index-url',
		'we7/site/console/share-url',
		'site/oauth/user/web-token/verify',
		'site/oauth/register-url/index',
		'site/oauth/user/info',
		'site/token/index',
		'site/oauth/login-url/index',
		'site/oauth/access-token/code',
	);
}

/**
 * @param bool $must_authorization_host 是否校验授权域名请求接口
 * @return array
 */
function _cloud_build_params($must_authorization_host = true) {
	global $_W;
	$pars = array();
	$pars['host'] = strexists($_SERVER['HTTP_HOST'], ':') ? parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) : $_SERVER['HTTP_HOST'];
	if (is_array($_W['setting']['site']) && !empty($_W['setting']['site']['url']) && !$must_authorization_host) {
		$pars['host'] = parse_url($_W['setting']['site']['url'], PHP_URL_HOST);
	}
	$pars['https'] = $_W['ishttps'] ? 1 : 0;
	$pars['family'] = IMS_FAMILY;
	$pars['version'] = IMS_VERSION;
	$pars['php_version'] = PHP_VERSION;
	$pars['current_host'] = $_SERVER['HTTP_HOST'];
	$pars['release'] = '';
	if (!empty($_W['setting']['site'])) {
		$pars['key'] = $_W['setting']['site']['key'];
		$pars['password'] = md5($_W['setting']['site']['key'] . $_W['setting']['site']['token']);
	}
	$clients = cloud_client_define();
	$string = '';
	foreach ($clients as $cli) {
		$string .= md5_file(IA_ROOT . $cli);
	}
	$pars['client'] = md5($string);
	return $pars;
}

function _cloud_shipping_parse($dat, $file) {
	if (is_error($dat)) {
		return error(-1, '网络传输故障，详情： ' . (strpos($dat['message'], 'Connection reset by peer') ? '云服务瞬时访问过大而导致网络传输中断，请稍后重试。' : $dat['message']));
	}
	$tmp = iunserializer($dat['content']);
	if (is_array($tmp) && is_error($tmp)) {
		if ($tmp['errno'] == '-2') {
			file_put_contents(IA_ROOT . '/framework/version.inc.php', str_replace("'x'", "'v'", file_get_contents(IA_ROOT . '/framework/version.inc.php')));
		}
		return $tmp;
	}
	if ($dat['content'] == 'patching') {
		return error(-1, '补丁程序正在更新中，请稍后再试！');
	}
	if ($dat['content'] == 'frequent') {
		return error(-1, '更新操作太频繁，请稍后再试！');
	}
	if ($dat['content'] == 'blacklist') {
		return error(-1, '抱歉，您的站点已被列入云服务黑名单，云服务一切业务已被禁止，请联系微擎客服！');
	}
	if ($dat['content'] == 'install-theme-protect' || $dat['content'] == 'install-module-protect') {
		return error('-1', '此' . ($dat['content'] == 'install-theme-protect' ? '模板' : '模块') . '已设置版权保护，您只能通过云平台来安装，请先删除该模块的所有文件，购买后再行安装。');
	}
	$content = json_decode($dat['content'], true);
	if (!empty($content['error'])) {
		return error(-1, $content['error']);
	}
	if (!empty($content) && is_array($content)) {
		return $content;
	}

	if (strlen($dat['content']) != 32) {
		$dat['content'] = iunserializer($dat['content']);
		if (is_array($dat['content']) && isset($dat['content']['files'])) {
			if (!empty($dat['content']['manifest'])) {
				$dat['content']['manifest'] = base64_decode($dat['content']['manifest']);
			}
			if (!empty($dat['content']['scripts'])) {
				$dat['content']['scripts'] = base64_decode($dat['content']['scripts']);
			}
			return $dat['content'];
		}
		if (is_array($dat['content']) && isset($dat['content']['data'])) {
			$data = $dat['content'];
		} else {
			return error(-1, '云服务平台向您的服务器传输数据过程中出现错误,详情:' . $dat['content']);
		}
	} else {
		$data = @file_get_contents($file);
		@unlink($file);
	}

	$ret = @iunserializer($data);
	if (empty($data) || empty($ret)) {
		return error(-1, '云服务平台向您的服务器传输的数据校验失败.可尝试：1、更新缓存 2、云服务诊断');
	}
	$ret = iunserializer($ret['data']);
	if (is_array($ret) && is_error($ret)) {
		if ($ret['errno'] == '-2') {
			file_put_contents(IA_ROOT . '/framework/version.inc.php', str_replace("'x'", "'v'", file_get_contents(IA_ROOT . '/framework/version.inc.php')));
		}
		if ($ret['errno'] == '-3') { //模块升级服务到期
			return array(
				'errno' => $ret['errno'],
				'message' => $ret['message'],
				'cloud_id' => $ret['data'],
			);
		}
	}
	if (!is_error($ret) && is_array($ret)) {
		if (!empty($ret) && !empty($ret['state']) && $ret['state'] == 'fatal') {
			return error($ret['errorno'], '发生错误: ' . $ret['message']);
		}
		return $ret;
	} else {
		return error($ret['errno'], "发生错误: {$ret['message']}");
	}
}

function cloud_request($url, $post = '', $extra = array(), $timeout = 60) {
	global $_W;
	load()->func('communication');
	if (!empty($_W['setting']['cloudip']['ip']) && empty($extra['ip'])) {
		$extra['ip'] = $_W['setting']['cloudip']['ip'];
	}
	if (strexists($url, 's.w7.cc')) {
		$extra = array();
	}

	$response = ihttp_request($url, $post, $extra, $timeout);
	if (is_error($response)) {
		setting_save(array(), 'cloudip');
	}
	return $response;
}

function cloud_api($method, $data = array(), $extra = array(), $timeout = 60) {
	global $_W;
	$cache_key = cache_system_key('cloud_api', array('method' => md5($method . json_encode($data))));
	$cache = cache_load($cache_key);
	$extra['nocache'] = empty($extra['nocache']) ? false : true;
	if (!empty($cache) && !$extra['nocache']) {
		return $cache;
	}
	$api_url = CLOUD_API_DOMAIN . '/%s';
	$not_must_authorization_method = cloud_not_must_authorization_method();
	$must_authorization_host = !in_array($method, $not_must_authorization_method);
	$pars = _cloud_build_params($must_authorization_host);
	if ($method != 'site/token/index') {
		$pars['token'] = cloud_build_transtoken();
	}
	$data = array_merge($pars, $data);
	if (starts_with($_SERVER['HTTP_USER_AGENT'], 'we7')) {
		$extra['CURLOPT_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'];
	}
	if (!empty($_W['config']['setting']['useragent']) && starts_with($_W['config']['setting']['useragent'], 'we7')) {
		$extra['CURLOPT_USERAGENT'] = $_W['config']['setting']['useragent'];
	}
	$extra['X-We7-Cache'] = cache_random(4, $extra['nocache']);
	$response = ihttp_request(sprintf($api_url, $method), $data, $extra, $timeout);
	$file = IA_ROOT . '/data/' . (!empty($data['file']) ? $data['file'] : str_replace('/', '', $method));
	$file = $file . cache_random();
	$ret = _cloud_shipping_parse($response, $file);
	if (is_error($ret)) {
		WeUtility::logging('cloud-api-error', array('method' => sprintf($api_url, $method), 'data' => $data, 'extra' => $extra, 'response' => $response), true);
	}
	if (!is_error($ret) && !empty($ret)) {
		cache_write($cache_key, $ret, CACHE_EXPIRE_MIDDLE);
	}
	return $ret;
}

function cloud_prepare() {
	global $_W;
	setting_load();
	if (empty($_W['setting']['site']['key']) || empty($_W['setting']['site']['token'])) {
		return error('-1', '站点注册信息丢失, 请通过"重置站点ID和通信密钥"重新获取 !');
	}
	return true;
}

function cloud_module_setting($acid, $module) {
	$pars = array(
		'acid' => $acid,
		'module_name' => $module['name'],
		'module_version' => $module['version'],
	);
	return cloud_api('module/setting/index', $pars);
}

function cloud_module_setting_save($acid, $module_name, $setting) {
	$pars = array(
		'acid' => $acid,
		'module_name' => $module_name,
		'setting' => $setting,
	);
	return cloud_api('module/setting/save', $pars, array('nocache' => STATUS_ON));
}

/**
 * 小程序配置信息
 */
function cloud_wxapp_info($moduleinfo) {
	return cloud_api('wxapp/info', $moduleinfo);
}

/**
 * 下载小程序
 */
function cloud_miniapp_get_package($data) {
	return cloud_api('wxapp/publish/download', $data);
}

function cloud_build_transtoken() {
	$pars['method'] = 'application.token';
	$pars['file'] = 'application.build';
	$ret = cloud_api('site/token/index', $pars);
	if (!empty($ret['token'])) {
		cache_write(cache_system_key('cloud_transtoken'), authcode($ret['token'], 'ENCODE'));
		return $ret['token'];
	}
	return '';
}