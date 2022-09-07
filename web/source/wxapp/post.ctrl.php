<?php
/**
 * 创建小程序
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('permission');
load()->model('module');
load()->func('communication');
load()->classs('wxapp.platform');

$dos = array('design_method', 'post', 'get_wxapp_modules', 'module_binding');
$do = in_array($do, $dos) ? $do : 'post';
$account_info = permission_user_account_num($_W['uid']);
$_W['breadcrumb'] = '新建平台账号';
if ('design_method' == $do) {
	// 1 普通小程序  2 授权小程序
	$choose = isset($_GPC['choose_type']) ? intval($_GPC['choose_type']) : 0;
	$uniacid = empty($_GPC['uniacid']) ? 0 : intval($_GPC['uniacid']);
	if ($choose) {
		template('wxapp/design-method');
	} else {
		if (!permission_user_account_creatable($_W['uid'], WXAPP_TYPE_SIGN)) {
			$authurl = "javascript:alert('创建小程序已达上限！');";
		}
		if (empty($authurl) && !empty($_W['setting']['platform']['authstate'])) {
			$account_platform = new WxappPlatform();
			$authurl = $account_platform->getAuthLoginUrl(ACCOUNT_PLATFORM_API_LOGIN_WXAPP);
		}
		if (empty($_W['setting']['platform']['authstate'])) {
			$authurl = "javascript:alert('请先开启微信开放平台！');";
		}
		template('wxapp/choose-type');
	}
}
if ('post' == $do) {
	$uniacid = intval($_GPC['uniacid']);
	$design_method = intval($_GPC['design_method']);
	$create_type = intval($_GPC['create_type']);
	$is_submit = checksubmit('submit');
	if (empty($unicid) && !permission_user_account_creatable($_W['uid'], WXAPP_TYPE_SIGN)) {
		$is_submit ? iajax(-1, '创建的小程序已达上限') : itoast('创建的小程序已达上限！', '', '');
	}

	$version_id = empty($_GPC['version_id']) ? 0 : intval($_GPC['version_id']);
	$isedit = $version_id > 0 ? 1 : 0;
	if ($isedit) {
		$wxapp_version = miniapp_version($version_id);
	}
	if (empty($design_method)) {
		$is_submit ? iajax(-1, '请先选择要添加小程序类型') : itoast('请先选择要添加小程序类型', referer(), 'error');
	}
	if (WXAPP_TEMPLATE == $design_method) {
		$is_submit ? iajax(-1, '拼命开发中。。。') : itoast('拼命开发中。。。', referer(), 'info');
	}

	if ($is_submit || $_W['isw7_request']) {
		if (WXAPP_TEMPLATE == $design_method && empty($_GPC['choose']['modules'])) {
			iajax(-1, '请选择要打包的模块应用', empty($_W['isw7_request']) ? '' : url('wxapp/post'));
		}
		if (!preg_match('/^[0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2})?$/', safe_gpc_string($_GPC['version']))) {
			iajax(-1, '版本号错误，只能是数字、点，数字最多2位，例如 1.1.1 或1.2');
		}
		//新建小程序公众号
		if (empty($uniacid)) {
			if (empty(safe_gpc_string($_GPC['name']))) {
				iajax(-1, '请填写小程序名称', empty($_W['isw7_request']) ? '' : url('wxapp/post'));
			}
			$account_wxapp_data = array(
				'name' => safe_gpc_string($_GPC['name']),
				'description' => safe_gpc_string($_GPC['description']),
				'original' => safe_gpc_string($_GPC['original']),
				'level' => 1,
				'key' => safe_gpc_string($_GPC['appid']),
				'secret' => safe_gpc_string($_GPC['appsecret']),
				'type' => ACCOUNT_TYPE_APP_NORMAL,
			);

			$attachment_url = array();
			if (!empty($_GPC['headimg'])) {
				$headimg = safe_gpc_path($_GPC['headimg']);
				if (file_is_image($headimg)) {
					$account_wxapp_data['headimg'] = $headimg;
					$attachment_url[] = str_replace($_W['siteroot'] . 'attachment/', '', $headimg);
				}
			}
			if (!empty($_GPC['qrcode'])) {
				$qrcode = safe_gpc_path($_GPC['qrcode']);
				if (file_is_image($qrcode)) {
					$account_wxapp_data['qrcode'] = $qrcode;
					$attachment_url[] = str_replace($_W['siteroot'] . 'attachment/', '', $qrcode);
				}
			}

			$uniacid = miniapp_create($account_wxapp_data);

			$unisettings['creditnames'] = array('credit1' => array('title' => '积分', 'enabled' => 1), 'credit2' => array('title' => '余额', 'enabled' => 1));
			$unisettings['creditnames'] = iserializer($unisettings['creditnames']);
			$unisettings['creditbehaviors'] = array('activity' => 'credit1', 'currency' => 'credit2');
			$unisettings['creditbehaviors'] = iserializer($unisettings['creditbehaviors']);
			$unisettings['uniacid'] = $uniacid;
			table('uni_settings')->fill($unisettings)->save(true);

			if (is_error($uniacid)) {
				iajax(-1, '添加小程序信息失败', empty($_W['isw7_request']) ? '' : url('wxapp/post'));
			}
			if (!empty($attachment_url)) {
				pdo_update('core_attachment', array('uniacid' => $uniacid), array('attachment in' => array_unique($attachment_url)));
			}
		} else {
			$wxapp_info = miniapp_fetch($uniacid);
			if (empty($wxapp_info)) {
				iajax(-1, '小程序不存在或是已经被删除', empty($_W['isw7_request']) ? '' : url('wxapp/post'));
			}
		}

		//小程序版本信息，打包多模块时，每次更改需要重建版本
		//打包单模块时，每添加一个模块算是一个版本
		$wxapp_version = array(
			'uniacid' => $uniacid,
			'multiid' => '0',
			'description' => safe_gpc_string($_GPC['description']),
			'version' => safe_gpc_string($_GPC['version']),
			'modules' => '',
			'design_method' => $design_method,
			'quickmenu' => '',
			'createtime' => TIMESTAMP,
			'template' => WXAPP_TEMPLATE == $design_method ? safe_gpc_int($_GPC['choose']['template']) : 0,
			//是否公众号应用 1 是 0默认小程序
			'type' => 0,
		);
		
		//多模块打包，每个版本对应一个微官网
		if (WXAPP_TEMPLATE == $design_method) {
			$multi_data = array(
				'uniacid' => $uniacid,
				'title' => $account_wxapp_data['name'],
				'styleid' => 0,
			);
			table('site_multi')->fill($multi_data)->save();
			$wxapp_version['multiid'] = pdo_insertid();
		}

		//打包模块
		$uni_modules = array();
		if (!empty($_GPC['choose']['modules'])) {
			$select_modules = array();
			$_GPC['choose']['modules'] = safe_gpc_array($_GPC['choose']['modules']);
			foreach ($_GPC['choose']['modules'] as $post_module) {
				$module = module_fetch($post_module['name']);
				if (empty($module)) {
					continue;
				}

				$uni_modules[] =  $module['name'];
				$select_modules[$module['name']] = array('name' => $module['name'],
					'newicon' => $post_module['newicon'],
					'version' => $module['version'], 'defaultentry' => $post_module['defaultentry'], );
			}

			$wxapp_version['modules'] = serialize($select_modules);
		}

		//快捷菜单
		if (!empty($_GPC['quickmenu'])) {
			$quickmenu = array(
				'color' => safe_gpc_string($_GPC['quickmenu']['bottom']['color'], '', 'color'),
				'selected_color' => safe_gpc_string($_GPC['quickmenu']['bottom']['selectedColor'], '', 'color'),
				'boundary' => safe_gpc_string($_GPC['quickmenu']['bottom']['boundary']),
				'bgcolor' => safe_gpc_string($_GPC['quickmenu']['bottom']['bgcolor'], '', 'color'),
				'show' => safe_gpc_string($_GPC['quickmenu']['show']) == 'true' ? 1 : 0,
				'menus' => array(),
			);
			if (!empty($_GPC['quickmenu']['menus'])) {
				$menus = safe_gpc_array($_GPC['quickmenu']['menus']);
				foreach ($menus as $row) {
					$quickmenu['menus'][] = array(
						'name' => $row['name'],
						'icon' => $row['defaultImage'],
						'selectedicon' => $row['selectedImage'],
						'url' => $row['module']['url'],
						'defaultentry' => empty($row['defaultentry']['eid']) ? 0 : $row['defaultentry']['eid'],
					);
				}
			}

			$wxapp_version['quickmenu'] = serialize($quickmenu);
		}
		if ($isedit) {
			$msg = '小程序修改成功';
			table('wxapp_versions')
				->where(array(
					'id' => $version_id,
					'uniacid' => $uniacid
				))
				->fill($wxapp_version)
				->save();
			cache_delete(cache_system_key('miniapp_version', array('version_id' => $version_id)));
		} else {
			$msg = '小程序创建成功';
			table('wxapp_versions')->fill($wxapp_version)->save();
			$version_id = pdo_insertid();
		}
		//记录平台和模块的关联数据
		if (!empty($uni_modules) && !empty($uniacid)) {
			$add_uni_modules_sql = '';
			$params[':uniacid'] = $uniacid;
			foreach ($uni_modules as $key => $module_name) {
				$params_key = ':' . $key . 'module_name';
				$add_uni_modules_sql .= '(:uniacid,' . $params_key . '),';
				$params[$params_key] = $module_name;
			}
			$add_uni_modules_sql = rtrim($add_uni_modules_sql, ',');
			$add_uni_modules_sql = 'INSERT INTO ' . tablename('uni_modules') . ' (`uniacid`, `module_name`) VALUES ' . $add_uni_modules_sql;
			pdo_query($add_uni_modules_sql, $params);
		}
		cache_delete(cache_system_key('user_accounts', array('type' => 'wxapp', 'uid' => $_W['uid'])));
		iajax(0, $msg, url('account/display/switch', array('uniacid' => $uniacid, 'type' => ACCOUNT_TYPE_APP_NORMAL)));
	}
	if (!empty($uniacid)) {
		$wxapp_info = miniapp_fetch($uniacid);
	}
	template('wxapp/post');
}

//获取所有支持小程序的模块
if ('get_wxapp_modules' == $do) {
	$wxapp_modules = miniapp_support_wxapp_modules();
	foreach ($wxapp_modules as $name => $module) {
		if ($module['issystem']) {
			$path = '/framework/builtin/' . $module['name'];
		} else {
			$path = '../addons/' . $module['name'];
		}
		$icon = $path . '/icon-custom.jpg';
		if (!file_exists($icon)) {
			$icon = $path . '/icon.jpg';
			if (!file_exists($icon)) {
				$icon = './resource/images/nopic-small.jpg';
			}
		}
		$module['logo'] = $icon;
	}
	iajax(0, $wxapp_modules, '');
}

if ('module_binding' == $do) {
	$modules = safe_gpc_string($_GPC['modules']);
	if (empty($modules)) {
		iajax(1, '参数无效');

		return;
	}
	$modules = explode(',', $modules);
	$modules = array_map(function ($item) {
		return trim($item);
	}, $modules);

	$modules = table('modules')->with(array('bindings' => function ($query) {
		return $query->where('entry', 'cover');
	}))->where('name', $modules)->getall();

	$modules = array_filter($modules, function ($module) {
		return count($module['bindings']) > 0;
	});
	iajax(0, $modules);
}
