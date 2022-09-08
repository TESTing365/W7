<?php
/**
 * 清除缓存脚本
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
define('IN_SYS', true);
require __DIR__ . '/framework/bootstrap.inc.php';

$result = cache_updatecache();
if ($result) {
	header('Location: ' . $_W['siteroot']);
	exit;
}
