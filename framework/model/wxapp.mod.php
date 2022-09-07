<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function wxapp_getpackage($data, $if_single = false) {
	load()->classs('cloudapi');

	$api = new CloudApi();
	$result = $api->post('wxapp', 'download', $data, 'html');
	if (is_error($result)) {
		return error(-1, $result['message']);
	} else {
		if (strpos($result, 'error:') === 0) {
			return error(-1, substr($result, 6));
		}
	}

	return $result;
}

function wxapp_account_create($account) {
	global $_W;
	load()->model('account');
	load()->model('user');
	load()->model('permission');
	$uni_account_data = array(
		'name' => $account['name'],
		'description' => $account['description'],
		'title_initial' => get_first_pinyin($account['name']),
		'groupid' => 0,
	);
	if (!pdo_insert('uni_account', $uni_account_data)) {
		return error(1, '添加公众号失败');
	}
	$uniacid = pdo_insertid();
	$account_data = array(
		'uniacid' => $uniacid,
		'type' => $account['type'],
		'hash' => random(8),
	);
	pdo_insert('account', $account_data);
	$acid = pdo_insertid();

	$wxapp_data = array(
		'acid' => $acid,
		'token' => isset($account['token']) ? $account['token'] : random(32),
		'encodingaeskey' => isset($account['encodingaeskey']) ? $account['encodingaeskey'] : random(43),
		'uniacid' => $uniacid,
		'name' => $account['name'],
		'original' => $account['original'],
		'level' => $account['level'],
		'key' => $account['key'],
		'secret' => $account['secret'],
//		'auth_refresh_token' => isset($account['auth_refresh_token']) ? $account['auth_refresh_token'] : ''
	);
	pdo_insert('account_wxapp', $wxapp_data);

	if (empty($_W['isfounder'])) {
		$user_info = permission_user_account_num($_W['uid']);
		uni_user_account_role($uniacid, $_W['uid'], ACCOUNT_MANAGE_NAME_OWNER);
		if (empty($user_info['usergroup_wxapp_limit'])) {
			pdo_update('account', array('endtime' => strtotime('+1 month', time())), array('uniacid' => $uniacid));
			pdo_insert('site_store_create_account', array('endtime' => strtotime('+1 month', time()), 'uid' => $_W['uid'], 'uniacid' => $uniacid, 'type' => ACCOUNT_TYPE_APP_NORMAL));
		}
	}
	if (user_is_vice_founder()) {
		uni_user_account_role($uniacid, $_W['uid'], ACCOUNT_MANAGE_NAME_VICE_FOUNDER);
	}
	if (!empty($_W['user']['owner_uid'])) {
		uni_user_account_role($uniacid, $_W['user']['owner_uid'], ACCOUNT_MANAGE_NAME_VICE_FOUNDER);
	}
	pdo_update('uni_account', array('default_acid' => $acid), array('uniacid' => $uniacid));

	return $uniacid;
}

/**
 * 获取所有支持小程序的模块.
 */
function wxapp_support_wxapp_modules() {
	global $_W;
	load()->model('user');
	$modules = user_modules($_W['uid']);
	$wxapp_modules = array();
	if (!empty($modules)) {
		foreach ($modules as $module) {
			if ($module['wxapp_support'] == MODULE_SUPPORT_WXAPP) {
				$wxapp_modules[$module['name']] = $module;
			}
		}
	}
	$store_table = table('store');
	$store_table->searchWithEndtime();
	$buy_wxapp_modules = $store_table->searchAccountBuyGoods($_W['uniacid'], STORE_TYPE_WXAPP_MODULE);
	$extra_permission = table('account')->getAccountExtraPermission($_W['uniacid']);
	$extra_modules = empty($extra_permission['modules']) ? array() : $extra_permission['modules'];
	foreach ($extra_modules as $key => $value) {
		$extra_modules[$value] = module_fetch($value);
		unset($extra_modules[$key]);
	}
	$wxapp_modules = array_merge($buy_wxapp_modules, $wxapp_modules, $extra_modules);
	if (empty($wxapp_modules)) {
		return array();
	}
	$bindings = pdo_getall('modules_bindings', array('module' => array_keys($wxapp_modules), 'entry' => 'page'));
	if (!empty($bindings)) {
		foreach ($bindings as $bind) {
			$wxapp_modules[$bind['module']]['bindings'][] = array('title' => $bind['title'], 'do' => $bind['do']);
		}
	}

	return $wxapp_modules;
}

