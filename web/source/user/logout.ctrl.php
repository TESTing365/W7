<?php
/**
 * 退出系统
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

isetcookie('__session', '', -10000);
isetcookie('__iscontroller', '', -10000);
isetcookie('__uniacid', '', -10000);
isetcookie('__w7sign', '', -10000);
$forward = !empty($_GPC['forward']) ? safe_gpc_url($_GPC['forward'], false) : '';
if (empty($forward)) {
	$forward = $_W['siteroot'];
}
if ($_W['isajax']) {
	iajax(0, '', $forward);
}
header('Location:' . $forward);
