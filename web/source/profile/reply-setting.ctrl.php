<?php
/**
 * 回复设置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('display', 'post');
$do = in_array($do, $dos) ? $do : 'display';
permission_check_account_user('platform_reply_setting');

if ('display' == $do) {
	$times = empty($_W['account']['setting']) ? 0 : intval($_W['account']['setting']['reply_setting']);
	template('profile/reply-setting');
}

if ('post' == $do) {
	if ($_W['isajax'] && $_W['ispost']) {
		$new_times = intval($_GPC['times']);
		if ($new_times > 50 || $new_times < 0) {
			itoast('次数超过限制，请重新设置！');
		}
		uni_setting_save('reply_setting', $new_times);
		iajax(0, '保存成功！', referer());
	}
}
