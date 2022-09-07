<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 更改某条消息提醒状态
 * @param $id
 * @return bool
 */
function message_notice_read($id) {
	$id = intval($id);
	if (empty($id)) {
		return true;
	}
	table('core_message_notice_log')->fillIsRead(MESSAGE_READ)->whereId($id)->save();
	return true;
}

/**
 * 更改全部消息或者某种类型消息为已读状态
 * @return bool
 */
function message_notice_all_read($type = '') {
	global $_W;
	$message_table = table('core_message_notice_log');
	if (!empty($type)) {
		$message_table->whereType($type);
	}
	if ($_W['isadmin']) {
		$message_table->fillIsRead(MESSAGE_READ)->whereIsRead(MESSAGE_NOREAD)->save();
		return true;
	}
	$message_table->fillIsRead(MESSAGE_READ)->whereIsRead(MESSAGE_NOREAD)->whereUid($_W['uid'])->save();
	return true;
}

/**
 * $type 为空返回用户配置项, 否则返回消息数据
 */
function message_setting($uid, $type = 0, $params = array()) {
	global $_W;
	$params['orderid'] = empty($params['orderid']) ? '' : $params['orderid'];
	$params['username'] = empty($params['username']) ? '' : $params['username'];
	$params['goods_name'] = empty($params['goods_name']) ? '' : $params['goods_name'];
	$params['money'] = empty($params['money']) ? 0 : $params['money'];
	$params['uid'] = empty($params['uid']) ? 0 : $params['uid'];
	$params['uuid'] = empty($params['uuid']) ? 0 : $params['uuid'];
	$params['updated_at'] = empty($params['updated_at']) ? '' : $params['updated_at'];
	$params['note'] = empty($params['note']) ? '' : $params['note'];
	$params['status'] = empty($params['status']) ? 2 : $params['status'];
	$params['source'] = empty($params['source']) ? '' : $params['source'];
	$params['module_name'] = empty($params['module_name']) ? '' : $params['module_name'];
	$data = array(
		'expire_message' => array(
			'title' => '到期消息',
			'msg' => '用户公众号，小程序到期，平台类型到期，将会有消息提醒，建议打开',
			'permission' => array(),
			'types' => array(
				MESSAGE_ACCOUNT_EXPIRE_TYPE => array(
					'title' => '公众号到期',
					'msg' => '用户公众号到期后，将会有消息提醒，建议打开',
					'permission' => array(),
					'notice_data' => array(
						'sign' => $params['uniacid'],
						'end_time' => $params['end_time'],
						'message' => sprintf('%s-%s已过期', $params['account_name'], $params['type_name'])
					),
				),
				MESSAGE_WECHAT_EXPIRE_TYPE => array(
					'title' => '小程序到期',
					'msg' => '用户小程序到期后，将会有消息提醒，建议打开',
					'permission' => array(),
					'notice_data' => array(
						'sign' => $params['uniacid'],
						'end_time' => $params['end_time'],
						'message' => sprintf('%s-%s已过期', $params['account_name'], $params['type_name'])
					),
				),
				MESSAGE_WEBAPP_EXPIRE_TYPE => array(
					'title' => 'pc过期',
					'msg' => '用户pc类型到期后，将会有消息提醒，建议打开',
					'permission' => array(),
					'notice_data' => array(
						'sign' => $params['uniacid'],
						'end_time' => $params['end_time'],
						'message' => sprintf('%s-%s已过期', $params['account_name'], $params['type_name'])
					),
				),
				MESSAGE_USER_EXPIRE_TYPE => array(
					'title' => '用户账号到期',
					'msg' => '用户账号到期后，将会有消息提醒，建议打开',
					'permission' => array(),
					'notice_data' => array(
						'sign' => $params['uid'],
						'end_time' => $params['end_time'],
						'message' => sprintf('%s 用户账号即将过期', $params['username'])
					),
				),
			)
		),
		'register_message' => array(
			'title' => '注册提醒',
			'msg' => '用户注册后，将会有消息提醒，建议打开',
			'permission' => array('founder'),
			'types' => array(
				MESSAGE_REGISTER_TYPE => array(
					'title' => '新用户注册',
					'msg' => '新用户注册后，将会有消息提醒，建议打开',
					'permission' => array('founder'),
					'notice_data' => array(
						'sign' => $params['uid'],
						'status' => $params['status'],
						'message' => sprintf('%s-%s %s注册成功--%s', $params['username'], $params['type_name'], date("Y-m-d H:i:s"), $params['source'])
					),
				),
			),
		),
	);
	if (empty($type)) {
		return $data;
	}
	foreach ($data as $item) {
		foreach ($item['types'] as $key => $row) {
			$types[$key] = $row;
		}
	}
	if (!is_numeric($type) || !in_array($type, array_keys($types))) {
		return error(1, '消息类型有误');
	}
	$users_table = table('users');
	$founder_notice_setting = $users_table->getNoticeSettingByUid($_W['config']['setting']['founder']);
	if (!empty($founder_notice_setting[$type]) && $founder_notice_setting[$type] == MESSAGE_DISABLE) {
		return error(2, '创始人未开启提醒');
	}
	if (!user_is_founder($uid, true)) {
		$user_notice_setting = $users_table->getNoticeSettingByUid($uid);
		if (!empty($user_notice_setting[$type]) && $user_notice_setting[$type] == MESSAGE_DISABLE) {
			return error(3, '用户未开启提醒');
		}
	}
	$notice_data = $types[$type]['notice_data'];
	$notice_data['uid'] = $uid;
	$notice_data['type'] = $type;
	$notice_data['url'] = '';
	return $notice_data;
}

