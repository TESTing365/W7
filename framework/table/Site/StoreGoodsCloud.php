<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Site;

class StoreGoodsCloud extends \We7Table {
	protected $tableName = 'site_store_goods_cloud';
	protected $primaryKey = 'id';
	protected $field = array(
		'cloud_id',
		'name',
		'title',
		'logo',
		'wish_branch',
		'is_edited',
		'isdeleted',
		'branchs'
	);
	protected $default = array(
		'cloud_id' => 0,
		'name' => '',
		'title' => '',
		'logo' => '',
		'wish_branch' => 0,
		'is_edited' => 0,
		'isdeleted' => 0,
		'branchs' => ''
	);
}