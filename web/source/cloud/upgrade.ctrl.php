<?php
/**
 * 用户登录
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');
$dos = array('get_upgrade_info', 'build', 'init');
$do = in_array($do, $dos) ? $do : '';
$setting = $_W['setting'];

if ('get_upgrade_info' == $do) {
	$upgrade = glob(IA_ROOT . '/upgrade/*');
	if (empty($upgrade)) {
		iajax(1, '已是最新版，无需更新！');
	}
	$result = array();
	foreach ($upgrade as $item) {
		$path_array = explode('/', $item);
		$version = end($path_array);
		if (!str_is_version($version)) {
			continue;
		}
		if (version_compare($version, IMS_VERSION, '<=')) {
			continue;
		}
		include_once $item . '/up.php';
		$class_name = 'W7\\U' . str_replace('.', '', $version) . '\\Up';
		$result[] = array('version' => $version, 'description' => $class_name::DESCRIPTION);
	}
	iajax(0, $result);
}

if ('upgrade' == $do) {
	$upgrade = glob(IA_ROOT . '/upgrade/*');
	foreach ($upgrade as $item) {
		$path_array = explode('/', $item);
		if (version_compare(IMS_VERSION, end($path_array))) {
			include_once $item . '/up.php';
		}
	}
}
template('cloud/upgrade');
