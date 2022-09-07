<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * User: fanyk
 * Date: 2017/12/29
 * Time: 10:34
 */

class UnigroupTable extends We7Table {

	protected $tableName = 'uni_group';
	protected $primaryKey = 'id';

	/**
	 *  获取当前用户权限组包含的所有公众号
	 */
	public function uniaccounts() {
		return $this->belongsMany('account', 'uniacid', 'id', 'uni_account_group', 'uniacid', 'groupid');
	}


}