/**
 * 获取当前公众号支持小程序的模块.
 *
 * @return array
 */
function wxapp_support_uniacid_modules($uniacid) {
	$uni_modules = uni_modules_by_uniacid($uniacid);
	$wxapp_modules = array();
	if (!empty($uni_modules)) {
		foreach ($uni_modules as $module_name => $module_info) {
			if ($module_info['wxapp_support'] == MODULE_SUPPORT_WXAPP) {
				$wxapp_modules[$module_name] = $module_info;
			}
		}
	}

	return $wxapp_modules;
}

/*
 * 获取小程序信息(包括上一次使用版本的版本信息，若从未使用过任何版本则取最新版本信息)
 * @params int $uniacid
 * @params int $versionid 不包含版本ID，默认获取上一次使用的版本，若从未使用过则取最新版本信息
 * @return array
*/
function wxapp_fetch($uniacid, $version_id = '') {
	global $_GPC;
	load()->model('extension');
	$wxapp_info = array();
	$uniacid = intval($uniacid);
	if (empty($uniacid)) {
		return $wxapp_info;
	}
	if (!empty($version_id)) {
		$version_id = intval($version_id);
	}

	$wxapp_info = pdo_get('account_wxapp', array('uniacid' => $uniacid));
	if (empty($wxapp_info)) {
		return $wxapp_info;
	}

	if (empty($version_id)) {
		$wxapp_cookie_uniacids = array();
		if (!empty($_GPC['__wxappversionids'])) {
			$wxappversionids = json_decode(htmlspecialchars_decode($_GPC['__wxappversionids']), true);
			foreach ($wxappversionids as $version_val) {
				$wxapp_cookie_uniacids[] = $version_val['uniacid'];
			}
		}
		if (in_array($uniacid, $wxapp_cookie_uniacids)) {
			$wxapp_version_info = wxapp_version($wxappversionids[$uniacid]['version_id']);
		}

		if (empty($wxapp_version_info)) {
			$sql = 'SELECT * FROM ' . tablename('wxapp_versions') . ' WHERE `uniacid`=:uniacid ORDER BY `id` DESC';
			$wxapp_version_info = pdo_fetch($sql, array(':uniacid' => $uniacid));
		}
	} else {
		$wxapp_version_info = pdo_get('wxapp_versions', array('id' => $version_id));
	}
	if (!empty($wxapp_version_info) && !empty($wxapp_version_info['modules'])) {

		$wxapp_version_info['modules'] = iunserializer($wxapp_version_info['modules']);
		//如果是单模块版并且本地模块，应该是开发者开发小程序，则模块版本号本地最新的。
		if ($wxapp_version_info['design_method'] == WXAPP_MODULE) {
			$module = current($wxapp_version_info['modules']);
			$manifest = ext_module_manifest($module['name']);
			if (!empty($manifest)) {
				$wxapp_version_info['modules'][$module['name']]['version'] = $manifest['application']['version'];
			} else {
				$last_install_module = module_fetch($module['name']);
				$wxapp_version_info['modules'][$module['name']]['version'] = $last_install_module['version'];
			}
		}
	}
	$wxapp_info['version'] = $wxapp_version_info;
	$wxapp_info['version_num'] = explode('.', $wxapp_version_info['version']);

	return  $wxapp_info;
}
/*
 * 获取小程序所有版本
 * @params int $uniacid
 * @return array
*/
function wxapp_version_all($uniacid) {
	load()->model('module');
	$wxapp_versions = array();
	$uniacid = intval($uniacid);

	if (empty($uniacid)) {
		return $wxapp_versions;
	}

	$wxapp_versions = pdo_getall('wxapp_versions', array('uniacid' => $uniacid), array('id'), '', array('id DESC'));
	if (!empty($wxapp_versions)) {
		foreach ($wxapp_versions as &$version) {
			$version = wxapp_version($version['id']);
		}
	}

	return $wxapp_versions;
}

