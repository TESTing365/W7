<?php
/**
 * 模块管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('extension');
load()->model('cloud');
load()->model('cache');
load()->model('module');
load()->model('user');
load()->model('account');
load()->model('utility');
load()->func('db');
$dos = array('subscribe', 'check_subscribe', 'check_upgrade', 'get_upgrade_info', 'upgrade',
			'install', 'installed', 'not_installed', 'uninstall', 'save_module_info', 'module_detail',
			'change_receive_ban', 'install_success', 'set_site_welcome_module',
			'founder_update_modules', 'recycle', 'recycle_post', 'init_modules_logo', 'local_uninstall'
);
$do = in_array($do, $dos) ? $do : 'installed';

$permission_check = array(
	'see_module_manage_system_install' => permission_check_account_user('see_module_manage_system_install') ? 1 : 0,
	'see_module_manage_system_stop' => permission_check_account_user('see_module_manage_system_stop') ? 1 : 0,
);

if ('subscribe' == $do) {
	$module_uninstall_total = module_uninstall_total($module_support);

	$module_list = user_modules($_W['uid']);
	$subscribe_type = ext_module_msg_types();

	$subscribe_module = array();
	$receive_ban = $_W['setting']['module_receive_ban'];

	if (is_array($module_list) && !empty($module_list)) {
		foreach ($module_list as $module) {
			if (!empty($module['subscribes']) && is_array($module['subscribes'])) {
				$subscribe_module[$module['name']]['subscribe'] = $module['subscribes'];
				$subscribe_module[$module['name']]['title'] = $module['title'];
				$subscribe_module[$module['name']]['name'] = $module['name'];
				$subscribe_module[$module['name']]['subscribe_success'] = 2;
				$subscribe_module[$module['name']]['receive_ban'] = in_array($module['name'], $receive_ban) ? 1 : 0;
			}
		}
	}
	if ($_W['isajax']) {
		$result = array(
			'subscribe_module' => $subscribe_module,
			'subscribe_type' => $subscribe_type,
			'development' => $_W['config']['setting']['development'],
		);
		iajax(0, $result);
	}
}

if ('check_subscribe' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$module = module_fetch($module_name);
	if (empty($module)) {
		iajax(-1);
	}
	$obj = WeUtility::createModuleReceiver($module['name']);
	if (empty($obj)) {
		iajax(-1);
	}
	$obj->uniacid = $_W['uniacid'];
	$obj->acid = empty($_W['acid']) ? 0 : $_W['acid'];
	$obj->message = array(
		'event' => 'subscribe',
	);
	if (method_exists($obj, 'receive')) {
		@$obj->receive();
		iajax(0);
	} else {
		iajax(-1);
	}
}

if ('get_upgrade_info' == $do) {
	$module_name = safe_gpc_string($_GPC['name']);
	$module = module_fetch($module_name);
	$install_support = array();
	$site_info = $_W['setting']['site'];
	if (is_error($site_info)) {
		iajax(1, '获取站点信息失败: ' . $site_info['message']);
	}

	$module_all_support = module_support_type();
	if (!empty($module)) {
		foreach ($module_all_support as $key => $value) {
			if ($module[$key] == $value['support']) {
				if (PHONEAPP_TYPE_SIGN == $value['type']) {
					array_push($install_support, "ios", "android");
				} else {
					$install_support[] = $value['type'];
				}
			}
		}
	}
	if (APPLICATION_TYPE_TEMPLATES == $module['application_type']) {
		$manifest = ext_template_manifest($module_name, false);
	} else {
		$manifest = ext_module_manifest($module_name);
	}
	if (!empty($manifest['platform']['supports']) && in_array('app', $manifest['platform']['supports'])) {
		foreach ($manifest['platform']['supports'] as $key => $support) {
			if ('app' == $support) {
				$manifest['platform']['supports'][$key] = 'account';
			}
			if ('system_welcome' == $support) {
				$manifest['platform']['supports'][$key] = 'welcome';
			}
			if ('android' == $support || 'ios' == $support) {
				$manifest['platform']['supports'][$key] = 'phoneapp';
			}
		}
	}
	$result = array(
		'name' => $module_name,
		'upgrade' => false,
		'site_branch' => array(),
		'new_branch' => false,
		'branches' => array(),
		'from' => $module['from'],
		'id' => '',
		'system_shutdown' => '',
		'system_shutdown_delay_time' => '',
		'can_update' => '',
		'title' => 'cloud_test' == $module['from'] ? $module['title'] : $manifest['title'],
		'service_expiretime' => '',
		'version' => $module['version'],
		'application_type' => $module['application_type'],
		'logo' => $module['logo'],
		'install_support' => $install_support,
		'support' => empty($manifest['platform']['supports']) ? '' : $manifest['platform']['supports'],
		'appmarket' => '',
		'site_info' => array(
			'name' => $site_info['name'],
			'url' => $site_info['url'],
		),
	);
	if (!empty($manifest) && version_compare($manifest['application']['version'], $module['version'], '>')) {
		$result['upgrade'] = true;
		$result['best_version'] = $manifest['application']['version'];
	}
	iajax(0, $result);
}

if ('check_upgrade' == $do) {
	$module_upgrade = module_upgrade_info();
	if (is_error($module_upgrade)) {
		iajax(-1, $module_upgrade['message']);
	}
	cache_build_uninstalled_module();

	iajax(0, $module_upgrade);
}

if ('upgrade' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$has_new_support = safe_gpc_boolean($_GPC['has_new_support']); //是否安装新支持
	//判断模块相关配置和文件是否合法
	$module = table('modules')->getByName($module_name);
	if (empty($module)) {
		itoast('模块已经被卸载或是不存在！', '', 'error');
	}
	if (APPLICATION_TYPE_TEMPLATES == $module['application_type']) {
		$manifest = ext_template_manifest($module_name, false);
	} else {
		$manifest = ext_module_manifest($module_name);
	}

	if (empty($manifest)) {
		itoast('模块安装配置文件不存在或是格式不正确！', '', 'error');
	}
	$check_manifest_result = ext_manifest_check($module_name, $manifest);
	if (is_error($check_manifest_result)) {
		itoast($check_manifest_result['message'], '', 'error');
	}

	$check_file_result = ext_file_check($module_name, $manifest);
	if (is_error($check_file_result)) {
		itoast($check_file_result['message'], '', 'error');
	}
	//选择安装到应用权限组
	if ($has_new_support && empty($_GPC['upgrade_flag'])) {
		$module_group = uni_groups();
		template('system/module-group');
		exit;
	}

	if (!empty($manifest['platform']['plugin_list'])) {
		pdo_delete('modules_plugin', array('main_module' => $manifest['application']['identifie']));
		foreach ($manifest['platform']['plugin_list'] as $plugin) {
			pdo_insert('modules_plugin', array('main_module' => $manifest['application']['identifie'], 'name' => $plugin));
		}
	}

	$module_upgrade = ext_module_convert($manifest);
	unset($module_upgrade['name'], $module_upgrade['title'], $module_upgrade['ability'], $module_upgrade['description']);

	//处理模块菜单
	$points = ext_module_bindings();
	$bindings = array_elements(array_keys($points), $module_upgrade, false);
	foreach ($points as $point_name => $point_info) {
		unset($module_upgrade[$point_name]);
		if (is_array($bindings[$point_name]) && !empty($bindings[$point_name])) {
			foreach ($bindings[$point_name] as $entry) {
				$entry['module'] = $manifest['application']['identifie'];
				$entry['entry'] = $point_name;
				if ('page' == $point_name && !empty($wxapp_support)) {
					$entry['url'] = $entry['do'];
					$entry['do'] = '';
				}
				if ($entry['title'] && $entry['do']) {
					//保存xml里面包含的do,最后删除数据库中废弃的do
					$not_delete_do[] = $entry['do'];
					$module_binding = table('modules_bindings')->getByEntryDo($module_name, $point_name, $entry['do']);
					if (!empty($module_binding)) {
						pdo_update('modules_bindings', $entry, array('eid' => $module_binding['eid']));
						continue;
					}
				} elseif ($entry['call']) {
					$not_delete_call[] = $entry['call'];
					$module_binding = table('modules_bindings')->getByEntryCall($module_name, $point_name, $entry['call']);
					if (!empty($module_binding)) {
						pdo_update('modules_bindings', $entry, array('eid' => $module_binding['eid']));
						continue;
					}
				}
				pdo_insert('modules_bindings', $entry);
			}
			//删除废弃的do
			$modules_bindings_table = table('modules_bindings');
			$modules_bindings_table
				->searchWithModuleEntry($manifest['application']['identifie'], $point_name)
				->where('call', '')
				->where('do !=', empty($not_delete_do) ? '' : $not_delete_do)
				->delete();
			//删除废弃的call
			$modules_bindings_table
				->searchWithModuleEntry($manifest['application']['identifie'], $point_name)
				->where('do', '')
				->where('title', '')
				->where('call !=', empty($not_delete_call) ? '' : $not_delete_call)
				->delete();
			unset($not_delete_do, $not_delete_call);
		} else {
			table('modules_bindings')->searchWithModuleEntry($manifest['application']['identifie'], $point_name)->delete();
		}
	}

	if ($packet['schemes']) {
		foreach ($packet['schemes'] as $remote) {
			$remote['tablename'] = trim(tablename($remote['tablename']), '`');
			$local = db_table_schema(pdo(), $remote['tablename']);
			$sqls = db_table_fix_sql($local, $remote);
			foreach ($sqls as $sql) {
				pdo_run($sql);
			}
		}
	}

	ext_module_run_script($manifest, 'upgrade');

	$module_upgrade['permissions'] = iserializer($module_upgrade['permissions']);
	if (!empty($manifest['application']['cloud_setting'])) {
		$module_upgrade['settings'] = 2;
	} else {
		$module_upgrade['settings'] = empty($manifest['application']['setting']) ? STATUS_OFF : STATUS_ON;
	}

	if (!empty($_GPC['support'])) {
		$support = explode(',', safe_gpc_string($_GPC['support']));
	}

	if ($has_new_support) {
		$module_upgrade['cloud_record'] = STATUS_OFF;
	}
	pdo_update('modules', $module_upgrade, array('name' => $module_name));

	$post_groups = safe_gpc_array($_GPC['group']);
	if ($_GPC['upgrade_flag'] && !empty($post_groups)) {
		$module_upgrade['name'] = $module_name;
		foreach ($post_groups as $groupid) {
			foreach ($support as $val) {
				module_add_to_uni_group($module_upgrade, $groupid, $val);
			}
		}
	}

	cache_build_account_modules();
	if (!empty($module_upgrade['subscribes'])) {
		ext_check_module_subscribe($module_name);
	}
	cache_delete(cache_system_key('cloud_transtoken'));
	cache_build_module_info($module_name);
	cache_build_uni_group();
	if ($has_new_support) {
		itoast('模块安装成功！', url('module/manage-system/installed'), 'success');
	} else {
		itoast('模块更新成功！', url('module/manage-system/installed'), 'success');
	}
}

if ('install' == $do) {
	if (empty($_W['isadmin'])) {
		if ($_W['isajax']) {
			iajax(-1, '您没有安装模块的权限');
		}
		itoast('您没有安装模块的权限', '', 'error');
	}
	$module_name = safe_gpc_string($_GPC['module_name']);
	$application_type = in_array($_GPC['application_type'], array(APPLICATION_TYPE_TEMPLATES, APPLICATION_TYPE_MODULE)) ? $_GPC['application_type'] : APPLICATION_TYPE_MODULE;
	$installed_module = table('modules')->getByName($module_name);
	if (!empty($_GPC['install_module_support'])) {
		$module_support_name = $_GPC['install_module_support'];
	}
	if (APPLICATION_TYPE_TEMPLATES == $installed_module['application_type'] || APPLICATION_TYPE_TEMPLATES == $application_type) {
		$manifest = ext_template_manifest($module_name, false);
	} else {
		$manifest = ext_module_manifest($module_name);
	}
	$module_is_cloud = 'local';
	if (!empty($manifest)) {
		if (!empty($installed_module)) {
			$has_new_support = module_check_notinstalled_support($installed_module, $manifest['platform']['supports']);
			if (empty($has_new_support)) {
				if ($_W['isajax']) {
					iajax(-1, '模块已经安装或是唯一标识已存在！');
				}
				itoast('模块已经安装或是唯一标识已存在！', '', 'error');
			} else {
				header('location: ' . url('module/manage-system/upgrade', array('support' => $module_support_name, 'module_name' => $module_name, 'has_new_support' => 1)));
				exit;
			}
		}
	}

	if (APPLICATION_TYPE_TEMPLATES == $installed_module['application_type'] || APPLICATION_TYPE_TEMPLATES == $application_type) {
		unset($manifest['settings']);
	}
	$module = ext_module_convert($manifest);
	if (APPLICATION_TYPE_TEMPLATES == $installed_module['application_type'] || APPLICATION_TYPE_TEMPLATES == $application_type) {
		$module['version'] = $packet['version'];
		$module['logo'] = 'app/themes/' . $module['name'] . '/preview.jpg';
	} else {
		if (!empty($manifest['platform']['main_module'])) {
			$main_module_fetch = module_fetch($manifest['platform']['main_module']);
			if (empty($main_module_fetch)) {
				if ($_W['isajax']) {
					iajax(-1, '请先安装主模块后再安装插件');
				}
				itoast('请先安装主模块后再安装插件', url('module/manage-system/installed'), 'error', array(array('title' => '查看主程序', 'url' => url('module/manage-system/module_detail', array('name' => $manifest['platform']['main_module'])))));
			}
			$plugin_exist = table('modules_plugin')->getPluginExists($manifest['platform']['main_module'], $manifest['application']['identifie']);
			if (empty($plugin_exist)) {
				pdo_insert('modules_plugin', array('main_module' => $manifest['platform']['main_module'], 'name' => $manifest['application']['identifie']));
			}
		}

		$check_manifest_result = ext_manifest_check($module_name, $manifest);
		if (is_error($check_manifest_result)) {
			if ($_W['isajax']) {
				iajax(-1, $check_manifest_result['message']);
			}
			itoast($check_manifest_result['message'], '', 'error');
		}
		$check_file_result = ext_file_check($module_name, $manifest);
		if (is_error($check_file_result)) {
			if ($_W['isajax']) {
				iajax(-1, '模块缺失文件，请检查模块文件中site.php, processor.php, module.php, receiver.php 文件是否存在！');
			}
			itoast('模块缺失文件，请检查模块文件中site.php, processor.php, module.php, receiver.php 文件是否存在！', url('module/manage-system/installed'), 'error');
		}

		if (file_exists(IA_ROOT . '/addons/' . $module['name'] . '/icon-custom.jpg')) {
			$module['logo'] = 'addons/' . $module['name'] . '/icon-custom.jpg';
		} else {
			$module['logo'] = 'addons/' . $module['name'] . '/icon.jpg';
		}
	}
	$post_groups = safe_gpc_array($_GPC['group']);
	if (APPLICATION_TYPE_TEMPLATES == $installed_module['application_type'] || APPLICATION_TYPE_TEMPLATES == $application_type) {
		$module['account_support'] = MODULE_SUPPORT_ACCOUNT;
	} else {
		if (!empty($manifest['platform']['plugin_list'])) {
			foreach ($manifest['platform']['plugin_list'] as $plugin) {
				pdo_insert('modules_plugin', array('main_module' => $manifest['application']['identifie'], 'name' => $plugin));
			}
		}
		$points = ext_module_bindings();
		if (!empty($points)) {
			$bindings = array_elements(array_keys($points), $module, false);
			table('modules_bindings')->deleteByName($manifest['application']['identifie']);
			foreach ($points as $name => $point) {
				unset($module[$name]);
				if (is_array($bindings[$name]) && !empty($bindings[$name])) {
					foreach ($bindings[$name] as $entry) {
						$entry['module'] = $manifest['application']['identifie'];
						$entry['entry'] = $name;
						if ('page' == $name && !empty($wxapp_support)) {
							$entry['url'] = $entry['do'];
							$entry['do'] = '';
						}
						table('modules_bindings')->fill($entry)->save();
					}
				}
			}
		}

		$module['permissions'] = iserializer($module['permissions']);

		$module_subscribe_success = true;
		if (!empty($module['subscribes'])) {
			$subscribes = iunserializer($module['subscribes']);
			if (!empty($subscribes)) {
				$module_subscribe_success = ext_check_module_subscribe($module['name']);
			}
		}

		if (!empty($manifest['application']['cloud_setting']) || !empty($manifest['cloudsetting'])) {
			$module['settings'] = 2;
		} else {
			$module['settings'] = empty($manifest['application']['setting']) ? STATUS_OFF : STATUS_ON;
		}

		if ($packet['schemes']) {
			foreach ($packet['schemes'] as $remote) {
				$remote['tablename'] = trim(tablename($remote['tablename']), '`');
				$local = db_table_schema(pdo(), $remote['tablename']);
				$sqls = db_table_fix_sql($local, $remote);
				foreach ($sqls as $sql) {
					pdo_run($sql);
				}
			}
		}

		ext_module_run_script($manifest, 'install');
	}
	$module['application_type'] = $application_type;
	$module['title_initial'] = get_first_pinyin($module['title']);
	$module['from'] = $module_is_cloud;
	$module['createtime'] = TIMESTAMP;
	$module['status'] = empty($module_info['status']) || empty($module_info['branches'][$module_info['branch_id']]['status']) || ($module_info['system_shutdown_delay_time'] && TIMESTAMP > $module_info['system_shutdown_delay_time']) ? STATUS_OFF : STATUS_ON;
	if (pdo_insert('modules', $module)) {
		if ($_GPC['flag'] && !empty($post_groups) && $module['name']) {
			foreach ($post_groups as $groupid) {
				foreach ($manifest['platform']['supports'] as $support_name) {
					module_add_to_uni_group($module, $groupid, $support_name);
				}
			}
		}
		cache_build_module_subscribe_type();
		cache_build_module_info($module_name);
		cache_build_uni_group();
		cache_delete(cache_system_key('user_modules', array('uid' => $_W['uid'])));
		itoast('模块安装成功！', url('module/manage-system/installed'), 'success');
	} else {
		itoast('模块安装失败, 请联系模块开发者！');
	}
}

if ('change_receive_ban' == $do) {
	$module_name = safe_gpc_string($_GPC['module_name']);
	$module_exist = module_fetch($module_name);
	if (empty($module_exist)) {
		iajax(-1, '模块不存在', '');
	}
	if (!is_array($_W['setting']['module_receive_ban'])) {
		$_W['setting']['module_receive_ban'] = array();
	}
	if (in_array($module_name, $_W['setting']['module_receive_ban'])) {
		unset($_W['setting']['module_receive_ban'][$module_name]);
	} else {
		$_W['setting']['module_receive_ban'][$module_name] = $module_name;
	}
	setting_save($_W['setting']['module_receive_ban'], 'module_receive_ban');
	cache_build_module_subscribe_type();
	cache_build_module_info($module_name);
	iajax(0, '更新成功');
}

if ('save_module_info' == $do) {
	$module_name = safe_gpc_string($_GPC['name']);
	if (empty($module_name)) {
		iajax(-1, '应用不存在！');
	}
	$module = module_fetch($module_name);
	if (empty($module)) {
		iajax(-1, '应用不存在！');
	}
	$manifest = ext_module_manifest($module_name);
	$module_update = array();
	$title = empty($_GPC['moduleinfo']['title']) ? '' : safe_gpc_string($_GPC['moduleinfo']['title']);
	if (!empty($title)) {
		if (strlen($title) > 100) {
			iajax(-1, '标题不可超过100个字符!');
		}
		$module_update['title'] = $title;
		$module_update['title_initial'] = get_first_pinyin($title);
	}
	$ability = empty($_GPC['moduleinfo']['ability']) ? '' : safe_gpc_string($_GPC['moduleinfo']['ability']);
	if (!empty($ability)) {
		$module_update['ability'] = $ability;
	}
	$description = empty($_GPC['moduleinfo']['description']) ? '' : safe_gpc_string($_GPC['moduleinfo']['description']);
	if (!empty($description)) {
		$module_update['description'] = $description;
	}
	$logo = empty($_GPC['moduleinfo']['logo']) ? '' : safe_gpc_url($_GPC['moduleinfo']['logo'], false);
	if (!empty($logo)) {
		$module_update['logo'] = $logo;
	}
	if (empty($module_update)) {
		iajax(-1, '无有效修改参数！');
	}
	$result = pdo_update('modules', $module_update, array('name' => $module_name));
	if ($logo != $module['logo']) {
		$image_destination_url = IA_ROOT . '/addons/' . $module_name . '/icon-custom.jpg';
		if (APPLICATION_TYPE_TEMPLATES == $module['application_type']) {
			$image_destination_url = IA_ROOT . '/app/themes/' . $module_name . '/icon-custom.jpg';
		}
		utility_image_rename($logo, $image_destination_url);
	}
	cache_build_module_info($module_name);
	if (!empty($result)) {
		iajax(0, '更新成功');
	}
	iajax(-1, '更新失败');
}

if ('module_detail' == $do) {
	$module_name = safe_gpc_string($_GPC['name']);
	$module_info = module_fetch($module_name);
	if (empty($module_info)) {
		if ($_W['isajax']) {
			iajax(-1, '模块未安装或是已经被删除');
		}
		itoast('模块未安装或是已经被删除', '', 'error');
	}

	$manifest = ext_module_manifest($module_name);
	$local_upgrade_info = array();
	if (!empty($manifest)) {
		if (version_compare($manifest['application']['version'], $module_info['version'], '>')) {
			$local_upgrade_info = array(
				'name' => $module_name,
				'upgrade' => true,
				'site_branch' => array(),
				'branches' => array(),
				'new_branch' => false,
				'from' => 'local',
				'best_version' => $manifest['application']['version'],
			);
		}
	}

	//计算此模块除了当前支持，还支持哪些
	foreach ($module_info as $key => $value) {
		if ($key != $module_support . '_support' && strexists($key, '_support') && MODULE_SUPPORT_ACCOUNT == $value) {
			$module_info['relation'][] = $key;
		}
	}

	if (!empty($module_info['main_module'])) {
		$main_module = module_fetch($module_info['main_module']);
	}
	if (!empty($module_info['plugin_list'])) {
		$module_info['plugin_list'] = module_get_plugin_list($module_name);
	}

	$module_group_list = pdo_getall('uni_group', array('uniacid' => 0, 'uid' => 0));
	$module_group = array();
	if (!empty($module_group_list)) {
		foreach ($module_group_list as $group) {
			if (user_is_vice_founder() && $group['owner_uid'] != $_W['uid']) {
				continue;
			}
			$group['modules'] = iunserializer($group['modules']);
			if (is_array($group['modules'])) {
				foreach ($group['modules'] as $modulenames) {
					if (is_array($modulenames) && in_array($module_name, $modulenames)) {
						$module_group[] = $group;
						break;
					}
				}
			}
		}
	}
	$subscribes_type = ext_module_msg_types();
	if ($_W['isajax']) {
		$result = array(
			'module_info' => $module_info,
			'subscribes_type' => $subscribes_type,
			'module_all_support' => $module_all_support,
			'local_upgrade_info' => $local_upgrade_info
		);
		iajax(0, $result);
	}
}

//卸载模块
if ('uninstall' == $do) {
	if (!$_W['isadmin']) {
		itoast('您没有卸载模块的权限！');
	}
	$application_type = isset($_GPC['application_type']) && in_array($_GPC['application_type'], array(APPLICATION_TYPE_MODULE, APPLICATION_TYPE_TEMPLATES)) ? intval($_GPC['application_type']) : 0;
	$name = safe_gpc_string(trim($_GPC['module_name']));
	if ('default' == $name && APPLICATION_TYPE_MODULE == $application_type) {
		itoast('默认模板不能卸载');
	}
	$module = module_fetch($name, false);

	if (!empty($module['issystem'])) {
		itoast('系统模块不能卸载！');
	}

	if (empty($module)) {
		itoast('应用不存在或是已经卸载！');
	}

	$confirm = empty($_GPC['confirm']) ? STATUS_OFF : STATUS_ON;
	if (!isset($confirm)) {
		$message = '';
		if ($module['isrulefields']) {
			$message .= '是否删除相关规则和统计分析数据<div><a class="btn btn-primary" style="width:80px;" href="' . url('module/manage-system/uninstall', array('module_name' => $name, 'confirm' => 1, 'support' => $module_support_name)) . '">是</a> &nbsp;&nbsp;<a class="btn btn-default" style="width:80px;" href="' . url('module/manage-system/uninstall', array('support' => $module_support_name, 'module_name' => $name, 'confirm' => 0)) . '">否</a></div>';
		}
		if (!empty($message)) {
			message($message, '', 'tips');
		}
	}
	ext_module_clean($name, $confirm);
	if ('cloud_test' != $module['from']) {
		ext_execute_uninstall_script($name);
	}
	cache_build_module_subscribe_type();

	$uni_groups_table = table('uni_group');
	$uni_gruops = $uni_groups_table->where(array('modules LIKE' => "%$name%"))->getall();
	foreach ($uni_gruops as &$uni_gruop) {
		$modules = iunserializer($uni_gruop['modules']);
		foreach ($modules as $type_sign => &$module) {
			foreach ($module as $key => $value) {
				if ($name == $value) {
					unset($module[$key]);
					break;
				}
			}
			break;
		}
		$uni_groups_table->where('id', $uni_gruop['id'])->fill(array('modules' => iserializer($modules)))->save();
		unset($module);
	}
	unset($uni_gruop);

	$uni_account_extra_module_table = table('uni_account_extra_modules');
	$uni_account_extra_modules = $uni_account_extra_module_table->where(array('modules LIKE' => "%$name%"))->getall();
	foreach ($uni_account_extra_modules as &$uni_account_extra_module) {
		$modules = iunserializer($uni_account_extra_module['modules']);
		foreach ($modules as $type_sign => &$module) {
			foreach ($module as $key => $value) {
				if ($name == $value) {
					unset($module[$key]);
					break;
				}
			}
			break;
		}
		$uni_account_extra_module_table->where('id', $uni_account_extra_module['id'])->fill(array('modules' => iserializer($modules)))->save();
		unset($module);
	}
	unset($uni_account_extra_module);

	table('users_extra_modules')->where(array('module_name' => $name))->delete();
	table('system_welcome_binddomain')->where(array('module_name' => $name))->delete();

	if ($module_support_name == 'wxapp_support') {
		$wxapp_version_table = table('wxapp_versions');
		$wxapp_versions = $wxapp_version_table->where(array('modules LIKE' => "%$name%"))->getall();
		foreach ($wxapp_versions as $wxapp_version) {
			$modules = iunserializer($wxapp_version['modules']);
			foreach ($modules as $key => $module) {
				if ($key != $name) {
					continue;
				}
				unset($modules[$key]);
				break;
			}
			$wxapp_version_table->where(array('id' => $wxapp_version['id']))->fill(array('modules' => iserializer($modules)))->save();
			cache_delete(cache_system_key('miniapp_version', array('version_id' => $wxapp_version['id'])));
		}
	}
	if (APPLICATION_TYPE_TEMPLATES == $application_type) {
		pdo_delete('site_styles', array('templateid' => intval($module['mid'])));
		pdo_delete('site_styles_vars', array('templateid' => intval($module['mid'])));
	}
	cache_build_account_modules(0, $_W['uid']);
	cache_build_module_info($name);
	module_upgrade_info();
	itoast('卸载成功！', url('module/manage-system/recycle', array('type' => MODULE_RECYCLE_INSTALL_DISABLED)), 'success');
}

//停用删除模块
if ('recycle_post' == $do) {
	$name = safe_gpc_string($_GPC['module_name']);
	if (empty($name)) {
		itoast('应用不存在或是已经被删除', referer(), 'error');
	}
	$module_exist = table('modules')->getByName($name);
	$module = $module_exist;
	$supports = array();
	$module_type = 'uninstall';
	if (empty($module)) {
		$module_type = 'recycle';
		$module = pdo_get('modules_recycle', array('name' => $name));
	}
	if (empty($module)) {
		$module_type = 'delete';
		$module = pdo_get('modules_cloud', array('name' => $name));
	}
	
	foreach ($module_all_support as $support => $value) {
		switch ($module_type) {
			case 'uninstall':
			case 'delete':
				if (MODULE_SUPPORT_ACCOUNT == $module[$support]) {
					$supports[] = $support;
				}
				break;
			case 'recycle':
				if (MODULE_NONSUPPORT_ACCOUNT == $module[$support]) {
					$supports[] = $support;
				}
				break;
		}
	}
	$recycle_table = table('modules_recycle');
	foreach ($supports as $support) {
		if (!in_array($support, array_keys($module_all_support))) {
			continue;
		}
		$recycle_table->searchWithSupport($support);
		if (!empty($module_exist[$support]) && 2 == $module_exist[$support]) {
			//已安装，停用,type = 1
			$module_recycle = $recycle_table->searchWithNameType($name, 1)->get();
			if (empty($module_recycle)) {
				$msg = '模块已停用!';
				module_recycle($name, MODULE_RECYCLE_INSTALL_DISABLED, $support);
			} else {
				$msg = '模块已恢复!';
				module_cancel_recycle($name, MODULE_RECYCLE_INSTALL_DISABLED, $support);
			}
			cache_write(cache_system_key('user_modules', array('uid' => $_W['uid'])), array());
			cache_build_module_info($name);
		} else {
			//未安装, 删除,type = 2
			$module_recycle = $recycle_table->searchWithNameType($name, 2)->get();
			if (empty($module_recycle)) {
				$msg = '模块已放入回收站!';
				module_recycle($name, MODULE_RECYCLE_UNINSTALL_IGNORE, $support);
			} else {
				$msg = '模块已恢复!';
				module_cancel_recycle($name, MODULE_RECYCLE_UNINSTALL_IGNORE, $support);
			}
		}
	}
	if (in_array('wxapp_support', $supports)) {
		$wxapp_version_table = table('wxapp_versions');
		$wxapp_versions = $wxapp_version_table->where(array('modules LIKE' => "%$name%"))->getall();
		foreach ($wxapp_versions as $wxapp_version) {
			cache_delete(cache_system_key('miniapp_version', array('version_id' => $wxapp_version['id'])));
		}
	}
	itoast($msg, referer(), 'success');
}

if ('recycle' == $do) {
	$type = intval($_GPC['type']);
	$support = empty($_GPC['support']) ? 'all' : safe_gpc_string($_GPC['support']);
	$title = empty($_GPC['title']) ? '' : safe_gpc_string($_GPC['title']);
	$letter = empty($_GPC['letter']) ? '' : safe_gpc_string($_GPC['letter']);

	$pageindex = empty($_GPC['page']) ? 1 : safe_gpc_int($_GPC['page']);
	$pagesize = 15;

	$module_recycle_table = table('modules_recycle');

	$fields = 'all' == $support ? 'a.title, a.title_initial, a.logo, a.version, b.*' : 'a.*, b.type';
	if (MODULE_RECYCLE_INSTALL_DISABLED == $type) {
		$fields .= 'all' == $support ? ', a.from' : '';
		$module_recycle_table->searchWithModules($fields);
	} else {
		$fields .= 'all' == $support ? ', a.cloud_id, a.service_expire_time' : '';
		$module_recycle_table->searchWithModulesCloud($fields);
	}

	$module_recycle_table->where('b.type', $type);
	if ('all' != $support) {
		$module_recycle_table->where("b.{$support}", 1);
	}

	if (!empty($title)) {
		$module_recycle_table->where('a.title LIKE', "%{$title}%");
	}

	if (!empty($letter) && 1 == strlen($letter)) {
		$module_recycle_table->where('a.title_initial', $letter);
	}

	$modulelist = $module_recycle_table->getall();
	if (!empty($modulelist)) {
		foreach ($modulelist as $modulename => $module) {
			if (MODULE_RECYCLE_INSTALL_DISABLED == $type) {
				if (empty($_W['config']['setting']['local_dev'])) {
					if ('local' == $module['from']) {
						unset($modulelist[$modulename]);
						continue;
					}
				} else {
					if ('local' != $module['from']) {
						unset($modulelist[$modulename]);
						continue;
					}
				}
			} else {
				if (empty($_W['config']['setting']['local_dev'])) {
					if (empty($module['cloud_id'])) {
						unset($modulelist[$modulename]);
						continue;
					}
				} else {
					if (!empty($module['cloud_id'])) {
						unset($modulelist[$modulename]);
						continue;
					}
				}
			}
			$module_info = module_fetch($module['name'], false);
			if (empty($module_info)) {
				$module_info = table('modules_cloud')->getByName($module_info['name']);
				if (!empty($module_info['main_module_name'])) {
					$main_module_info = table('modules_cloud')->getByName($module_info['main_module_name']);
					$module_info['main_module'] = $main_module_info['name'];
					$module_info['main_module_logo'] = $main_module_info['title'];
					$module_info['main_module_title'] = $main_module_info['logo'];
				}
			}
			$modulelist[$modulename]['main_module'] = empty($module_info['main_module']) ? '' : $module_info['main_module'];
			$modulelist[$modulename]['main_module_logo'] = empty($module_info['main_module_logo']) ? '' : $module_info['main_module_logo'];
			$modulelist[$modulename]['main_module_title'] = empty($module_info['main_module_title']) ? '' : $module_info['main_module_title'];
			$modulelist[$modulename]['logo'] = empty($module['logo']) ? '' : tomedia($module['logo']);
		}
	}

	$total = count((array)$modulelist);
	$pager = pagination($total, $pageindex, $pagesize, '', array('ajaxcallback' => true, 'callbackfuncname' => 'loadMore'));

	$module_uninstall_total = module_uninstall_total($module_support);
}

if ('installed' == $do) {
	$module_list = module_installed_list($module_support);
	if (!empty($module_list)) {
		foreach ($module_list as $key => &$module) {
			if (!empty($module['issystem'])) {
				unset($module_list[$key]);
			}
			if ($module['application_type'] == APPLICATION_TYPE_TEMPLATES && $_GPC['application_type'] == APPLICATION_TYPE_MODULE) {
				unset($module_list[$key]);
			}
			if ((empty($module['application_type']) || $module['application_type'] == APPLICATION_TYPE_MODULE) && !empty($_GPC['application_type']) && $_GPC['application_type'] == APPLICATION_TYPE_TEMPLATES) {
				unset($module_list[$key]);
			}
			if ('all' != $module_support) {
				$module['support_name'] = $module_all_support[$module_support_name]['type_name'];
			}
			if (!empty($module['label'])) {
				$module['label'] = iunserializer($module['label']);
			}
		}
		unset($module);
	}
	$pager = pagination(count($module_list), 1, 15, '', array('ajaxcallback' => true, 'callbackfuncname' => 'loadMore'));
	$module_uninstall_total = module_uninstall_total($module_support);
}

if ('not_installed' == $do) {
	$title = empty($_GPC['title']) ? '' : safe_gpc_string($_GPC['title']);
	$letter = empty($_GPC['letter']) ? '' : safe_gpc_string($_GPC['letter']);
	$order = empty($_GPC['order']) ? '' : safe_gpc_string($_GPC['order']);
	$pageindex = empty($_GPC['page']) ? 1 : safe_gpc_int($_GPC['page']);
	$pagesize = 15;

	cache_build_uninstalled_module();

	$module_cloud_table = table('modules_cloud');

	if (!empty($title)) {
		$module_cloud_table->where('title LIKE', "%{$title}%");
	}
	if (!empty($letter) && 1 == strlen($letter)) {
		$module_cloud_table->where('title_initial', $letter);
	}
	if ('all' != $module_support) {
		$module_cloud_table->where($module_support . '_support', 2);
	}
	if (empty($_W['config']['setting']['local_dev'])) {
		$module_cloud_table->where('install_status', MODULE_CLOUD_UNINSTALL);
	} else {
		$module_cloud_table->where('install_status', MODULE_LOCAL_UNINSTALL);
	}
	$order_type = 'buytime_desc' == $order ? 'desc' : 'asc';
	$module_cloud_table->orderby('buytime', $order_type);
	$modulelist = $module_cloud_table->getall();

	if (!empty($modulelist)) {
		//模块停用删除数据
		$modulenames = array();
		foreach ($modulelist as $key => $module) {
			$main_module_info = array();
			if (!empty($module['label'])) {
				$modulelist[$key]['label'] = iunserializer($module['label']);
			}
			if (!empty($module['name']) && !in_array($module['name'], $modulenames)) {
				$modulenames[] = $module['name'];
			}
			if (!empty($module['main_module_name'])) {
				$main_module_info = module_fetch($module['main_module_name']);
				if (empty($main_module_info)) {
					$main_module_info = table('modules_cloud')->getByName($module['main_module_name']);
				}
			}
			$modulelist[$key]['main_module_title'] = empty($main_module_info['title']) ? '' : $main_module_info['title'];
			$modulelist[$key]['system_shutdown'] = empty($module['system_shutdown_time']) ? 1 : 2;
			$modulelist[$key]['system_shutdown_delay_time'] = empty($module['system_shutdown_time']) ? 0 : date('Y-m-d', $module['system_shutdown_time']);
			$modulelist[$key]['service_expire'] = empty($module['service_expire_time']) || $module['service_expire_time'] > TIMESTAMP ? 0 : 1;
			$modulelist[$key]['from'] = empty($module['cloud_id']) ? 'local' : 'cloud';
		}
		$module_recycle_support = array();
		if ($modulenames) {
			$modules_recycle = table('modules_recycle')->getByName($modulenames, '');
			if (!empty($modules_recycle)) {
				foreach ($modules_recycle as $info) {
					foreach ($module_all_support as $support => $value) {
						if (empty($module_recycle_support[$info['name']][$support])) {
							$module_recycle_support[$info['name']][$support] = $info[$support];
						}
					}
				}
			}
		}
		//unset 数据中已停用删除支持, 和 系统首页支持
		foreach ($modulelist as $key => $module) {
			$is_unset = true;
			foreach ($module_all_support as $support => $value) {
				if (!empty($module_recycle_support[$module['name']][$support])) {
					$module[$support] = $value['not_support'];
				}
				if ($module[$support] == $value['support']) {
					$is_unset = false;
				}
			}
			if ($is_unset) {
				unset($modulelist[$key]);
			}
		}
	}
	$module_uninstall_total = module_uninstall_total($module_support);
	$pager = pagination(count($modulelist), $pageindex, $pagesize, '', array('ajaxcallback' => true, 'callbackfuncname' => 'loadMore'));
}

if ('init_modules_logo' == $do) {
	$modules = pdo_fetchall('SELECT `name`,`application_type` FROM ' . tablename('modules') . ' WHERE issystem!=1');
	foreach ($modules as $key => $val) {
		if (APPLICATION_TYPE_TEMPLATES == $val['application_type']) {
			$val['logo'] = 'app/themes/' . $val['name'] . '/preview.jpg';
			if (file_exists(IA_ROOT . '/app/themes/' . $val['name'] . '/preview-custom.jpg')) {
				$val['logo'] = 'app/themes/' . $val['name'] . '/preview-custom.jpg';
			}
		} else {
			$val['logo'] = 'addons/' . $val['name'] . '/icon.jpg';
			if (file_exists(IA_ROOT . '/addons/' . $val['name'] . '/icon-custom.jpg')) {
				$val['logo'] = 'addons/' . $val['name'] . '/icon-custom.jpg';
			}
		}
		pdo_update('modules', array('logo' => $val['logo']), array('name' => $val['name']));
	}
	iajax(0, '更新成功', url('module/manage-system/installed'));
}

if ('local_uninstall' == $do) {
	if (STATUS_OFF == $_W['config']['setting']['development']) {
		iajax(-1, '请先开启开发模式！');
	}
	$module_list = module_installed_list($module_support);
	foreach ($module_list as $key => $module) {
		if (!empty($module['issystem'])) {
			unset($module_list[$key]);
		}
		if ('local' != $module['from']) {
			unset($module_list[$key]);
		}
		if (1632647471 > $module['createtime']) {
			unset($module_list[$key]);
		}
	}
	$module_uninstall_total = module_uninstall_total($module_support);
	$message = array(
		'list' => $module_list,
		'module_uninstall_total' => $module_uninstall_total,
		'development' => $_W['config']['setting']['development'],
	);
	iajax(0, $message);
}

template('module/manage-system');
