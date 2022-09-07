<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Site;

class StoreCashLog extends \We7Table {
	protected $tableName = 'site_store_cash_log';
	protected $primaryKey = 'id';
	protected $field = array(
		'founder_uid',
		'number',
		'amount',
		'status',
		'create_time'
	);
	protected $default = array(
		'founder_uid' => 0,
		'number' => '',
		'amount' => '0.00',
		'status' => 1,
		'create_time' => 0
	);
}