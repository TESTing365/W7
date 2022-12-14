<?php
/**
 * 应用欢迎页
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('reply');
load()->model('miniapp');
load()->model('phoneapp');
load()->model('welcome');
load()->model('cache');

$dos = array('display', 'welcome_display', 'get_module_info', 'get_module_replies', 'get_module_accounts', 'get_module_covers');
$do = in_array($do, $dos) ? $do : 'display';

$module_name = safe_gpc_string($_GPC['module_name']) ? safe_gpc_string($_GPC['module_name']) : safe_gpc_string($_GPC['m']);
$uniacid = $_W['uniacid'];
$uni_modules = uni_modules();
$wxapp_create_default = false;
if (!empty($_GPC['version_id'])) {
	$version_info = miniapp_version(intval($_GPC['version_id']));
	if (!empty($version_info) && WXAPP_CREATE_DEFAULT < $version_info['type']) {
		$wxapp_create_default = true;
	}
}
if(!in_array($module_name, array_keys($uni_modules)) && !$wxapp_create_default) {
	if ($_W['isajax']) {
		iajax(-1, '无法访问该模块，该模块已经删除或者停用');
	}
	itoast('无法访问该模块，该模块已经删除或者停用');
}
$module = $_W['current_module'] = module_fetch($module_name, false);
$type = $_W['account']->typeSign;
if (!empty($module) && empty($module['is_delete']) && !empty($module['recycle_info'])) {
	foreach ($module['recycle_info'] as $key => $value)
	{
		if ($type.'_support' == $key && $value == MODULE_RECYCLE_UNINSTALL_IGNORE) {
			$module = array();
			break;
		}
		if ( $type.'_support' == $key && $value == MODULE_RECYCLE_INSTALL_DISABLED ){
			$expire_notice = module_expire_notice();
			itoast($expire_notice, url('home/welcome'), 'info');
		}
	}
}

if (empty($module) || !empty($module['is_delete'])) {
	$_W['current_module'] = array();
	cache_build_account_modules($uniacid);
	itoast('抱歉，你操作的模块不能被访问！', url('home/welcome'), 'info');
}

if ('display' == $do) {
	user_save_operate_history(USERS_OPERATE_TYPE_MODULE, $module_name);
	$notices = welcome_notices_get();
	template('module/welcome');
}

if ('welcome_display' == $do) {
	$site = WeUtility::createModule($module_name);
	if (!is_error($site)) {
		$method = 'welcomeDisplay';
		if (method_exists($site, $method)) {
			define('FRAME', 'module_welcome');
			$entries = module_entries($module_name, array('menu', 'home', 'profile', 'shortcut', 'cover', 'mine'));
			$site->$method($entries);
			exit;
		}
	}
}

if ('get_module_info' == $do) {
	$uni_modules_talbe = table('uni_modules');
	$uni_modules_talbe->searchWithModuleName($module_name);
	$module_info = $uni_modules_talbe->getModulesByUid($_W['uid'], $uniacid);
	$module_info = current($module_info['modules']);
	$module_info['welcome_display'] = false;

	// 模块默认入口
	$site = WeUtility::createModule($module_name);
	if (!is_error($site) && method_exists($site, 'welcomeDisplay')) {
		$module_info['welcome_display'] = true;
	}

	$data = array(
		'module_info' => $module_info,
	);
	iajax(0, $data);
}

if ('get_module_replies' == $do) {
	// 关键字
	$condition = "uniacid = :uniacid AND module != 'cover' AND module != 'userapi'";
	$condition .= ' AND `module` = :type';
	$params[':type'] = $module_name;
	$params[':uniacid'] = $uniacid;
	$replies = reply_search($condition, $params);

	if (!empty($replies)) {
		foreach ($replies as &$item) {
			$condition = '`rid`=:rid';
			$params = array();
			$params[':rid'] = $item['id'];
			$item['keywords'] = reply_keywords_search($condition, $params);
			$item['allreply'] = reply_content_search($item['id']);
			$entries = module_entries($item['module'], array('rule'), $item['id']);

			if (!empty($entries)) {
				$item['options'] = $entries['rule'];
			}
			//若是模块，获取模块图片
			if (!in_array($item['module'], array('basic', 'news', 'images', 'voice', 'video', 'music', 'wxcard', 'reply'))) {
				$item['module_info'] = module_fetch($item['module']);
			}
		}
		unset($item);
	}
	iajax(0, $replies);
}

if ('get_module_accounts' == $do) {
	// 主帐号
	$account_info = uni_fetch($uniacid);
	if (ACCOUNT_MANAGE_NAME_CLERK == $account_info['current_user_role']) {
		unset($account_info['switchurl']);
	}
	// 子账号
	$sub_account_uniacids = table('uni_link_uniacid')->getSubUniacids($uniacid, $module_name);
	$link_accounts = array();
	if (!empty($sub_account_uniacids)) {
		foreach ($sub_account_uniacids as $sub_uniacid) {
			$sub_account_info = uni_fetch($sub_uniacid);
			if ($sub_account_info->supportVersion) {
				if (ACCOUNT_TYPE_PHONEAPP_NORMAL == $type) {
					$versions = phoneapp_get_some_lastversions($sub_uniacid);
				} else {
					$versions = miniapp_get_some_lastversions($sub_uniacid);
				}
				foreach ($versions as $val) {
					if ($val['current']) {
						$version_id = $val['id'];
					}
				}
				$sub_account_info['unbindurl'] = url('wxapp/module-link-uniacid', array('uniacid' => $sub_uniacid, 'version_id' => $version_id));
			} else {
				$sub_account_info['unbindurl'] = url('profile/module-link-uniacid', array('uniacid' => $sub_uniacid));
			}
			if (ACCOUNT_MANAGE_NAME_CLERK == $sub_account_info['current_user_role']) {
				unset($sub_account_info['switchurl']);
			}
			$link_accounts[] = $sub_account_info;
		}
	}

	$data = array('account_info' => $account_info, 'link_accounts' => $link_accounts);
	iajax(0, $data);
}

if ('get_module_covers' == $do) {
	// 封面链接入口
	$entries = module_entries($module_name);
	if (!empty($entries['cover'])) {
		$covers = $entries['cover'];
		$cover_eid = current($covers);
		$cover_eid = $cover_eid['eid'];
	}
	iajax(0, array('covers' => $covers, 'cover_eid' => $cover_eid));
}