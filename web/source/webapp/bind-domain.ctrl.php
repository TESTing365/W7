<?php
/**
 * 域名访问设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('bind_domain', 'delete', 'default_module');
$do = in_array($do, $dos) ? $do : 'bind_domain';

if ('bind_domain' == $do) {
	
	$modulelist = uni_modules();
	if (!empty($modulelist)) {
		foreach ($modulelist as $key => $module_val) {
			if (!empty($module_val['issystem']) || MODULE_SUPPORT_WEBAPP != $module_val['webapp_support']) {
				unset($modulelist[$key]);
				continue;
			}
		}
	}
	template('webapp/bind-domain');
}

if ('delete' == $do) {
	uni_setting_save('bind_domain', '');
	itoast('删除成功！', referer(), 'success');
}

if ('default_module' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	if (empty($module_name)) {
		iajax(-1, '请选择一个模块！');
	}
	$modulelist = array_keys(uni_modules());
	if (!in_array($module_name, $modulelist)) {
		iajax(-1, '模块不可用！');
	}
	uni_setting_save('default_module', $module_name);
	iajax(0, '修改成功！', referer());
}