/**
 * 获取某一小程序最新四个版本信息，并标记出来最后使用的版本.
 *
 * @param int $uniacid
 * @param int $page
 * @param int $pagesize
 *					  return array
 */
function wxapp_get_some_lastversions($uniacid) {
	$version_lasts = array();
	$uniacid = intval($uniacid);

	if (empty($uniacid)) {
		return $version_lasts;
	}
	$version_lasts = table('wxapp')->latestVersion($uniacid);
	$last_switch_version = wxapp_last_switch_version();
	if (!empty($last_switch_version[$uniacid]) && !empty($version_lasts[$last_switch_version[$uniacid]['version_id']])) {
		$version_lasts[$last_switch_version[$uniacid]['version_id']]['current'] = true;
	} else {
		reset($version_lasts);
		$firstkey = key($version_lasts);
		$version_lasts[$firstkey]['current'] = true;
	}

	return $version_lasts;
}

/**
 * 更新最新使用版本.
 *
 * @param int $version_id
 *						return boolean
 */
function wxapp_update_last_use_version($uniacid, $version_id) {
	global $_GPC;
	$uniacid = intval($uniacid);
	$version_id = intval($version_id);
	if (empty($uniacid) || empty($version_id)) {
		return false;
	}
	$cookie_val = array();
	if (!empty($_GPC['__wxappversionids'])) {
		$wxapp_uniacids = array();
		$cookie_val = json_decode(htmlspecialchars_decode($_GPC['__wxappversionids']), true);
		if (!empty($cookie_val)) {
			foreach ($cookie_val as &$version) {
				$wxapp_uniacids[] = $version['uniacid'];
				if ($version['uniacid'] == $uniacid) {
					$version['version_id'] = $version_id;
					$wxapp_uniacids = array();
					break;
				}
			}
			unset($version);
		}
		if (!empty($wxapp_uniacids) && !in_array($uniacid, $wxapp_uniacids)) {
			$cookie_val[$uniacid] = array('uniacid' => $uniacid, 'version_id' => $version_id);
		}
	} else {
		$cookie_val = array(
				$uniacid => array('uniacid' => $uniacid, 'version_id' => $version_id),
			);
	}
	isetcookie('__uniacid', $uniacid, 7 * 86400);
	isetcookie('__wxappversionids', json_encode($cookie_val), 7 * 86400);

	return true;
}

/**
 * 获取小程序单个版本.
 *
 * @param int $version_id
 */
function wxapp_version($version_id) {
	$version_info = array();
	$version_id = intval($version_id);

	if (empty($version_id)) {
		return $version_info;
	}

	$cachekey = cache_system_key('wxapp_version', array('version_id' => $version_id));
	$cache = cache_load($cachekey);
	if (!empty($cache)) {
		return $cache;
	}

	$version_info = pdo_get('wxapp_versions', array('id' => $version_id));
	$version_info = wxapp_version_detail_info($version_info);
	cache_write($cachekey, $version_info);

	return $version_info;
}

/**
 * 根据版本号获取当前小程序版本信息.
 *
 * @param mixed $version
 *
 * @return array()
 */
function wxapp_version_by_version($version) {
	global $_W;
	$version_info = array();
	$version = trim($version);
	if (empty($version)) {
		return $version_info;
	}
	$version_info = pdo_get('wxapp_versions', array('uniacid' => $_W['uniacid'], 'version' => $version));
	$version_info = wxapp_version_detail_info($version_info);

	return $version_info;
}

