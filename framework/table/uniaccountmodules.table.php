<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');
class UniaccountmodulesTable extends We7Table {
	protected $tableName = 'uni_account_modules';
	protected $field = array('uniacid', 'name', 'module', 'containtype', 'displayorder', 'status', 'reply_type');
}