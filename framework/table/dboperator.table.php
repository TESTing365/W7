<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * User: fanyk
 * Date: 2018/1/30
 * Time: 10:31
 *  临时示例  无需发布
 */

class DbOperatorTable extends We7Table{
	protected $tableName = 'db_operator';
	protected $primaryKey = 'id';
	protected $field = array('name', 'age', 'is_delete', 'update_time', 'type');

}