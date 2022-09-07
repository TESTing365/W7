<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Site;

class StoreGoods extends \We7Table {
	protected $tableName = 'site_store_goods';
	protected $primaryKey = 'id';
	protected $field = array(
		'type',
		'title',
		'module',
		'module_group',
		'user_group',
		'account_num',
		'wxapp_num',
		'price',
		'user_group_price',
		'unit',
		'slide',
		'category_id',
		'title_initial',
		'status',
		'createtime',
		'synopsis',
		'description',
		'account_group',
		'api_num',
		'is_wish',
		'logo',
	);
	protected $default = array(
		'type' => '',
		'title' => '',
		'module' => '',
		'module_group' => 0,
		'user_group' => '',
		'account_num' => '',
		'wxapp_num' => '',
		'price' => '',
		'user_group_price' => '',
		'unit' => '',
		'slide' => '',
		'category_id' => '',
		'title_initial' => '',
		'status' => '',
		'createtime' => '',
		'synopsis' => '',
		'description' => '',
		'account_group' => 0,
		'api_num' => '',
		'is_wish' => 0,
		'logo' => '',
	);

	public function searchWithKeyword($title) {
		if (!empty($title)) {
			$this->where('title LIKE', "%{$title}%");
		}
		return $this;
	}

	public function searchWithIswishAndStatus($is_wish, $status) {
		$this->query->where(array(
			'is_wish' => $is_wish,
			'status' => $status
		));
		return $this;
	}

	public function searchWithTypeAndTitle($type = 0, $title = '') {
		if (!empty($type) && is_numeric($type)) {
			$this->query->where('type', $type);
		}
		if (!empty($title)) {
			$this->query->where('title LIKE', "%$title%");
		}
		return $this;
	}

	/**
	 * @param $group
	 * 		模块 module
	 * 		平台个数 account_num
	 * 		平台续费 renew
	 */
	public function searchWithTypeGroup($group_name) {
		if (!empty($group_name) && !is_numeric($group_name)) {
			load()->model('store');
			$types = store_goods_type_info($group_name);
			$this->query->where('type', array_keys($types));
		}
		return $this;
	}
	
	public function getByModuleName($module_name, $is_wish = 0) {
		return $this->query->where(array('module' => $module_name, 'is_wish' => $is_wish))->get();
	}
	public function getGoods($is_wish = 0, $status = 1) {
		$data = $this->query
			->where(array('is_wish' => $is_wish, 'status' => $status))
			->orderby('id', 'DESC')
			->getall();

		if (!empty($data)) {
			load()->model('store');
			$types = store_goods_type_info();

			foreach ($data as &$item) {
				$item['user_group_price'] = iunserializer($item['user_group_price']);
				$item['slide'] = iunserializer($item['slide']);
				$item['type_info'] = $types[$item['type']];
			}
		}
		return $data;
	}
}