<?php
/**
 * 支付参数配置
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('get_setting', 'display', 'save_setting');
$do = in_array($do, $dos) ? $do : 'display';
permission_check_account_user('wxapp_payment_pay');

$setting = uni_setting_load('payment', $_W['uniacid']);
$pay_setting = $setting['payment'];
$wxapp_info = miniapp_fetch($_W['uniacid']);

if ('get_setting' == $do) {
	iajax(0, $pay_setting, '');
}

if ('display' == $do) {
	if (empty($pay_setting)) {
		$pay_setting = array(
			'wechat' => array('mchid' => '', 'signkey' => ''),
			'wechat_facilitator' => array('sub_mch_id' => '', 'service' => ''),
		);
	}
	
}

if ('save_setting' == $do) {
	$type = safe_gpc_string($_GPC['type']);
	$param = safe_gpc_array($_GPC['param']);
	$setting = uni_setting_load('payment', $_W['uniacid']);
	$pay_setting = empty($setting['payment']) ? array() : $setting['payment'];

	
	if ('wechat' == $type) {
		$param['switch'] = !empty($param['switch']) && 'true' == $param['switch'] ? true : false;
		$param['account'] = $_W['acid'];
		if ((true == $param['switch']) && isset($pay_setting['wechat_facilitator']['switch'])) {
			$pay_setting['wechat_facilitator']['switch'] = false;
		}
	}

	$pay_setting[$type] = $param;
	$payment = iserializer($pay_setting);
	uni_setting_save('payment', $payment);
	iajax(0, '设置成功', url('wxapp/payment', array('version_id' => $version_id)));
}
template('wxapp/payment');