/**
 * 消息提醒记录
 * @param array $notice_info  message_setting() 获取到的消息数据
 */
function message_notice_record($uid, $type, $params) {
	$notice_info = message_setting($uid, $type, $params);
	if (is_error($notice_info)) {
		return $notice_info;
	}
	$message_validate_exists = message_validate_exists($notice_info);
	if (!empty($message_validate_exists)) {
		return true;
	}
	$notice_info['create_time'] = empty($notice_info['create_time']) ? TIMESTAMP : $notice_info['create_time'];
	$notice_info['is_read'] = empty($notice_info['is_read']) ? MESSAGE_NOREAD : $notice_info['is_read'];

	table('core_message_notice_log')->fill($notice_info)->save();

	message_send_wechat_notice($notice_info);
	return true;
}

function message_send_wechat_notice($notice_info) {
	global $_W;
	$setting = setting_load('message_wechat_notice_setting');
	$setting = $setting['message_wechat_notice_setting'];
	if (empty($setting['uniacid'])) {
		return error(-1, '未设置公众号');
	}
	$uniaccount = table('account')->getUniAccountByUniacid($setting['uniacid']);
	if (empty($uniaccount)) {
		return error(-1, '帐号不存在或是已经被删除');
	}
	$account_api = WeAccount::createByUniacid($uniaccount['uniacid']);
	if (is_error($account_api)) {
		return $account_api;
	}
	$type_template = array(
		MESSAGE_ORDER_TYPE => 'order',
		MESSAGE_ACCOUNT_EXPIRE_TYPE => 'expire',
		MESSAGE_WECHAT_EXPIRE_TYPE => 'expire',
		MESSAGE_WEBAPP_EXPIRE_TYPE => 'expire',
		MESSAGE_USER_EXPIRE_TYPE => 'expire',
		MESSAGE_REGISTER_TYPE => 'register',
		MESSAGE_SYSTEM_UPGRADE => '',
		MESSAGE_OFFICIAL_DYNAMICS => '',
	);
	if (empty($setting['template'][$type_template[$notice_info['type']]])) {
		return error(-1, '未设置模板ID');
	}
	if ($type_template[$notice_info['type']] == 'expire' && user_is_founder($notice_info['uid'], true)) {
		return error(-1, '主管理员不发送过期消息');
	}
	if ($notice_info['type'] == MESSAGE_REGISTER_TYPE) {
		$notice_info['uid'] = $_W['config']['setting']['founder'];
	}
	$users_bind = table('users_bind')->getByTypeAndUid(USER_REGISTER_TYPE_OPEN_WECHAT, $notice_info['uid']);
	if (empty($users_bind['bind_sign'])) {
		return error(-1, '用户未绑定微信');
	}
	$mc_mapping_fans_table = table('mc_mapping_fans');
	$mc_mapping_fans_table->searchWithUniacid($setting['uniacid']);
	$mc_mapping_fans_table->searchWithUnionid($users_bind['bind_sign']);
	$fans = $mc_mapping_fans_table->get();
	if (empty($fans['openid'])) {
		return error(-1, '用户未关注公众号');
	}
	$msg_data = array();
	switch ($notice_info['type']) {
		case MESSAGE_ACCOUNT_EXPIRE_TYPE:
			$time = empty($notice_info['end_time']) ? TIMESTAMP : $notice_info['end_time'];
			$msg_data = array(
				'first' => array('value' => '您好，您有过期的账号！'),
				'keyword1' => array('value' => $notice_info['message']),
				'keyword2' => array('value' => '公众号'),
				'keyword3' => array('value' => date('Y年m月d日 H:i', $time)),
				'remark' => array('value' => '感谢您的使用！'),
			);
			break;
		case MESSAGE_WECHAT_EXPIRE_TYPE:
			$time = empty($notice_info['end_time']) ? TIMESTAMP : $notice_info['end_time'];
			$msg_data = array(
				'first' => array('value' => '您好，您有过期的账号！'),
				'keyword1' => array('value' => $notice_info['message']),
				'keyword2' => array('value' => '小程序'),
				'keyword3' => array('value' => date('Y年m月d日 H:i', $time)),
				'remark' => array('value' => '感谢您的使用！'),
			);
			break;
		case MESSAGE_WEBAPP_EXPIRE_TYPE:
			$time = empty($notice_info['end_time']) ? TIMESTAMP : $notice_info['end_time'];
			$msg_data = array(
				'first' => array('value' => '您好，您有过期的账号！'),
				'keyword1' => array('value' => $notice_info['message']),
				'keyword2' => array('value' => 'PC'),
				'keyword3' => array('value' => date('Y年m月d日 H:i', $time)),
				'remark' => array('value' => '感谢您的使用！'),
			);
			break;
		case MESSAGE_USER_EXPIRE_TYPE:
			$msg_data = array(
				'first' => array('value' => '您好，您的账号即将过期！'),
				'keyword1' => array('value' => $_W['user']['username']),
				'keyword2' => array('value' => '用户账号'),
				'keyword3' => array('value' => date('Y年m月d日 H:i', $_W['user']['endtime'])),
				'remark' => array('value' => '感谢您的使用！'),
			);
			break;
		case MESSAGE_REGISTER_TYPE:
			$source = substr($notice_info['message'], stripos($notice_info['message'], '--') + 2);
			$source_array = array('mobile' => '手动注册', 'system' => '手动注册', 'qq' => 'QQ 注册', 'wechat' => '微信注册', 'admin' => '管理员添加');
			$user = pdo_get('users', array('uid' => $notice_info['sign']));
			$msg_data = array(
				'first' => array('value' => '您好，有新用户在站点注册！'),
				'keyword1' => array('value' => $user['username']),
				'keyword2' => array('value' => date('Y年m月d日 H:i')),
				'keyword3' => array('value' => $source_array[$source]),
				'remark' => array('value' => '感谢您的使用！'),
			);
			break;
		case MESSAGE_SYSTEM_UPGRADE:
		case MESSAGE_OFFICIAL_DYNAMICS:
			break;
	}
	return $account_api->sendTplNotice($fans['openid'], $setting['template'][$type_template[$notice_info['type']]], $msg_data);
}

