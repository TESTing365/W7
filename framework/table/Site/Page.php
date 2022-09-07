<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Site;

class Page extends \We7Table {
	protected $tableName = 'site_page';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'multiid',
		'title',
		'description',
		'params',
		'html',
		'multipage',
		'type',
		'status',
		'createtime',
		'goodnum',
	);
	protected $default = array(
		'uniacid' => 0,
		'multiid' => 0,
		'title' => '',
		'description' => '',
		'params' => '',
		'html' => '',
		'multipage' => '',
		'type' => 1,
		'status' => 1,
		'createtime' => 0,
		'goodnum' => 0,
	);
	public function searchWithMultiid($id) {
		return $this->query->where('multiid', $id);
	}
}