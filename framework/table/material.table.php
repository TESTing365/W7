<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class MaterialTable extends We7Table {
	public function materialNewsList($attch_id) {
		$this->query->from('wechat_news')
			->where('attach_id', $attch_id)
			->orderby('displayorder', 'ASC');
		return $this->query->getall();
	}
}