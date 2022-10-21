<?php
/**
 * 公共数据
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

$system_roles = array('founder', 'vice_founder', 'owner', 'manager', 'operator', 'clerk', 'unbind_user', 'expired');
$user_defined_constants = get_defined_constants('true');
$user_defined_constants = $user_defined_constants['user'];
//兼容系统分类筛选
$account_all_type_sign['welcome'] = array(
	'title' => '系统首页',
	'icon' => 'wi wi-branch',
);
$common_info = array(
	'uid' => $_W['uid'],
	'submit_token' => $_W['token'],
	'siteroot' => $_W['setting']['site']['url'],
	'isfounder' => $_W['isfounder'],
	'highest_role' => $_W['highest_role'],
	'system_roles' => $system_roles,
	'links' => array(),
	'uni_account_type' => $account_all_type,
	'uni_account_type_sign' => $account_all_type_sign,
	'permission' => $acl['see_more_info'][$_W['highest_role']],
	'defined_constants' => $user_defined_constants,
	'development' => $_W['config']['setting']['development'],
	'ishttps' => $_W['ishttps'],
	'show_console' => '2.7.56' == IMS_VERSION || 1 == version_compare(IMS_VERSION, '2.7.56') ? STATUS_ON : STATUS_OFF, //控制台是否显示开启关闭云端
	'show_guide' => STATUS_ON, //是否显示未绑定用户登录页绑定引导设置项
);
iajax(0, $common_info);
