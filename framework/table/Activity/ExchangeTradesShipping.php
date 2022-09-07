<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Activity;

class ExchangeTradesShipping extends \We7Table {
	protected $tableName = 'activity_exchange_trades_shipping';
	protected $primaryKey = 'tid';
	protected $field = array(
		'uniacid',
		'uid',
		'exid',
		'status',
		'createtime',
		'province',
		'city',
		'district',
		'address',
		'zipcode',
		'mobile',
		'name'
	);
	protected $default = array(
		'uniacid' => '',
		'uid' => '',
		'exid' => '',
		'status' => 0,
		'createtime' => '',
		'province' => '',
		'city' => '',
		'district' => '',
		'address' => '',
		'zipcode' => '',
		'mobile' => '',
		'name' => ''
	);
}