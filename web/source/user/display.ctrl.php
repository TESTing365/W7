<?php
/**
 * 用户管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');
load()->model('message');

$dos = array('display', 'operate');
$do = in_array($do, $dos) ? $do : 'display';

if ('display' == $do) {
	$message_id = empty($_GPC['message_id']) ? 0 : intval($_GPC['message_id']);
	message_notice_read($message_id);

	$user_groups = user_group();
	$pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$psize = 20;
	$users_table = table('users');
	$expire = empty($_GPC['expire']) ? 0 : intval($_GPC['expire']);
	$users_table->searchWithTimelimitStatus($expire);
	if (!empty($_GPC['user_type'])) {
		$user_type = USER_TYPE_COMMON == intval($_GPC['user_type']) ? USER_TYPE_COMMON : USER_TYPE_CLERK;
		if (USER_TYPE_CLERK == $user_type) {
			$users_table->searchWithType(USER_TYPE_CLERK);
		} else {
			$users_table->searchWithType(USER_TYPE_COMMON);
		}
	}

	$type = empty($_GPC['type']) ? 'display' : safe_gpc_string($_GPC['type']);
	if (in_array($type, array('display', 'check', 'recycle'))) {
		switch ($type) {
			case 'check':
				$status = USER_STATUS_CHECK;
				$users_table->searchWithStatus($status);
				$users_table->userOrderBy('joindate', 'DESC');
				break;
			case 'recycle':
				$status = USER_STATUS_BAN;
				$users_table->searchWithStatus($status);
				break;
			default:
				$status = USER_STATUS_NORMAL;
				$users_table->searchWithStatus($status);
				$users_table->searchWithFounder(array(ACCOUNT_MANAGE_GROUP_GENERAL, ACCOUNT_MANAGE_GROUP_FOUNDER));
				break;
		}

		$search = empty($_GPC['search']) ? '' : safe_gpc_string($_GPC['search']);
		if (!empty($search)) {
			$sql = 'SELECT up.uid FROM ' . tablename('users_profile') . ' AS up LEFT JOIN ' . tablename('users') . ' AS u ON up.uid = u.uid WHERE concat(up.nickname, up.mobile) LIKE :search AND u.status = :status';
			$params = array(':search' => '%' . trim($search) . '%', ':status' => $status);
			$search_uids = pdo_fetchall($sql, $params);
			$users_table->searchWithNameOrMobile($search, false, is_array($search_uids) ? array_column($search_uids, 'uid') : array());
		}

		$group_id = empty($_GPC['groupid']) ? 0 : intval($_GPC['groupid']);
		if (!empty($group_id)) {
			$users_table->searchWithGroupId($group_id);
		}

		

		$users_table->searchWithoutFounder();
		$users_table->searchWithPage($pindex, $psize);
		$users = $users_table->getUsersList(false);
		$total = $users_table->getLastQueryTotal();
		if (!empty($users)) {
			foreach ($users as $user_key => $user) {
				if ($user['type'] == 1) {
					$users[$user_key]['typename'] = '普通用户';
				}
				if ($user['type'] == 3) {
					$users[$user_key]['typename'] = '应用操作员';
				}
			}
			$profiles = table('users_profile')->searchWithUid(array_keys($users))->getAll('uid');
			foreach ($profiles as $profile) {
				$users[$profile['uid']]['avatar'] = $profile['avatar'];
			}
		}
		$users = user_list_format($users, false);
		$users = array_values($users);
		$pager = pagination($total, $pindex, $psize);
	}
	if ($_W['isajax']) {
		iajax(0, array(
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize,
			'list' => $users,
		));
	}
	template('user/display');
}

if ('operate' == $do) {
	$unbind_console = array();
	$type = safe_gpc_string($_GPC['type']);
	$types = array('recycle', 'recycle_delete', 'recycle_restore', 'check_pass');
	if (!in_array($type, $types)) {
		iajax(-1, '类型错误!', referer());
	}
	$uid = empty($_GPC['uid']) ? 0 : safe_gpc_int($_GPC['uid']);
	if (!empty($uid)) {
		$uids = array($uid);
	} else {
		$uids = safe_gpc_array($_GPC['uids']);
	}
	

	if (isset($founder_own_uids) && empty($founder_own_uids)) {
		iajax(-1, '非法操作');
	}

	foreach ($uids as $uid) {
		
		if (user_is_founder($uid, true)) {
			iajax(-1, '访问错误, 无法操作站长.', url('user/display'));
		}
		$uid_user = user_single($uid);
		if (empty($uid_user)) {
			exit('未指定用户,无法删除.');
		}
		
		if ('recycle_delete' == $type && !empty($uid_user['console'])) {
			$unbind_console[$uid_user['uid']] = is_numeric($uid_user['console']) ? '' : $uid_user['console'];
		}
	}
	switch ($type) {
		case 'check_pass':
			$data = array('status' => USER_STATUS_NORMAL);
			foreach ($uids as $uid) {
				pdo_update('users', $data, array('uid' => $uid));
			}
			iajax(0, '更新成功', referer());
			break;
		case 'recycle'://删除用户到回收站
			foreach ($uids as $uid) {
				user_delete($uid, true);
			}
			iajax(0, '禁用成功', referer());
			break;
		case 'recycle_delete'://永久删除用户
			foreach ($uids as $uid) {
				user_delete($uid);
			}
			iajax(0, '删除成功', referer());
			break;
		case 'recycle_restore':
			$data = array('status' => USER_STATUS_NORMAL);
			foreach ($uids as $uid) {
				pdo_update('users', $data, array('uid' => $uid));
			}
			iajax(0, '启用成功', referer());
			break;
	}
}
