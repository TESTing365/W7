<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class UsersprofileTable extends We7Table {
	protected $tableName = 'users_profile';
	protected $primaryKey = 'id';

	protected $field = array('send_expire_status');

}