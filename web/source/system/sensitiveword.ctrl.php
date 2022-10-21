<?php
/**
 * 敏感词汇
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('setting');

$dos = array('display', 'add', 'delete');
$do = !empty($_GPC['do']) && in_array($_GPC['do'], $dos) ? $_GPC['do'] : 'display';

$words_list = setting_load('sensitive_words');
$words_list = !empty($words_list['sensitive_words']) ? $words_list['sensitive_words'] : array();

if ('display' == $do) {
	$keyword = empty($_GPC['keyword']) ? '' : safe_gpc_string($_GPC['keyword']);
	$lists = $words_list;
	if (!empty($keyword)) {
		$lists = array();
		foreach ($words_list as $word) {
			if (strexists($word, $keyword)) {
				$lists[] = $word;
			}
		}
	}
	if ($_W['isajax']) {
		$message = array(
			'lists' => $lists
		);
		iajax(0, $message);
	}
}

if ('add' == $do) {
	$add_word = safe_gpc_string($_GPC['word']);
	if (empty($add_word)) {
		iajax(-1, '敏感词不能为空', url('system/sensitiveword'));
	}
	$add_word_array = explode("\n", $add_word);
	foreach ($add_word_array as &$word) {
		$word = safe_gpc_string(trim($word));
	}
	$words_list = array_merge($words_list, $add_word_array);
	$word_add = setting_save(array_unique($words_list), 'sensitive_words');
	if (is_error($word_add)) {
		iajax(-1, '添加失败', url('system/sensitiveword'));
	}
	iajax(0, '添加成功', url('system/sensitiveword'));
}

if ('delete' == $do) {
	$del_word = safe_gpc_string($_GPC['word']);
	if (empty($del_word)) {
		iajax(-1, '不能为空');
	}
	$del_word_index = array_search($del_word, $words_list);
	if (false === $del_word_index) {
		iajax(-1, '敏感词不存在');
	}
	unset($words_list[$del_word_index]);
	$update = setting_save($words_list, 'sensitive_words');
	iajax(0, '删除成功', url('system/sensitiveword'));
}
template('system/sensitive-word');
