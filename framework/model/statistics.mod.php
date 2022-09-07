<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 获取昨日、最近一周、最近一个月内的访问信息数据
 * @param string $type 类型：web:后端;app:手机端;api:微信API;all:包含web、app、api的所有统计信息
 * @param string $time_type 时间类型：today、yesterday、week、month、daterange
 * @param string $module 要统计的模块，为空则默认统计所有模块
 * @param array() $daterange 时间范围
 * @param boolean 是否系统统计
 * @return array()
 */
function stat_visit_info($type, $time_type, $module = '', $daterange = array(), $is_system_stat = false) {
	global $_W;
	$result = array();
	if (empty($type) || empty($time_type) || !empty($type) && !in_array($type, array('web', 'app', 'api', 'all'))) {
		return $result;
	}

	$stat_visit_table = table('stat_visit');

	if ($type != 'all') {
		$stat_visit_table->searchWithType($type);
	}
	if (empty($is_system_stat)) {
		$stat_visit_table->searchWithUnacid($_W['uniacid']);
	}
	if (!empty($module)) {
		$stat_visit_table->searchWithModule($module);
	}
	switch ($time_type) {
		case 'today':
			$stat_visit_table->searchWithDate(date('Ymd'));
			break;
		case 'yesterday':
			$stat_visit_table->searchWithDate(date('Ymd', strtotime('-1 days')));
			break;
		case 'week':
			$stat_visit_table->searchWithGreaterThenDate(date('Ymd', strtotime('-6 days')));
			$stat_visit_table->searchWithLessThenDate(date('Ymd'));
			break;
		case 'month':
			$stat_visit_table->searchWithGreaterThenDate(date('Ymd', strtotime('-29 days')));
			$stat_visit_table->searchWithLessThenDate(date('Ymd'));
			break;
		case 'daterange':
			if (empty($daterange)) {
				return stat_visit_info($type, 'month', $module, array(), $is_system_stat);
			}
			$stat_visit_table->searchWithGreaterThenDate(date('Ymd', strtotime($daterange['start'])));
			$stat_visit_table->searchWithLessThenDate(date('Ymd', strtotime($daterange['end'])));
			break;
	}
	$visit_info = $stat_visit_table->getall();
	if (!empty($visit_info)) {
		$result = $visit_info;
	}
	return $result;
}

/**
 * 根据公众号划分，获取昨日、最近一周、最近一个月内的访问信息数据
 * @param string $time_type 类型：today、yesterday、week、month、daterange
 * @param string $module 要统计的模块，为空则默认统计所有模块
 * @return array()
 */
function stat_visit_app_byuniacid($time_type, $module = '', $daterange = array(), $is_system_stat = false) {
	$result = array();
	$visit_info = stat_visit_info('app', $time_type, $module, $daterange, $is_system_stat);
	if (empty($visit_info)) {
		return $result;
	}
	foreach ($visit_info as $info) {
		if ($is_system_stat) {
			if (empty($info['uniacid'])) {
				continue;
			}
			if ($result[$info['uniacid']]['uniacid'] == $info['uniacid']) {
				$result[$info['uniacid']]['count'] += $info['count'];
				$result[$info['uniacid']]['highest'] = $result[$info['uniacid']]['highest'] >= $info['count'] ? $result[$info['uniacid']]['highest'] : $info['count'];
			} else {
				$result[$info['uniacid']] = $info;
				$result[$info['uniacid']]['highest'] = $info['count'];
			}
		} else {
			if (empty($info['module'])) {
				continue;
			}
			if (!empty($result[$info['module']]) && $result[$info['module']]['module'] == $info['module']) {
				$result[$info['module']]['count'] += $info['count'];
				$result[$info['module']]['highest'] = $result[$info['module']]['highest'] >= $info['count'] ? $result[$info['module']]['highest'] : $info['count'];
			} else {
				$result[$info['module']] = $info;
				$result[$info['module']]['highest'] = $info['count'];
			}
		}
	}
	$modules = stat_modules_except_system();
	$count = count($modules);
	foreach ($result as $key => $val) {
		$result[$key]['avg'] = round($val['count'] / $count);
	}
	return $result;
}

/**
 * 根据日期划分，获取昨日、最近一周、最近一个月内的访问信息数据
 * @param string $time_type 类型：today、yesterday、week、month、daterange
 * @param string $module 要统计的模块，为空则默认统计所有模块
 * @return array()
 */
function stat_visit_app_bydate($time_type, $module = '', $daterange = array(), $is_system_stat = false) {
	$result = array();
	$visit_info = stat_visit_info('app', $time_type, $module, $daterange, $is_system_stat);
	if (empty($visit_info)) {
		return $result;
	}
	$count = stat_account_count();
	foreach ($visit_info as $info) {
		if (empty($info['uniacid']) || empty($info['date'])) {
			continue;
		}
		if (!empty($result[$info['date']]) && $result[$info['date']]['date'] == $info['date']) {
			$result[$info['date']]['count'] += $info['count'];
			$result[$info['date']]['highest'] = $result[$info['date']]['highest'] >= $info['count'] ? $result[$info['date']]['highest'] : $info['count'];
		} else {
			unset($info['module'], $info['uniacid']);
			$result[$info['date']] = $info;
			$result[$info['date']]['highest'] = $info['count'];
		}
	}
	if (empty($result)) {
		return $result;
	}
	foreach ($result as $key => $val) {
		$result[$key]['avg'] = round($val['count'] / $count);
	}
	return $result;
}