/**
 * 检测消息记录是否已经插入数据库
 */
function message_validate_exists($message) {
	$message_exists = table('core_message_notice_log')->messageExists($message);
	if (!empty($message_exists)) {
		return true;
	}
	return false;
}

/**
 * frame  栏目小红点消息提醒获取
 * @return array
 */
function message_event_notice_list() {
	load()->model('user');
	global $_W;
	$message_table = table('core_message_notice_log');
	$message_table->searchWithIsRead(MESSAGE_NOREAD);
	if ($_W['isadmin']) {
		$message_table->searchWithOutType(MESSAGE_USER_EXPIRE_TYPE);
	} else {
		$message_table->searchWithUid($_W['uid']);
		$message_table->searchWithType(array(
			MESSAGE_ACCOUNT_EXPIRE_TYPE,
			MESSAGE_WECHAT_EXPIRE_TYPE,
			MESSAGE_WEBAPP_EXPIRE_TYPE,
			MESSAGE_USER_EXPIRE_TYPE,
			MESSAGE_SYSTEM_UPGRADE,
			MESSAGE_OFFICIAL_DYNAMICS
		));
	}
	$message_table->searchWithPage(1, 10);
	$lists = $message_table->orderby('id', 'DESC')->getall();
	$total = $message_table->getLastQueryTotal();
	$lists = message_list_detail($lists);
	return array(
		'lists' => $lists,
		'total' => $total,
		'more_url' => url('message/notice') . (igetcookie('__iscontroller') ? 'iscontroller=1' : ''),
		'all_read_url' => url('message/notice/all_read') . (igetcookie('__iscontroller') ? 'iscontroller=1' : ''),
	);
}

