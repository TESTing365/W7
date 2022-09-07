<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Account;

class Wxapp extends \We7Table {
	protected $tableName = 'account_wxapp';
	protected $primaryKey = 'acid';
	protected $field = array(
		'uniacid',
		'token',
		'encodingaeskey',
		'level',
		'account',
		'original',
		'key',
		'secret',
		'name',
		'appdomain',
		'auth_refresh_token',
	);
	protected $default = array(
		'uniacid' => '',
		'token' => '',
		'encodingaeskey' => '',
		'level' => '',
		'account' => '',
		'original' => '',
		'key' => '',
		'secret' => '',
		'name' => '',
		'appdomain' => '',
		'auth_refresh_token' => '',
	);

	public function getAccount($uniacid) {
		return $this->query->where('uniacid', $uniacid)->get();
	}

	public function wxappInfo($uniacid) {
		if (is_array($uniacid)) {
			return $this->query->where('uniacid', $uniacid)->getall('uniacid');
		} else {
			return $this->query->where('uniacid', $uniacid)->get();
		}
	}

	public function searchWithAccount() {
		return $this->query->from($this->tableName, 't')
			->leftjoin('account', 'a')
			->on(array('t.uniacid' => 'a.uniacid'));
	}
}