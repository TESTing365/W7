<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('list', 'execute', 'display');
$do = in_array($do, $dos) ? $do : 'display';

if ($do == 'list') {
	$list = table('job')->getall();
	iajax(0, $list);
}

if ($do == 'execute') {
	$id = intval($_GPC['id']);
	job_execute($id);
}

if ($do == 'display') {
	template('utility/job');
}

