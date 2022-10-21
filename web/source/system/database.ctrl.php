<?php

/**
 * 数据库相关操作
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
//防止30秒运行超时的错误（Maximum execution time of 30 seconds exceeded).
set_time_limit(0);

load()->func('file');
load()->model('cloud');
load()->func('db');
load()->model('system');
$dos = array('backup', 'restore', 'optimize');
$do = in_array($do, $dos) ? $do : 'backup';

//备份
if ('backup' == $do) {
	if (!empty($_GPC['status'])) {
		if (empty($_W['setting']['copyright']['status'])) {
			if ($_W['isw7_request']) {
				iajax(-1, '为了保证备份数据完整请关闭站点后再进行此操作', url('system/site'));
			}
			itoast('为了保证备份数据完整请关闭站点后再进行此操作', url('system/site'), 'error');
		}
		$sql = "SHOW TABLE STATUS LIKE '{$_W['config']['db']['tablepre']}%'";
		$tables = pdo_fetchall($sql);
		if (empty($tables)) {
			if ($_W['isw7_request']) {
				$message = array(
					'continue' => 0,
					'message' => '数据已经备份完成'
				);
				iajax(0, $message);
			}
			itoast('数据已经备份完成', url('system/database/'), 'success');
		}
		$series = empty($_GPC['series']) ? 1 : intval($_GPC['series']);
		$volume_suffix = md5(complex_authkey());
		if (!empty($_GPC['folder_suffix']) && !preg_match('/[^0-9A-Za-z-_]/', safe_gpc_string($_GPC['folder_suffix']))) {
			$folder_suffix = safe_gpc_string($_GPC['folder_suffix']);
		} else {
			$folder_suffix = TIMESTAMP . '_' . random(8);
		}
		$bakdir = IA_ROOT . '/data/backup/' . $folder_suffix;
		if (!empty(trim($_GPC['start']))) {
			$result = mkdirs($bakdir);
		}
		$size = 300;
		$volumn = 1024 * 1024 * 2;
		$dump = '';
		if (empty($_GPC['last_table'])) {
			$last_table = '';
			$catch = true;
		} else {
			$last_table = safe_gpc_string($_GPC['last_table']);
			$catch = false;
		}
		foreach ($tables as $table) {
			$table = array_shift($table);
			if (!empty($last_table) && $table == $last_table) {
				$catch = true;
			}
			if (!$catch) {
				continue;
			}
			if (!empty($dump)) {
				$dump .= "\n\n";
			}
			if ($table != $last_table) {
				$row = db_table_schemas($table);
				$dump .= $row;
			}
			$index = 0;
			if (!empty($_GPC['index'])) {
				$index = intval($_GPC['index']);
				$_GPC['index'] = 0;
			}
			//枚举所有表的INSERT语句
			while (true) {
				$start = $index * $size;
				$result = db_table_insert_sql($table, $start, $size);
				if (!empty($result)) {
					$dump .= $result['data'];
					if (strlen($dump) > $volumn) {
						$bakfile = $bakdir . "/volume-{$volume_suffix}-{$series}.sql";
						$dump .= "\n\n";
						file_put_contents($bakfile, $dump);
						++$series;
						++$index;
						$current = array(
							'last_table' => $table,
							'index' => $index,
							'series' => $series,
							'folder_suffix' => $folder_suffix,
							'status' => 1,
						);
						$current_series = $series - 1;
						if ($_W['isw7_request']) {
							$message = array(
								'continue' => 1,
								'message' => '正在导出数据, 请不要关闭浏览器, 当前第 ' . $current_series . ' 卷.',
								'url' => url('system/database/backup/', $current)
							);
							iajax(0, $message);
						}
						message('正在导出数据, 请不要关闭浏览器, 当前第 ' . $current_series . ' 卷.', url('system/database/backup/', $current), 'info');
					}
				}

				if (empty($result) || count($result['result']) < $size) {
					break;
				}
				++$index;
			}
		}
		$bakfile = $bakdir . "/volume-{$volume_suffix}-{$series}.sql";
		$dump .= "\n\n----WeEngine MySQL Dump End";
		file_put_contents($bakfile, $dump);
		if ($_W['isw7_request']) {
			$message = array(
				'continue' => 0,
				'message' => '数据已经备份完成'
			);
			iajax(0, $message);
		}
		itoast('数据已经备份完成', url('system/database/'), 'success');
	}
}
//还原
if ('restore' == $do) {
	//获取备份目录下数据库备份数组
	$reduction = system_database_backup();
	//备份还原
	if (!empty($_GPC['restore_dirname'])) {
		$restore_dirname = safe_gpc_string($_GPC['restore_dirname']);
		$restore_dirname_list = array_keys($reduction);
		if (!in_array($restore_dirname, $restore_dirname_list)) {
			if ($_W['isw7_request']) {
				iajax(-1, '非法访问');
			}
			itoast('非法访问', '', 'error');
			exit;
		}

		$volume_list = $reduction[$restore_dirname]['volume_list'];
		$restore_volume_sizes = empty($_GPC['restore_volume_sizes']) ? 1 : intval($_GPC['restore_volume_sizes']);
		if (1 == $restore_volume_sizes) {
			$restore_volume_name = $volume_list[0];
		} else {
			$listkey = $restore_volume_sizes - 1;
			$restore_volume_name = empty($volume_list[$listkey]) ? '' : $volume_list[$listkey];
		}
		if ($reduction[$restore_dirname]['volume'] < $restore_volume_sizes) {
			if ($_W['isw7_request']) {
				$message = array(
					'continue' => 0,
					'message' => '成功恢复数据备份. 可能还需要你更新缓存.'
				);
				iajax(0, $message);
			}
			itoast('成功恢复数据备份. 可能还需要你更新缓存.', url('system/database/restore'), 'success');
			exit;
		}
		$volume_sizes = $restore_volume_sizes;
		system_database_volume_restore($restore_volume_name);
		$next_restore_volume_name = system_database_volume_next($restore_volume_name);
		++$restore_volume_sizes ;
		$restore = array(
				'restore_volume_sizes' => $restore_volume_sizes,
				'restore_dirname' => $restore_dirname,
		);
		if ($_W['isw7_request']) {
			$message = array(
				'continue' => 1,
				'message' => '正在恢复数据备份, 请不要关闭浏览器, 当前第 ' . $volume_sizes . ' 卷.',
				'url' => url('system/database/restore', $restore)
			);
			iajax(0, $message);
		}
		message('正在恢复数据备份, 请不要关闭浏览器, 当前第 ' . $volume_sizes . ' 卷.', url('system/database/restore', $restore), 'success');
	}
	//删除备份
	if (!empty($_GPC['delete_dirname'])) {
		$delete_dirname = safe_gpc_string($_GPC['delete_dirname']);
		if (!empty($reduction[$delete_dirname]) && system_database_backup_delete($delete_dirname)) {
			if ($_W['isw7_request']) {
				iajax(0, '删除备份成功.');
			}
			itoast('删除备份成功.', url('system/database/restore'), 'success');
		}
	}
	if ($_W['isw7_request']) {
		$message = array(
			'reduction' => $reduction
		);
		iajax(0, $message);
	}
}
//优化
if ('optimize' == $do) {
	$optimize_table = array();
	$sql = "SHOW TABLE STATUS LIKE '{$_W['config']['db']['tablepre']}%'";
	$tables = pdo_fetchall($sql);
	foreach ($tables as $tableinfo) {
		if ('InnoDB' == $tableinfo['Engine']) {
			continue;
		}
		if (!empty($tableinfo) && !empty($tableinfo['Data_free'])) {
			$row = array(
				'title' => $tableinfo['Name'],
				'type' => $tableinfo['Engine'],
				'rows' => $tableinfo['Rows'],
				'data' => sizecount($tableinfo['Data_length']),
				'index' => sizecount($tableinfo['Index_length']),
				'free' => sizecount($tableinfo['Data_free']),
			);
			$optimize_table[$row['title']] = $row;
		}
	}

	if ($_W['ispost']) {
		foreach ($_GPC['select'] as $tablename) {
			if (!empty($optimize_table[$tablename])) {
				$sql = "OPTIMIZE TABLE {$tablename}";
				pdo_fetch($sql);
			}
		}
		if ($_W['isw7_request']) {
			iajax(0, '数据表优化成功.');
		}
		itoast('数据表优化成功.', 'refresh', 'success');
	}
	if ($_W['isw7_request']) {
		$message = array(
			'optimize_table' => $optimize_table
		);
		iajax(0, $message);
	}
}

template('system/database');
