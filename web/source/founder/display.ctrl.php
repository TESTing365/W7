<?php
/**
 * 用户管理
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');

$dos = array('display', 'del');
$do = in_array($do, $dos) ? $do: 'display';

$_W['page']['title'] = '用户列表 - 用户管理';
$founders = explode(',', $_W['config']['setting']['founder']);

if ($do == 'display') {
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$users_table = table('users');
	$users_table->searchWithFounder(ACCOUNT_MANAGE_GROUP_VICE_FOUNDER);

	$username = trim($_GPC['username']);
	if (!empty($username)) {
		$users_table->searchWithName($username);
	}

	$users_table->searchWithPage($pindex, $psize);
	$users = $users_table->searchUsersList();
	$total = $users_table->getLastQueryTotal();
	$users = user_list_format($users);
	$pager = pagination($total, $pindex, $psize);
	template('founder/display');
}

if ($do == 'del') {
	if (!$_W['isajax'] || !$_W['ispost']) {
		iajax(-1, '非法操作！', url('founder/display'));
	}
	$uid = intval($_GPC['uid']);
	$uid_user = user_single($uid);
	if (in_array($uid, $founders)) {
		iajax(0,'访问错误, 无法操作站长.', url('founder/display'));
	}
	if (empty($uid_user)) {
		iajax(0,'未指定用户,无法删除.', url('founder/display'));
	}
	if ($uid_user['founder_groupid'] != ACCOUNT_MANAGE_GROUP_VICE_FOUNDER) {
		iajax(0,'非法操作！', url('founder/display'));
	}
	user_delete($uid);
	iajax(0,'删除成功！', referer());
}