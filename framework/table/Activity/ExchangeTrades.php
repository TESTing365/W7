<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Activity;

class ExchangeTrades extends \We7Table {
	protected $tableName = 'activity_exchange_trades';
	protected $primaryKey = 'tid';
	protected $field = array(
		'uniacid',
		'uid',
		'exid',
		'type',
		'createtime',
	);
	protected $default = array(
		'uniacid' => '',
		'uid' => '',
		'exid' => '',
		'type' => '',
		'createtime' => 0,
	);
}