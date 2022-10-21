<?php

/**
 * 查看日志
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('wechat', 'system', 'database', 'attachment', 'login');
$do = in_array($do, $dos) ? $do : 'wechat';

$params = array();
$where = '';
$order = ' ORDER BY `id` DESC';
if (!empty($_GPC['time']) && !empty($_GPC['time']['start'])) {
	//获取日期范围
	$starttime = strtotime($_GPC['time']['start']);
	$endtime = strtotime($_GPC['time']['end']);
	$timewhere = ' `createtime` >= :starttime AND `createtime` < :endtime';
	$params[':starttime'] = $starttime;
	$params[':endtime'] = $endtime + 86400;
}

//微信日志
if ('wechat' == $do) {
	$path = IA_ROOT . '/data/logs/';
	$files = glob($path . '*');
	if (!empty($_GPC['searchtime'])) {
		$searchtime = safe_gpc_string($_GPC['searchtime']) . '.php';
	} else {
		$searchtime = date('Ymd', time()) . '.php';
	}
	$tree = array();
	foreach ($files as $key => $file) {
		if (!preg_match('/\/[0-9]+\.php/', $file)) {
			continue;
		}
		$pathinfo = pathinfo($file);
		array_unshift($tree, $pathinfo['filename']);
		if (strexists($file, $searchtime)) {
			$contents = file_get_contents($file);
		}
	}
	if ($_W['isajax']) {
		$message = array(
			'tree' => $tree,
			'content' => empty($contents) ? '' : $contents
		);
		iajax(0, $message);
	}
}

//系统日志
if ('system' == $do) {
	$pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$psize = 10;
	$where .= " WHERE `type` = '1'";
	$timewhere = empty($timewhere) ? '' : ' AND ' . $timewhere;
	$sql = 'SELECT * FROM ' . tablename('core_performance') . " $where $timewhere $order LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql, $params);
	foreach ($list as $key => $value) {
		$list[$key]['type'] = '系统日志';
		$list[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
	}
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('core_performance') . $where . $timewhere, $params);
	$pager = pagination($total, $pindex, $psize);
	if ($_W['isajax']) {
		$message = array(
			'list' => $list,
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize,
		);
		iajax(0, $message);
	}
}

//数据库日志
if ('database' == $do) {
	$pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$psize = 10;
	$where .= " WHERE `type` = '2'";
	$timewhere = empty($timewhere) ? '' : ' AND ' . $timewhere;
	$sql = 'SELECT * FROM ' . tablename('core_performance') . " $where $timewhere $order LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql, $params);
	foreach ($list as $key => $value) {
		$list[$key]['type'] = '数据库日志';
		$list[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
	}
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('core_performance') . $where . $timewhere, $params);
	$pager = pagination($total, $pindex, $psize);
	if ($_W['isajax']) {
		$message = array(
			'list' => $list,
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize,
		);
		iajax(0, $message);
	}
}

if ('attachment' == $do) {
	$where = array(
		'a.uid <>' => 0,
	);
	if (!empty($starttime)) {
		$where['a.createtime >='] = $starttime;
		$where['a.createtime <'] = $endtime + 86400;
	}
	if (!empty($_GPC['keyword'])) {
		$where['c.name LIKE'] = '%' . safe_gpc_string($_GPC['keyword']) . '%';
	}
	if (!empty($_GPC['keyword'])) {
		$where['c.name LIKE'] = '%' . safe_gpc_string($_GPC['keyword']) . '%';
	}
	$pindex = empty($_GPC['page']) ? 1 : safe_gpc_int($_GPC['page']);
	$psize = 20;
	$core_attachment_table = table('core_attachment');
	$core_list = $core_attachment_table
		->SearchWithUserAndUniAccount()
		->select('a.uniacid, a.uid, a.filename, a.createtime, b.username, c.name, a.type')
		->orderby(array(
			'a.createtime' => 'DESC',
			'a.displayorder' => 'DESC'
		))
		->where($where)
		->getall();
	$wechat_attachment_table = table('wechat_attachment');
	$wechat_list = $wechat_attachment_table
		->SearchWithUserAndUniAccount()
		->select('a.uniacid, a.uid, a.filename, a.createtime, b.username, c.name, a.type')
		->orderby(array(
			'a.createtime' => 'DESC'
		))
		->where($where)
		->getall();
	$list = array_merge($core_list, $wechat_list);
	$last_names = array_column($list, 'createtime');
	array_multisort($last_names,SORT_DESC, $list);
	$total = $core_attachment_table->getLastQueryTotal();
	$total += $wechat_attachment_table->getLastQueryTotal();
	$list =  array_slice($list, ($pindex - 1) * $psize, $psize);
	$pager = pagination($total, $pindex, $psize);
	if ($_W['isajax']) {
		$message = array(
			'list' => $list,
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize
		);
		iajax(0, $message);
	}
}

//用户登录日志
if ('login' == $do) {
	$timewhere = empty($timewhere) ? '' : ' WHERE ' . $timewhere;
	if (!empty($_GPC['username'])) {
		$username = safe_gpc_string($_GPC['username']);
		if (empty($timewhere)) {
			$timewhere = ' WHERE u.`username` LIKE :username ';
		} else {
			$timewhere = $timewhere . ' AND u.`username` LIKE :username ';
		}
		$params[':username'] = "%{$username}%";
	}
	$pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$psize = 10;
	$sql = 'SELECT l.ip,l.createtime,u.username FROM ' . tablename('users_login_logs') . ' as l LEFT JOIN ' . tablename('users') . ' as u ON l.uid = u.uid ' . $timewhere . $order . ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
	$list = pdo_fetchall($sql, $params);
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('users_login_logs') . ' as l LEFT JOIN ' . tablename('users') . ' as u ON l.uid = u.uid ' . $timewhere, $params);
	$pager = pagination($total, $pindex, $psize);

	if ($_W['isajax']) {
		$message = array(
			'list' => $list,
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize,
		);
		iajax(0, $message);
	}
}

template('system/logs');
