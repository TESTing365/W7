<?php
/**
 * 炮灰域名管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
$dos = array('display', 'detail', 'post', 'delete', 'bind_list', 'bind', 'unbind');
$do = in_array($do, $dos) ? $do : 'display';

if ('display' == $do) {
	$psize = 24;
	$keyword = empty($_GPC['keyword']) ? '' : safe_gpc_string($_GPC['keyword']);
	$pindex = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
	$where = array();
	if (!empty($keyword)) {
		$where['domain LIKE'] = "%{$keyword}%";
	}
	$cannon_fodder_list = pdo_getall('cannon_fodder', $where, '', '', array('id DESC'), ($pindex - 1) * $psize . ',' . $psize);
	$cannon_fodder_count = pdo_getall('cannon_fodder', $where, array('id'));

	iajax(0, array(
		'total' => count($cannon_fodder_count),
		'page' => $pindex,
		'page_size' => $psize,
		'list' => $cannon_fodder_list,
	));
}

if ('detail' == $do) {
	$id = safe_gpc_int($_GPC['id']);
	if (empty($id)) {
		iajax(-1, '域名不存在！');
	}
	$domain = pdo_get('cannon_fodder', array('id' => $id));
	if (empty($domain)) {
		iajax(-1, '域名不存在！');
	}

	iajax(0, $domain);
}

if ('post' == $do) {
	$id = empty($_GPC['id']) ? 0 : safe_gpc_int($_GPC['id']);
	$domains = safe_gpc_string($_GPC['domains']);
	if (empty($domains)) {
		iajax(-1, '请输入域名！');
	}
	$domains_data = explode("\n", $domains);
	if (count($domains_data) != count(array_unique($domains_data))) {
		iajax(-1, '请勿添加重复的域名！');
	}
	foreach ($domains_data as &$domain) {
		$domain = safe_gpc_url($domain, false);
		if (!starts_with($domain, 'http')) {
			iajax(-1, '域名请以http://或以https://开头！');
		}
		if ('/' == substr($domain, -1)) {
			iajax(-1, '域名结尾不可以加"/"！');
		}
		$domain_exits = pdo_get('cannon_fodder', array('domain' => $domain));
		if (!empty($domain_exits)) {
			iajax(-1, $domain . ' 域名已存在！');
		}
	}
	unset($domain);
	foreach ($domains_data as $domain) {
		if (empty($id)) {
			pdo_insert('cannon_fodder', array('domain' => $domain));
		} else {
			pdo_update('cannon_fodder', array('domain' => $domain), array('id' => $id));
		}
	}

	iajax(0, '域名编辑成功！');
}

if ('delete' == $do) {
	$id = safe_gpc_int($_GPC['id']);
	if (empty($id)) {
		iajax(-1, '域名不存在！');
	}
	$domain = pdo_get('cannon_fodder', array('id' => $id));
	if (empty($domain)) {
		iajax(-1, '域名不存在！');
	}
	pdo_delete('cannon_fodder', array('id' => $id));
	pdo_update('users', array('bind_domain_id' => 0), array('bind_domain_id' => $id));

	iajax(0, '删除成功！');
}

if ('bind_list' == $do) {
	$psize = 24;
	$keyword = empty($_GPC['keyword']) ? '' : safe_gpc_string($_GPC['keyword']);
	$pindex = empty($_GPC['page']) ? 1 : max(1, intval($_GPC['page']));
	$cannon_fodder_id = safe_gpc_int($_GPC['id']);
	if (empty($cannon_fodder_id)) {
		iajax(-1, '域名不存在！');
	}
	$where = array('bind_domain_id' => $cannon_fodder_id);
	if (!empty($keyword)) {
		$where['username LIKE'] = "%{$keyword}%";
	}
	$bind_list = pdo_getall('users', $where, array('uid', 'username'), '', array('uid DESC'), ($pindex - 1) * $psize . ',' . $psize);
	$bind_count = pdo_getall('users', $where, array('uid'));

	iajax(0, array(
		'total' => count($bind_count),
		'page' => $pindex,
		'page_size' => $psize,
		'list' => $bind_list,
	));
}

if (in_array($do, array('bind', 'unbind'))) {
	$cannon_fodder_id = safe_gpc_int($_GPC['id']);
	$username = safe_gpc_string($_GPC['username']);
	if (empty($username) || empty($cannon_fodder_id)) {
		iajax(-1, '账号不存在！');
	}
	$domain = pdo_get('cannon_fodder', array('id' => $cannon_fodder_id));
	if (empty($domain)) {
		iajax(-1, '域名不存在！');
	}
	$user = user_single(array('username' => $username));
	if (empty($user)) {
		iajax(-1, '账号不存在！');
	}
}

if ('bind' == $do) {
	if (!empty($user['bind_domain_id'])) {
		$bind_domain = pdo_get('cannon_fodder', array('id' => $user['bind_domain_id']));
		iajax(-1, '账号 ' . $username . ' 已绑定 ' . $bind_domain['domain'] . ' 域名，请删除后再试！');
	}
	if (2 != $user['status']) {
		iajax(-1, '账号未通过审核或不存在！');
	}
	pdo_update('users', array('bind_domain_id' => $cannon_fodder_id), array('uid' => $user['uid']));

	iajax(0, '添加成功！');
}

if ('unbind' == $do) {
	pdo_update('users', array('bind_domain_id' => 0), array('uid' => $user['uid']));

	iajax(0, '删除成功！');
}