function wxapp_version_detail_info($version_info) {
	global $_W;
	if (empty($version_info) || empty($version_info['uniacid'])) {
		return array();
	}
	$uni_modules = uni_modules_by_uniacid($version_info['uniacid']);
	$uni_modules = array_keys($uni_modules);
	$version_info['cover_entrys'] = array();
	$version_info['last_modules'] = iunserializer($version_info['last_modules']);
	if (!empty($version_info['modules'])) {
		$version_info['modules'] = iunserializer($version_info['modules']);
		if (!empty($version_info['modules'])) {
			foreach ($version_info['modules'] as $i => $module) {
				if (!empty($module['uniacid'])) {
					$account = uni_fetch($module['uniacid']);
				}
				$module_info = module_fetch($module['name']);
				$module_info['account'] = $account;
				unset($version_info['modules'][$module['name']]);
				if (!in_array($module['name'], $uni_modules)) {
					continue;
				}
				//模块默认入口
				$module_info['cover_entrys'] = module_entries($module['name'], array('cover'));
				$module_info['defaultentry'] = $module['defaultentry'];
				$module_info['newicon'] = $module['newicon'];
				$version_info['modules'][] = $module_info;
			}
		}
	}
	if (count($version_info['modules']) > 0) {
		$cover_entrys = !empty($version_info['modules'][0]['cover_entrys']) ? $version_info['modules'][0]['cover_entrys'] : array();
		$version_info['cover_entrys'] = !empty($cover_entrys['cover']) ? $cover_entrys['cover'] : array();
	}
	if (!empty($version_info['quickmenu'])) {
		$version_info['quickmenu'] = iunserializer($version_info['quickmenu']);
	}

	return $version_info;
}

function wxapp_site_info($multiid) {
	$site_info = array();
	$multiid = intval($multiid);

	if (empty($multiid)) {
		return array();
	}

	$site_info['slide'] = pdo_getall('site_slide', array('multiid' => $multiid));
	$site_info['nav'] = pdo_getall('site_nav', array('multiid' => $multiid));
	if (!empty($site_info['nav'])) {
		foreach ($site_info['nav'] as &$nav) {
			$nav['css'] = iunserializer($nav['css']);
		}
		unset($nav);
	}
	$recommend_sql = 'SELECT a.name, b.* FROM ' . tablename('site_category') . ' AS a LEFT JOIN ' . tablename('site_article') . ' AS b ON a.id = b.pcate WHERE a.parentid = 0 AND a.multiid = :multiid';
	$site_info['recommend'] = pdo_fetchall($recommend_sql, array(':multiid' => $multiid));

	return $site_info;
}

/**
 * 获取小程序支付参数.
 *
 * @return mixed
 */
function wxapp_payment_param() {
	global $_W;
	$setting = uni_setting_load('payment', $_W['uniacid']);
	$pay_setting = $setting['payment'];

	return $pay_setting;
}

function wxapp_update_daily_visittrend() {
	global $_W;
	$yesterday = date('Ymd', strtotime('-1 days'));
	$trend = pdo_get('wxapp_general_analysis', array('uniacid' => $_W['uniacid'], 'type' => WXAPP_STATISTICS_DAILYVISITTREND, 'ref_date' => $yesterday));
	if (!empty($trend)) {
		return true;
	}
	return wxapp_insert_date_visit_trend($yesterday);
}

function wxapp_insert_date_visit_trend($date) {
	global $_W;
	$account_api = WeAccount::create();
	$wxapp_stat = $account_api->getDailyVisitTrend($date);
	if (is_error($wxapp_stat) || empty($wxapp_stat)) {
		return error(-1, '调用微信接口错误');
	} else {
		$insert_stat = array(
			'uniacid' => $_W['uniacid'],
			'session_cnt' => $wxapp_stat['session_cnt'],
			'visit_pv' => $wxapp_stat['visit_pv'],
			'visit_uv' => $wxapp_stat['visit_uv'],
			'visit_uv_new' => $wxapp_stat['visit_uv_new'],
			'type' => WXAPP_STATISTICS_DAILYVISITTREND,
			'stay_time_uv' => $wxapp_stat['stay_time_uv'],
			'stay_time_session' => $wxapp_stat['stay_time_session'],
			'visit_depth' => $wxapp_stat['visit_depth'],
			'ref_date' => $wxapp_stat['ref_date'],
		);
		pdo_insert('wxapp_general_analysis', $insert_stat);
	}
	return $insert_stat;
}

