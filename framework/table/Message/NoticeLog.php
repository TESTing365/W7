<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Message;

class NoticeLog extends \We7Table {
	protected $tableName = 'message_notice_log';
	protected $primaryKey = 'id';
	protected $field = array(
		'message',
		'is_read',
		'uid',
		'sign',
		'type',
		'status',
		'create_time',
		'end_time',
		'url',

	);
	protected $default = array(
		'message' => '',
		'is_read' => '1',
		'uid' => '0',
		'sign' => '',
		'type' => '0',
		'status' => '0',
		'create_time' => '0',
		'end_time' => '0',
		'url' => '',

	);
}