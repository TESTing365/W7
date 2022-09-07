<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Account;

class Webapp extends \We7Table {
	protected $tableName = 'account_webapp';
	protected $primaryKey = 'acid';
	protected $field = array(
		'uniacid',
		'name',
	);
	protected $default = array(
		'uniacid' => '',
		'name' => '',
	);

	public function getAccount($uniacid) {
		return $this->query->where('uniacid', $uniacid)->get();
	}
	public function searchWithAccount() {
		return $this->query->from($this->tableName, 't')
			->leftjoin('account', 'a')
			->on(array('t.uniacid' => 'a.uniacid'));
	}
}