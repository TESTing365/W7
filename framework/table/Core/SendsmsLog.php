<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class SendsmsLog extends \We7Table {
	protected $tableName = 'core_sendsms_log';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'mobile',
		'content',
		'result',
		'createtime',
	);
	protected $default = array(
		'uniacid' => 0,
		'mobile' => '',
		'content' => '',
		'result' => '',
		'createtime' => 0,
	);
}