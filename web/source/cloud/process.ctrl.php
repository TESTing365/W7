<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
load()->func('communication');

$step = $_GPC['step'];
$steps = array('scripts');
$step = in_array($step, $steps) ? $step : '';
$upgrade = glob(IA_ROOT . '/upgrade/*');
$result = array();
if (!empty($upgrade)) {
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
		$result[] = array('version' => $version, 'class' => $class_name, 'description' => $class_name::DESCRIPTION);
	}
}
if ('scripts' == $step && $_W['ispost']) {
	$version = trim($_GPC['version']);
	$result = array_column($result, null, 'version');
	if (class_exists($result[$version]['class'])) {
		set_time_limit(0);
		$up_class = new $result[$version]['class']();
		if ($up_class->up()) {
			cache_build_users_struct();
			cache_build_setting();
			setting_upgrade_version($version);
			exit('success');
		}
	}
	exit('failed');
}
if (empty($result)) {
	cache_updatecache();
	if (ini_get('opcache.enable') || ini_get('opcache.enable_cli')) {
		opcache_reset();
	}
	itoast('更新已完成. ', url('cloud/upgrade'), 'success');
}
template('cloud/process');
