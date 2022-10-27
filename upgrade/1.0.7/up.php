<?php
namespace W7\U107;

defined('IN_IA') or exit('Access Denied');
class Up {
    const DESCRIPTION = '兼容云参数设置问题';
	public function up() {
		if (!pdo_fieldexists('modules', 'cloudsetting')) {
			pdo_query("ALTER TABLE " . tablename('modules') . " ADD `cloudsetting` VARCHAR(2000) NOT NULL DEFAULT '' COMMENT '云参数设置序列化值';");
		}
		return true;
	}

	public function down() {
		return true;
	}
}
