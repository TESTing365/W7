<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\System;

class WelcomeBinddomain extends \We7Table {
	protected $tableName = 'system_welcome_binddomain';
	protected $primaryKey = 'id';
	protected $field = array(
		'uid',
		'module_name',
		'domain',
		'createtime',
	);
	protected $default = array(
		'uid' => '',
		'module_name' => '',
		'domain' => '',
		'createtime' => '',
	);
}