<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/app/source/utility/preview.ctrl.php : v 5de61aa922c2 : 2015/06/26 01:09:28 : RenChao $
 */
defined('IN_IA') or exit('Access Denied');
$dos = array('home');
$do = in_array($do, $dos) ? $do : exit('Access Denied');

if ($do == 'home') {
	$multiid = intval($_GPC['multiid']);
	$multi = table('site_multi')
		->select('styleid')
		->where(array('id' => $multiid))
		->get();
	$style = table('site_styles')
		->searchWithTemplates(array('a.*', 'b.name AS tname', 'b.title'))
		->where(array(
			'a.uniacid' => $_W['uniacid'],
			'a.id' => $multi['styleid']
		))
		->get();
	template("../{$style['tname']}/home/home");
}
