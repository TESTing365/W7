<?php
/**
 * 找回密码短信签名设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('setting');

$dos = array('display', 'update_expire', 'save_expire', 'change_status', 'delete_expire', 'expire_info');
$do = in_array($do, $dos) ? $do : 'display';

$system_module_expire = setting_load('system_module_expire');
$system_module_expire = !empty($system_module_expire['system_module_expire']) ? $system_module_expire['system_module_expire'] : '您访问的功能模块不存在，请重新进入';
$module_expire = setting_load('module_expire');
$module_expire = !empty($module_expire['module_expire']) ? $module_expire['module_expire'] : array();
$module_uninstall_total = module_uninstall_total($module_support);
$url = url('module/expire');
if ('display' == $do) {
	if ($_W['isajax']) {
		$message = array(
			'system_module_expire' => $system_module_expire,
			'extend_buttons' => $module_expire,
			'development' => $_W['config']['setting']['development'],
		);
		iajax(0, $message);
	}
	template('module/expire');
}

if ('save_expire' == $do) {
	if ($_W['ispost']) {
		if (count($module_expire) >= 5) {
			empty($_W['isajax']) ? itoast('最多可设置5条', $url, 'warning') : iajax(-1, '最多可设置5条');
		}
		$title = safe_gpc_string($_GPC['title']);
		$notice = safe_gpc_string($_GPC['notice']);
		if (empty($title)) {
			empty($_W['isajax']) ? itoast('请输入提示名称', $url, 'warning') : iajax(-1, '请输入提示名称');
		}
		if (empty($notice)) {
			empty($_W['isajax']) ? itoast('请输入提示内容', $url, 'warning') : iajax(-1, '请输入提示内容');
		}
		$expire['title'] = $title;
		$expire['notice'] = $notice;
		$expire['status'] = 0;
		$module_expire[] = $expire;
		$result = setting_save($module_expire, 'module_expire');
		if (is_error($result)) {
			empty($_W['isajax']) ? itoast('添加失败', referer(), 'error') : iajax(-1, '添加失败');
		}
		empty($_W['isajax']) ? itoast('添加成功', $url, 'success') : iajax(0, '添加成功');
	}
	template('module/expire_add');
}

if ('update_expire' == $do) {
	$id = safe_gpc_int($_GPC['id']);
	if (empty($module_expire[$id])) {
		empty($_W['isajax']) ? itoast('系统错误，请刷新后再试', $url, 'error') : iajax(-1, '系统错误，请刷新后再试');
	}
	if ($_W['ispost']) {
		$expire['title'] = !empty($_GPC['title']) ? safe_gpc_string($_GPC['title']) : '';
		$expire['notice'] = !empty($_GPC['notice']) ? safe_gpc_string($_GPC['notice']) : '';
		$expire['status'] = $module_expire[$id]['status'];
		$module_expire[$id] = $expire;
		$result = setting_save($module_expire, 'module_expire');
		if (is_error($result)) {
			empty($_W['isajax']) ? itoast('设置失败', referer(), 'error') : iajax(-1, '设置失败');
		}
		empty($_W['isajax']) ? itoast('设置成功', $url, 'success') : iajax(0, '设置成功');
	}
	$expire = $module_expire[$id];
	if (empty($expire)) {
		empty($_W['isajax']) ? itoast('系统错误，请刷新后再试', $url, 'error') : iajax(-1, '系统错误，请刷新后再试');
	}
	template('module/expire_add');
}

if ('change_status' == $do) {
	$status = safe_gpc_int($_GPC['status']) ? 1 : 0;
	$id = safe_gpc_int($_GPC['id']);
	foreach ($module_expire as $key => &$value) {
		$value['status'] = 0;
		if ($key == $id) {
			$value['status'] = $status;
		}
	}
	$result = setting_save($module_expire, 'module_expire');
	if (is_error($result)) {
		iajax(-1, '设置失败', $url);
	}
	iajax(0, '设置成功', $url);
}

if ('delete_expire' == $do) {
	$id = safe_gpc_int($_GPC['id']);
	unset($module_expire[$id]);
	$result = setting_save($module_expire, 'module_expire');
	if (is_error($result)) {
		iajax(-1, '刪除失败', $url);
	}
	iajax(0, '刪除成功', $url);
}

if ('expire_info' == $do) {
	$id = safe_gpc_int($_GPC['id']);
	if (empty($module_expire[$id])) {
		iajax(-1, '参数错误');
	}
	$result = $module_expire[$id];
	$result['id'] = $id;
	iajax(0, $result);
}
