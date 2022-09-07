<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Site;

class Multi extends \We7Table {
	protected $tableName = 'site_multi';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'title',
		'styleid',
		'site_info',
		'status',
		'bindhost',
	);
	protected $default = array(
		'uniacid' => '',
		'title' => '',
		'styleid' => '',
		'site_info' => '',
		'status' => '1',
		'bindhost' => '',
	);

	public function getAllByUniacid($uniacid) {
		return $this->query->where('uniacid', $uniacid)->getall();
	}
}