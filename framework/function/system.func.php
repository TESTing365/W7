<?php
/**
 * 微擎系统内部公共函数
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */

/**
 * 获取全局变量 $_W 中的值
 *
 * @param string $key
 */
if (!function_exists('getglobal')) {
	function getglobal($key) {
		global $_W;
		$key = explode('/', $key);

		$v = &$_W;
		foreach ($key as $k) {
			if (!isset($v[$k])) {
				return null;
			}
			$v = &$v[$k];
		}

		return $v;
	}
}

if (!function_exists('strip_gpc')) {
	function strip_gpc($values, $type = 'g') {
		$filter = array(
			'g' => "'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",
			'p' => '\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)',
			'c' => '\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)',
		);
		if (!isset($values)) {
			return '';
		}
		if (is_array($values)) {
			foreach ($values as $key => $val) {
				$values[addslashes($key)] = strip_gpc($val, $type);
			}
		} else {
			if (1 == preg_match('/' . $filter[$type] . '/is', $values, $match)) {
				$values = '';
			}
		}

		return $values;
	}
}
