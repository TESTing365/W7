<?php
/**
 * 小程序入口页面
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');

$dos = array('entrance_link');
$do = in_array($do, $dos) ? $do : 'entrance_link';

permission_check_account_user('wxapp_entrance_link');

$wxapp_info = miniapp_fetch($_W['uniacid']);

if ('entrance_link' == $do) {
	$module_info = array();

	if (!empty($version_info['type']) && in_array($version_info['type'], array(WXAPP_CREATE_MODULE, WXAPP_CREATE_MUTI_MODULE))) {
		$module_info = array(array('eid' => '1', 'do' => 'wxapp_web/pages/view/index', 'title' => '入口'));
	} else {
		if (!empty($version_info['modules'])) {
			$module_info = table('modules_bindings')
				->where(array(
					'module' => array_keys($version_info['modules']),
					'entry' => 'page'
				))
				->getall();
		}
	}
	template('wxapp/version-entrance');
}
