<?php
/**
 * 设置模块启用停用，并显示模块到快捷菜单中.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('account');
load()->model('user');
load()->model('cloud');
load()->model('cache');
load()->model('extension');

$dos = array('display', 'setting', 'setting_params', 'shortcut', 'enable', 'top');
$do = in_array($do, $dos) ? $do : 'display';

$modulelist = uni_modules();

if ('display' == $do) {
	$pageindex = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
	$pagesize = 30;

	$modules = $displayorder = array();
	if (!empty($modulelist)) {
		foreach ($modulelist as $name => $row) {
			if (!empty($row['issystem']) || APPLICATION_TYPE_TEMPLATES == $row['application_type']) {
				continue;
			}
			if (!empty($_GPC['keyword']) && !strexists($row['title'], safe_gpc_string($_GPC['keyword']))) {
				continue;
			}
			if (!empty($_GPC['letter']) && $row['title_initial'] != safe_gpc_string($_GPC['letter'])) {
				continue;
			}
			$displayorder[$name] = $row['displayorder'];
			$modules[$name] = $row;
		}
	}
	array_multisort($displayorder, SORT_DESC, $modules);
	template('module/manage-account');
}
if ('shortcut' == $do) {
	$status = intval($_GPC['shortcut']);
	$module_name = safe_gpc_string($_GPC['modulename']);

	$module_enabled = uni_account_module_shortcut_enabled($module_name, $status);
	if (is_error($module_enabled)) {
		itoast($module_enabled['message'], referer(), 'error');
	}
	itoast(($status ? '添加' : '取消') . '添加模块快捷操作成功！', referer(), 'success');
}
if ('enable' == $do) {
	$module_name = safe_gpc_string($_GPC['modulename']);
	if (empty($modulelist[$module_name])) {
		itoast('抱歉，你操作的模块不能被访问！', '', '');
	}
	pdo_update('uni_account_modules', array(
		'enabled' => empty($_GPC['enabled']) ? STATUS_OFF : STATUS_ON,
	), array(
		'module' => $module_name,
		'uniacid' => $_W['uniacid'],
	));
	cache_build_module_info($module_name);
	itoast('模块操作成功！', referer(), 'success');
}
if ('top' == $do) {
	$module_name = safe_gpc_string($_GPC['modulename']);
	$module = $modulelist[$module_name];
	if (empty($module)) {
		itoast('抱歉，你操作的模块不能被访问！', '', '');
	}
	$max_displayorder = (int) pdo_getcolumn('uni_account_modules', array('uniacid' => $_W['uniacid']), 'MAX(displayorder)');

	$module_profile = pdo_get('uni_account_modules', array('module' => $module_name, 'uniacid' => $_W['uniacid']));
	if (!empty($module_profile)) {
		pdo_update('uni_account_modules', array('displayorder' => ++$max_displayorder), array('id' => $module_profile['id'], 'uniacid' => $_W['uniacid']));
	} else {
		pdo_insert('uni_account_modules', array(
			'displayorder' => ++$max_displayorder,
			'module' => $module_name,
			'uniacid' => $_W['uniacid'],
			'enabled' => STATUS_ON,
			'shortcut' => STATUS_OFF,
		));
	}
	cache_build_module_info($module_name);
	cache_build_account_modules($_W['uniacid']);
	itoast('模块置顶成功', referer(), 'success');
}
if ('setting' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']) ? safe_gpc_string($_GPC['module_name']) : safe_gpc_string($_GPC['m']);
	$module = $_W['current_module'] = $modulelist[$module_name];
	if (empty($module)) {
		itoast('抱歉，你操作的模块不能被访问！', '', '');
	}

	if (!permission_check_account_user_module($module_name . '_settings', $module_name)) {
		itoast('您没有权限进行该操作', '', '');
	}

	if (!defined('IN_MODULE')) {
		define('IN_MODULE', $module_name);
	}
	// 兼容历史性问题：模块内获取不到模块信息$module的问题
	define('CRUMBS_NAV', 1);
	$config = empty($module['config']) ? array() : $module['config'];
	if ((2 == $module['settings']) && !is_file(IA_ROOT . "/addons/{$module['name']}/developer.cer")) {
		template('module/manage-account-setting');
		exit();
	}
	$obj = WeUtility::createModule($module['name']);
	$obj->settingsDisplay($config);
	exit();
}
if ('setting_params' == $do) {
	$module_name = !empty($_GPC['module_name']) ? safe_gpc_string($_GPC['module_name']) : safe_gpc_string($_GPC['m']);
	$module = module_fetch($module_name);
	if (empty($module)) {
		iajax(-1, '抱歉，你操作的模块不能被访问！');
	}
	if (2 != $module['settings'] || is_file(IA_ROOT . "/addons/{$module['name']}/developer.cer")) {
		iajax(-1, '模块未开启云参数');
	}
	if (!permission_check_account_user_module($module_name . '_settings', $module_name)) {
		iajax(-1, '您没有权限进行该操作');
	}

	if (checksubmit()) {
		$post = array(
			'setting' => safe_gpc_array($_GPC['setting']),
			'params' => safe_gpc_array($_GPC['params']),
		);
		if (is_array($post['params'])) {
			foreach ($post['params'] as $param) {
				if ('richtext' == $param['type'] && !empty($post['setting'][$param['name']])) {
					$post['setting'][$param['name']] = safe_gpc_html(htmlspecialchars_decode($post['setting'][$param['name']], ENT_QUOTES));
				}
			}
		}
		$pars = array('module' => $module_name, 'uniacid' => $_W['uniacid']);
		if (pdo_get('uni_account_modules', array('module' => $module_name, 'uniacid' => $_W['uniacid']), array('id'))) {
			$result = pdo_update('uni_account_modules', array('settings' => iserializer($post['setting'])), $pars);
		} else {
			$result = pdo_insert('uni_account_modules', array('settings' => iserializer($post['setting']), 'module' => $module_name, 'uniacid' => $_W['uniacid'], 'enabled' => 1));
		}
		cache_build_module_info($module_name);
		iajax(0, $result);
	}

	$setting = iunserializer($module['cloudsetting']);
	if (is_error($setting)) {
		iajax(-1, $setting['message']);
	}
	$setting['setting'] = $module['config'];
	iajax(0, $setting);
}