function wxapp_search_link_account($module_name = '') {
	global $_W;
	$module_name = trim($module_name);
	if (empty($module_name)) {
		return array();
	}
	$owned_account = uni_owned();
	if (!empty($owned_account)) {
		foreach ($owned_account as $key => $account) {
			if (!in_array($account['type'], array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH))) {
				unset($owned_account[$key]);
			}
			$account['role'] = permission_account_user_role($_W['uid'], $account['uniacid']);
			if (!in_array($account['role'], array(ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_FOUNDER))) {
				unset($owned_account[$key]);
			}
		}
		foreach ($owned_account as $key => $account) {
			$account_modules = uni_modules_by_uniacid($account['uniacid']);
			if (empty($account_modules[$module_name])) {
				unset($owned_account[$key]);
			} elseif ($account_modules[$module_name][MODULE_SUPPORT_ACCOUNT_NAME] != MODULE_SUPPORT_ACCOUNT || $account_modules[$module_name]['wxapp_support'] != MODULE_SUPPORT_WXAPP) {
				unset($owned_account[$key]);
			}
		}
	}

	return $owned_account;
}

/**
 * 获取当前用户使用每个小程序的最后版本.
 */
function wxapp_last_switch_version() {
	global $_GPC;
	static $wxapp_cookie_uniacids;
	if (empty($wxapp_cookie_uniacids) && !empty($_GPC['__wxappversionids'])) {
		$wxapp_cookie_uniacids = json_decode(htmlspecialchars_decode($_GPC['__wxappversionids']), true);
	}

	return $wxapp_cookie_uniacids;
}

/**
 *   通知服务器生成小程序代码
 */
function wxapp_code_generate($version_id) {
	global $_W;
	load()->classs('cloudapi');
	$api = new CloudApi();
	$version_info = wxapp_version($version_id);
	$account_wxapp_info = wxapp_fetch($version_info['uniacid'], $version_id);
	if (empty($account_wxapp_info)) {
		return error(1, '版本不存在');
	}
	$siteurl = $_W['siteroot'] . 'app/index.php';
	if (!empty($account_wxapp_info['appdomain'])) {
		$siteurl = $account_wxapp_info['appdomain'];
	}
	if (!starts_with($siteurl, 'https')) { //不是https 开头强制改为https开头
		return error(1, '小程序域名必须为https');
	}

	if ($version_info['type'] == WXAPP_CREATE_MODULE && $version_info['entry_id'] <= 0) {
		return error(1, '请先设置小程序入口');
	}

	$appid = $account_wxapp_info['key'];
	$siteinfo = array(
		'name' => $account_wxapp_info['name'],
		'uniacid' => $account_wxapp_info['uniacid'],
		'acid' => $account_wxapp_info['acid'],
		'multiid' => $account_wxapp_info['version']['multiid'],
		'version' => $account_wxapp_info['version']['version'],
		'siteroot' => $siteurl,
		'design_method' => $account_wxapp_info['version']['design_method'],
	);

	$commit_data = array('do' => 'generate',
		'appid' => $appid,
		'modules' => $account_wxapp_info['version']['modules'],
		'siteinfo' => $siteinfo,
		'tabBar' => json_decode($account_wxapp_info['version']['quickmenu'], true),
		// 小程序类型 如果是模块类型使用默认网页小程序
		'wxapp_type' => isset($version_info['type']) ? $version_info['type'] : 0,
	);

	$do = 'upload2';
	if ($version_info['use_default'] == 0) {
		$appjson = wxapp_code_custom_appjson_tobase64($version_id);
		if ($appjson) {
			if (!isset($appjson['tabBar']['list'])) {
				unset($appjson['tabBar']);
			}
			$commit_data['appjson'] = $appjson;
		}
	}

	$data = $api->post('wxapp', $do, $commit_data,
		'json', false);
	//	var_dump($data);
	return $data;
}

