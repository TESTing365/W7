<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('visit');
$dos = array('showjs');
$do = in_array($do, $dos) ? $do : 'showjs';

if ('showjs' == $do) {
	$type = 'web';
	$module_name = '';
	if ('account' == safe_gpc_string($_GPC['type'])) {
		$module_name = 'we7_account';
	} elseif ('webapp' == safe_gpc_string($_GPC['type'])) {
		$module_name = 'we7_webapp';
	}
	visit_update_today($type, $module_name);
}

echo '';
exit;
