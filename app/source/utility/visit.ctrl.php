<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/app/source/utility/style.ctrl.php : v b53c8ba00893 : 2014/06/16 12:17:57 : RenChao $
 */
defined('IN_IA') or exit('Access Denied');

load()->model('visit');

$dos = array('showjs', 'health');
$do = in_array($do, $dos) ? $do : 'showjs';

if ($do == 'showjs') {
	$module_name = !empty($_GPC['m']) ? safe_gpc_string($_GPC['m']) : 'wesite';
	visit_app_update_today_visit($module_name);
}


// https 站点校验是否能正常访问
if($do == 'health') {
	echo json_encode(error(0, 'success'));
	exit;
}