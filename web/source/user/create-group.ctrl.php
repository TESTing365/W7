<?php
/**
 * 帐号权限组管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('display', 'del', 'post', 'save');
$do = in_array($do, $dos) ? $do : 'display';

$account_group_table = table('users_create_group');
if ('display' == $do) {
	$pageindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$pagesize = 10;
	$group_name = empty($_GPC['group_name']) ? '' : safe_gpc_string($_GPC['group_name']);

	if (!empty($group_name)) {
		$account_group_table->searchLikeGroupName($group_name);
	}
	$account_group_table->searchWithPage($pageindex, $pagesize);
	$lists = $account_group_table->getCreateGroupList();
	$total = $account_group_table->getLastQueryTotal();
	$pager = pagination($total, $pageindex, $pagesize);

	if (user_is_vice_founder()) {
		$table = table('users_founder_own_create_groups');
		$create_groups = $table->getGroupsByFounderUid($_W['uid'], $pageindex, $pagesize);
		$create_groups['pager'] = pagination($create_groups['total'], $pageindex, $pagesize, '', array('ajaxcallback' => true, 'callbackfuncname' => 'changePage'));
		$lists = $create_groups['groups'];
		$total = $create_groups['total'];
		$page = $create_groups['pager'];
	}

	if ($_W['isajax']) {
		$message = array(
			'total' => $total,
			'page' => $pageindex,
			'page_size' => $pagesize,
			'list' => $lists,
		);
		iajax(0, $message);
	}
	template('user/create-group-display');
}

if ('post' == $do) {
	$id = empty($_GPC['id']) ? 0 : intval($_GPC['id']);
	if (!empty($id)) {
		$account_group_info = $account_group_table->getById($id);
	}

	$account_all_type = uni_account_type();
	$account_all_type_sign = array_keys(uni_account_type_sign());
	if ($_W['ispost']) {
		$user_account_group = array(
			'id' => $id,
			'group_name' => safe_gpc_string($_GPC['group_name']),
		);
		$max_type_all = 0;
		foreach ($account_all_type_sign as $account_type) {
			$maxtype = 'max' . $account_type;
			$user_account_group[$maxtype] = intval($_GPC[$maxtype]);
			$max_type_all += intval($_GPC[$maxtype]);
		}

		if ($max_type_all <= 0) {
			if ($_W['isajax']) {
				iajax(-1, '至少能创建一个账号!');
			}
			itoast('至少能创建一个账号!', '', '');
		}

		$res = user_save_create_group($user_account_group);

		if (is_error($res)) {
			if ($_W['isajax']) {
				iajax(-1, $res['message']);
			}
			itoast($res['message'], '', '');
		}
		if ($_W['isajax']) {
			iajax(0, '操作成功!');
		}
		itoast('操作成功!', url('user/create-group/display'), '');
	}

	if ($_W['iajax']) {
		iajax(0, $account_group_info);
	}
	template('user/create-group-post');
}

if ('del' == $do) {
	$id = intval($_GPC['id']);
	$res = $account_group_table->deleteById($id);
	table('users_founder_own_create_groups')->where('create_group_id', $id)->delete();
	$url = url('user/create-group/display');
	$msg = $res ? '成功' : '失败';
	if ($_W['isajax']) {
		iajax(0, '操作' . $msg);
	}
	itoast('操作' . $msg, $url);
}
