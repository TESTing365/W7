<?php
/*
 * 性能优化相关操作
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */

defined('IN_IA') or exit('Access Denied');

load()->func('cache');

$dos = array('opcache');
$do = in_array($do, $dos) ? $do : 'index';

if ('opcache' == $do) {
	opcache_reset();
	if ($_W['isw7_request']) {
		iajax(0, '清空缓存成功');
	}
	itoast('清空缓存成功', url('system/optimize'), 'success');
} else {
	$cache_type = cache_type();
	$clear = array('url' => url('system/updatecache', array('do' => 'updatecache')), 'title' => '更新缓存');
	$extensions = array(
		'memcache' => array(
			'support' => extension_loaded('memcache'),
			'status' => 'memcache' == $cache_type,
			'clear' => $clear,
		),
		'redis' => array(
			'support' => extension_loaded('redis'),
			'status' => 'redis' == $cache_type,
			'clear' => $clear,
		),
		'eAccelerator' => array(
			'support' => function_exists('eaccelerator_optimizer'),
			'status' => function_exists('eaccelerator_optimizer'),
		),
		'opcache' => array(
			'support' => function_exists('opcache_get_configuration'),
			'status' => ini_get('opcache.enable') || ini_get('opcache.enable_cli'),
			'clear' => array(
				'url' => url('system/optimize/opcache'),
				'title' => '清空缓存',
			),
		),
	);
	$slave = $_W['config']['db'];
	$setting = $_W['config']['setting'];

	if ($extensions['memcache']['status']) {
		$memobj = cache_memcache();
		if (!empty($memobj) && method_exists($memobj, 'getExtendedStats')) {
			//缓存服务器池中所有服务器统计信息
			$status = $memobj->getExtendedStats();
			if (!empty($status)) {
				foreach ($status as $server => $row) {
					$data_status[] = '已用：' . round($row['bytes'] / 1048576, 2) . ' M / 共：' . round($row['limit_maxbytes'] / 1048576) . ' M';
				}
				$extensions['memcache']['extra'] = ', ' . implode(', ', $data_status);
			}
		}
	}
	if ($extensions['redis']['status']) {
		$redisobj = cache_redis();
		if (!empty($redisobj) && method_exists($redisobj, 'info')) {
			//缓存服务器池中所有服务器统计信息
			$status = $redisobj->info();
			if (!empty($status)) {
				$extensions['redis']['extra'] = '消耗峰值：' . round($status['used_memory_peak'] / 1048576, 2) . ' M/ 内存总量：' . round($status['used_memory'] / 1048576, 2) . ' M';
			}
		}
	}
	if ($_W['isw7_request']) {
		$message = array(
			'extensions' => $extensions,
			'slave_status' => $slave['slave_status'],
			'session_setting' => array(
				'memcache' => empty($setting['memcache']['session']) ? STATUS_OFF : STATUS_ON,
				'redis' => empty($setting['redis']['session']) ? STATUS_OFF : STATUS_ON
			),
			'slave_except_table' => !empty($slave['common']['slave_except_table']) ? $slave['common']['slave_except_table']: array(),
			'proxy_host' => !empty($setting['proxy']['host']) ? $setting['proxy']['host'] : ''
		);
		iajax(0, $message);
	}
	template('system/optimize');
}
