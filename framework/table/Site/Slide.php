<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Site;

class Slide extends \We7Table {
	protected $tableName = 'site_slide';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'multiid',
		'title',
		'url',
		'thumb',
		'displayorder',
	);
	protected $default = array(
		'uniacid' => '',
		'multiid' => 0,
		'title' => '',
		'url' => '',
		'thumb' => '',
		'displayorder' => 0,
	);

	public function getBySnake($fields = '*', $where = array(), $order = array('id' => 'DESC')) {
		return $this->query->select($fields)->where($where)->orderby($order);
	}
}