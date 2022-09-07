<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/app/source/utility/style.ctrl.php : v b53c8ba00893 : 2014/06/16 12:17:57 : RenChao $
 */
defined('IN_IA') or exit('Access Denied');
header('content-type: text/css');
$src = '';
if(!empty($_W['styles']['imgdir'])) {
	$src = $_W['styles']['imgdir'];
}