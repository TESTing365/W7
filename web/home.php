<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 */
define('IN_SYS', true);
require __DIR__ . '/../framework/bootstrap.inc.php';
require IA_ROOT . '/web/common/bootstrap.sys.inc.php';
if (empty($_W['isadmin']) && !empty($_W['user']) && ($_W['user']['status'] == USER_STATUS_CHECK || $_W['user']['status'] == USER_STATUS_BAN)) {
	isetcookie('__session', '', -10000);
	itoast('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！', url('account/welcome'), 'info');
}
if (empty($_W['isadmin']) && $_W['role'] == ACCOUNT_MANAGE_NAME_EXPIRED || $_W['highest_role'] == ACCOUNT_MANAGE_NAME_EXPIRED) {
	itoast('您的账号已过期，请联系管理员处理！', url('account/welcome'), 'info');
}

if (!empty($_W['setting']['copyright']['status']) && $_W['setting']['copyright']['status'] == 1 && empty($_W['isfounder'])) {
	isetcookie('__session', '', -10000);
	itoast('站点已关闭，关闭原因：' . $_W['setting']['copyright']['reason']);
}

$_W['page'] = array();
$_W['page']['copyright'] = $_W['setting']['copyright'];
checklogin();

isetcookie('__iscontroller', 0);
$_W['iscontroller'] = 0;
function _calc_current_frames() {
	global $_W;
	$_W['page']['title'] = '';
	return true;
}
if (!empty($_GPC['getmenu'])) {
	$home_menu = system_star_menu();
	iajax(0, $home_menu);
}
template('home/home');
