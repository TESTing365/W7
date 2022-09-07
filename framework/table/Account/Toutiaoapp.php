<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Account;

class Toutiaoapp extends \We7Table {
	protected $tableName = 'account_toutiaoapp';
	protected $primaryKey = 'acid';
	protected $field = array(
		'uniacid',
		'name',
		'appid',
		'key',
		'secret',
		'description',
	);
	protected $default = array(
		'uniacid' => '',
		'name' => '',
		'appid' => '',
		'key' => '',
		'secret' => '',
		'description' => '',
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