/**
 * 获取昨日、最近一周、最近一个月内的所有$type总的访问信息数据
 * @param string $time_type 时间类型：today、yesterday、week、month、daterange
 * @param array() $daterange 时间范围
 * @param boolean 是否系统统计
 * @return array()
 */
function stat_visit_all_bydate($time_type, $daterange = array(), $is_system_stat = false) {
	$result = array();
	$visit_info = stat_visit_info('all', $time_type, '', $daterange, $is_system_stat);
	if (empty($visit_info)) {
		return $result;
	} else {
		foreach ($visit_info as $visit) {
			$result['count'][$visit['date']] = empty($result['count'][$visit['date']]) ? 0 : $result['count'][$visit['date']];
			$result['ip_count'][$visit['date']] = empty($result['ip_count'][$visit['date']]) ? 0 : $result['ip_count'][$visit['date']];
			$result['count'][$visit['date']] += $visit['count'];
			$result['ip_count'][$visit['date']] += $visit['ip_count'];
		}
	}
	return $result;
}

function stat_visit_web_bydate($time_type, $daterange = array(), $is_system_stat = false) {
	$result = array();
	$visit_info = stat_visit_info('web', $time_type, '', $daterange, $is_system_stat);
	if (empty($visit_info)) {
		return $result;
	} else {
		foreach ($visit_info as $visit) {
			$result['count'][$visit['date']] = empty($result['count'][$visit['date']]) ? 0 : $result['count'][$visit['date']];
			$result['ip_count'][$visit['date']] = empty($result['ip_count'][$visit['date']]) ? 0 : $result['ip_count'][$visit['date']];
			$result['count'][$visit['date']] += $visit['count'];
			$result['ip_count'][$visit['date']] += $visit['ip_count'];
		}
	}
	return $result;
}

/**
 * 统计公众号整体访问信息
 * @param string $type 'current_account'当前公众号；'all_account' 所有公众号
 * @data array() 要统计的访问信息
 * @return array()
 */
function stat_all_visit_statistics($type, $data) {
	if ($type == 'current_account') {
		$modules = stat_modules_except_system();
		$count = count($modules);
	} elseif ($type == 'all_account') {
		$count = stat_account_count();
	}
	$result = array(
		'visit_sum' => 0,
		'visit_highest' => 0,
		'visit_avg' => 0
	);
	if (empty($data)) {
		return $result;
	}
	foreach ($data as $val) {
		$result['visit_sum'] += $val['count'];
		if ($result['visit_highest'] < $val['count']) {
			$result['visit_highest'] = $val['count'];
		}

	}
	$result['visit_avg'] = round($result['visit_sum'] / $count);
	return $result;
}

/**
 * 获取当前公号下除系统模块外的所有安装模块及模块信息
 * @return array()
 */
function stat_modules_except_system() {
	$modules = uni_modules();
	if (!empty($modules)) {
		foreach ($modules as $key => $module) {
			if (!empty($module['issystem'])) {
				unset($modules[$key]);
			}
		}
	}
	return $modules;
}

function stat_account_count() {
	$count = 0;
	$account_table = table('account');
	$account_table->searchWithType(array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH));
	$account_table->accountRankOrder();
	$account_list = $account_table->searchAccountList();
	$count = count($account_list);
	return $count;
}

/**
 * 获取日期数组
 * @param string start 开始日期
 * @param string end 结束日期
 * @return array()
 */
function stat_date_range($start, $end) {
	$result = array();
	if (empty($start) || empty($end)) {
		return $result;
	}
	$start = strtotime($start);
	$end = strtotime($end);
	$i = 0;
	while(strtotime(end($result)) < $end) {
		$result[] = date('Ymd', $start + $i * 86400);
		$i++;
	}
	return $result;
}

/**
 * 站点所有会员统计（总量、各类型占比）
 * @return array
 */
function stat_mc_member() {
	$result = array('total' => 0);
	$members = table('mc_members')->searchWithAccount()->select('m.uid, a.type')->getall();
	if (empty($members)) {
		return $result;
	}
	$result['total'] = count($members);
	$account_type = uni_account_type();
	foreach ($members as $member) {
		foreach ($account_type as $type => $type_info) {
			if (!isset($result[$type_info['type_sign']])) {
				$result[$type_info['type_sign']] = 0;
			}
			if ($member['type'] == $type) {
				$result[$type_info['type_sign']] += 1;
			}
		}
	}
	foreach ($result as $key => &$item) {
		if ('total' != $key) {
			$item = round($item/$result['total'] * 100, 2);
		}
	}
	return $result;
}

/**
 * 站点所有模块统计（总量、各类型占比）
 * tips:一个模块同时支持多个类型,按多个模块算（如模块A支持3个类型则按3个模块算）
 * @return array
 */
function stat_module() {
	$module_support_type = module_support_type();
	$result = array('total' => 0, 'account' => 0, 'wxapp' => 0, 'other' => 0);
	$select_fields = array_merge(array('name'), array_keys($module_support_type));
	$modules = table('modules')->select($select_fields)->where('issystem', 0)->getall();
	if (empty($modules)) {
		return $result;
	}
	foreach ($modules as $module) {
		foreach ($module_support_type as $type => $type_info) {
			if ($module[$type] == $type_info['support']) {
				if (in_array($type_info['type'], array_keys($result))) {
					$result[$type_info['type']] += 1;
				}
				if (!in_array($type_info['type'], array_keys($result))) {
					$result['other'] += 1;
				}
				$result['total'] += 1;
			}
		}
	}
	foreach ($result as $key => $item) {
		if ('total' != $key) {
			$result[$key] = round($item/$result['total'] * 100, 2);
		}
	}
	return $result;
}