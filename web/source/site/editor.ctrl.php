<?php
/**
 * 会员中心
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');
load()->model('site');
load()->model('module');
load()->library('qrcode');

$do = !empty($do) ? $do : 'uc';
$do = in_array($do, array('quickmenu', 'uc', 'qrcode')) ? $do : 'uc';

if (in_array($do, array('quickmenu', 'uc'))) {
	permission_check_account_user('mc_member_' . $do);
}

if ($do == 'uc') {
	if (!empty($_GPC['wapeditor'])) {
		$params = json_decode(ihtml_entity_decode($_GPC['wapeditor']['params']), true);
		$params = safe_gpc_array($params);
		if (empty($params)) {
			itoast('请您先设计手机端页面.', '', 'error');
		}
		$page = $params[0];
		$html = safe_gpc_html(htmlspecialchars_decode($_GPC['wapeditor']['html'], ENT_QUOTES));
		$html = preg_replace('/background\-image\:(\s)*url\(\"(.*)\"\)/U', 'background-image: url($2)', $html);
		$data = array(
			'uniacid' => $_W['uniacid'],
			'multiid' => '0',
			'title' => $page['params']['title'],
			'description' => $page['params']['description'],
			'type' => 3,
			'status' => 1,
			'params' => stripslashes(ijson_encode($params, JSON_UNESCAPED_UNICODE)),
			'html' => $html,
			'createtime' => TIMESTAMP,
		);
		$id = table('site_page')
			->where(array(
				'uniacid' => $_W['uniacid'],
				'type' => 3
			))
			->getcolumn('id');
		if (empty($id)) {
			table('site_page')->fill($data)->save();
			$id = pdo_insertid();
		} else {
			table('site_page')
				->where(array(
					'id' => $id,
					'uniacid' => $_W['uniacid']
				))
				->fill($data)
				->save();
		}
		if (!empty($page['params']['keyword'])) {
			$cover = array(
				'uniacid' => $_W['uniacid'],
				'title' => $page['params']['title'],
				'keyword' => $page['params']['keyword'],
				'url' => murl('mc/home', array(), true, false),
				'description' => $page['params']['description'],
				'thumb' => $page['params']['cover'],
				'module' => 'mc',
			);
			site_cover($cover);
		}
		//处理链接
		$nav = json_decode(ihtml_entity_decode($_GPC['wapeditor']['nav']), true);
		$nav = safe_gpc_array($nav);
		$ids = array(0);
		if (!empty($nav)) {
			foreach ($nav as $row) {
				$data = array(
					'uniacid' => $_W['uniacid'],
					'name' => $row['name'],
					'position' => 2,
					'url' => $row['url'],
					'icon' => '',
					'css' => iserializer($row['css']),
					'status' => $row['status'],
					'displayorder' => 0,
				);
				if (!empty($row['id'])) {
					table('site_nav')
						->where(array(
							'id' => $row['id'],
							'uniacid' => $_W['uniacid']
						))
						->fill($data)
						->save();
				} else {
					$data['status'] = 1;
					table('site_nav')->fill($data)->save();
					$row['id'] = pdo_insertid();
				}
				$ids[] = $row['id'];
			}
		}
		table('site_nav')
			->where(array(
				'uniacid' => $_W['uniacid'],
				'position' => '2',
				'id <>' => $ids
			))
			->delete();
		itoast('个人中心保存成功.', url('site/editor/uc'), 'success');
	}
	$navs = table('site_nav')->getBySnake(array('id', 'icon', 'css', 'name', 'module', 'status', 'url'), array('uniacid' => $_W['uniacid'], 'position' => 2), array('displayorder' => 'DESC', 'id' => 'ASC'))->getall();
	if (!empty($navs)) {
		foreach ($navs as &$nav) {
			/*处理icon图片链接*/
			if (!empty($nav['module'])) {
				$nav['module_info'] = module_fetch($nav['module']);
			}
			if (!empty($nav['icon'])) {
				$nav['icon'] = tomedia($nav['icon']);
			}
			if (is_serialized($nav['css'])) {
				$nav['css'] = iunserializer($nav['css']);
			}
			if (!empty($nav['css']) && empty($nav['css']['icon']['icon'])) {
				$nav['css']['icon']['icon'] = 'fa fa-external-link';
			}
		}
		unset($nav);
	}
	$page = table('site_page')
		->where(array(
			'uniacid' => $_W['uniacid'],
			'type' => 3
		))
		->get();
	template('site/editor');
} elseif ($do == 'quickmenu') {
	$multiid = empty($_GPC['multiid']) ? 0 : intval($_GPC['multiid']);
	$type = empty($_GPC['type']) ? 2 : intval($_GPC['type']);
	if (!empty($_GPC['wapeditor'])) {
		$params = json_decode(html_entity_decode(urldecode($_GPC['wapeditor']['params'])), true);
		$params = safe_gpc_array($params);
		if (empty($params)) {
			itoast('请您先设计手机端页面.', '', 'error');
		}
		$html = safe_gpc_html(htmlspecialchars_decode($_GPC['wapeditor']['html'], ENT_QUOTES));
		$html = preg_replace('/background\-image\:(\s)*url\(\"(.*)\"\)/U', 'background-image: url($2)', $html);
		$data = array(
			'uniacid' => $_W['uniacid'],
			'multiid' => $multiid,
			'title' => '快捷菜单',
			'description' => '',
			'status' => intval($_GPC['status']),
			'type' => $type,
			'params' => stripslashes(ijson_encode($params, JSON_UNESCAPED_UNICODE)),
			'html' => $html,
			'createtime' => TIMESTAMP,
		);
		if ($type == '4') {
			$id = table('site_page')
				->where(array(
					'uniacid' => $_W['uniacid'],
					'type' => $type
				))
				->getcolumn('id');
		} else {
			$id = table('site_page')
				->where(array(
					'uniacid' => $_W['uniacid'],
					'multiid' => $multiid,
					'type' => $type
				))
				->getcolumn('id');
		}
		if (!empty($id)) {
			table('site_page')
				->where(array(
					'id' => $id,
					'uniacid' => $_W['uniacid']
				))
				->fill($data)
				->save();
		} else {
			if ($type == 4) {
				$data['status'] = 1;
			}
			table('site_page')->fill($data)->save();
			$id = pdo_insertid();
		}
		itoast('快捷菜单保存成功.', url('site/editor/quickmenu', array('multiid' => $multiid, 'type' => $type)), 'success');
	}
	if ($type == '4') {
		$page = table('site_page')
			->where(array(
				'type' => $type,
				'uniacid' => $_W['uniacid']
			))
			->get();
	} else {
		$page = table('site_page')
			->where(array(
				'type' => $type,
				'multiid' => $multiid,
				'uniacid' => $_W['uniacid']
			))
			->get();
	}

	$modules = uni_modules();
	template('site/editor');
} elseif ($do == 'qrcode') {
	$error_correction_level = "L";
	$matrix_point_size = "8";
	$text = safe_gpc_url($_GPC['text']);
	QRcode::png($text, false, $error_correction_level, $matrix_point_size);
}