/**
 * 公众号过期记录
 * @return bool
 */
function message_account_expire() {
	global $_W;
	load()->model('account');
	$account_table = table('account');
	$expire_account_list = $account_table->searchAccountList();
	if (empty($expire_account_list)) {
		return true;
	}
	foreach ($expire_account_list as $account) {
		$account_detail = uni_fetch($account['uniacid']);
		if (empty($account_detail->owner['uid'])) {
			continue;
		}
		if ($account_detail['endtime'] > USER_ENDTIME_GROUP_UNLIMIT_TYPE && $account_detail['endtime'] < TIMESTAMP) {
			switch ($account_detail['type']) {
				case ACCOUNT_TYPE_APP_NORMAL:
					$type = MESSAGE_WECHAT_EXPIRE_TYPE;
					break;
				case ACCOUNT_TYPE_WEBAPP_NORMAL:
					$type = MESSAGE_WEBAPP_EXPIRE_TYPE;
					break;
				default:
					$type = MESSAGE_ACCOUNT_EXPIRE_TYPE;
					break;
			}
			$params = array(
				'uniacid' => $account_detail['uniacid'],
				'end_time' => $account_detail['endtime'],
				'account_name' => $account_detail['name'],
				'type_name' => $account_detail->typeName,
			);
			$result = message_notice_record($account_detail->owner['uid'], $type, $params);
			if (is_error($result) && $result['errno'] == 3) {
				message_notice_record($_W['config']['setting']['founder'], $type, $params);
			}
		}
	}
	return true;
}

/**
 * 用户到期消息提醒
 * @return bool
 */
function message_user_expire_notice() {
	global $_W;
	if (!empty($_W['user']['endtime']) && $_W['user']['endtime'] < strtotime('+7 days')) {
		$params = array(
			'uid' => $_W['user']['uid'],
			'username' => $_W['user']['username'],
			'end_time' => $_W['user']['endtime'],
		);
		$result = message_notice_record($_W['uid'], MESSAGE_USER_EXPIRE_TYPE, $params);
		if (is_error($result) && $result['errno'] == 3) {
			message_notice_record($_W['config']['setting']['founder'], MESSAGE_USER_EXPIRE_TYPE, $params);
		}
	}
	return true;
}

/**
 * 列表详情
 * @param $lists
 * @return mixed
 */
function message_list_detail($lists) {
	if (empty($lists)) {
		return $lists;
	}
	foreach ($lists as &$message) {
		$message['create_time'] = date('Y-m-d H:i:s', $message['create_time']);
		if ($message['type'] != MESSAGE_SYSTEM_UPGRADE && $message['type'] != MESSAGE_OFFICIAL_DYNAMICS) {
			$message['url'] = url('message/notice');
		}
		if ($message['type'] == MESSAGE_REGISTER_TYPE) {
			$source_array = array('mobile' => '手动注册', 'system' => '手动注册', 'qq' => 'QQ 注册', 'wechat' => '微信注册', 'admin' => '管理员添加');
			$msg = explode('--', $message['message']);
			if (count($msg) > 1 && !empty($source_array[$msg[1]])) {
				$message['message'] = $msg[0];
				$message['source'] = $source_array[$msg[1]];
			}
		}
	}

	return $lists;
}