/**
 *  获取服务器小程序是否已经生成好了.
 *
 * @param $code_uuid
 *
 * @return array(errno,$message,data[is_gen]= 1|0);
 */
function wxapp_check_code_isgen($code_uuid) {
	load()->classs('cloudapi');
	$api = new CloudApi();
	$data = $api->get('wxapp', 'upload', array('do' => 'check_gen',
		'code_uuid' => $code_uuid, ),
		'json', false);

	return $data;
}

/**
 *  开发者工具二维码 的UUID.
 *
 * @return array|mixed|string array(errno=>0|1, $messge, data=>array('code_token'=>))
 */
function wxapp_code_token() {
	global $_W;
	load()->classs('cloudapi');
	$cloud_api = new CloudApi();
	$data = $cloud_api->get('wxapp', 'upload', array('do' => 'code_token'), 'json', false);

	return $data;
}

/**
 *  开发者工具二维码
 *
 * @param $uuid  图片二进制数据
 */
function wxapp_code_qrcode($code_token) {
	$cloud_api = new CloudApi();
	$data = $cloud_api->get('wxapp', 'upload', array('do' => 'qrcode',
		'code_token' => $code_token, ),
		'html', false);

	return $data;
}

/**
 * @param $uuid 微信UUID
 * @param $last //微信返回的扫码状态
 *
 * @return array|mixed|string array('errno'=>,'message', 'data'=>array('errcode'=>,'code_token'=>'上传代码凭证'))
 *							errcode 408 超时 404 已扫码 403 已取消 405 已确认扫码
 */
function wxapp_code_check_scan($code_token, $last) {
	$cloud_api = new CloudApi();
	$data = $cloud_api->get('wxapp', 'upload',
		array('do' => 'checkscan',
			'code_token' => $code_token,
			'last' => $last,
		),
		'json', false);

	return $data;
}

/**
 *  获取预览二维码
 *
 * @param $code_uuid
 * @param $code_token
 *
 * @return array|mixed|string
 */
function wxapp_code_preview_qrcode($code_uuid, $code_token) {
	$cloud_api = new CloudApi();

	$commit_data = array(
		'do' => 'preview_qrcode',
		'code_uuid' => $code_uuid,
		'code_token' => $code_token,
	);
	$data = $cloud_api->post('wxapp', 'upload', $commit_data,
		'json', false);

	return $data;
}
/**
 * @param $code_uuid  服务器生成代码 返回的UUID
 * @param $code_token  //开发工具调用凭据
 * @param int	$user_version //用户版本
 * @param string $user_desc	// 用户描述
 *
 * @return array('errno'=>0|1, $message=>'');
 */
function wxapp_code_commit($code_uuid, $code_token, $user_version = 3, $user_desc = '代码提交') {
	$cloud_api = new CloudApi();

	$commit_data = array(
		'do' => 'commitcode',
		'code_uuid' => $code_uuid,
		'code_token' => $code_token,
		'user_version' => $user_version,
		'user_desc' => $user_desc,
	);
	$data = $cloud_api->post('wxapp', 'upload', $commit_data,
		'json', false);

	return $data;
}

/**
 *  更新普通模块 小程序的入口页.
 *
 * @param $version_id
 * @param $entry_id
 *
 * @return mixed
 */
function wxapp_update_entry($version_id, $entry_id) {
	return pdo_update('wxapp_versions', array('entry_id' => $entry_id), array('id' => $version_id));
}

/**
 *  获取当前appjson 函数内部判断默认还是自定义appjson.
 *
 * @param $version_id
 *
 * @return mixed
 *
 * @since version
 */
