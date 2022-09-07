<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class ProfilefieldsTable extends We7Table {
	protected $tableName = 'profile_fields';

	public function searchKeyword($keyword) {
		$this->query->where('title LIKE', "%{$keyword}%");
		return $this;
	}

	public function getFieldsList() {
		return $this->query->from($this->tableName)->orderby('displayorder', 'DESC')->getall();
	}
}