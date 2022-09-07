<?php
/**
 * 计划任务运行日志
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
error_reporting(0);

$dos = array('log');
if (!in_array($do, $dos)) {
	exit('Access Denied');
}

if ('log' == $do) {
	$tid = intval($_GPC['tid']);
	$module = trim($_GPC['module']);
	$type = trim($_GPC['type']);
	$data = pdo_getall('core_cron_record', array('uniacid' => $_W['uniacid'], 'tid' => $tid, 'module' => $module, 'type' => $type));
	if (!empty($data)) {
		foreach ($data as &$da) {
			$da['createtime'] = date('Y-m-d H:i:s', $da['createtime']);
		}
		unset($da);
	}
	iajax(0, array('items' => $data));
}