function wxapp_code_current_appjson($version_id) {
	load()->classs('cloudapi');
	$version_info = wxapp_version($version_id);
	//自定义appjson
	if (!$version_info['use_default'] && isset($version_info['appjson'])) {
		return iunserializer($version_info['appjson']);
	}
	//默认appjson
	if ($version_info['use_default']) {
		$appjson = $version_info['default_appjson'];
		if ($appjson) {
			return iunserializer($appjson);
		}
		// 从云中取
		$cloud_api = new CloudApi();
		$account_wxapp_info = wxapp_fetch($version_info['uniacid'], $version_id);
		$commit_data = array('do' => 'appjson',
			'wxapp_type' => isset($version_info['type']) ? $version_info['type'] : 0,
			'modules' => $account_wxapp_info['version']['modules'],
		);
		$cloud_appjson = $cloud_api->get('wxapp', 'upload2', $commit_data,
			'json', false);
		if (is_error($cloud_appjson)) { //数据访问失败
			return null;
		}
		$appjson = $cloud_appjson['data']['appjson'];
		pdo_update('wxapp_versions', array('default_appjson' => serialize($appjson)),
			array('id' => $version_id));
		cache_delete(cache_system_key("wxapp_version:{$version_id}"));
		return $appjson;
	}
}

/** 自定义appjson 路径转base64
 * @param $version_id
 *
 * @return array|null
 */
function wxapp_code_custom_appjson_tobase64($version_id) {
	load()->classs('image');
	$version_info = wxapp_version($version_id);
	$appjson = iunserializer($version_info['appjson']);
	if (!$appjson) {
		return false;
	}
	if (isset($appjson['tabBar']) && isset($appjson['tabBar']['list'])) {
		$tablist = &$appjson['tabBar']['list'];
		foreach ($tablist as &$item) {
			//判断默认图标和选中图片存在且不是base64编码的 进行base64编码
			if (isset($item['iconPath']) && !starts_with($item['iconPath'], 'data:image')) {
				$item['iconPath'] = Image::create($item['iconPath'])->resize(81, 81)->toBase64();
			}
			if (isset($item['selectedIconPath']) && !starts_with($item['selectedIconPath'], 'data:image')) {
				$item['selectedIconPath'] = Image::create($item['selectedIconPath'])->resize(81, 81)->toBase64();
			}
		}
	}

	return $appjson;
}

/**
 *  素材图片转为小程序图片大小并保存.
 *
 * @param $att_id  素材ID
 *
 * @return null|string
 */
function wxapp_code_path_convert($attachment_id) {
	load()->classs('image');
	load()->func('file');

	$attchid = intval($attachment_id);
	global $_W;
	/* @var  $att_table  AttachmentTable */
	$att_table = table('attachment');
	$attachment = $att_table->getById($attchid);
	if ($attachment) {
		$attach_path = $attachment['attachment'];
		$ext = pathinfo($attach_path, PATHINFO_EXTENSION);
		$url = tomedia($attach_path);
		$uniacid = intval($_W['uniacid']);
		$path = "images/{$uniacid}/" . date('Y/m/');
		mkdirs($path);
		$filename = file_random_name(ATTACHMENT_ROOT . '/' . $path, $ext);
		Image::create($url)->resize(81, 81)->saveTo(ATTACHMENT_ROOT . $path . $filename);
		$attachdir = $_W['config']['upload']['attachdir'];

		return $_W['siteroot'] . $attachdir . '/' . $path . $filename;
	}

	return null;
}

/**
 * 保存自定义appjson.
 *
 * @param $uniacid
 * @param $version_id
 * @param $json
 *
 * @return bool
 *
 * @since version
 */
function wxapp_code_save_appjson($version_id, $json) {
	$result = pdo_update('wxapp_versions', array('appjson' => serialize($json), 'use_default' => 0), array('id' => $version_id));
	cache_delete(cache_system_key("wxapp_version:{$version_id}"));
	return $result;
}

/**
 *  设为默认的appjson.
 *
 * @param $version_id
 *
 * @since version
 */
function wxapp_code_set_default_appjson($version_id) {
	$result = pdo_update('wxapp_versions', array('appjson' => '', 'use_default' => 1), array('id' => $version_id));
	cache_delete(cache_system_key("wxapp_version:{$version_id}"));
	return $result;
}
