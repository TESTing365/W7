<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Stat;

class VisitIp extends \We7Table {
	protected $tableName = 'stat_visit_ip';
	protected $primaryKey = 'id';
	protected $field = array(
		'ip',
		'uniacid',
		'type',
		'module',
		'date',

	);
	protected $default = array(
		'ip' => '',
		'uniacid' => '',
		'type' => '',
		'module' => '',
		'date' => '',

	);

	public function searchWithIp($ip) {
		return $this->query->where('ip', $ip);
	}

	public function searchWithDate($date) {
		return $this->query->where('date', $date);
	}

	public function searchWithGreaterThenDate($date) {
		return $this->query->where('date >=', $date);
	}

	public function searchWithLessThenDate($date) {
		return $this->query->where('date <=', $date);
	}

	public function searchWithModule($module) {
		return $this->query->where('module', $module);
	}

	public function searchWithType($type) {
		return $this->query->where('type', $type);
	}
}