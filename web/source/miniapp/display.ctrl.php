<?php
/**
 * 小程序列表
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->model('miniapp');

$dos = array('version_display');
$do = in_array($do, $dos) ? $do : 'version_display';

if ($do == 'version_display') {
	$version_list = miniapp_version_all($_W['uniacid']);
	template('miniapp/version-display');
}