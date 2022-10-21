<?php
/**
 * 后台菜单管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('system');

$dos = array('display', 'post', 'display_status', 'delete', 'change_displayorder');
$do = in_array($do, $dos) ? $do : 'display';

$system_top_menu = array_keys(system_menu());
$system_menu = system_menu_permission_list();

$account_all_type = uni_account_type();
$account_all_type_sign = array_keys(uni_account_type_sign());
$not_operate_menu = array_merge($account_all_type_sign, array('site', 'myself'));

$not_show_menu = array('site', 'system', 'account_manage', 'module_manage', 'permission', 'user_manage');
//获取全部permission_name，方便判断是否是系统菜单
$system_menu_permission = array();
if (!empty($system_menu)) {
	foreach ($system_menu as $menu_name => $menu) {
		if (in_array($menu_name, $system_top_menu)) {
			$system_menu_permission[] = $menu_name;
		}
		if (in_array($menu_name, $not_show_menu)) {
			unset($system_menu[$menu_name]);
			continue;
		}
		if (in_array($menu_name, $not_operate_menu)) {
			$system_menu[$menu_name]['is_operate'] = 0;
		} else {
			$system_menu[$menu_name]['is_operate'] = 1;
		}
		if (!empty($menu['section'])) {
			foreach ($menu['section'] as $section_name => $section) {
				if (!empty($section['menu'])) {
					foreach ($section['menu']  as $permission_name => $sub_menu) {
						if ($menu_name == 'account' && $section_name == 'profile') {
							$sub_section_name = explode('_', $permission_name);
							if ($sub_section_name[0] != $section_name) {
								$system_menu[$sub_section_name[0]]['section'][$section_name]['menu'][$permission_name] = $sub_menu;
								$system_menu[$sub_section_name[0]]['section'][$section_name]['title'] = $section['title'];
								unset($system_menu[$menu_name]['section'][$section_name]['menu'][$permission_name]);
							}
						}
						if ($sub_menu['is_system']) {
							$system_menu_permission[] = $sub_menu['permission_name'];
						}
					}
				}
			}
		}
	}
}
if ('display' == $do) {
	template('system/menu');
}
if ('post' == $do) {
	$id = empty($_GPC['id']) ? 0 : intval($_GPC['id']);
	if ('platform_module' == safe_gpc_string($_GPC['group'])) {
		iajax(-1, '应用模块下不可添加下级分类！', referer());
	}
	$menu = array(
		'title' => safe_gpc_string($_GPC['title']),
		'url' => empty($_GPC['url']) ? '' : safe_gpc_url($_GPC['url']),
		'permission_name' => empty($_GPC['permissionName']) ? '' : safe_gpc_string($_GPC['permissionName']),
		'is_system' => empty($_GPC['isSystem']) ? '' : intval($_GPC['isSystem']),
		'displayorder' => empty($_GPC['displayorder']) ? 0 : intval($_GPC['displayorder']),
		'type' => 'url',
		'icon' => empty($_GPC['icon']) ? '' : safe_gpc_string($_GPC['icon']),
	);
	if (empty($menu['title'])) {
		iajax(-1, '请输入菜单名称', referer());
	}
	if (empty($menu['url'])) {
		iajax(-1, '请输入正确的链接地址', referer());
	}
	if (empty($menu['permission_name'])) {
		iajax(-1, '请输入菜单标识', referer());
	}
	if (!preg_match('/^[a-zA-Z0-9_]+$/', $menu['permission_name'], $match)) {
		iajax(-1, '菜单标识只能是数字、字母、下划线', referer());
	}
	if (in_array($menu['permission_name'], $system_menu_permission)) {
		$menu['is_system'] = 1;
		unset($menu['url']);
	} else {
		$menu['group_name'] = safe_gpc_string($_GPC['group']);
		$menu['is_system'] = 0;

		$menu_db = pdo_get('core_menu', array('permission_name' => $menu['permission_name']));
		if (!empty($menu_db) && $menu_db['id'] != $id) {
			iajax(-1, '菜单标识不得重复请更换', referer());
		}
	}
	$permission_name = $menu['permission_name'];
	$menu_db = pdo_get('core_menu', array('permission_name' => $permission_name));
	if (!empty($menu_db)) {
		unset($menu['permission_name']);
		$menu['group_name'] = $menu_db['group_name'];
		pdo_update('core_menu', $menu, array('permission_name' => $permission_name));
	} else {
		$menu['is_display'] = 1;
		pdo_insert('core_menu', $menu);
	}
	cache_clean(cache_system_key('system_frame'));
	iajax(0, '更新成功', referer());
}
if ('display_status' == $do) {
	$permission_name = safe_gpc_string($_GPC['permission_name']);
	$status = intval($_GPC['status']);
	$menu_db = pdo_get('core_menu', array('permission_name' => $permission_name));

	if (!empty($menu_db)) {
		pdo_update('core_menu', array('is_display' => $status), array('permission_name' => $permission_name));
	} else {
		$menu_data = array('is_display' => $status, 'permission_name' => $permission_name);
		if (in_array($permission_name, $system_top_menu)) {
			$menu_data['is_system'] = 1;
			$menu_data['group_name'] = 'frame';
		}
		pdo_insert('core_menu', $menu_data);
	}
	cache_clean(cache_system_key('system_frame'));
	iajax(0, '更新成功', referer());
}
if ('delete' == $do) {
	$permission_name = safe_gpc_string($_GPC['permission_name']);
	$menu_db = pdo_get('core_menu', array('permission_name' => $permission_name));
	if (!empty($menu_db['is_system'])) {
		iajax(-1, '系统菜单不能删除', referer());
	}
	if (!empty($menu_db)) {
		pdo_delete('core_menu', array('id' => $menu_db['id']));
		cache_clean(cache_system_key('system_frame'));
	}
	iajax(0, '更新成功', referer());
}
if ('change_displayorder' == $do) {
	$menu_db = pdo_get('core_menu', array('permission_name' => safe_gpc_string($_GPC['permission']), 'group_name' => 'frame'));
	if (empty($menu_db)) {
		$menu = array(
			'group_name' => 'frame',
			'displayorder' => intval($_GPC['displayorder']),
			'permission_name' => safe_gpc_string($_GPC['permission']),
			'is_display' => 1,
		);
		if (in_array($_GPC['permission'], $system_top_menu)) {
			$menu['is_system'] = 1;
		}
		pdo_insert('core_menu', $menu);
	} else {
		pdo_update('core_menu', array('displayorder' => intval($_GPC['displayorder'])), array('id' => $menu_db['id']));
	}
	cache_clean(cache_system_key('system_frame'));
	iajax(0, '更新成功', referer());
}
