<?php
/**
 * 小程序下载
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('miniapp');
load()->classs('uploadedfile');
load()->func('file');

$dos = array('front_download', 'domainset', 'getpackage', 'entrychoose', 'set_wxapp_entry', 'platform_version_manage',
	'custom', 'custom_save', 'custom_default', 'custom_convert_img', 'upgrade_module', 'tominiprogram');
$do = in_array($do, $dos) ? $do : 'front_download';

$wxapp_info = miniapp_fetch($_W['uniacid']);
// 是否是模块打包小程序
$is_module_wxapp = false;
if (!empty($version_id)) {
	$is_single_module_wxapp = WXAPP_CREATE_MODULE == $version_info['type']; //是否单应用打包
}

// 自定义appjson 入口
if ('custom' == $do) {
	$default_appjson = miniapp_code_current_appjson($version_id);

	$default_appjson = json_encode($default_appjson);
	template('wxapp/version-front-download');
}
// 使用默认appjson
if ('custom_default' == $do) {
	$result = miniapp_code_set_default_appjson($version_id);
	if (false === $result) {
		iajax(1, '操作失败，请重试！');
	} else {
		iajax(0, '设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)));
	}
}

// 保存自定义appjson
if ('custom_save' == $do) {
	if (empty($version_info)) {
		iajax(1, '参数错误！');
	}
	$json = array();
	if (!empty($_GPC['json']['window'])) {
		$json['window'] = array(
			'navigationBarTitleText' => safe_gpc_string($_GPC['json']['window']['navigationBarTitleText']),
			'navigationBarTextStyle' => safe_gpc_string($_GPC['json']['window']['navigationBarTextStyle']),
			'navigationBarBackgroundColor' => safe_gpc_string($_GPC['json']['window']['navigationBarBackgroundColor']),
			'backgroundColor' => safe_gpc_string($_GPC['json']['window']['backgroundColor']),
		);
	}
	if (!empty($_GPC['json']['tabBar'])) {
		$json['tabBar'] = array(
			'color' => safe_gpc_string($_GPC['json']['tabBar']['color']),
			'selectedColor' => safe_gpc_string($_GPC['json']['tabBar']['selectedColor']),
			'backgroundColor' => safe_gpc_string($_GPC['json']['tabBar']['backgroundColor']),
			'borderStyle' => in_array($_GPC['json']['tabBar']['borderStyle'], array('black', 'white')) ? safe_gpc_string($_GPC['json']['tabBar']['borderStyle']) : '',
		);
	}
	$result = miniapp_code_save_appjson($version_id, $json);
	cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
	iajax(0, '设置成功！', url('wxapp/front-download/front_download', array('version_id' => $version_id)));
}

if ('custom_convert_img' == $do) {
	$attchid = intval($_GPC['att_id']);
	$filename = miniapp_code_path_convert($attchid);
	iajax(0, $filename);
}

if ('front_download' == $do) {
	permission_check_account_user('publish_front_download');
	$appurl = $_W['siteroot'] . '/app/index.php';
	$uptype = empty($_GPC['uptype']) ? '' : safe_gpc_string($_GPC['uptype']);
	if (!in_array($uptype, array('auto', 'normal'))) {
		$uptype = 'normal';
	}
	if (empty($version_info)) {
		itoast('请先分配应用后再发布！', url('miniapp/version/home', array('version_id' => $version_id, 'uniacid' => $version_info['uniacid'])), 'error');
	}
	if (!empty($version_info['last_modules'])) {
		$last_modules = current($version_info['last_modules']);
	}
	$need_upload = false;
	$module = array();
	if (!empty($version_info['modules'])) {
		foreach ($version_info['modules'] as $item) {
			$module = module_fetch($item['name']);
			$need_upload = !empty($last_modules) && ($module['version'] != $last_modules['version']);
		}
	}
	if (!empty($version_info['version'])) {
		$user_version = explode('.', $version_info['version']);
		$user_version[count($user_version) - 1] += 1;
		$user_version = join('.', $user_version);
	}
	if (WXAPP_TYPE_SIGN == $_W['account']->typeSign) {
		if ($version_info['type'] == 0) {
			$account_wxapp_info = miniapp_fetch($version_info['uniacid'], $version_id);
			$params = array(
				'module' => empty($account_wxapp_info['version']['modules']) ? array() : array_shift($account_wxapp_info['version']['modules'])
			);
			if (empty($params['module'])) {
				itoast('请先分配应用后再发布！', url('miniapp/version/home', array('version_id' => $version_id, 'uniacid' => $version_info['uniacid'])), 'error');
			}
		}
	}
	template('wxapp/version-front-download');
}

if ('platform_version_manage' == $do) {
	$platform_version_info = array('success' => array(), 'audit' => array(), 'develop' => array());
	$wxapp_register_version = table('wxapp_register_version')->getByUniacid($_W['uniacid']);
	foreach ($wxapp_register_version as $key => $value) {
		if (WXAPP_REGISTER_VERSION_STATUS_RELEASE == $value['status']) {
			$platform_version_info['success'][] = $value;
		} elseif (in_array($value['status'], array(WXAPP_REGISTER_VERSION_STATUS_CHECKING, WXAPP_REGISTER_VERSION_STATUS_CHECKFAIL, WXAPP_REGISTER_VERSION_STATUS_CHECKSUCCESS))) {
			$params = array(
				':uniacid' => $value['uniacid'],
				':version_id' => $value['version_id'],
			);
			$day_num = pdo_fetch('select count(id) day_num from ' . tablename('wxapp_undocodeaudit_log') . ' where TO_DAYS(from_unixtime(`revoke_time`)) = TO_DAYS(NOW()) and uniacid = :uniacid and version_id = :version_id;', $params);
			$month_num = pdo_fetch('select count(id) month_num from ' . tablename('wxapp_undocodeaudit_log') . ' where DATE_FORMAT(from_unixtime(`revoke_time`), "%Y%m")=DATE_FORMAT(CURDATE(), "%Y%m") and uniacid = :uniacid and version_id = :version_id;', $params);
			$value['day_num'] = empty($day_num) || $day_num['day_num'] < 1 ? 1 : 0;
			if (empty($month_num)) {
				$value['month_num'] = 10;
			} else {
				$value['month_num'] = $month_num['month_num'] >= 10 ? 0 : 10 - $month_num['month_num'];
			}
			$platform_version_info['audit'][] = $value;
		} elseif (WXAPP_REGISTER_VERSION_STATUS_DEVELOP == $value['status']) {
			$platform_version_info['develop'][] = $value;
		}
	}
	template('wxapp/version-front-download');
}
if ('upgrade_module' == $do) {
	$modules = table('wxapp_versions')
		->where(array('id' => $version_id))
		->getcolumn('modules');
	$modules = iunserializer($modules);
	if (!empty($modules)) {
		foreach ($modules as $name => $module) {
			$module_info = module_fetch($name);
			if (!empty($module_info['version'])) {
				$modules[$name]['version'] = $module_info['version'];
			}
		}
		$modules = iserializer($modules);
		table('wxapp_versions')
			->where(array('id' => $version_id))
			->fill(array(
				'modules' => $modules,
				'last_modules' => $modules,
				'version' => safe_gpc_string($_GPC['version']),
				'description' => safe_gpc_html($_GPC['description']),
				'upload_time' => TIMESTAMP,
			))
			->save();
		cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
	}
	iajax(0, '更新模块信息成功');
}

if ('tominiprogram' == $do) {
	$tomini_lists = iunserializer($version_info['tominiprogram']);
	if (!is_array($tomini_lists)) {
		$tomini_lists = array();
		miniapp_version_update($version_id, array('tominiprogram' => iserializer(array())));
	}

	if (checksubmit()) {
		$appids = safe_gpc_array($_GPC['appid']);
		$app_names = safe_gpc_array($_GPC['app_name']);
		$is_add = intval($_GPC['is_add']);

		if (!is_array($appids) || !is_array($app_names)) {
			itoast('参数有误！', referer(), 'error');
		}
		$data = $is_add ? $tomini_lists : array();
		foreach ($appids as $k => $appid) {
			if (empty($appid) || empty($app_names[$k])) {
				continue;
			}
			$appid = safe_gpc_string($appid);
			$data[$appid] = array(
				'appid' => $appid,
				'app_name' => safe_gpc_string($app_names[$k])
			);
			if (count($data) >= 10) {
				break;
			}
		}
		miniapp_version_update($version_id, array('tominiprogram' => iserializer($data)));
		itoast('保存成功！', referer(), 'success');
	}
	template('wxapp/version-front-download');
}

if ('getpackage' == $do) {
	if (empty($version_id)) {
		itoast('参数错误！', '', '');
	}
	$account_wxapp_info = miniapp_fetch($version_info['uniacid'], $version_id);
	if (empty($account_wxapp_info)) {
		itoast('版本不存在！', referer(), 'error');
	}
	$module = array_shift($account_wxapp_info['version']['modules']);
	$request_cloud_data = array(
		'module' => $module,
		'support' => $_W['account']['type_sign'],
		'filename' => 'package.zip'
	);
	if ($_W['account']['type_sign'] == 'wxapp') {
		$module_root = IA_ROOT . '/addons/' . $module['name'] . '/';
		$dir_name = $module['name'] . '_wxapp';
		if (is_dir($module_root . $dir_name)) {
			$app_json = array();
			$tomini_lists = iunserializer($version_info['tominiprogram']);
			if (!empty($tomini_lists) && file_exists($module_root . $dir_name . '/app.json')) {
				$app_json = json_decode(file_get_contents($module_root . $dir_name . '/app.json'));
				$app_json->embeddedAppIdList = array_keys($version_info['tominiprogram']);
			}
			$uniacid_zip_name = $module['name'] . '_wxapp_' . $_W['uniacid'] . md5(time()) . '.zip';
			$zip = new ZipArchive();
			if ($zip->open($module_root . $uniacid_zip_name, ZipArchive::CREATE) === true) {//如果只用ZipArchive::OVERWRITE那么如果指定目标存在的话就会复写，否则返回错误9，而两个都用则会避免这个错误
				addFileToZip($module_root . $dir_name, $zip, $module_root);
				$zip->close();
			}
			if (!is_dir(ATTACHMENT_ROOT . '/siteinfo')) {
				mkdir(ATTACHMENT_ROOT . '/siteinfo');
			}
			$copy_result = copy($module_root . $uniacid_zip_name, ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name);
			if (!$copy_result) {
				itoast('小程序前端报预处理打包失败，请将权限设置成755后再试！');
			} else {
				@unlink($module_root . $uniacid_zip_name);
			}
			$siteinfo_content = <<<EOF
var siteinfo = {
  "name": 'we7_wxappsample',
  "uniacid": "{$_W['uniacid']}",
  "acid": "{$_W['uniacid']}",
  "multiid": "0",
  "version": "{$version_info['info']}",
  "siteroot": "{$_W['siteroot']}app/index.php",
  "method_design": "3"
};
module.exports = siteinfo;
EOF;
			$tmp_siteinfo_file = 'siteinfo/siteinfo_' . $_W['uniacid'] . '.js';
			file_write($tmp_siteinfo_file, $siteinfo_content);
			$tmp_app_json_file = '';
			if (!empty($app_json)) {
				$tmp_app_json_file = 'siteinfo/app_' . $_W['uniacid'] . '.json';
				file_write($tmp_app_json_file, json_encode($app_json));
			}
			if ($zip->open(ATTACHMENT_ROOT . '/siteinfo/' . $uniacid_zip_name) === true) {
				$zip->addFile(ATTACHMENT_ROOT . '/' . $tmp_siteinfo_file, $dir_name . '/siteinfo.js');
				if (!empty($tmp_app_json_file)) {
					$zip->addFile(ATTACHMENT_ROOT . '/' . $tmp_app_json_file, $dir_name . '/app.json');
				}
				$zip->close();
				$result = array('url' => $_W['siteroot'] . 'attachment/siteinfo/' . $uniacid_zip_name);
			}
			@unlink(ATTACHMENT_ROOT . '/' . $tmp_siteinfo_file);
			if (!empty($tmp_app_json_file)) {
				@unlink(ATTACHMENT_ROOT . '/' . $tmp_app_json_file);
			}
		} else {
			$result = error(-1, '没有检测到小程序前端包的存在，需创始人下载代码包上传至addons目录解压后再重试！');
		}
	} else {
		$result = cloud_miniapp_get_package($request_cloud_data);
	}
	if (is_error($result)) {
		itoast($result['message'], '', '');
	} else {
		header("http/1.1 301 moved permanently");
		header("location: " . $result['url']);
	}
	exit;
}

function addFileToZip($path, $zip, $root_path) {
	$handler = opendir($path);
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			if (is_dir($path . "/" . $filename)) {
				addFileToZip($path . "/" . $filename, $zip, $root_path);
			} else {
				$zip->addFile($path . "/" . $filename, substr($path . "/" . $filename, strlen($root_path)));
			}
		}
	}
	@closedir($handler);
	return true;
}
