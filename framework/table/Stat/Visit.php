<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Stat;

class Visit extends \We7Table {
	protected $tableName = 'stat_visit';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'type',
		'module',
		'count',
		'date',
	);
	protected $default = array(
		'uniacid' => '',
		'type' => '',
		'module' => '',
		'count' => '',
		'date' => '',
	);

	public function searchWithUnacid($uniacid) {
		return $this->query->where('uniacid', $uniacid);
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