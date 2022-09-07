<?php
/**
 * 检测文件BOM相关操作
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

set_time_limit(0);

load()->func('file');


if ($_W['ispost'] && $_W['token'] == safe_gpc_string($_GPC['token'])) {

	//检测BOM异常
	if (!empty($_GPC['submit'])) {
		$path = IA_ROOT;
		$trees = file_tree($path);
		$bomtree = array();
		foreach ($trees as $tree) {
			$tree = str_replace($path, '', $tree);
			$tree = str_replace('\\', '/', $tree);
			if (strexists($tree, '.php')) {
				$fname = $path . $tree;
				$fp = fopen($fname, 'r');
				if (!empty($fp)) {
					$bom = fread($fp, 3);
					fclose($fp);
					if ("\xEF\xBB\xBF" == $bom) {
						$bomtree[] = $tree;
					}
				}
			}
		}
		cache_write(cache_system_key('bomtree'), $bomtree);
		if ($_W['isw7_request']) {
			$message = array(
				'bomtree' => $bomtree
			);
			iajax(0, $message);
		}
	}
	//处理BOM异常
	if (!empty($_GPC['dispose'])) {
		$trees = cache_load(cache_system_key('bomtree'));
		$path = IA_ROOT;
		if (is_array($trees)) {
			foreach ($trees as $tree) {
				$fname = $path . $tree;
				$string = file_get_contents($fname);
				$string = substr($string, 3);
				file_put_contents($fname, $string);
				if (is_resource($fp)) {
					@fclose($fp);
				}
			}
		}
		cache_delete(cache_system_key('bomtree'));
		if ($_W['isw7_request']) {
			iajax(0, '操作成功');
		}
	}
}

template('system/bom');
