<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class CoresendsmslogTable extends We7Table {
	protected $tableName = 'core_sendsms_log';
	protected $primaryKey = 'id';

	protected $field = array('id', 'uniacid', 'mobile', 'content', 'result', 'createtime');
}