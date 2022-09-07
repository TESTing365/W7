<?php
/**
 * 小程序域名设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
load()->classs('uploadedfile');
load()->func('file');

$dos = array('display', 'domain_post');
$do = in_array($do, $dos) ? $do : 'display';

$wxapp_info = miniapp_fetch($_W['uniacid']);

// 是否是模块打包小程序
$is_module_wxapp = false;
if (!empty($version_id)) {
	$is_single_module_wxapp = WXAPP_CREATE_MODULE == $version_info['type']; //是否单应用打包
}

if ('display' == $do) {
	$appurl = $_W['siteroot'] . 'app/index.php';
	$uniacid = 0;
	if (!empty($version_info)) {
		$wxapp = table('account_wxapp')->wxappInfo($version_info['uniacid']);
		if ($wxapp && !empty($wxapp['appdomain'])) {
			$appurl = $wxapp['appdomain'];
		}
		if (!starts_with($appurl, 'https')) { //不是https 开头强制改为https开头
			$appurl = str_replace('http', 'https', $appurl);
		}
		$uniacid = $version_info['uniacid'];
		if ($_W['account']->type == ACCOUNT_TYPE_APP_AUTH) {
			$data = array(
				'action' => 'get',
			);
			$webviewdomain = $_W['account']->setWebViewDomain($data);
			if (is_error($webviewdomain)) {
				itoast($webviewdomain['message']);
			}
		}
	}
	if ($_W['ispost']) {
		$appurl = safe_gpc_url($_GPC['appurl'], false);

		if (!starts_with($appurl, 'https')) {
			itoast('域名必须以https开头');

			return;
		}

		$file = $_FILES['file'];
		if (!empty($file['name']) && 0 == $file['error']) {
			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
			if ('txt' == strtolower($ext)) {
				$file['name'] = parse_path($file['name']);
				file_move($file['tmp_name'], IA_ROOT . '/' . $file['name']);
			}
		}
		if ($version_info) {
			$update = table('account_wxapp')
				->where(array('uniacid' => $uniacid))
				->fill(array('appdomain' => $appurl))
				->save();
			itoast('更新成功'); //新 旧域名一样 返回$update 为0
		}
	}

	template('wxapp/domainset');
}

if ('domain_post' == $do) {
	$action = safe_gpc_string($_GPC['action']);
	$action = in_array($action, array('add', 'delete')) ? $action : 'add';
	$webviewdomain = safe_gpc_string($_GPC['webviewdomain']);

	if (empty($webviewdomain) || substr($webviewdomain, 0, 8) !== "https://") {
		iajax(-1, '请输入正确的业务域名!');
	}
	$data = array(
		'action' => $action,
		'webviewdomain' => $webviewdomain
	);
	$result = $_W['account']->setWebViewDomain($data);
	if (is_error($result)) {
		iajax(-1, $result['message']);
	}
	cache_delete(cache_system_key('account_web_view_domain', array('uniacid' => $_W['uniacid'])));
	iajax(0, '操作成功!', referer());
}
