<?php
/**
 * 云服务相关
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
if ('process' == $action) {
	define('FRAME', '');
} else {
	!defined('FRAME') && define('FRAME', 'site');
}

if ('touch' == $action) {
	exit('success');
}
